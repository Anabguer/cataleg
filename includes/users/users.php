<?php
declare(strict_types=1);

/**
 * Lògica de dades i validació del mòdul usuaris (PDO).
 */

/**
 * Camps permesos per ORDER BY (whitelist; mai concatenar entrada de l’usuari al SQL).
 *
 * @return list<string>
 */
function users_list_sort_keys(): array
{
    return ['username', 'full_name', 'email', 'role_name', 'is_active'];
}

/**
 * @return array{by:string, dir:string}
 */
function users_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(users_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'username';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

    return ['by' => $by, 'dir' => $dir];
}

/**
 * @param array{q?:string, role_id?:string, active?:string} $filters
 * @return array{where: list<string>, params: array<string, mixed>}
 */
function users_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        /* PDO (preparacions natives): cada marcador ha de ser únic; no es pot repetir :q tres vegades. */
        $where[] = '(u.username LIKE :q1 OR u.full_name LIKE :q2 OR COALESCE(u.email, \'\') LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }

    $roleRaw = trim((string) ($filters['role_id'] ?? ''));
    if ($roleRaw === 'none') {
        $where[] = 'u.role_id IS NULL';
    } else {
        $roleId = null_if_empty_int($roleRaw);
        if ($roleId !== null) {
            $where[] = 'u.role_id = :role_id';
            $params['role_id'] = $roleId;
        }
    }

    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'u.is_active = :active';
        $params['active'] = (int) $active;
    }

    return ['where' => $where, 'params' => $params];
}

/**
 * Fragment ORDER BY segur (només columnes whitelist).
 */
function users_list_order_sql(string $sortBy, string $sortDir): string
{
    $norm = users_normalize_sort($sortBy, $sortDir);
    $by = $norm['by'];
    $dir = strtoupper($norm['dir']) === 'DESC' ? 'DESC' : 'ASC';

    switch ($by) {
        case 'full_name':
            return 'u.full_name ' . $dir;
        case 'email':
            return 'u.email ' . $dir;
        case 'role_name':
            return 'r.name ' . $dir;
        case 'is_active':
            return 'u.is_active ' . $dir;
        case 'username':
        default:
            return 'u.username ' . $dir;
    }
}

/**
 * @param array{q?:string, role_id?:string, active?:string} $filters
 */
function users_count(PDO $db, array $filters): int
{
    $parts = users_list_filters_clause($filters);
    $where = $parts['where'];
    $params = $parts['params'];
    $sql = 'SELECT COUNT(DISTINCT u.id) AS c
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE ' . implode(' AND ', $where);
    $st = $db->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();

    return (int) ($row['c'] ?? 0);
}

/**
 * @param array{q?:string, role_id?:string, active?:string} $filters
 * @return list<array<string,mixed>>
 */
function users_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $parts = users_list_filters_clause($filters);
    $where = $parts['where'];
    $params = $parts['params'];
    $order = users_list_order_sql($sortBy, $sortDir);

    $sql = 'SELECT u.id, u.username, u.full_name, u.email, u.is_active, u.role_id,
                   r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $order . '
            ' . db_sql_limit_offset($limit, $offset);

    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v);
    }
    $st->execute();

    return $st->fetchAll() ?: [];
}

/**
 * @return array{page:int, per_page:int, total_pages:int, offset:int}
 */
function users_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) {
        $perPage = 20;
    }
    if ($perPage > 100) {
        $perPage = 100;
    }
    if ($page < 1) {
        $page = 1;
    }
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

/**
 * @return array<string,mixed>|null
 */
function users_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $sql = 'SELECT u.id, u.username, u.password_hash, u.full_name, u.email, u.is_active,
                   u.role_id, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * @return list<array{id:int,name:string,slug:string}>
 */
function users_roles_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, name, slug FROM roles ORDER BY name ASC');
    return $st->fetchAll() ?: [];
}

/**
 * @param array<string,mixed> $data
 * @return array<string,string> errors field => message
 */
function users_validate_save(PDO $db, array $data, bool $isCreate, ?int $userId = null): array
{
    $errors = [];

    $username = trim((string) ($data['username'] ?? ''));
    if ($username === '') {
        $errors['username'] = 'El nom d’usuari és obligatori.';
    } elseif (strlen($username) > 64) {
        $errors['username'] = 'Màxim 64 caràcters.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/u', $username)) {
        $errors['username'] = 'Només lletres, números, punt, guió i guió baix.';
    }

    $fullName = trim((string) ($data['full_name'] ?? ''));
    if ($fullName === '') {
        $errors['full_name'] = 'El nom complet és obligatori.';
    } elseif (strlen($fullName) > 150) {
        $errors['full_name'] = 'Màxim 150 caràcters.';
    }

    $emailRaw = null_if_empty((string) ($data['email'] ?? ''));
    if ($emailRaw !== null) {
        if (strlen($emailRaw) > 150) {
            $errors['email'] = 'Màxim 150 caràcters.';
        } elseif (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correu no és vàlid.';
        }
    }

    $roleId = null_if_empty_int($data['role_id'] ?? null);
    if ($roleId === null || $roleId < 1) {
        $errors['role_id'] = 'Selecciona un rol.';
    } else {
        $st = $db->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
        $st->execute(['id' => $roleId]);
        if (!$st->fetch()) {
            $errors['role_id'] = 'Rol no vàlid.';
        }
    }

    $password = (string) ($data['password'] ?? '');
    $password2 = (string) ($data['password_confirm'] ?? '');
    if ($isCreate) {
        if ($password === '') {
            $errors['password'] = 'La contrasenya és obligatòria en l’alta.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Mínim 8 caràcters.';
        } elseif ($password !== $password2) {
            $errors['password_confirm'] = 'Les contrasenyes no coincideixen.';
        }
    } else {
        if ($password !== '' || $password2 !== '') {
            if (strlen($password) < 8) {
                $errors['password'] = 'Mínim 8 caràcters.';
            } elseif ($password !== $password2) {
                $errors['password_confirm'] = 'Les contrasenyes no coincideixen.';
            }
        }
    }

    if ($username !== '' && !isset($errors['username'])) {
        $sql = 'SELECT id FROM users WHERE username = :u';
        $params = ['u' => $username];
        if ($userId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $userId;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['username'] = 'Aquest nom d’usuari ja existeix.';
        }
    }

    if ($emailRaw !== null && !isset($errors['email'])) {
        $sql = 'SELECT id FROM users WHERE email = :e';
        $params = ['e' => $emailRaw];
        if ($userId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $userId;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['email'] = 'Aquest correu ja està en ús.';
        }
    }

    return $errors;
}

/**
 * @param array<string,mixed> $data
 */
function users_create(PDO $db, array $data): int
{
    $errors = users_validate_save($db, $data, true, null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $username = trim((string) $data['username']);
    $fullName = trim((string) $data['full_name']);
    $email = null_if_empty((string) ($data['email'] ?? ''));
    $roleId = (int) null_if_empty_int($data['role_id']);
    $hash = password_hash((string) $data['password'], PASSWORD_DEFAULT);
    $isActive = isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0;

    $sql = 'INSERT INTO users (role_id, username, password_hash, full_name, email, is_active)
            VALUES (:role_id, :username, :password_hash, :full_name, :email, :is_active)';
    $st = $db->prepare($sql);
    $st->execute([
        'role_id' => $roleId,
        'username' => $username,
        'password_hash' => $hash,
        'full_name' => $fullName,
        'email' => $email,
        'is_active' => $isActive,
    ]);
    return (int) $db->lastInsertId();
}

/**
 * @param array<string,mixed> $data
 */
function users_update(PDO $db, int $id, array $data): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid');
    }
    $errors = users_validate_save($db, $data, false, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $row = users_get_by_id($db, $id);
    if (!$row) {
        throw new RuntimeException('Usuari no trobat');
    }

    $username = trim((string) $data['username']);
    $fullName = trim((string) $data['full_name']);
    $email = null_if_empty((string) ($data['email'] ?? ''));
    $roleId = (int) null_if_empty_int($data['role_id']);
    $isActive = isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0;

    $password = (string) ($data['password'] ?? '');
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'UPDATE users SET role_id = :role_id, username = :username,
                password_hash = :password_hash, full_name = :full_name, email = :email, is_active = :is_active
                WHERE id = :id';
        $st = $db->prepare($sql);
        $st->execute([
            'id' => $id,
            'role_id' => $roleId,
            'username' => $username,
            'password_hash' => $hash,
            'full_name' => $fullName,
            'email' => $email,
            'is_active' => $isActive,
        ]);
    } else {
        $sql = 'UPDATE users SET role_id = :role_id, username = :username,
                full_name = :full_name, email = :email, is_active = :is_active
                WHERE id = :id';
        $st = $db->prepare($sql);
        $st->execute([
            'id' => $id,
            'role_id' => $roleId,
            'username' => $username,
            'full_name' => $fullName,
            'email' => $email,
            'is_active' => $isActive,
        ]);
    }
}

function users_delete(PDO $db, int $id, int $currentUserId): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid');
    }
    if ($id === $currentUserId) {
        throw new InvalidArgumentException('No pots eliminar el teu propi usuari.');
    }
    $st = $db->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Usuari no trobat');
    }
}

/**
 * Resposta JSON d’errors des d’InvalidArgumentException amb JSON al missatge.
 *
 * @return array<string,string>|null
 */
function users_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $msg = $e->getMessage();
    $decoded = json_decode($msg, true);
    return is_array($decoded) ? $decoded : null;
}

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/permissions/permissions.php';

/**
 * Lògica de dades i validació del mòdul rols (PDO).
 */

/**
 * Camps permesos per ORDER BY al llistat de rols.
 *
 * @return list<string>
 */
function roles_list_sort_keys(): array
{
    return ['name', 'slug', 'created_at', 'users_count'];
}

/**
 * @return array{by:string, dir:string}
 */
function roles_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(roles_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'name';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

    return ['by' => $by, 'dir' => $dir];
}

/**
 * @param array{q?:string} $filters
 * @return array{where:list<string>, params:array<string,mixed>}
 */
function roles_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(r.name LIKE :q1 OR r.slug LIKE :q2)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
    }

    return ['where' => $where, 'params' => $params];
}

/**
 * Fragment ORDER BY segur (només columnes whitelist).
 */
function roles_list_order_sql(string $sortBy, string $sortDir): string
{
    $norm = roles_normalize_sort($sortBy, $sortDir);
    $by = $norm['by'];
    $dir = strtoupper($norm['dir']) === 'DESC' ? 'DESC' : 'ASC';

    switch ($by) {
        case 'slug':
            return 'r.slug ' . $dir;
        case 'created_at':
            return 'r.created_at ' . $dir;
        case 'users_count':
            return 'users_count ' . $dir;
        case 'name':
        default:
            return 'r.name ' . $dir;
    }
}

/**
 * @param array{q?:string} $filters
 */
function roles_count(PDO $db, array $filters): int
{
    $parts = roles_list_filters_clause($filters);
    $where = $parts['where'];
    $params = $parts['params'];

    $sql = 'SELECT COUNT(DISTINCT r.id) AS c
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            WHERE ' . implode(' AND ', $where);

    $st = $db->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();

    return (int) ($row['c'] ?? 0);
}

/**
 * @param array{q?:string} $filters
 * @return list<array<string,mixed>>
 */
function roles_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $parts = roles_list_filters_clause($filters);
    $where = $parts['where'];
    $params = $parts['params'];
    $order = roles_list_order_sql($sortBy, $sortDir);

    $sql = 'SELECT r.id,
                   r.name,
                   r.slug,
                   r.description,
                   r.created_at,
                   r.updated_at,
                   COUNT(u.id) AS users_count
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY r.id, r.name, r.slug, r.description, r.created_at, r.updated_at
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
 * Normalitza pàgina, per_pàgina i calcula offset (mateix patró que usuaris).
 *
 * @return array{page:int, per_page:int, total_pages:int, offset:int}
 */
function roles_normalize_pagination(int $page, int $perPage, int $total): array
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
function roles_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $db->prepare('SELECT id, name, slug, description, created_at, updated_at
                        FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();

    return $row ?: null;
}

/**
 * @param array<string,mixed> $data
 * @return array<string,string> errors field => message
 */
function roles_validate_save(PDO $db, array $data, bool $isCreate, ?int $roleId = null): array
{
    $errors = [];

    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'El nom és obligatori.';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Màxim 100 caràcters.';
    }

    $slug = trim((string) ($data['slug'] ?? ''));
    if ($slug === '') {
        $errors['slug'] = 'El codi és obligatori.';
    } elseif (strlen($slug) > 100) {
        $errors['slug'] = 'Màxim 100 caràcters.';
    } elseif (!preg_match('/^[a-z0-9_-]+$/u', $slug)) {
        $errors['slug'] = 'Només lletres minúscules, números, guió i guió baix.';
    }

    $description = null_if_empty((string) ($data['description'] ?? ''));

    if ($slug !== '' && !isset($errors['slug'])) {
        $sql = 'SELECT id FROM roles WHERE slug = :s';
        $params = ['s' => $slug];
        if ($roleId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $roleId;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['slug'] = 'Aquest codi de rol ja existeix.';
        }
    }

    if (!$isCreate && $roleId !== null && $roleId > 0) {
        $existing = roles_get_by_id($db, $roleId);
        if (
            $existing !== null
            && (string) $existing['slug'] === permissions_administrator_role_slug()
            && $slug !== permissions_administrator_role_slug()
        ) {
            $errors['slug'] = 'El codi del rol administrador del sistema no es pot canviar.';
        }
    }

    return $errors;
}

/**
 * @param array<string,mixed> $data
 */
function roles_create(PDO $db, array $data): int
{
    $errors = roles_validate_save($db, $data, true, null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $name = trim((string) $data['name']);
    $slug = trim((string) $data['slug']);
    $description = null_if_empty((string) ($data['description'] ?? ''));

    $st = $db->prepare('INSERT INTO roles (name, slug, description)
                        VALUES (:name, :slug, :description)');
    $st->execute([
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
    ]);

    return (int) $db->lastInsertId();
}

/**
 * @param array<string,mixed> $data
 */
function roles_update(PDO $db, int $id, array $data): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid');
    }

    $existing = roles_get_by_id($db, $id);
    if (!$existing) {
        throw new RuntimeException('Rol no trobat');
    }

    $errors = roles_validate_save($db, $data, false, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $name = trim((string) $data['name']);
    $slug = trim((string) $data['slug']);
    if ((string) $existing['slug'] === permissions_administrator_role_slug()) {
        $slug = permissions_administrator_role_slug();
    }
    $description = null_if_empty((string) ($data['description'] ?? ''));

    $st = $db->prepare('UPDATE roles
                        SET name = :name,
                            slug = :slug,
                            description = :description
                        WHERE id = :id');
    $st->execute([
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
    ]);
}

function roles_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid');
    }

    $role = roles_get_by_id($db, $id);
    if ($role && (string) $role['slug'] === permissions_administrator_role_slug()) {
        throw new InvalidArgumentException(json_encode([
            '_general' => 'No es pot eliminar el rol administrador del sistema (codi «admin»).',
        ], JSON_THROW_ON_ERROR));
    }

    // No permetre esborrar si hi ha usuaris associats al rol.
    $st = $db->prepare('SELECT COUNT(*) AS c FROM users WHERE role_id = :id');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    $count = (int) ($row['c'] ?? 0);
    if ($count > 0) {
        throw new InvalidArgumentException(json_encode([
            '_general' => 'No es pot eliminar el rol perquè té usuaris associats.',
        ], JSON_THROW_ON_ERROR));
    }

    $st = $db->prepare('DELETE FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Rol no trobat');
    }
}

/**
 * Resposta JSON d’errors des d’InvalidArgumentException amb JSON al missatge.
 *
 * @return array<string,string>|null
 */
function roles_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $msg = $e->getMessage();
    $decoded = json_decode($msg, true);

    return is_array($decoded) ? $decoded : null;
}


<?php
declare(strict_types=1);

function training_authorizers_areas_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, area_code, name, alias FROM org_areas ORDER BY area_code ASC, name ASC');
    return $st->fetchAll() ?: [];
}

function training_authorizers_list_sort_keys(): array
{
    return ['full_name', 'area_name', 'is_active', 'created_at'];
}

function training_authorizers_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_authorizers_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'full_name';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
    return ['by' => $by, 'dir' => $dir];
}

function training_authorizers_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(t.full_name LIKE :q1 OR a.name LIKE :q2 OR a.alias LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $areaId = null_if_empty_int($filters['area_id'] ?? null);
    if ($areaId !== null) {
        $where[] = 't.area_id = :area_id';
        $params['area_id'] = $areaId;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 't.is_active = :active';
        $params['active'] = (int) $active;
    }
    return ['where' => $where, 'params' => $params];
}

function training_authorizers_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_authorizers_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'area_name':
            return 'a.name ' . $dir;
        case 'is_active':
            return 't.is_active ' . $dir;
        case 'created_at':
            return 't.created_at ' . $dir;
        default:
            return 't.full_name ' . $dir;
    }
}

function training_authorizers_count(PDO $db, array $filters): int
{
    $f = training_authorizers_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c
            FROM training_authorizers t
            INNER JOIN org_areas a ON a.id = t.area_id
            WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $row = $st->fetch();
    return (int) ($row['c'] ?? 0);
}

function training_authorizers_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_authorizers_list_filters_clause($filters);
    $sql = 'SELECT t.*, a.name AS area_name, a.alias AS area_alias, a.area_code
            FROM training_authorizers t
            INNER JOIN org_areas a ON a.id = t.area_id
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_authorizers_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function training_authorizers_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) { $perPage = 20; }
    if ($perPage > 100) { $perPage = 100; }
    if ($page < 1) { $page = 1; }
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > $totalPages) { $page = $totalPages; }
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages, 'offset' => ($page - 1) * $perPage];
}

function training_authorizers_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) { return null; }
    $st = $db->prepare('SELECT * FROM training_authorizers WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function training_authorizers_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $areaId = null_if_empty_int($data['area_id'] ?? null);
    if ($areaId === null) {
        $errors['area_id'] = 'Selecciona una àrea.';
    } else {
        $st = $db->prepare('SELECT id FROM org_areas WHERE id = :id LIMIT 1');
        $st->execute(['id' => $areaId]);
        if (!$st->fetch()) {
            $errors['area_id'] = 'Àrea no vàlida.';
        }
    }
    $fullName = trim((string) ($data['full_name'] ?? ''));
    if ($fullName === '') {
        $errors['full_name'] = 'El nom complet és obligatori.';
    } elseif (mb_strlen($fullName) > 150) {
        $errors['full_name'] = 'Màxim 150 caràcters.';
    }

    if (!isset($errors['area_id']) && !isset($errors['full_name'])) {
        $sql = 'SELECT id FROM training_authorizers WHERE area_id = :area_id AND full_name = :full_name';
        $params = ['area_id' => $areaId, 'full_name' => $fullName];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['full_name'] = 'Ja existeix aquest autoritzador dins la mateixa àrea.';
        }
    }
    return $errors;
}

function training_authorizers_create(PDO $db, array $data): int
{
    $errors = training_authorizers_validate_save($db, $data, null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('INSERT INTO training_authorizers (area_id, full_name, is_active) VALUES (:area_id, :full_name, :is_active)');
    $st->execute([
        'area_id' => (int) $data['area_id'],
        'full_name' => trim((string) $data['full_name']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
    return (int) $db->lastInsertId();
}

function training_authorizers_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_authorizers_get_by_id($db, $id)) {
        throw new RuntimeException('Autoritzador no trobat');
    }
    $errors = training_authorizers_validate_save($db, $data, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('UPDATE training_authorizers SET area_id = :area_id, full_name = :full_name, is_active = :is_active WHERE id = :id');
    $st->execute([
        'id' => $id,
        'area_id' => (int) $data['area_id'],
        'full_name' => trim((string) $data['full_name']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
}

function training_authorizers_delete(PDO $db, int $id): void
{
    if ($id < 1) { throw new InvalidArgumentException('ID invàlid'); }
    $st = $db->prepare('DELETE FROM training_authorizers WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Autoritzador no trobat');
    }
}

function training_authorizers_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) { return null; }
    $decoded = json_decode($e->getMessage(), true);
    return is_array($decoded) ? $decoded : null;
}

<?php
declare(strict_types=1);

function areas_list_sort_keys(): array
{
    return ['area_code', 'name', 'alias', 'is_active', 'created_at'];
}

function areas_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(areas_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'area_code';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
    return ['by' => $by, 'dir' => $dir];
}

function areas_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(a.name LIKE :q1 OR a.alias LIKE :q2 OR CAST(a.area_code AS CHAR) LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'a.is_active = :active';
        $params['active'] = (int) $active;
    }
    return ['where' => $where, 'params' => $params];
}

function areas_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = areas_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'a.name ' . $dir;
        case 'alias':
            return 'a.alias ' . $dir;
        case 'is_active':
            return 'a.is_active ' . $dir;
        case 'created_at':
            return 'a.created_at ' . $dir;
        default:
            return 'a.area_code ' . $dir;
    }
}

function areas_count(PDO $db, array $filters): int
{
    $parts = areas_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM org_areas a WHERE ' . implode(' AND ', $parts['where']);
    $st = $db->prepare($sql);
    $st->execute($parts['params']);
    $row = $st->fetch();
    return (int) ($row['c'] ?? 0);
}

function areas_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $parts = areas_list_filters_clause($filters);
    $sql = 'SELECT a.*
            FROM org_areas a
            WHERE ' . implode(' AND ', $parts['where']) . '
            ORDER BY ' . areas_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $parts['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function areas_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) { $perPage = 20; }
    if ($perPage > 100) { $perPage = 100; }
    if ($page < 1) { $page = 1; }
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > $totalPages) { $page = $totalPages; }
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages, 'offset' => ($page - 1) * $perPage];
}

function areas_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) { return null; }
    $st = $db->prepare('SELECT * FROM org_areas WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    return $row ?: null;
}

function areas_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $codeRaw = trim((string) ($data['area_code'] ?? ''));
    if ($codeRaw === '' || !ctype_digit($codeRaw)) {
        $errors['area_code'] = 'El codi d’àrea és obligatori i numèric.';
    } else {
        $code = (int) $codeRaw;
        if ($code < 0 || $code > 9) {
            $errors['area_code'] = 'El codi d’àrea ha de ser d’1 dígit (0-9).';
        }
    }
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') { $errors['name'] = 'El nom és obligatori.'; }
    $alias = trim((string) ($data['alias'] ?? ''));
    if ($alias === '') { $errors['alias'] = 'L’àlies és obligatori.'; }

    if (!isset($errors['area_code'])) {
        $sql = 'SELECT id FROM org_areas WHERE area_code = :code';
        $params = ['code' => (int) $codeRaw];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) { $errors['area_code'] = 'Aquest codi d’àrea ja existeix.'; }
    }
    if ($alias !== '') {
        $sql = 'SELECT id FROM org_areas WHERE alias = :alias';
        $params = ['alias' => $alias];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) { $errors['alias'] = 'Aquest àlies ja existeix.'; }
    }
    return $errors;
}

function areas_create(PDO $db, array $data): int
{
    $errors = areas_validate_save($db, $data, null);
    if ($errors !== []) { throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR)); }
    $st = $db->prepare('INSERT INTO org_areas (area_code, name, alias, is_active) VALUES (:code, :name, :alias, :is_active)');
    $st->execute([
        'code' => (int) $data['area_code'],
        'name' => trim((string) $data['name']),
        'alias' => trim((string) $data['alias']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
    return (int) $db->lastInsertId();
}

function areas_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !areas_get_by_id($db, $id)) { throw new RuntimeException('Àrea no trobada'); }
    $errors = areas_validate_save($db, $data, $id);
    if ($errors !== []) { throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR)); }
    $st = $db->prepare('UPDATE org_areas SET area_code = :code, name = :name, alias = :alias, is_active = :is_active WHERE id = :id');
    $st->execute([
        'id' => $id,
        'code' => (int) $data['area_code'],
        'name' => trim((string) $data['name']),
        'alias' => trim((string) $data['alias']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
}

function areas_delete(PDO $db, int $id): void
{
    if ($id < 1) { throw new InvalidArgumentException('ID invàlid'); }
    $st = $db->prepare('SELECT COUNT(*) AS c FROM org_sections WHERE area_id = :id');
    $st->execute(['id' => $id]);
    if ((int) (($st->fetch() ?: [])['c'] ?? 0) > 0) {
        throw new InvalidArgumentException(json_encode(['_general' => 'No es pot eliminar aquesta àrea perquè té seccions associades.'], JSON_THROW_ON_ERROR));
    }
    $del = $db->prepare('DELETE FROM org_areas WHERE id = :id LIMIT 1');
    try {
        $del->execute(['id' => $id]);
    } catch (PDOException $e) {
        if (db_is_integrity_constraint_violation($e)) {
            throw new InvalidArgumentException(
                json_encode(
                    ['_general' => 'No es pot eliminar aquesta àrea perquè té seccions associades.'],
                    JSON_THROW_ON_ERROR
                )
            );
        }
        throw $e;
    }
    if ($del->rowCount() === 0) {
        throw new RuntimeException('Àrea no trobada');
    }
}

function areas_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) { return null; }
    $decoded = json_decode($e->getMessage(), true);
    return is_array($decoded) ? $decoded : null;
}

<?php
declare(strict_types=1);

function training_locations_list_sort_keys(): array { return ['location_code', 'name', 'is_active', 'created_at']; }
function training_locations_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_locations_list_sort_keys());
    return ['by' => isset($allowed[$sortBy]) ? $sortBy : 'location_code', 'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc'];
}
function training_locations_list_filters_clause(array $filters): array
{
    $where = ['1=1']; $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(name LIKE :q1 OR CAST(location_code AS CHAR) LIKE :q2)';
        $like = '%' . $q . '%';
        $params['q1'] = $like; $params['q2'] = $like;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') { $where[] = 'is_active = :active'; $params['active'] = (int) $active; }
    return ['where' => $where, 'params' => $params];
}
function training_locations_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_locations_normalize_sort($sortBy, $sortDir); $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'name ' . $dir;
        case 'is_active':
            return 'is_active ' . $dir;
        case 'created_at':
            return 'created_at ' . $dir;
        default:
            return 'location_code ' . $dir;
    }
}
function training_locations_count(PDO $db, array $filters): int
{
    $f = training_locations_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM training_locations WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql); $st->execute($f['params']); $r = $st->fetch(); return (int) ($r['c'] ?? 0);
}
function training_locations_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_locations_list_filters_clause($filters);
    $sql = 'SELECT * FROM training_locations
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_locations_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}
function training_locations_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100; if ($page < 1) $page = 1;
    $tp = $total > 0 ? (int) ceil($total / $perPage) : 1; if ($page > $tp) $page = $tp;
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $tp, 'offset' => ($page - 1) * $perPage];
}
function training_locations_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) return null;
    $st = $db->prepare('SELECT * FROM training_locations WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]); $r = $st->fetch(); return $r ?: null;
}
function training_locations_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $codeRaw = trim((string) ($data['location_code'] ?? ''));
    if ($codeRaw === '' || !ctype_digit($codeRaw)) $errors['location_code'] = 'El codi és obligatori i numèric.';
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') $errors['name'] = 'El nom és obligatori.';
    if (!isset($errors['location_code'])) {
        $sql = 'SELECT id FROM training_locations WHERE location_code = :code';
        $params = ['code' => (int) $codeRaw];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1'); $st->execute($params);
        if ($st->fetch()) $errors['location_code'] = 'Aquest codi ja existeix.';
    }
    return $errors;
}
function training_locations_create(PDO $db, array $data): int
{
    $errors = training_locations_validate_save($db, $data, null); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('INSERT INTO training_locations (location_code, name, is_active) VALUES (:code, :name, :is_active)');
    $st->execute(['code' => (int) $data['location_code'], 'name' => trim((string) $data['name']), 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
    return (int) $db->lastInsertId();
}
function training_locations_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_locations_get_by_id($db, $id)) throw new RuntimeException('Lloc d’impartició no trobat');
    $errors = training_locations_validate_save($db, $data, $id); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('UPDATE training_locations SET location_code = :code, name = :name, is_active = :is_active WHERE id = :id');
    $st->execute(['id' => $id, 'code' => (int) $data['location_code'], 'name' => trim((string) $data['name']), 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
}
function training_locations_delete(PDO $db, int $id): void
{
    if ($id < 1) throw new InvalidArgumentException('ID invàlid');
    $st = $db->prepare('DELETE FROM training_locations WHERE id = :id LIMIT 1'); $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) throw new RuntimeException('Lloc d’impartició no trobat');
}
function training_locations_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) return null;
    $d = json_decode($e->getMessage(), true); return is_array($d) ? $d : null;
}

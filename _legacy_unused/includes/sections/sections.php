<?php
declare(strict_types=1);

function sections_areas_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, area_code, name FROM org_areas ORDER BY area_code ASC, name ASC');
    return $st->fetchAll() ?: [];
}

function sections_list_sort_keys(): array { return ['section_code', 'name', 'area_name', 'is_active', 'created_at']; }
function sections_normalize_sort(string $sortBy, string $sortDir): array
{
    $a = array_flip(sections_list_sort_keys());
    return ['by' => isset($a[$sortBy]) ? $sortBy : 'section_code', 'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc'];
}
function sections_list_filters_clause(array $filters): array
{
    $where = ['1=1']; $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') { $where[] = '(s.name LIKE :q1 OR CAST(s.section_code AS CHAR) LIKE :q2 OR a.name LIKE :q3)'; $like='%' . $q . '%'; $params['q1']=$like; $params['q2']=$like; $params['q3']=$like; }
    $areaId = null_if_empty_int($filters['area_id'] ?? null); if ($areaId !== null) { $where[] = 's.area_id = :area_id'; $params['area_id'] = $areaId; }
    $active = trim((string) ($filters['active'] ?? '')); if ($active === '1' || $active === '0') { $where[]='s.is_active = :active'; $params['active']=(int)$active; }
    return ['where' => $where, 'params' => $params];
}
function sections_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = sections_normalize_sort($sortBy, $sortDir); $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 's.name ' . $dir;
        case 'area_name':
            return 'a.name ' . $dir;
        case 'is_active':
            return 's.is_active ' . $dir;
        case 'created_at':
            return 's.created_at ' . $dir;
        default:
            return 's.section_code ' . $dir;
    }
}
function sections_count(PDO $db, array $filters): int
{
    $p = sections_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM org_sections s INNER JOIN org_areas a ON a.id = s.area_id WHERE ' . implode(' AND ', $p['where']);
    $st = $db->prepare($sql); $st->execute($p['params']); $r = $st->fetch(); return (int) ($r['c'] ?? 0);
}
function sections_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $p = sections_list_filters_clause($filters);
    $sql = 'SELECT s.*, a.name AS area_name, a.area_code
            FROM org_sections s
            INNER JOIN org_areas a ON a.id = s.area_id
            WHERE ' . implode(' AND ', $p['where']) . '
            ORDER BY ' . sections_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $p['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}
function sections_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) { $perPage = 20; } if ($perPage > 100) { $perPage = 100; } if ($page < 1) { $page = 1; }
    $tp = $total > 0 ? (int) ceil($total / $perPage) : 1; if ($page > $tp) { $page = $tp; }
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $tp, 'offset' => ($page - 1) * $perPage];
}
function sections_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) return null;
    $st = $db->prepare('SELECT s.*, a.name AS area_name, a.area_code FROM org_sections s INNER JOIN org_areas a ON a.id = s.area_id WHERE s.id = :id LIMIT 1');
    $st->execute(['id' => $id]); $r = $st->fetch(); return $r ?: null;
}
function sections_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $codeRaw = trim((string) ($data['section_code'] ?? ''));
    if ($codeRaw === '' || !ctype_digit($codeRaw)) { $errors['section_code'] = 'El codi de secció és obligatori i numèric.'; }
    $name = trim((string) ($data['name'] ?? '')); if ($name === '') { $errors['name'] = 'El nom és obligatori.'; }
    $areaId = null_if_empty_int($data['area_id'] ?? null); if ($areaId === null) { $errors['area_id'] = 'Selecciona una àrea.'; }
    if ($areaId !== null) { $st = $db->prepare('SELECT id FROM org_areas WHERE id = :id LIMIT 1'); $st->execute(['id' => $areaId]); if (!$st->fetch()) { $errors['area_id'] = 'Àrea no vàlida.'; } }
    if (!isset($errors['section_code'])) {
        $sql = 'SELECT id FROM org_sections WHERE section_code = :code'; $params = ['code' => (int) $codeRaw];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1'); $st->execute($params); if ($st->fetch()) { $errors['section_code'] = 'Aquest codi de secció ja existeix.'; }
    }
    return $errors;
}
function sections_create(PDO $db, array $data): int
{
    $errors = sections_validate_save($db, $data, null); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('INSERT INTO org_sections (section_code, name, area_id, is_active) VALUES (:code, :name, :area_id, :is_active)');
    $st->execute(['code' => (int) $data['section_code'], 'name' => trim((string) $data['name']), 'area_id' => (int) $data['area_id'], 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
    return (int) $db->lastInsertId();
}
function sections_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !sections_get_by_id($db, $id)) throw new RuntimeException('Secció no trobada');
    $errors = sections_validate_save($db, $data, $id); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('UPDATE org_sections SET section_code = :code, name = :name, area_id = :area_id, is_active = :is_active WHERE id = :id');
    $st->execute(['id' => $id, 'code' => (int) $data['section_code'], 'name' => trim((string) $data['name']), 'area_id' => (int) $data['area_id'], 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
}
function sections_delete(PDO $db, int $id): void
{
    if ($id < 1) throw new InvalidArgumentException('ID invàlid');
    $st = $db->prepare('SELECT COUNT(*) AS c FROM org_units WHERE section_id = :id'); $st->execute(['id' => $id]);
    if ((int) (($st->fetch() ?: [])['c'] ?? 0) > 0) {
        throw new InvalidArgumentException(json_encode(['_general' => 'No es pot eliminar aquesta secció perquè té unitats associades.'], JSON_THROW_ON_ERROR));
    }
    $del = $db->prepare('DELETE FROM org_sections WHERE id = :id LIMIT 1');
    try {
        $del->execute(['id' => $id]);
    } catch (PDOException $e) {
        if (db_is_integrity_constraint_violation($e)) {
            throw new InvalidArgumentException(
                json_encode(
                    ['_general' => 'No es pot eliminar aquesta secció perquè té unitats associades.'],
                    JSON_THROW_ON_ERROR
                )
            );
        }
        throw $e;
    }
    if ($del->rowCount() === 0) {
        throw new RuntimeException('Secció no trobada');
    }
}
function sections_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) return null;
    $d = json_decode($e->getMessage(), true); return is_array($d) ? $d : null;
}

<?php
declare(strict_types=1);

function units_sections_for_select(PDO $db, ?int $areaId = null): array
{
    $sql = 'SELECT s.id, s.section_code, s.name, s.area_id, a.name AS area_name, a.area_code
            FROM org_sections s
            INNER JOIN org_areas a ON a.id = s.area_id';
    $params = [];
    if ($areaId !== null && $areaId > 0) { $sql .= ' WHERE s.area_id = :aid'; $params['aid'] = $areaId; }
    $sql .= ' ORDER BY a.area_code ASC, s.section_code ASC, s.name ASC';
    $st = $db->prepare($sql); $st->execute($params);
    return $st->fetchAll() ?: [];
}
function units_areas_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, area_code, name FROM org_areas ORDER BY area_code ASC, name ASC');
    return $st->fetchAll() ?: [];
}
function units_list_sort_keys(): array { return ['unit_code', 'name', 'section_name', 'area_name', 'is_active', 'created_at']; }
function units_normalize_sort(string $sortBy, string $sortDir): array
{
    $a = array_flip(units_list_sort_keys());
    return ['by' => isset($a[$sortBy]) ? $sortBy : 'unit_code', 'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc'];
}
function units_list_filters_clause(array $filters): array
{
    $w = ['1=1']; $p = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') { $w[] = '(u.name LIKE :q1 OR CAST(u.unit_code AS CHAR) LIKE :q2 OR s.name LIKE :q3 OR a.name LIKE :q4)'; $like='%' . $q . '%'; $p['q1']=$like; $p['q2']=$like; $p['q3']=$like; $p['q4']=$like; }
    $areaId = null_if_empty_int($filters['area_id'] ?? null); if ($areaId !== null) { $w[] = 's.area_id = :area_id'; $p['area_id'] = $areaId; }
    $sectionId = null_if_empty_int($filters['section_id'] ?? null); if ($sectionId !== null) { $w[] = 'u.section_id = :section_id'; $p['section_id'] = $sectionId; }
    $active = trim((string) ($filters['active'] ?? '')); if ($active === '1' || $active === '0') { $w[]='u.is_active = :active'; $p['active'] = (int) $active; }
    return ['where' => $w, 'params' => $p];
}
function units_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = units_normalize_sort($sortBy, $sortDir); $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'u.name ' . $dir;
        case 'section_name':
            return 's.name ' . $dir;
        case 'area_name':
            return 'a.name ' . $dir;
        case 'is_active':
            return 'u.is_active ' . $dir;
        case 'created_at':
            return 'u.created_at ' . $dir;
        default:
            return 'u.unit_code ' . $dir;
    }
}
function units_count(PDO $db, array $filters): int
{
    $x = units_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c
            FROM org_units u
            INNER JOIN org_sections s ON s.id = u.section_id
            INNER JOIN org_areas a ON a.id = s.area_id
            WHERE ' . implode(' AND ', $x['where']);
    $st = $db->prepare($sql); $st->execute($x['params']); $r = $st->fetch(); return (int) ($r['c'] ?? 0);
}
function units_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $x = units_list_filters_clause($filters);
    $sql = 'SELECT u.*, s.name AS section_name, s.section_code, s.area_id, a.name AS area_name, a.area_code
            FROM org_units u
            INNER JOIN org_sections s ON s.id = u.section_id
            INNER JOIN org_areas a ON a.id = s.area_id
            WHERE ' . implode(' AND ', $x['where']) . '
            ORDER BY ' . units_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $x['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}
function units_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100; if ($page < 1) $page = 1;
    $tp = $total > 0 ? (int) ceil($total / $perPage) : 1; if ($page > $tp) $page = $tp;
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $tp, 'offset' => ($page - 1) * $perPage];
}
function units_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) return null;
    $st = $db->prepare('SELECT u.*, s.name AS section_name, s.section_code, s.area_id, a.name AS area_name, a.area_code
                        FROM org_units u
                        INNER JOIN org_sections s ON s.id = u.section_id
                        INNER JOIN org_areas a ON a.id = s.area_id
                        WHERE u.id = :id LIMIT 1');
    $st->execute(['id' => $id]); $r = $st->fetch(); return $r ?: null;
}
function units_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $codeRaw = trim((string) ($data['unit_code'] ?? ''));
    if ($codeRaw === '' || !ctype_digit($codeRaw)) $errors['unit_code'] = 'El codi d’unitat és obligatori i numèric.';
    $name = trim((string) ($data['name'] ?? '')); if ($name === '') $errors['name'] = 'El nom és obligatori.';
    $sectionId = null_if_empty_int($data['section_id'] ?? null); if ($sectionId === null) $errors['section_id'] = 'Selecciona una secció.';
    if ($sectionId !== null) { $st = $db->prepare('SELECT id FROM org_sections WHERE id = :id LIMIT 1'); $st->execute(['id' => $sectionId]); if (!$st->fetch()) $errors['section_id'] = 'Secció no vàlida.'; }
    if (!isset($errors['unit_code'])) {
        $sql = 'SELECT id FROM org_units WHERE unit_code = :code'; $params = ['code' => (int) $codeRaw];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1'); $st->execute($params); if ($st->fetch()) $errors['unit_code'] = 'Aquest codi d’unitat ja existeix.';
    }
    return $errors;
}
function units_create(PDO $db, array $data): int
{
    $errors = units_validate_save($db, $data, null); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('INSERT INTO org_units (unit_code, name, section_id, is_active) VALUES (:code, :name, :section_id, :is_active)');
    $st->execute(['code' => (int) $data['unit_code'], 'name' => trim((string) $data['name']), 'section_id' => (int) $data['section_id'], 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
    return (int) $db->lastInsertId();
}
function units_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !units_get_by_id($db, $id)) throw new RuntimeException('Unitat no trobada');
    $errors = units_validate_save($db, $data, $id); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $st = $db->prepare('UPDATE org_units SET unit_code = :code, name = :name, section_id = :section_id, is_active = :is_active WHERE id = :id');
    $st->execute(['id' => $id, 'code' => (int) $data['unit_code'], 'name' => trim((string) $data['name']), 'section_id' => (int) $data['section_id'], 'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0]);
}
function units_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid');
    }
    $st = $db->prepare('DELETE FROM org_units WHERE id = :id LIMIT 1');
    try {
        $st->execute(['id' => $id]);
    } catch (PDOException $e) {
        if (db_is_integrity_constraint_violation($e)) {
            throw new InvalidArgumentException(
                json_encode(
                    ['_general' => 'No es pot eliminar aquesta unitat perquè té llocs de treball associats.'],
                    JSON_THROW_ON_ERROR
                )
            );
        }
        throw $e;
    }
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Unitat no trobada');
    }
}
function units_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) return null;
    $d = json_decode($e->getMessage(), true); return is_array($d) ? $d : null;
}

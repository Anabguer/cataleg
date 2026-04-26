<?php
declare(strict_types=1);

/** Codi mínim per a unitats generades automàticament per a llocs no catàleg (job_positions nous). */
const JOB_POSITIONS_AUTO_UNIT_CODE_MIN = 8000;

function job_positions_areas_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, area_code, name, alias FROM org_areas ORDER BY area_code ASC, name ASC');
    return $st->fetchAll() ?: [];
}

function job_positions_sections_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, section_code, name, area_id FROM org_sections ORDER BY section_code ASC, name ASC');
    return $st->fetchAll() ?: [];
}

/**
 * Unitats per a filtres (amb codi per al llistat).
 *
 * @return list<array{id:int,unit_code:int,name:string,section_id:int,section_code:int,section_name:string,area_id:int,area_code:int,area_name:string}>
 */
function job_positions_units_for_select(PDO $db): array
{
    $sql = 'SELECT u.id, u.unit_code, u.name, u.section_id, s.section_code, s.name AS section_name,
                   a.id AS area_id, a.area_code, a.name AS area_name
            FROM org_units u
            INNER JOIN org_sections s ON s.id = u.section_id
            INNER JOIN org_areas a ON a.id = s.area_id
            ORDER BY u.unit_code ASC, u.name ASC';
    $st = $db->query($sql);
    return $st->fetchAll() ?: [];
}

/**
 * Unitats automàtiques (unit_code ≥ 8000) per assignar un lloc nou dins d’una unitat ja existent.
 *
 * @return list<array{id:int,unit_code:int,name:string}>
 */
function job_positions_auto_units_for_select(PDO $db): array
{
    $min = JOB_POSITIONS_AUTO_UNIT_CODE_MIN;
    $st = $db->prepare(
        'SELECT id, unit_code, name FROM org_units WHERE unit_code >= :min ORDER BY unit_code ASC, name ASC'
    );
    $st->execute(['min' => $min]);
    return $st->fetchAll() ?: [];
}

/**
 * Següent `unit_code` a assignar a una unitat nova automàtica: MAX(unit_code >= 8000) + 1, o 8000 si no n’hi ha cap.
 */
function job_positions_next_auto_unit_code(PDO $db): int
{
    $min = JOB_POSITIONS_AUTO_UNIT_CODE_MIN;
    $below = $min - 1;
    $st = $db->prepare(
        'SELECT COALESCE(MAX(unit_code), :below) AS m FROM org_units WHERE unit_code >= :min'
    );
    $st->execute(['below' => $below, 'min' => $min]);
    $r = $st->fetch();
    $m = (int) ($r['m'] ?? $below);
    return $m + 1;
}

/**
 * Primera secció organitzativa (per ancorar unitats creades automàticament; requereix dades mestres vàlides).
 */
function job_positions_default_section_id(PDO $db): ?int
{
    $st = $db->query('SELECT id FROM org_sections ORDER BY id ASC LIMIT 1');
    $r = $st->fetch();
    return $r ? (int) $r['id'] : null;
}

/**
 * Obté l’id d’org_units per al codi donat, o crea una fila “Unitat automàtica {codi}” vinculada a una secció per defecte.
 *
 * Les unitats amb unit_code >= 8000 generades aquí serveixen només per donar suport a llocs de treball no catàleg.
 */
function job_positions_get_or_create_auto_unit(PDO $db, int $unitCode): int
{
    $st = $db->prepare('SELECT id FROM org_units WHERE unit_code = :c LIMIT 1');
    $st->execute(['c' => $unitCode]);
    $row = $st->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $sectionId = job_positions_default_section_id($db);
    if ($sectionId === null) {
        throw new RuntimeException('No hi ha cap secció organitzativa per crear la unitat automàtica.');
    }
    $name = 'Unitat automàtica ' . $unitCode;
    $ins = $db->prepare(
        'INSERT INTO org_units (unit_code, name, section_id, is_active) VALUES (:c, :n, :sid, 1)'
    );
    $ins->execute(['c' => $unitCode, 'n' => $name, 'sid' => $sectionId]);
    return (int) $db->lastInsertId();
}

function job_positions_list_sort_keys(): array
{
    return ['code', 'name', 'unit_name', 'is_catalog', 'is_active', 'created_at'];
}

function job_positions_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(job_positions_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'code';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
    return ['by' => $by, 'dir' => $dir];
}

function job_positions_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(jp.name LIKE :q1 OR CAST(u.unit_code AS CHAR) LIKE :q2 OR CAST(jp.position_number AS CHAR) LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $areaId = null_if_empty_int($filters['area_id'] ?? null);
    if ($areaId !== null) {
        $where[] = 'a.id = :area_id';
        $params['area_id'] = $areaId;
    }
    $sectionId = null_if_empty_int($filters['section_id'] ?? null);
    if ($sectionId !== null) {
        $where[] = 's.id = :section_id';
        $params['section_id'] = $sectionId;
    }
    $unitId = null_if_empty_int($filters['unit_id'] ?? null);
    if ($unitId !== null) {
        $where[] = 'jp.unit_id = :unit_id';
        $params['unit_id'] = $unitId;
    }
    $catalog = trim((string) ($filters['is_catalog'] ?? ''));
    if ($catalog === '1' || $catalog === '0') {
        $where[] = 'jp.is_catalog = :is_catalog';
        $params['is_catalog'] = (int) $catalog;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'jp.is_active = :active';
        $params['active'] = (int) $active;
    }
    return ['where' => $where, 'params' => $params];
}

function job_positions_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = job_positions_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'jp.name ' . $dir;
        case 'unit_name':
            return $dir === 'DESC'
                ? 'u.unit_code DESC, u.name DESC'
                : 'u.unit_code ASC, u.name ASC';
        case 'is_catalog':
            return 'jp.is_catalog ' . $dir;
        case 'is_active':
            return 'jp.is_active ' . $dir;
        case 'created_at':
            return 'jp.created_at ' . $dir;
        case 'code':
            return $dir === 'DESC'
                ? 'u.unit_code DESC, jp.position_number DESC'
                : 'u.unit_code ASC, jp.position_number ASC';
        default:
            return 'u.unit_code ASC, jp.position_number ASC';
    }
}

function job_positions_list_from_clause(): string
{
    return 'job_positions jp
            INNER JOIN org_units u ON u.id = jp.unit_id
            INNER JOIN org_sections s ON s.id = u.section_id
            INNER JOIN org_areas a ON a.id = s.area_id';
}

function job_positions_count(PDO $db, array $filters): int
{
    $f = job_positions_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM ' . job_positions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();
    return (int) ($r['c'] ?? 0);
}

/**
 * @return list<array<string,mixed>>
 */
function job_positions_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = job_positions_list_filters_clause($filters);
    $sql = 'SELECT jp.*, u.unit_code, u.name AS unit_name,
                   s.section_code, s.name AS section_name,
                   a.area_code, a.name AS area_name
            FROM ' . job_positions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . job_positions_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function job_positions_normalize_pagination(int $page, int $perPage, int $total): array
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
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages, 'offset' => ($page - 1) * $perPage];
}

function job_positions_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $db->prepare(
        'SELECT jp.*, u.unit_code, u.name AS unit_name
         FROM job_positions jp
         INNER JOIN org_units u ON u.id = jp.unit_id
         WHERE jp.id = :id
         LIMIT 1'
    );
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Validació per a alta (sense unit_id: es resol al servidor).
 *
 * @return array<string, string>
 */
function job_positions_validate_create_fields(array $data): array
{
    $errors = [];
    $posRaw = trim((string) ($data['position_number'] ?? ''));
    if ($posRaw === '' || !ctype_digit($posRaw)) {
        $errors['position_number'] = 'El número és obligatori i ha de ser numèric enter (1–99).';
    } else {
        $pn = (int) $posRaw;
        if ($pn < 1 || $pn > 99) {
            $errors['position_number'] = 'El número ha d’estar entre 1 i 99 (format de 2 dígits).';
        }
    }
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'La denominació és obligatòria.';
    } elseif (mb_strlen($name) > 200) {
        $errors['name'] = 'Màxim 200 caràcters.';
    }
    return $errors;
}

/**
 * Validació d’alta: mode d’assignació d’unitat (existent ≥8000 o nova automàtica).
 *
 * @return array<string, string>
 */
function job_positions_validate_create(PDO $db, array $data): array
{
    $errors = job_positions_validate_create_fields($data);
    if ($errors !== []) {
        return $errors;
    }
    $mode = trim((string) ($data['assignment_mode'] ?? ''));
    if ($mode !== 'existing' && $mode !== 'new') {
        $errors['assignment_mode'] = 'Selecciona una opció d’assignació d’unitat.';
        return $errors;
    }
    if ($mode === 'existing') {
        $uid = null_if_empty_int($data['existing_unit_id'] ?? null);
        if ($uid === null) {
            $errors['existing_unit_id'] = 'Selecciona una unitat automàtica.';
            return $errors;
        }
        $st = $db->prepare(
            'SELECT id FROM org_units WHERE id = :id AND unit_code >= :min LIMIT 1'
        );
        $st->execute(['id' => $uid, 'min' => JOB_POSITIONS_AUTO_UNIT_CODE_MIN]);
        if (!$st->fetch()) {
            $errors['existing_unit_id'] = 'Unitat no vàlida o no és una unitat automàtica (codi ≥ 8000).';
        }
    }
    return $errors;
}

function job_positions_duplicate_check(PDO $db, int $unitId, int $positionNumber, ?int $excludeId = null): bool
{
    $sql = 'SELECT id FROM job_positions WHERE unit_id = :uid AND position_number = :pn';
    $params = ['uid' => $unitId, 'pn' => $positionNumber];
    if ($excludeId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $excludeId;
    }
    $st = $db->prepare($sql . ' LIMIT 1');
    $st->execute($params);
    return (bool) $st->fetch();
}

/**
 * Validació per a actualització: mateixa unitat (no es canvia des de la UI); no catàleg.
 *
 * @return array<string, string>
 */
function job_positions_validate_update(PDO $db, array $existing, array $data, int $id): array
{
    $errors = [];
    if (!empty($existing['is_catalog'])) {
        $errors['_general'] = 'No es pot modificar un lloc de catàleg.';
        return $errors;
    }
    $posRaw = trim((string) ($data['position_number'] ?? ''));
    $pn = null;
    if ($posRaw === '' || !ctype_digit($posRaw)) {
        $errors['position_number'] = 'El número és obligatori i ha de ser numèric enter (1–99).';
    } else {
        $pn = (int) $posRaw;
        if ($pn < 1 || $pn > 99) {
            $errors['position_number'] = 'El número ha d’estar entre 1 i 99 (format de 2 dígits).';
            $pn = null;
        }
    }
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'La denominació és obligatòria.';
    } elseif (mb_strlen($name) > 200) {
        $errors['name'] = 'Màxim 200 caràcters.';
    }
    $unitId = (int) $existing['unit_id'];
    if (!isset($errors['position_number']) && $pn !== null) {
        if (job_positions_duplicate_check($db, $unitId, $pn, $id)) {
            $errors['position_number'] = 'El número ja existeix per a aquesta unitat.';
        }
    }
    return $errors;
}

function job_positions_create(PDO $db, array $data): int
{
    $errors = job_positions_validate_create($db, $data);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $positionNumber = (int) $data['position_number'];
    $isActive = isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0;
    $mode = trim((string) ($data['assignment_mode'] ?? ''));

    $db->beginTransaction();
    try {
        if ($mode === 'existing') {
            $uid = (int) null_if_empty_int($data['existing_unit_id'] ?? null);
            $st = $db->prepare(
                'SELECT id FROM org_units WHERE id = :id AND unit_code >= :min LIMIT 1'
            );
            $st->execute(['id' => $uid, 'min' => JOB_POSITIONS_AUTO_UNIT_CODE_MIN]);
            $row = $st->fetch();
            if (!$row) {
                $db->rollBack();
                throw new InvalidArgumentException(
                    json_encode(['existing_unit_id' => 'Unitat no vàlida.'], JSON_THROW_ON_ERROR)
                );
            }
            $unitId = (int) $row['id'];
        } else {
            $nextCode = job_positions_next_auto_unit_code($db);
            $unitId = job_positions_get_or_create_auto_unit($db, $nextCode);
        }
        if (job_positions_duplicate_check($db, $unitId, $positionNumber, null)) {
            $db->rollBack();
            throw new InvalidArgumentException(
                json_encode(['position_number' => 'El número ja existeix per a aquesta unitat.'], JSON_THROW_ON_ERROR)
            );
        }
        $st = $db->prepare(
            'INSERT INTO job_positions (unit_id, position_number, name, is_catalog, is_active)
             VALUES (:unit_id, :position_number, :name, 0, :is_active)'
        );
        $st->execute([
            'unit_id' => $unitId,
            'position_number' => $positionNumber,
            'name' => trim((string) $data['name']),
            'is_active' => $isActive,
        ]);
        $id = (int) $db->lastInsertId();
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function job_positions_update(PDO $db, int $id, array $data): void
{
    $existing = job_positions_get_by_id($db, $id);
    if ($id < 1 || !$existing) {
        throw new RuntimeException('Lloc de treball no trobat');
    }
    $errors = job_positions_validate_update($db, $existing, $data, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare(
        'UPDATE job_positions SET
            position_number = :position_number,
            name = :name,
            is_active = :is_active
         WHERE id = :id AND is_catalog = 0'
    );
    $st->execute([
        'id' => $id,
        'position_number' => (int) $data['position_number'],
        'name' => trim((string) $data['name']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
    if ($st->rowCount() === 0) {
        throw new InvalidArgumentException(
            json_encode(['_general' => 'No es pot modificar un lloc de catàleg.'], JSON_THROW_ON_ERROR)
        );
    }
}

function job_positions_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('SELECT is_catalog FROM job_positions WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Lloc de treball no trobat');
    }
    if (!empty($row['is_catalog'])) {
        throw new InvalidArgumentException(
            json_encode(['_general' => 'No es pot eliminar un lloc de catàleg.'], JSON_THROW_ON_ERROR)
        );
    }
    $del = $db->prepare('DELETE FROM job_positions WHERE id = :id LIMIT 1');
    try {
        $del->execute(['id' => $id]);
    } catch (PDOException $e) {
        if (db_is_integrity_constraint_violation($e)) {
            throw new InvalidArgumentException(
                json_encode(
                    ['_general' => 'No es pot eliminar aquest lloc de treball perquè té persones associades.'],
                    JSON_THROW_ON_ERROR
                )
            );
        }
        throw $e;
    }
    if ($del->rowCount() === 0) {
        throw new RuntimeException('Lloc de treball no trobat');
    }
}

function job_positions_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);
    return is_array($d) ? $d : null;
}

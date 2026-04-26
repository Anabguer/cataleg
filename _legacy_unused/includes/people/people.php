<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/job_positions/job_positions_view_helpers.php';

/** Codi mínim assignat automàticament a persones noves (sèrie ≥ 80000). */
const PEOPLE_PERSON_CODE_MIN = 80000;

function people_next_person_code(PDO $db): int
{
    $min = PEOPLE_PERSON_CODE_MIN;
    $st = $db->prepare('SELECT MAX(person_code) AS m FROM people WHERE person_code >= :min');
    $st->execute(['min' => $min]);
    $r = $st->fetch();
    $m = $r['m'] ?? null;
    if ($m === null || $m === '') {
        return $min;
    }
    return (int) $m + 1;
}

/**
 * Nom complet per mostrar (ordre: nom, cognom1, cognom2).
 *
 * @param array<string,mixed> $row
 */
function people_format_display_name(array $row): string
{
    $fn = trim((string) ($row['first_name'] ?? ''));
    $ln1 = trim((string) ($row['last_name_1'] ?? ''));
    $ln2 = trim((string) ($row['last_name_2'] ?? ''));
    $parts = [];
    foreach ([$fn, $ln1, $ln2] as $p) {
        if ($p !== '') {
            $parts[] = $p;
        }
    }

    return trim(implode(' ', $parts));
}

/**
 * Cognoms, nom (sense codi). Ex.: «García López, Marta».
 *
 * @param array<string,mixed> $row
 */
function people_format_surname_first(array $row): string
{
    $ln1 = trim((string) ($row['last_name_1'] ?? ''));
    $ln2 = trim((string) ($row['last_name_2'] ?? ''));
    $fn = trim((string) ($row['first_name'] ?? ''));
    $surnames = trim($ln1 . ' ' . $ln2);
    if ($surnames !== '' && $fn !== '') {
        return $surnames . ', ' . $fn;
    }
    if ($surnames !== '') {
        return $surnames;
    }

    return $fn;
}

/**
 * Etiqueta per llistats i selectors: cognoms, nom i codi entre parèntesis.
 * Ex.: "Garcia López, Maria (00042)"
 *
 * @param array<string,mixed> $row
 */
function people_format_surname_first_with_code(array $row): string
{
    $ln1 = trim((string) ($row['last_name_1'] ?? ''));
    $ln2 = trim((string) ($row['last_name_2'] ?? ''));
    $fn = trim((string) ($row['first_name'] ?? ''));
    $surnames = trim($ln1 . ' ' . $ln2);
    $code = format_padded_code((int) ($row['person_code'] ?? 0), 5);
    if ($surnames !== '' && $fn !== '') {
        return $surnames . ', ' . $fn . ' (' . $code . ')';
    }
    if ($surnames !== '') {
        return $surnames . ' (' . $code . ')';
    }
    if ($fn !== '') {
        return $fn . ' (' . $code . ')';
    }

    return '(' . $code . ')';
}

/**
 * Etiqueta visible del lloc de treball (requereix JOIN job_positions + org_units a la consulta).
 *
 * @param array<string,mixed> $row
 */
function people_job_position_label_from_joined_row(array $row): ?string
{
    $jid = null_if_empty_int($row['job_position_id'] ?? null);
    if ($jid === null || $jid < 1) {
        return null;
    }
    $name = trim((string) ($row['job_position_name'] ?? ''));
    if ($name === '') {
        return null;
    }
    $uc = (int) ($row['job_unit_code'] ?? 0);
    $pn = (int) ($row['job_position_number'] ?? 0);

    return format_job_position_code($uc, $pn) . ' — ' . $name;
}

/**
 * Cerca ràpida de persones actives per selector (assistents, etc.).
 *
 * @return list<array{id:int,person_code:int,label:string,dni:?string,email:?string}>
 */
function people_search_for_training_picker(PDO $db, string $q, int $limit = 80): array
{
    $limit = max(1, min(200, $limit));
    $filters = [
        'q' => $q,
        'active' => '1',
    ];
    $rows = people_list($db, $filters, 'person_code', 'asc', $limit, 0);
    $out = [];
    foreach ($rows as $r) {
        $code = (int) $r['person_code'];
        $out[] = [
            'id' => (int) $r['id'],
            'person_code' => $code,
            'label' => format_padded_code($code, 5) . ' — ' . people_format_display_name($r),
            'dni' => isset($r['dni']) && (string) $r['dni'] !== '' ? (string) $r['dni'] : null,
            'email' => isset($r['email']) && (string) $r['email'] !== '' ? (string) $r['email'] : null,
        ];
    }

    return $out;
}

/**
 * Llista de persones actives per al selector d’assistents (etiqueta només des del backend).
 *
 * @return list<array{id:int,label:string}>
 */
function people_list_for_attendee_picker(PDO $db, int $limit = 500): array
{
    $limit = max(1, min(1000, $limit));
    $sql = 'SELECT id, person_code, first_name, last_name_1, last_name_2
            FROM people
            WHERE is_active = 1
            ORDER BY last_name_1 ASC, last_name_2 ASC, first_name ASC, person_code ASC
            LIMIT ' . (int) $limit;
    $st = $db->query($sql);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'label' => people_format_surname_first_with_code($r),
        ];
    }

    return $out;
}

/**
 * Tots els llocs (filtre del llistat): actius i inactius.
 *
 * @return list<array{id:int,unit_code:int,position_number:int,name:string}>
 */
function people_job_positions_for_filter(PDO $db): array
{
    $sql = 'SELECT jp.id, u.unit_code, jp.position_number, jp.name
            FROM job_positions jp
            INNER JOIN org_units u ON u.id = jp.unit_id
            ORDER BY u.unit_code ASC, jp.position_number ASC, jp.name ASC';
    $st = $db->query($sql);
    return $st->fetchAll() ?: [];
}

/**
 * Només llocs actius per al select del modal (alta; en edició el lloc inactiu assignat s’afegeix per JS/API).
 *
 * @return list<array{id:int,unit_code:int,position_number:int,name:string}>
 */
function people_job_positions_for_form_modal(PDO $db): array
{
    $sql = 'SELECT jp.id, u.unit_code, jp.position_number, jp.name
            FROM job_positions jp
            INNER JOIN org_units u ON u.id = jp.unit_id
            WHERE jp.is_active = 1
            ORDER BY u.unit_code ASC, jp.position_number ASC, jp.name ASC';
    $st = $db->query($sql);
    return $st->fetchAll() ?: [];
}

/** @param mixed $value */
function people_normalize_optional_string($value): ?string
{
    $t = trim((string) ($value ?? ''));
    return $t === '' ? null : $t;
}

function people_list_sort_keys(): array
{
    return ['person_code', 'person_name', 'dni', 'email', 'job_position', 'is_catalog', 'is_active', 'created_at'];
}

function people_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(people_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'person_code';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
    return ['by' => $by, 'dir' => $dir];
}

function people_list_from_clause(): string
{
    return 'people p
            LEFT JOIN job_positions jp ON jp.id = p.job_position_id
            LEFT JOIN org_units u ON u.id = jp.unit_id';
}

function people_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(p.last_name_1 LIKE :q OR p.last_name_2 LIKE :q OR p.first_name LIKE :q
            OR p.dni LIKE :q OR p.email LIKE :q OR CAST(p.person_code AS CHAR) LIKE :q OR jp.name LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'p.is_active = :active';
        $params['active'] = (int) $active;
    }
    $catalog = trim((string) ($filters['is_catalog'] ?? ''));
    if ($catalog === '1' || $catalog === '0') {
        $where[] = 'p.is_catalog = :is_catalog';
        $params['is_catalog'] = (int) $catalog;
    }
    $jpid = null_if_empty_int($filters['job_position_id'] ?? null);
    if ($jpid !== null) {
        $where[] = 'p.job_position_id = :job_position_id';
        $params['job_position_id'] = $jpid;
    }
    $hasJob = trim((string) ($filters['has_job_position'] ?? ''));
    if ($hasJob === '1') {
        $where[] = 'p.job_position_id IS NOT NULL';
    } elseif ($hasJob === '0') {
        $where[] = 'p.job_position_id IS NULL';
    }
    return ['where' => $where, 'params' => $params];
}

function people_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = people_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'person_name':
            return 'p.last_name_1 ' . $dir . ', p.last_name_2 ' . $dir . ', p.first_name ' . $dir;
        case 'dni':
            return '(p.dni IS NULL OR p.dni = \'\') ASC, p.dni ' . $dir;
        case 'email':
            return '(p.email IS NULL OR p.email = \'\') ASC, p.email ' . $dir;
        case 'job_position':
            return '(p.job_position_id IS NULL) ASC, u.unit_code ' . $dir . ', jp.position_number ' . $dir;
        case 'is_active':
            return 'p.is_active ' . $dir;
        case 'is_catalog':
            return 'p.is_catalog ' . $dir;
        case 'created_at':
            return 'p.created_at ' . $dir;
        case 'person_code':
            return 'p.person_code ' . $dir;
        default:
            return 'p.person_code ' . $dir;
    }
}

function people_count(PDO $db, array $filters): int
{
    $f = people_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM ' . people_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();
    return (int) ($r['c'] ?? 0);
}

/**
 * @return list<array<string,mixed>>
 */
function people_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = people_list_filters_clause($filters);
    $sql = 'SELECT p.*, jp.name AS job_position_name, jp.position_number AS job_position_number,
                   u.unit_code AS job_unit_code
            FROM ' . people_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . people_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function people_normalize_pagination(int $page, int $perPage, int $total): array
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

function people_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $sql = 'SELECT p.*, jp.name AS job_position_name, jp.position_number AS job_position_number,
                   u.unit_code AS job_unit_code, jp.is_active AS job_position_is_active
            FROM people p
            LEFT JOIN job_positions jp ON jp.id = p.job_position_id
            LEFT JOIN org_units u ON u.id = jp.unit_id
            WHERE p.id = :id
            LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Camps extra per a la resposta JSON del modal (etiqueta opció injectada si el lloc és inactiu).
 *
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function people_person_for_api(array $row): array
{
    $out = $row;
    $jid = null_if_empty_int($row['job_position_id'] ?? null);
    if ($jid !== null) {
        $uc = (int) ($row['job_unit_code'] ?? 0);
        $pn = (int) ($row['job_position_number'] ?? 0);
        $name = (string) ($row['job_position_name'] ?? '');
        $active = !empty($row['job_position_is_active']);
        $out['job_position_is_active'] = $active ? 1 : 0;
        $out['job_position_option_label'] = format_job_position_code($uc, $pn) . ' — ' . $name . ($active ? '' : ' (inactiu)');
    }
    return $out;
}

/**
 * @return array<string, string>
 */
function people_validate_payload(PDO $db, array $data, ?array $existingPerson = null): array
{
    $errors = [];
    $ln1 = trim((string) ($data['last_name_1'] ?? ''));
    if ($ln1 === '') {
        $errors['last_name_1'] = 'El primer cognom és obligatori.';
    } elseif (mb_strlen($ln1) > 150) {
        $errors['last_name_1'] = 'Màxim 150 caràcters.';
    }
    $ln2 = people_normalize_optional_string($data['last_name_2'] ?? null);
    if ($ln2 !== null && mb_strlen($ln2) > 150) {
        $errors['last_name_2'] = 'Màxim 150 caràcters.';
    }
    $fn = trim((string) ($data['first_name'] ?? ''));
    if ($fn === '') {
        $errors['first_name'] = 'El nom és obligatori.';
    } elseif (mb_strlen($fn) > 150) {
        $errors['first_name'] = 'Màxim 150 caràcters.';
    }
    $dni = people_normalize_optional_string($data['dni'] ?? null);
    if ($dni !== null && mb_strlen($dni) > 20) {
        $errors['dni'] = 'Màxim 20 caràcters.';
    }
    $email = people_normalize_optional_string($data['email'] ?? null);
    if ($email !== null) {
        if (mb_strlen($email) > 150) {
            $errors['email'] = 'Màxim 150 caràcters.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'El correu electrònic no és vàlid.';
        }
    }
    $jid = null_if_empty_int($data['job_position_id'] ?? null);
    if ($jid !== null) {
        $st = $db->prepare('SELECT id, is_active FROM job_positions WHERE id = :id LIMIT 1');
        $st->execute(['id' => $jid]);
        $jpRow = $st->fetch();
        if (!$jpRow) {
            $errors['job_position_id'] = 'Lloc de treball no vàlid.';
        } else {
            $jpActive = !empty($jpRow['is_active']);
            if ($existingPerson === null) {
                if (!$jpActive) {
                    $errors['job_position_id'] = 'Seleccioneu un lloc de treball actiu.';
                }
            } else {
                $prevJid = null_if_empty_int($existingPerson['job_position_id'] ?? null);
                if ($jid !== $prevJid && !$jpActive) {
                    $errors['job_position_id'] = 'Seleccioneu un lloc de treball actiu.';
                }
            }
        }
    }
    return $errors;
}

/**
 * @return array<string, string>
 */
function people_validate_create(PDO $db, array $data): array
{
    return people_validate_payload($db, $data, null);
}

/**
 * @return array<string, string>
 */
function people_validate_update(PDO $db, array $existing, array $data): array
{
    $errors = [];
    if (!empty($existing['is_catalog'])) {
        $errors['_general'] = 'No es pot modificar un registre de catàleg.';
        return $errors;
    }
    return people_validate_payload($db, $data, $existing);
}

function people_create(PDO $db, array $data): int
{
    $errors = people_validate_create($db, $data);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $code = people_next_person_code($db);
    $isActive = isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0;
    $ln2 = people_normalize_optional_string($data['last_name_2'] ?? null);
    $dni = people_normalize_optional_string($data['dni'] ?? null);
    $email = people_normalize_optional_string($data['email'] ?? null);
    $jid = null_if_empty_int($data['job_position_id'] ?? null);
    $st = $db->prepare(
        'INSERT INTO people (person_code, last_name_1, last_name_2, first_name, dni, job_position_id, email, is_catalog, is_active)
         VALUES (:person_code, :last_name_1, :last_name_2, :first_name, :dni, :job_position_id, :email, 0, :is_active)'
    );
    $st->execute([
        'person_code' => $code,
        'last_name_1' => trim((string) $data['last_name_1']),
        'last_name_2' => $ln2,
        'first_name' => trim((string) $data['first_name']),
        'dni' => $dni,
        'job_position_id' => $jid,
        'email' => $email,
        'is_active' => $isActive,
    ]);
    return (int) $db->lastInsertId();
}

function people_update(PDO $db, int $id, array $data): void
{
    $existing = people_get_by_id($db, $id);
    if ($id < 1 || !$existing) {
        throw new RuntimeException('Persona no trobada');
    }
    $errors = people_validate_update($db, $existing, $data);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $isActive = isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0;
    $ln2 = people_normalize_optional_string($data['last_name_2'] ?? null);
    $dni = people_normalize_optional_string($data['dni'] ?? null);
    $email = people_normalize_optional_string($data['email'] ?? null);
    $jid = null_if_empty_int($data['job_position_id'] ?? null);
    $st = $db->prepare(
        'UPDATE people SET
            last_name_1 = :last_name_1,
            last_name_2 = :last_name_2,
            first_name = :first_name,
            dni = :dni,
            job_position_id = :job_position_id,
            email = :email,
            is_active = :is_active
         WHERE id = :id AND is_catalog = 0'
    );
    $st->execute([
        'id' => $id,
        'last_name_1' => trim((string) $data['last_name_1']),
        'last_name_2' => $ln2,
        'first_name' => trim((string) $data['first_name']),
        'dni' => $dni,
        'job_position_id' => $jid,
        'email' => $email,
        'is_active' => $isActive,
    ]);
    if ($st->rowCount() === 0) {
        throw new InvalidArgumentException(
            json_encode(['_general' => 'No es pot modificar un registre de catàleg.'], JSON_THROW_ON_ERROR)
        );
    }
}

function people_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('SELECT is_catalog FROM people WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Persona no trobada');
    }
    if (!empty($row['is_catalog'])) {
        throw new InvalidArgumentException(
            json_encode(['_general' => 'No es pot eliminar un registre de catàleg.'], JSON_THROW_ON_ERROR)
        );
    }
    $del = $db->prepare('DELETE FROM people WHERE id = :id AND is_catalog = 0 LIMIT 1');
    $del->execute(['id' => $id]);
    if ($del->rowCount() === 0) {
        throw new RuntimeException('Persona no trobada');
    }
}

function people_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);
    return is_array($d) ? $d : null;
}

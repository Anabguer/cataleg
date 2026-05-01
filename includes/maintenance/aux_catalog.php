<?php
declare(strict_types=1);

require_once __DIR__ . '/maintenance_columns.php';

function maintenance_modules_config(): array
{
    return [
        'maintenance_scales' => ['title' => 'Escales funcionaris', 'table' => 'civil_service_scales', 'implemented' => true],
        'maintenance_subscales' => ['title' => 'Subescales funcionaris', 'table' => 'civil_service_subscales', 'implemented' => true],
        'maintenance_classes' => ['title' => 'Classes funcionaris', 'table' => 'civil_service_classes', 'implemented' => true],
        'maintenance_categories' => ['title' => 'Categories funcionaris', 'table' => 'civil_service_categories', 'implemented' => true],
        'maintenance_legal_relationships' => ['title' => 'Relacions jurídiques', 'table' => 'legal_relations', 'implemented' => true],
        'maintenance_administrative_statuses' => ['title' => 'Situacions administratives funcionaris', 'table' => 'administrative_statuses', 'implemented' => true],
        'maintenance_position_classes' => ['title' => 'Classes de plaça', 'table' => 'position_classes', 'implemented' => true],
        'maintenance_access_types' => ['title' => 'Tipus d’accés', 'table' => 'access_types', 'implemented' => true],
        'maintenance_access_systems' => ['title' => 'Sistemes d’accés', 'table' => 'access_systems', 'implemented' => true],
        'maintenance_work_centers' => ['title' => 'Centres de treball', 'table' => 'work_centers', 'implemented' => true],
        'maintenance_availability_types' => ['title' => 'Disponibilitat Llocs', 'table' => 'availability_options', 'implemented' => true],
        'maintenance_provision_forms' => ['title' => 'Formes de provisió', 'table' => 'provision_methods', 'implemented' => true],
        'maintenance_organic_level_1' => ['title' => 'Classificació Orgànica 1 dígit', 'table' => 'org_units_level_1', 'implemented' => true],
        'maintenance_organic_level_2' => ['title' => 'Classificació Orgànica 2 dígits', 'table' => 'org_units_level_2', 'implemented' => true],
        'maintenance_organic_level_3' => ['title' => 'Classificació Orgànica 4 dígits', 'table' => 'org_units_level_3', 'implemented' => true],
        'maintenance_programs' => ['title' => 'Programes', 'table' => 'programs', 'implemented' => true],
        'maintenance_social_security_companies' => ['title' => 'Empreses', 'table' => 'social_security_companies', 'implemented' => true],
        'maintenance_social_security_coefficients' => ['title' => 'Coeficients Seguretat Social', 'table' => 'social_security_coefficients', 'implemented' => true],
        'maintenance_social_security_base_limits' => ['title' => 'Bases mínimes i màximes Seguretat Social', 'table' => 'social_security_base_limits', 'implemented' => true],
        'maintenance_salary_base_by_group' => ['title' => 'Sous', 'table' => 'salary_base_by_group', 'implemented' => true],
        'maintenance_destination_allowances' => ['title' => 'Complement destinació', 'table' => 'destination_allowances', 'implemented' => true],
        'maintenance_seniority_pay_by_group' => ['title' => 'Triennis', 'table' => 'seniority_pay_by_group', 'implemented' => true],
        'maintenance_specific_compensation_special_prices' => ['title' => 'Complement específic especial', 'table' => 'specific_compensation_special', 'implemented' => true],
        'maintenance_specific_compensation_general' => ['title' => 'Complement específic general', 'table' => 'specific_compensation_general', 'implemented' => true],
        'maintenance_personal_transitory_bonus' => ['title' => 'CPT personal (transitori)', 'table' => 'people', 'implemented' => true],
        'people' => ['title' => 'Catàleg de persones', 'table' => 'people', 'implemented' => true],
        'management_positions' => ['title' => 'Catàleg places', 'table' => 'positions', 'implemented' => true],
        'job_positions' => ['title' => 'Llocs de Treball', 'table' => 'job_positions', 'implemented' => true],
        'maintenance_subprograms' => ['title' => 'Subprogrames', 'table' => 'subprograms', 'implemented' => true],
    ];
}

function maintenance_module_config(string $module): ?array
{
    $all = maintenance_modules_config();
    return $all[$module] ?? null;
}

function maintenance_sort_normalize(string $module, string $sortBy, string $sortDir): array
{
    return [
        'by' => maintenance_sort_key_canonical($module, $sortBy),
        'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc',
    ];
}

/** Mòduls amb llistat SQL genèric (escales / subescales / classes / categories). */
function maintenance_catalog_list_modules(): array
{
    return ['maintenance_scales', 'maintenance_subscales', 'maintenance_classes', 'maintenance_categories', 'maintenance_legal_relationships', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_programs', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits', 'maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus', 'people', 'management_positions', 'job_positions', 'maintenance_subprograms'];
}

/** Mòduls de llistat amb persistència CRUD al catàleg (exclou llistats només lectura / accions massives sobre altres taules). */
function maintenance_catalog_crud_modules(): array
{
    return array_values(array_diff(maintenance_catalog_list_modules(), ['maintenance_personal_transitory_bonus']));
}

/**
 * Converteix entrada monetària textual (format europeu/internacional i € opcional) a decimal SQL amb punt i 2 decimals.
 *
 * @return array{ok:true, value:?string}|array{ok:false, error:string}
 */
function maintenance_parse_optional_money_input(string $raw): array
{
    $t = trim($raw);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }

    $t = str_replace(["\xC2\xA0", '€', ' '], '', $t);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    if (preg_match('/[^0-9.,]/', $t)) {
        return ['ok' => false, 'error' => 'Import invàlid.'];
    }

    $lastDot = strrpos($t, '.');
    $lastComma = strrpos($t, ',');
    if ($lastDot !== false && $lastComma !== false) {
        $decSep = $lastDot > $lastComma ? '.' : ',';
    } elseif ($lastComma !== false) {
        $decSep = ',';
    } elseif ($lastDot !== false) {
        $afterDot = strlen($t) - $lastDot - 1;
        $decSep = ($afterDot >= 1 && $afterDot <= 2) ? '.' : '';
    } else {
        $decSep = '';
    }

    if ($decSep === ',') {
        $norm = str_replace('.', '', $t);
        $norm = str_replace(',', '.', $norm);
    } elseif ($decSep === '.') {
        $norm = str_replace(',', '', $t);
    } else {
        $norm = str_replace([',', '.'], '', $t);
    }

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $norm)) {
        return ['ok' => false, 'error' => 'Import invàlid (màxim 2 decimals).'];
    }

    return ['ok' => true, 'value' => number_format((float) $norm, 2, '.', '')];
}

/**
 * @return array{ok:true,value:?string}|array{ok:false,error:string}
 */
function maintenance_parse_optional_date_input(string $raw, string $label): array
{
    $t = trim($raw);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $t, $m)) {
        $iso = $m[3] . '-' . $m[2] . '-' . $m[1];
        $dt = DateTime::createFromFormat('Y-m-d', $iso);
        if ($dt && $dt->format('Y-m-d') === $iso) {
            return ['ok' => true, 'value' => $iso];
        }
        return ['ok' => false, 'error' => 'La data de ' . $label . ' no és vàlida.'];
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
        $dt = DateTime::createFromFormat('Y-m-d', $t);
        if ($dt && $dt->format('Y-m-d') === $t) {
            return ['ok' => true, 'value' => $t];
        }
    }
    return ['ok' => false, 'error' => 'La data de ' . $label . ' no és vàlida.'];
}

function maintenance_normalize_pagination(int $page, int $perPage, int $total): array
{
    $perPage = max(1, min(100, $perPage));
    $page = max(1, $page);
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    $page = min($page, $totalPages);
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages, 'offset' => ($page - 1) * $perPage];
}

function maintenance_scales_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT scale_id AS id, scale_name AS name FROM civil_service_scales WHERE catalog_year = :y ORDER BY scale_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

function maintenance_subscales_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT subscale_id AS id, scale_id, subscale_name AS name FROM civil_service_subscales WHERE catalog_year = :y ORDER BY scale_id ASC, subscale_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

function maintenance_classes_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT class_id AS id, subscale_id, scale_id, class_name AS name FROM civil_service_classes WHERE catalog_year = :y ORDER BY scale_id ASC, subscale_id ASC, class_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

/** Opcions per al combo «Orgànic 1 dígit» (manteniment nivell 2). `id` és string (BBDD VARCHAR). */
function maintenance_org_units_level_1_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT org_unit_level_1_id AS id, org_unit_level_1_name AS name FROM org_units_level_1 WHERE catalog_year = :y ORDER BY CAST(org_unit_level_1_id AS UNSIGNED) ASC, org_unit_level_1_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

/** Opcions per al combo «Orgànic 2» (manteniment nivell 3 / 4 dígits). `id` és string (BBDD VARCHAR). */
function maintenance_org_units_level_2_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT org_unit_level_2_id AS id, org_unit_level_2_name AS name FROM org_units_level_2 WHERE catalog_year = :y ORDER BY CAST(org_unit_level_2_id AS UNSIGNED) ASC, org_unit_level_2_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

/** Opcions de lloc de treball per al selector de responsable (manteniment de programes). */
function maintenance_job_positions_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT job_position_id AS id, job_title AS name FROM job_positions WHERE catalog_year = :y AND deleted_at IS NULL ORDER BY job_position_id ASC');
    $st->execute(['y' => $year]);
    return $st->fetchAll() ?: [];
}

/** Opcions de programa per al selector de subprogrames (`id` = `program_id` VARCHAR). */
function maintenance_programs_options_for_select(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT program_id AS id, program_name AS name FROM programs WHERE catalog_year = :y ORDER BY program_id ASC');
    $st->execute(['y' => $year]);

    return $st->fetchAll() ?: [];
}

/**
 * Llocs de treball amb `job_type_id = CM` i actius (no eliminats), per a selectors de subprogrames.
 *
 * @return list<array{id:string,name:string}>
 */
function maintenance_job_positions_cm_options(PDO $db, int $year): array
{
    $st = $db->prepare("SELECT job_position_id AS id, job_title AS name, catalog_code FROM job_positions WHERE catalog_year = :y AND job_type_id = 'CM' AND deleted_at IS NULL ORDER BY job_title ASC, job_position_id ASC");
    $st->execute(['y' => $year]);

    return $st->fetchAll() ?: [];
}

/**
 * Tots els llocs de comandament (CM) per al selector «Responsable» a la modal de llocs.
 * Sense filtrar per baixa: inclou registres amb `deleted_at` (p. ex. codi històric).
 * Ordenat per `job_position_id`.
 *
 * @return list<array{job_position_id:string,job_title:string}>
 */
function maintenance_job_positions_responsible_cm_options(PDO $db, int $year): array
{
    $st = $db->prepare("SELECT job_position_id, job_title FROM job_positions WHERE catalog_year = :y AND UPPER(TRIM(job_type_id)) = 'CM' ORDER BY job_position_id ASC");
    $st->execute(['y' => $year]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Imports de complements específics especials per any (per omplir el camp import a la modal de llocs).
 *
 * @return array<string, string> mapa special_specific_compensation_id (string) => import amb punt decimal
 */
function maintenance_job_positions_special_comp_amount_map(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT special_specific_compensation_id, amount FROM specific_compensation_special WHERE catalog_year = :y');
    $st->execute(['y' => $year]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
        $sid = (string) ($r['special_specific_compensation_id'] ?? '');
        if ($sid === '') {
            continue;
        }
        $a = $r['amount'] ?? null;
        if ($a === null || $a === '') {
            $out[$sid] = '';
        } else {
            $out[$sid] = is_numeric($a) ? (string) $a : trim((string) $a);
        }
    }

    return $out;
}

/**
 * Imports de complements específics generals per any (modal de llocs).
 *
 * @return array<string, string> mapa general_specific_compensation_id (string) => import amb punt decimal
 */
function maintenance_job_positions_general_comp_amount_map(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT general_specific_compensation_id, amount FROM specific_compensation_general WHERE catalog_year = :y');
    $st->execute(['y' => $year]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
        $sid = (string) ($r['general_specific_compensation_id'] ?? '');
        if ($sid === '') {
            continue;
        }
        $a = $r['amount'] ?? null;
        if ($a === null || $a === '') {
            $out[$sid] = '';
        } else {
            $out[$sid] = is_numeric($a) ? (string) $a : trim((string) $a);
        }
    }

    return $out;
}

/**
 * Imports de grups de sou (salary_base_by_group) per a la modal de llocs.
 *
 * @return array<string, string> classification_group (string) => import amb punt decimal
 */
function maintenance_job_positions_salary_group_amount_map(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT classification_group, base_salary FROM salary_base_by_group WHERE catalog_year = :y');
    $st->execute(['y' => $year]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
        $g = trim((string) ($r['classification_group'] ?? ''));
        if ($g === '') {
            continue;
        }
        $a = $r['base_salary'] ?? null;
        if ($a === null || $a === '') {
            $out[$g] = '';
        } else {
            $out[$g] = is_numeric($a) ? (string) $a : trim((string) $a);
        }
    }

    return $out;
}

/**
 * Imports de nivell orgànic (destination_allowances) per a la modal de llocs.
 *
 * @return array<string, string> organic_level (string) => import amb punt decimal
 */
function maintenance_job_positions_organic_level_amount_map(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT organic_level, destination_allowance, destination_allowance_new FROM destination_allowances WHERE catalog_year = :y');
    $st->execute(['y' => $year]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
        $olv = trim((string) ($r['organic_level'] ?? ''));
        if ($olv === '') {
            continue;
        }
        $pick = $r['destination_allowance_new'] ?? $r['destination_allowance'] ?? null;
        if ($pick === null || $pick === '') {
            $out[$olv] = '';
        } else {
            $out[$olv] = is_numeric($pick) ? (string) $pick : trim((string) $pick);
        }
    }

    return $out;
}

/**
 * Tipus de lloc (codi + denominació coneguda) per al selector de llocs.
 *
 * @return list<array{id:string,label:string}>
 */
function maintenance_job_position_type_options(PDO $db, int $year): array
{
    $labels = [
        'CM' => 'Comandament',
    ];
    $st = $db->prepare("SELECT DISTINCT TRIM(job_type_id) AS jid FROM job_positions WHERE catalog_year = :y AND deleted_at IS NULL AND job_type_id IS NOT NULL AND TRIM(job_type_id) <> ''");
    $st->execute(['y' => $year]);
    $seen = [];
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) ?: [] as $jid) {
        $jid = trim((string) $jid);
        if ($jid !== '') {
            $seen[$jid] = true;
        }
    }
    foreach (array_keys($labels) as $k) {
        $seen[$k] = true;
    }
    $ids = array_keys($seen);
    usort($ids, static fn (string $a, string $b): int => strcmp($a, $b));
    $out = [];
    foreach ($ids as $id) {
        $dn = $labels[$id] ?? '';
        $out[] = [
            'id' => $id,
            'label' => $dn !== '' ? ($id . ' — ' . $dn) : $id,
        ];
    }

    return $out;
}

/**
 * Opcions fixes de relació jurídica de llocs de treball.
 *
 * @return list<array{id:string,name:string,label:string}>
 */
function maintenance_job_position_legal_relation_options(): array
{
    $rows = [
        ['id' => 'E', 'name' => 'Eventual'],
        ['id' => 'F', 'name' => 'Funcionari/a'],
        ['id' => 'I', 'name' => 'Funcionari/a Interí/na per programa temporal'],
        ['id' => 'L', 'name' => 'Laboral'],
        ['id' => 'P', 'name' => 'Funcionari/a Pràctiques'],
        ['id' => 'T', 'name' => 'Laboral temporal'],
        ['id' => 'D', 'name' => 'Directiu'],
    ];
    foreach ($rows as &$r) {
        $r['label'] = $r['id'] . ' - ' . $r['name'];
    }
    unset($r);

    return $rows;
}

/**
 * Opcions de «Grau personal» per al mòdul people.
 * Detecta automàticament la taula real (destination_alloweed / destination_allowed / destination_allowances)
 * i adapta els noms de columna disponibles.
 *
 * @return list<array{id:string,name:string}>
 */
function maintenance_people_personal_grade_options(PDO $db, int $year): array
{
    $candidateTables = ['destination_alloweed', 'destination_allowed', 'destination_allowances'];
    $tableName = null;
    foreach ($candidateTables as $candidate) {
        $stTbl = $db->prepare('SELECT COUNT(*) AS c
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
        $stTbl->execute(['t' => $candidate]);
        if (((int) ($stTbl->fetchColumn() ?: 0)) > 0) {
            $tableName = $candidate;
            break;
        }
    }
    if ($tableName === null) {
        return [];
    }

    $stCols = $db->prepare('SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $stCols->execute(['t' => $tableName]);
    $colsRaw = $stCols->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $cols = array_map(static fn($c): string => (string) $c, $colsRaw);
    $hasCol = static fn(string $c): bool => in_array($c, $cols, true);

    $idCandidates = ['id', 'organic_level', 'destination_allowance_id', 'grade_id'];
    $nameCandidates = ['name', 'destination_allowance', 'description', 'label'];
    $idCol = null;
    foreach ($idCandidates as $c) {
        if ($hasCol($c)) {
            $idCol = $c;
            break;
        }
    }
    $nameCol = null;
    foreach ($nameCandidates as $c) {
        if ($hasCol($c)) {
            $nameCol = $c;
            break;
        }
    }
    if ($idCol === null || $nameCol === null) {
        return [];
    }

    $sql = 'SELECT CAST(' . $idCol . ' AS CHAR) AS id, CAST(' . $nameCol . ' AS CHAR) AS name
        FROM ' . $tableName;
    $params = [];
    if ($hasCol('catalog_year')) {
        $sql .= ' WHERE catalog_year = :y';
        $params['y'] = $year;
    }
    $sql .= ' ORDER BY ' . $idCol . ' ASC';

    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

/**
 * Imports de trienni per grup per als càlculs visuals de people.
 *
 * @return array<string,array{monthly_amount:float,extra_pay_amount:float}>
 */
function maintenance_people_seniority_pay_by_group(PDO $db, int $catalogYear): array
{
    $st = $db->prepare('SELECT classification_group, seniority_amount, seniority_extra_pay_amount
        FROM seniority_pay_by_group
        WHERE catalog_year = :y');
    $st->execute(['y' => $catalogYear]);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $row) {
        $key = strtoupper(trim((string) ($row['classification_group'] ?? '')));
        if ($key === '') {
            continue;
        }
        $out[$key] = [
            'monthly_amount' => (float) ($row['seniority_amount'] ?? 0),
            'extra_pay_amount' => (float) ($row['seniority_extra_pay_amount'] ?? 0),
        ];
    }
    return $out;
}

function maintenance_job_position_is_cm(PDO $db, int $year, string $jobPositionId): bool
{
    $st = $db->prepare("SELECT 1 FROM job_positions WHERE catalog_year = :y AND job_position_id = :jid AND job_type_id = 'CM' LIMIT 1");
    $st->execute(['y' => $year, 'jid' => $jobPositionId]);

    return (bool) $st->fetch();
}

/**
 * @return list<string>
 */
function get_salary_groups(PDO $db, int $catalogYear): array
{
    $st = $db->prepare('SELECT DISTINCT classification_group
        FROM salary_base_by_group
        WHERE catalog_year = :y
          AND classification_group IS NOT NULL
          AND TRIM(classification_group) <> \'\'
        ORDER BY classification_group ASC');
    $st->execute(['y' => $catalogYear]);
    return array_map(static fn ($v) => (string) $v, $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

/**
 * @param array<string,string> $raw
 * @return array<string,string>
 */
function management_positions_normalize_filters(array $raw): array
{
    $out = [];
    foreach (['f_position_id', 'f_position_name', 'f_position_class_id', 'f_scale_id', 'f_subscale_id', 'f_class_id', 'f_category_id', 'f_is_active'] as $k) {
        $out[$k] = trim((string) ($raw[$k] ?? ''));
    }
    if (!in_array($out['f_is_active'], ['', '1', '0'], true)) {
        $out['f_is_active'] = '';
    }
    return $out;
}

function maintenance_people_normalize_filters(array $raw): array
{
    $f = [
        'f_person_id' => '',
        'f_last_name_1' => '',
        'f_last_name_2' => '',
        'f_first_name' => '',
        'f_national_id_number' => '',
        'f_email' => '',
        'f_job_position_id' => '',
        'f_position_id' => '',
        'f_legal_relation_id' => '',
        'f_is_active' => '',
    ];
    foreach (array_keys($f) as $k) {
        $f[$k] = trim((string) ($raw[$k] ?? ''));
    }
    if (!in_array($f['f_is_active'], ['', '0', '1'], true)) {
        $f['f_is_active'] = '';
    }

    return $f;
}

function maintenance_people_filters_clause(array $filters): array
{
    $sql = '';
    $params = [];
    if ($filters['f_person_id'] !== '') {
        $sql .= ' AND (CAST(p.person_id AS CHAR) LIKE :ppl_f_person_id OR LPAD(CAST(p.person_id AS CHAR), 5, \'0\') LIKE :ppl_f_person_id_pad)';
        $params['ppl_f_person_id'] = '%' . $filters['f_person_id'] . '%';
        $params['ppl_f_person_id_pad'] = '%' . $filters['f_person_id'] . '%';
    }
    if ($filters['f_last_name_1'] !== '') {
        $sql .= ' AND p.last_name_1 LIKE :ppl_f_last_name_1';
        $params['ppl_f_last_name_1'] = '%' . $filters['f_last_name_1'] . '%';
    }
    if ($filters['f_last_name_2'] !== '') {
        $sql .= ' AND p.last_name_2 LIKE :ppl_f_last_name_2';
        $params['ppl_f_last_name_2'] = '%' . $filters['f_last_name_2'] . '%';
    }
    if ($filters['f_first_name'] !== '') {
        $sql .= ' AND p.first_name LIKE :ppl_f_first_name';
        $params['ppl_f_first_name'] = '%' . $filters['f_first_name'] . '%';
    }
    if ($filters['f_national_id_number'] !== '') {
        $sql .= ' AND p.national_id_number LIKE :ppl_f_nid';
        $params['ppl_f_nid'] = '%' . $filters['f_national_id_number'] . '%';
    }
    if ($filters['f_email'] !== '') {
        $sql .= ' AND p.email LIKE :ppl_f_email';
        $params['ppl_f_email'] = '%' . $filters['f_email'] . '%';
    }
    if ($filters['f_job_position_id'] !== '') {
        $sql .= ' AND p.job_position_id LIKE :ppl_f_job_position_id';
        $params['ppl_f_job_position_id'] = '%' . $filters['f_job_position_id'] . '%';
    }
    if ($filters['f_position_id'] !== '') {
        $sql .= ' AND (CAST(p.position_id AS CHAR) LIKE :ppl_f_position_id OR LPAD(CAST(p.position_id AS CHAR), 4, \'0\') LIKE :ppl_f_position_id_pad)';
        $params['ppl_f_position_id'] = '%' . $filters['f_position_id'] . '%';
        $params['ppl_f_position_id_pad'] = '%' . $filters['f_position_id'] . '%';
    }
    if ($filters['f_legal_relation_id'] !== '') {
        $sql .= ' AND CAST(p.legal_relation_id AS CHAR) = :ppl_f_legal_relation_id';
        $params['ppl_f_legal_relation_id'] = $filters['f_legal_relation_id'];
    }
    if ($filters['f_is_active'] !== '') {
        $sql .= ' AND p.is_active = :ppl_f_is_active';
        $params['ppl_f_is_active'] = (int) $filters['f_is_active'];
    }

    return ['sql' => $sql, 'params' => $params];
}

/**
 * @param array<string,string> $filters
 * @return array{sql:string,params:array<string,mixed>}
 */
function management_positions_filters_clause(array $filters): array
{
    $sql = '';
    $params = [];
    if ($filters['f_position_id'] !== '') {
        $sql .= ' AND (CAST(t.position_id AS CHAR) LIKE :mp_f_position_id OR LPAD(CAST(t.position_id AS CHAR), 4, \'0\') LIKE :mp_f_position_id_pad)';
        $params['mp_f_position_id'] = '%' . $filters['f_position_id'] . '%';
        $params['mp_f_position_id_pad'] = '%' . $filters['f_position_id'] . '%';
    }
    if ($filters['f_position_name'] !== '') {
        $sql .= ' AND t.position_name LIKE :mp_f_position_name';
        $params['mp_f_position_name'] = '%' . $filters['f_position_name'] . '%';
    }
    if ($filters['f_position_class_id'] !== '') {
        $sql .= ' AND CAST(t.position_class_id AS CHAR) = :mp_f_position_class_id';
        $params['mp_f_position_class_id'] = $filters['f_position_class_id'];
    }
    if ($filters['f_scale_id'] !== '') {
        $sql .= ' AND CAST(t.scale_id AS CHAR) = :mp_f_scale_id';
        $params['mp_f_scale_id'] = $filters['f_scale_id'];
    }
    if ($filters['f_subscale_id'] !== '') {
        $sql .= ' AND CAST(t.subscale_id AS CHAR) = :mp_f_subscale_id';
        $params['mp_f_subscale_id'] = $filters['f_subscale_id'];
    }
    if ($filters['f_class_id'] !== '') {
        $sql .= ' AND CAST(t.class_id AS CHAR) = :mp_f_class_id';
        $params['mp_f_class_id'] = $filters['f_class_id'];
    }
    if ($filters['f_category_id'] !== '') {
        $sql .= ' AND CAST(t.category_id AS CHAR) = :mp_f_category_id';
        $params['mp_f_category_id'] = $filters['f_category_id'];
    }
    if ($filters['f_is_active'] !== '') {
        $sql .= ' AND t.is_active = :mp_f_is_active';
        $params['mp_f_is_active'] = (int) $filters['f_is_active'];
    }
    return ['sql' => $sql, 'params' => $params];
}

/**
 * Mode d’agrupació de relació jurídica per a llocs de treball (regles D/E/I/P, F, L/T sobre els camps de funcionari/laboral).
 * Retorna: civil | labor | none
 */
function maintenance_job_position_legal_relation_mode(?string $legalRelationId): string
{
    if ($legalRelationId === null) {
        return 'none';
    }
    $id = strtoupper(trim($legalRelationId));
    if ($id === '') {
        return 'none';
    }
    if ($id === 'F' || $id === 'I' || $id === 'P') {
        return 'civil';
    }
    if ($id === 'L' || $id === 'T') {
        return 'labor';
    }

    return 'none';
}

/**
 * @return array<string, string> mapa legal_relation_id (string) => civil|labor|none
 */
function maintenance_job_position_legal_relation_modes_for_year(PDO $db, int $year): array
{
    $out = [];
    foreach (maintenance_job_position_legal_relation_options() as $it) {
        $rid = strtoupper(trim((string) ($it['id'] ?? '')));
        if ($rid === '') {
            continue;
        }
        $out[$rid] = maintenance_job_position_legal_relation_mode($rid);
    }

    return $out;
}

/**
 * @param array<string,string> $raw
 * @return array<string,string>
 */
function maintenance_job_positions_normalize_filters(array $raw): array
{
    $out = [];
    foreach (['f_job_code', 'f_job_title', 'f_org_dependency_id', 'f_is_active', 'f_scale_id', 'f_legal_relation_id', 'f_is_to_be_amortized'] as $k) {
        $out[$k] = trim((string) ($raw[$k] ?? ''));
    }
    if (!in_array($out['f_is_active'], ['', '1', '0'], true)) {
        $out['f_is_active'] = '';
    }
    if (!in_array($out['f_is_to_be_amortized'], ['', '1', '0'], true)) {
        $out['f_is_to_be_amortized'] = '';
    }

    return $out;
}

/**
 * @param array<string,string> $filters
 * @return array{sql:string,params:array<string,mixed>}
 */
function maintenance_job_positions_filters_clause(array $filters): array
{
    $sql = '';
    $params = [];
    if ($filters['f_job_code'] !== '') {
        $sql .= ' AND (t.job_position_id LIKE :jp_f_code OR t.catalog_code LIKE :jp_f_code2)';
        $v = '%' . $filters['f_job_code'] . '%';
        $params['jp_f_code'] = $v;
        $params['jp_f_code2'] = $v;
    }
    if ($filters['f_job_title'] !== '') {
        $sql .= ' AND t.job_title LIKE :jp_f_title';
        $params['jp_f_title'] = '%' . $filters['f_job_title'] . '%';
    }
    if ($filters['f_org_dependency_id'] !== '') {
        $term = '%' . $filters['f_org_dependency_id'] . '%';
        $sql .= ' AND (
            t.org_dependency_id LIKE :jp_f_dep
            OR EXISTS (
                SELECT 1 FROM job_positions jpf
                WHERE jpf.catalog_year = t.catalog_year
                  AND jpf.job_position_id = t.org_dependency_id
                  AND jpf.job_type_id = \'CM\'
                  AND jpf.deleted_at IS NULL
                  AND (
                    jpf.job_title LIKE :jp_f_dep_tit
                    OR jpf.catalog_code LIKE :jp_f_dep_cc
                    OR CAST(jpf.job_position_id AS CHAR) LIKE :jp_f_dep_jid
                  )
            )
        )';
        $params['jp_f_dep'] = $term;
        $params['jp_f_dep_tit'] = $term;
        $params['jp_f_dep_cc'] = $term;
        $params['jp_f_dep_jid'] = $term;
    }
    if ($filters['f_is_active'] !== '') {
        if ($filters['f_is_active'] === '1') {
            $sql .= ' AND t.deleted_at IS NULL';
        } else {
            $sql .= ' AND t.deleted_at IS NOT NULL';
        }
    }
    if ($filters['f_scale_id'] !== '') {
        $sql .= ' AND CAST(t.civil_service_scale_id AS CHAR) = :jp_f_scale';
        $params['jp_f_scale'] = $filters['f_scale_id'];
    }
    if ($filters['f_legal_relation_id'] !== '') {
        $sql .= ' AND CAST(t.legal_relation_id AS CHAR) = :jp_f_lr';
        $params['jp_f_lr'] = $filters['f_legal_relation_id'];
    }
    if ($filters['f_is_to_be_amortized'] !== '') {
        $sql .= ' AND t.is_to_be_amortized = :jp_f_amort';
        $params['jp_f_amort'] = (int) $filters['f_is_to_be_amortized'];
    }

    return ['sql' => $sql, 'params' => $params];
}

/**
 * @return list<array{person_id:int, label:string}>
 */
function maintenance_job_positions_people_picker_options(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT person_id, last_name_1, last_name_2, first_name FROM people WHERE catalog_year = :y ORDER BY last_name_1 ASC, last_name_2 ASC, first_name ASC, person_id ASC');
    $st->execute(['y' => $year]);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $pid = (int) ($r['person_id'] ?? 0);
        if ($pid < 1) {
            continue;
        }
        $name = trim((string) implode(' ', array_filter([
            trim((string) ($r['last_name_1'] ?? '')),
            trim((string) ($r['last_name_2'] ?? '')),
            trim((string) ($r['first_name'] ?? '')),
        ], static fn ($x) => $x !== '')));
        $out[] = [
            'person_id' => $pid,
            'label' => str_pad((string) $pid, 5, '0', STR_PAD_LEFT) . ($name !== '' ? ' — ' . $name : ''),
        ];
    }

    return $out;
}

/**
 * Percentatge visual (100 => 1.0000).
 *
 * @return array{ok:true,value:?string}|array{ok:false,error:string}
 */
function maintenance_parse_optional_visual_percent_to_fraction(string $raw): array
{
    $t = trim($raw);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    $t = str_replace(["\xC2\xA0", '%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if ($t === '' || preg_match('/[^0-9.]/', $t)) {
        return ['ok' => false, 'error' => 'Percentatge invàlid.'];
    }
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $t)) {
        return ['ok' => false, 'error' => 'Percentatge invàlid (màxim 2 decimals).'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'Percentatge invàlid.'];
    }
    return ['ok' => true, 'value' => number_format($pct / 100.0, 4, '.', '')];
}

/**
 * @return array{ok:true,value:?string}|array{ok:false,error:string}
 */
function maintenance_parse_optional_visual_percent_0_100(string $raw): array
{
    $t = trim($raw);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    $t = str_replace(["\xC2\xA0", '%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if ($t === '' || preg_match('/[^0-9.]/', $t)) {
        return ['ok' => false, 'error' => 'Percentatge invàlid.'];
    }
    if (!preg_match('/^\d+(?:\.\d{1,4})?$/', $t)) {
        return ['ok' => false, 'error' => 'Percentatge invàlid (màxim 4 decimals).'];
    }
    $n = (float) $t;
    if (!is_finite($n) || $n < 0.0 || $n > 100.0) {
        return ['ok' => false, 'error' => 'El percentatge ha d’estar entre 0 i 100.'];
    }

    return ['ok' => true, 'value' => number_format($n, 4, '.', '')];
}

/**
 * Percentatge visual 0..100 cap a fracció 0..1 (per people).
 * @return array{ok:true,value:?string}|array{ok:false,error:string}
 */
function maintenance_parse_optional_visual_percent_to_fraction_0_1(string $raw): array
{
    $parsed = maintenance_parse_optional_visual_percent_0_100($raw);
    if (!$parsed['ok']) {
        return $parsed;
    }
    if ($parsed['value'] === null) {
        return ['ok' => true, 'value' => null];
    }
    $pct = (float) $parsed['value'];
    if (!is_finite($pct) || $pct < 0.0 || $pct > 100.0) {
        return ['ok' => false, 'error' => 'El percentatge ha d’estar entre 0 i 100.'];
    }

    return ['ok' => true, 'value' => number_format($pct / 100.0, 6, '.', '')];
}

function maintenance_count(PDO $db, string $module, int $year, string $q, array $filters = []): int
{
    $search = maintenance_list_q_search_clause($module, $q);
    $params = ['y' => $year] + $search['params'];
    if ($module === 'maintenance_scales') {
        $sql = 'SELECT COUNT(*) AS c FROM civil_service_scales t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_subscales') {
        $sql = 'SELECT COUNT(*) AS c FROM civil_service_subscales t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_classes') {
        $sql = 'SELECT COUNT(*) AS c FROM civil_service_classes t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                INNER JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.subscale_id=t.subscale_id AND ss.scale_id=t.scale_id
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_categories') {
        $sql = 'SELECT COUNT(*) AS c FROM civil_service_categories t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                INNER JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.subscale_id=t.subscale_id AND ss.scale_id=t.scale_id
                INNER JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.class_id=t.class_id AND c.scale_id=t.scale_id AND c.subscale_id=t.subscale_id
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_legal_relationships') {
        $sql = 'SELECT COUNT(*) AS c FROM legal_relations t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_administrative_statuses') {
        $sql = 'SELECT COUNT(*) AS c FROM administrative_statuses t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_position_classes') {
        $sql = 'SELECT COUNT(*) AS c FROM position_classes t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_access_types') {
        $sql = 'SELECT COUNT(*) AS c FROM access_types t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_access_systems') {
        $sql = 'SELECT COUNT(*) AS c FROM access_systems t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_work_centers') {
        $sql = 'SELECT COUNT(*) AS c FROM work_centers t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_availability_types') {
        $sql = 'SELECT COUNT(*) AS c FROM availability_options t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_provision_forms') {
        $sql = 'SELECT COUNT(*) AS c FROM provision_methods t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_organic_level_1') {
        $sql = 'SELECT COUNT(*) AS c FROM org_units_level_1 t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_organic_level_2') {
        $sql = 'SELECT COUNT(*) AS c FROM org_units_level_2 t
                INNER JOIN org_units_level_1 o1 ON o1.catalog_year = t.catalog_year AND o1.org_unit_level_1_id = t.org_unit_level_1_id
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_organic_level_3') {
        $sql = 'SELECT COUNT(*) AS c FROM org_units_level_3 t
                INNER JOIN org_units_level_2 o2 ON o2.catalog_year = t.catalog_year AND o2.org_unit_level_2_id = t.org_unit_level_2_id
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_programs') {
        $sql = 'SELECT COUNT(*) AS c FROM programs t
                LEFT JOIN job_positions jp ON jp.catalog_year = t.catalog_year AND jp.job_position_id = t.responsible_person_code AND jp.deleted_at IS NULL
                WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_social_security_companies') {
        $sql = 'SELECT COUNT(*) AS c FROM social_security_companies t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_social_security_coefficients') {
        $sql = 'SELECT COUNT(*) AS c FROM social_security_coefficients t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_social_security_base_limits') {
        $sql = 'SELECT COUNT(*) AS c FROM social_security_base_limits t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_salary_base_by_group') {
        $sql = 'SELECT COUNT(*) AS c FROM salary_base_by_group t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_destination_allowances') {
        $sql = 'SELECT COUNT(*) AS c FROM destination_allowances t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_seniority_pay_by_group') {
        $sql = 'SELECT COUNT(*) AS c FROM seniority_pay_by_group t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_specific_compensation_special_prices') {
        $sql = 'SELECT COUNT(*) AS c FROM specific_compensation_special t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_specific_compensation_general') {
        $sql = 'SELECT COUNT(*) AS c FROM specific_compensation_general t WHERE t.catalog_year = :y' . $search['sql'];
    } elseif ($module === 'maintenance_personal_transitory_bonus') {
        $sql = 'SELECT COUNT(*) AS c FROM people p
                WHERE p.catalog_year = :y AND p.is_active = 1 AND p.personal_transitory_bonus <> 0' . $search['sql'];
    } elseif ($module === 'job_positions') {
        $f = maintenance_job_positions_filters_clause(maintenance_job_positions_normalize_filters($filters));
        $params += $f['params'];
        $sql = 'SELECT COUNT(*) AS c FROM job_positions t
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.civil_service_scale_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=t.catalog_year AND lr.legal_relation_id=t.legal_relation_id
                LEFT JOIN job_positions jp_dep ON jp_dep.catalog_year = t.catalog_year AND jp_dep.job_position_id = t.org_dependency_id AND jp_dep.job_type_id = \'CM\' AND jp_dep.deleted_at IS NULL
                WHERE t.catalog_year = :y' . $search['sql'] . $f['sql'];
    } elseif ($module === 'people') {
        $f = maintenance_people_filters_clause(maintenance_people_normalize_filters($filters));
        $params += $f['params'];
        $sql = 'SELECT COUNT(*) AS c FROM people p
                LEFT JOIN job_positions jp ON jp.catalog_year=p.catalog_year AND jp.job_position_id=p.job_position_id
                LEFT JOIN positions pos ON pos.catalog_year=p.catalog_year AND pos.position_id=p.position_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=p.catalog_year AND lr.legal_relation_id=p.legal_relation_id
                WHERE p.catalog_year = :y' . $search['sql'] . $f['sql'];
    } elseif ($module === 'management_positions') {
        $f = management_positions_filters_clause(management_positions_normalize_filters($filters));
        $params += $f['params'];
        $sql = 'SELECT COUNT(*) AS c FROM positions t
                LEFT JOIN position_classes pc ON pc.catalog_year=t.catalog_year AND pc.position_class_id=t.position_class_id
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                LEFT JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.scale_id=t.scale_id AND ss.subscale_id=t.subscale_id
                LEFT JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.scale_id=t.scale_id AND c.subscale_id=t.subscale_id AND c.class_id=t.class_id
                LEFT JOIN civil_service_categories cat ON cat.catalog_year=t.catalog_year AND cat.scale_id=t.scale_id AND cat.subscale_id=t.subscale_id AND cat.class_id=t.class_id AND cat.category_id=t.category_id
                WHERE t.catalog_year = :y' . $search['sql'] . $f['sql'];
    } elseif ($module === 'maintenance_subprograms') {
        $sql = 'SELECT COUNT(*) AS c FROM subprograms t
                INNER JOIN programs p ON p.catalog_year = t.catalog_year AND p.program_id = t.program_id
                LEFT JOIN job_positions jp_t ON jp_t.catalog_year = t.catalog_year AND jp_t.job_position_id = t.technical_manager_code
                LEFT JOIN job_positions jp_e ON jp_e.catalog_year = t.catalog_year AND jp_e.job_position_id = t.elected_manager_code
                WHERE t.catalog_year = :y' . $search['sql'];
    } else {
        return 0;
    }
    $st = $db->prepare($sql);
    $st->execute($params);
    return (int) (($st->fetch())['c'] ?? 0);
}

function maintenance_list(PDO $db, string $module, int $year, string $q, string $sortBy, string $sortDir, int $limit, int $offset, array $filters = []): array
{
    $n = maintenance_sort_normalize($module, $sortBy, $sortDir);
    $sortBy = $n['by'];
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    $params = ['y' => $year];
    $q = trim($q);

    if ($module === 'maintenance_scales') {
        $order = match ($sortBy) {
            'scale_name' => 't.scale_name ' . $dir,
            'scale_short_name' => 't.scale_short_name ' . $dir,
            'scale_full_name' => 't.scale_full_name ' . $dir,
            default => 't.scale_id ' . $dir,
        };
        $sql = 'SELECT t.scale_id, t.scale_name, t.scale_short_name, t.scale_full_name FROM civil_service_scales t WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.scale_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_subscales') {
        $order = match ($sortBy) {
            'subscale_name' => 't.subscale_name ' . $dir,
            'subscale_short_name' => 't.subscale_short_name ' . $dir,
            'scale_name' => 's.scale_name ' . $dir,
            'scale_id' => 't.scale_id ' . $dir,
            default => 't.subscale_id ' . $dir,
        };
        $sql = 'SELECT t.subscale_id, t.scale_id, t.subscale_name, t.subscale_short_name, s.scale_name
                FROM civil_service_subscales t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.subscale_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_classes') {
        $order = match ($sortBy) {
            'class_short_name' => 't.class_short_name ' . $dir,
            'class_name' => 't.class_name ' . $dir,
            'scale_name' => 's.scale_name ' . $dir,
            'scale_id' => 't.scale_id ' . $dir,
            'subscale_name' => 'ss.subscale_name ' . $dir,
            'subscale_id' => 't.subscale_id ' . $dir,
            default => 't.class_id ' . $dir,
        };
        $sql = 'SELECT t.class_id, t.scale_id, t.subscale_id, t.class_name, t.class_short_name,
                       s.scale_name, ss.subscale_name
                FROM civil_service_classes t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                INNER JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.subscale_id=t.subscale_id AND ss.scale_id=t.scale_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.class_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_categories') {
        $order = match ($sortBy) {
            'category_name' => 't.category_name ' . $dir,
            'category_short_name' => 't.category_short_name ' . $dir,
            'scale_name' => 's.scale_name ' . $dir,
            'scale_id' => 't.scale_id ' . $dir,
            'subscale_name' => 'ss.subscale_name ' . $dir,
            'subscale_id' => 't.subscale_id ' . $dir,
            'class_name' => 'c.class_name ' . $dir,
            'class_id' => 't.class_id ' . $dir,
            default => 't.category_id ' . $dir,
        };
        $sql = 'SELECT t.category_id, t.scale_id, t.subscale_id, t.class_id, t.category_name, t.category_short_name,
                       s.scale_name, ss.subscale_name, c.class_name
                FROM civil_service_categories t
                INNER JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                INNER JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.subscale_id=t.subscale_id AND ss.scale_id=t.scale_id
                INNER JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.class_id=t.class_id AND c.scale_id=t.scale_id AND c.subscale_id=t.subscale_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.category_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_administrative_statuses') {
        $order = match ($sortBy) {
            'administrative_status_name' => 't.administrative_status_name ' . $dir,
            default => 't.administrative_status_id ' . $dir,
        };
        $sql = 'SELECT t.administrative_status_id, t.administrative_status_name
                FROM administrative_statuses t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.administrative_status_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_position_classes') {
        $order = match ($sortBy) {
            'position_class_name' => 't.position_class_name ' . $dir,
            'position_class_id' => 't.position_class_id ' . $dir,
            default => 't.position_class_id ' . $dir,
        };
        $sql = 'SELECT t.position_class_id, t.position_class_name
                FROM position_classes t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.position_class_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_legal_relationships') {
        $order = match ($sortBy) {
            'legal_relation_name' => 't.legal_relation_name ' . $dir,
            'legal_relation_id' => 't.legal_relation_id ' . $dir,
            default => 't.legal_relation_id ' . $dir,
        };
        $sql = 'SELECT t.legal_relation_id, t.legal_relation_name
                FROM legal_relations t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.legal_relation_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_access_types') {
        $order = match ($sortBy) {
            'access_type_name' => 't.access_type_name ' . $dir,
            default => 't.access_type_id ' . $dir,
        };
        $sql = 'SELECT t.access_type_id, t.access_type_name
                FROM access_types t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.access_type_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_access_systems') {
        $order = match ($sortBy) {
            'access_system_name' => 't.access_system_name ' . $dir,
            default => 't.access_system_id ' . $dir,
        };
        $sql = 'SELECT t.access_system_id, t.access_system_name
                FROM access_systems t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.access_system_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_work_centers') {
        $order = match ($sortBy) {
            'city' => 't.city ' . $dir,
            'work_center_name' => 't.work_center_name ' . $dir,
            default => 't.work_center_id ' . $dir,
        };
        $sql = 'SELECT t.work_center_id, t.work_center_name, t.address, t.postal_code, t.city, t.phone, t.fax
                FROM work_centers t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.work_center_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_availability_types') {
        $order = match ($sortBy) {
            'availability_id' => 't.availability_id ' . $dir,
            'availability_name' => 't.availability_name ' . $dir,
            default => 't.sort_order ' . $dir,
        };
        $sql = 'SELECT t.availability_id, t.availability_name, t.sort_order
                FROM availability_options t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.availability_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_provision_forms') {
        $order = match ($sortBy) {
            'provision_method_id' => 't.provision_method_id ' . $dir,
            'provision_method_name' => 't.provision_method_name ' . $dir,
            default => 't.sort_order ' . $dir,
        };
        $sql = 'SELECT t.provision_method_id, t.provision_method_name, t.sort_order
                FROM provision_methods t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.provision_method_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_organic_level_1') {
        $order = match ($sortBy) {
            'org_unit_level_1_name' => 't.org_unit_level_1_name ' . $dir,
            default => 't.org_unit_level_1_id ' . $dir,
        };
        $sql = 'SELECT t.org_unit_level_1_id, t.org_unit_level_1_name
                FROM org_units_level_1 t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.org_unit_level_1_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_organic_level_2') {
        $order = match ($sortBy) {
            'org_unit_level_2_name' => 't.org_unit_level_2_name ' . $dir,
            'org_unit_level_2_id' => 'CAST(t.org_unit_level_2_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_2_id ' . $dir,
            'org_unit_level_1_id' => 'CAST(t.org_unit_level_1_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_1_id ' . $dir,
            'org_unit_level_1_name' => 'o1.org_unit_level_1_name ' . $dir,
            default => 'CAST(t.org_unit_level_2_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_2_id ' . $dir,
        };
        $sql = 'SELECT t.org_unit_level_2_id, t.org_unit_level_2_name, t.org_unit_level_1_id, o1.org_unit_level_1_name
                FROM org_units_level_2 t
                INNER JOIN org_units_level_1 o1 ON o1.catalog_year = t.catalog_year AND o1.org_unit_level_1_id = t.org_unit_level_1_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', CAST(t.org_unit_level_2_id AS UNSIGNED) ASC, t.org_unit_level_2_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_organic_level_3') {
        $order = match ($sortBy) {
            'org_unit_level_3_name' => 't.org_unit_level_3_name ' . $dir,
            'org_unit_level_3_id' => 'CAST(t.org_unit_level_3_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_3_id ' . $dir,
            'org_unit_level_2_id' => 'CAST(t.org_unit_level_2_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_2_id ' . $dir,
            'org_unit_level_2_name' => 'o2.org_unit_level_2_name ' . $dir,
            default => 'CAST(t.org_unit_level_3_id AS UNSIGNED) ' . $dir . ', t.org_unit_level_3_id ' . $dir,
        };
        $sql = 'SELECT t.org_unit_level_3_id, t.org_unit_level_3_name, t.org_unit_level_2_id, o2.org_unit_level_2_name
                FROM org_units_level_3 t
                INNER JOIN org_units_level_2 o2 ON o2.catalog_year = t.catalog_year AND o2.org_unit_level_2_id = t.org_unit_level_2_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', CAST(t.org_unit_level_3_id AS UNSIGNED) ASC, t.org_unit_level_3_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_programs') {
        $tieBreak = ', LPAD(TRIM(t.subfunction_id), 3, \'0\') ASC, t.program_number ASC, t.program_id ASC';
        $order = match ($sortBy) {
            'subfunction_id' => 'LPAD(TRIM(t.subfunction_id), 3, \'0\') ' . $dir . ', t.program_number ASC, t.program_id ASC',
            'program_number' => 't.program_number ' . $dir . ', LPAD(TRIM(t.subfunction_id), 3, \'0\') ASC, t.program_id ASC',
            'program_code' => 't.program_id ' . $dir,
            'program_name' => 't.program_name ' . $dir . $tieBreak,
            'responsible_person_code' => 't.responsible_person_code ' . $dir . $tieBreak,
            'responsible_job_title' => 'jp.job_title ' . $dir . $tieBreak,
            default => 'LPAD(TRIM(t.subfunction_id), 3, \'0\') ASC, t.program_number ASC, t.program_id ASC',
        };
        $sql = 'SELECT t.program_id, t.subfunction_id, t.program_number,
                t.program_name, t.responsible_person_code, t.description,
                jp.job_title AS responsible_job_title
                FROM programs t
                LEFT JOIN job_positions jp ON jp.catalog_year = t.catalog_year AND jp.job_position_id = t.responsible_person_code AND jp.deleted_at IS NULL
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ' ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_subprograms') {
        $tieBreak = ', LPAD(TRIM(t.subprogram_number), 2, \'0\') ASC, t.subprogram_id ASC';
        $order = match ($sortBy) {
            'subprogram_program_id' => 't.program_id ' . $dir . $tieBreak,
            'subprogram_program_name' => 'p.program_name ' . $dir . ', t.program_id ASC' . $tieBreak,
            'subprogram_number' => 'LPAD(TRIM(t.subprogram_number), 2, \'0\') ' . $dir . ', t.program_id ASC, t.subprogram_id ASC',
            'subprogram_code' => 't.subprogram_id ' . $dir,
            'subprogram_name' => 't.subprogram_name ' . $dir . ', t.program_id ASC' . $tieBreak,
            'technical_manager_code' => 't.technical_manager_code ' . $dir . $tieBreak,
            'technical_job_title' => 'jp_t.job_title ' . $dir . ', t.program_id ASC' . $tieBreak,
            'is_mandatory_service' => 't.is_mandatory_service ' . $dir . $tieBreak,
            'has_corporate_agreements' => 't.has_corporate_agreements ' . $dir . $tieBreak,
            default => 't.program_id ASC' . $tieBreak,
        };
        $sql = 'SELECT t.subprogram_id, t.program_id, t.subprogram_number, t.subprogram_name,
                t.technical_manager_code, t.elected_manager_code, t.is_mandatory_service, t.has_corporate_agreements, t.nature,
                p.program_name,
                jp_t.job_title AS technical_job_title, jp_e.job_title AS elected_job_title
                FROM subprograms t
                INNER JOIN programs p ON p.catalog_year = t.catalog_year AND p.program_id = t.program_id
                LEFT JOIN job_positions jp_t ON jp_t.catalog_year = t.catalog_year AND jp_t.job_position_id = t.technical_manager_code
                LEFT JOIN job_positions jp_e ON jp_e.catalog_year = t.catalog_year AND jp_e.job_position_id = t.elected_manager_code
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ' ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_social_security_companies') {
        $order = match ($sortBy) {
            'company_description' => 't.company_description ' . $dir,
            'contribution_account_code' => 'REPLACE(t.contribution_account_code, \' \', \'\') ' . $dir,
            default => 't.company_id ' . $dir,
        };
        $sql = 'SELECT t.company_id, t.company_description, t.contribution_account_code
                FROM social_security_companies t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.company_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_social_security_coefficients') {
        $order = match ($sortBy) {
            'company_1' => 't.company_1 ' . $dir,
            'company_2' => 't.company_2 ' . $dir,
            'company_3' => 't.company_3 ' . $dir,
            'company_4' => 't.company_4 ' . $dir,
            'company_5a' => 't.company_5a ' . $dir,
            'company_5b' => 't.company_5b ' . $dir,
            'company_5c' => 't.company_5c ' . $dir,
            'company_5d' => 't.company_5d ' . $dir,
            'company_5e' => 't.company_5e ' . $dir,
            'temporary_employment_company' => 't.temporary_employment_company ' . $dir,
            default => 't.contribution_epigraph_id ' . $dir,
        };
        $sql = 'SELECT t.contribution_epigraph_id, t.company_1, t.company_2, t.company_3, t.company_4, t.company_5a, t.company_5b, t.company_5c, t.company_5d, t.company_5e, t.temporary_employment_company
                FROM social_security_coefficients t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.contribution_epigraph_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_social_security_base_limits') {
        $order = match ($sortBy) {
            'contribution_group_description' => 't.contribution_group_description ' . $dir,
            'minimum_base' => 't.minimum_base ' . $dir,
            'maximum_base' => 't.maximum_base ' . $dir,
            'period_label' => 't.period_label ' . $dir,
            default => 't.contribution_group_id ' . $dir,
        };
        $sql = 'SELECT t.contribution_group_id, t.contribution_group_description, t.minimum_base, t.maximum_base, t.period_label
                FROM social_security_base_limits t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.contribution_group_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_salary_base_by_group') {
        $order = match ($sortBy) {
            'base_salary' => 't.base_salary ' . $dir,
            'base_salary_extra_pay' => 't.base_salary_extra_pay ' . $dir,
            'base_salary_new' => 't.base_salary_new ' . $dir,
            'base_salary_extra_pay_new' => 't.base_salary_extra_pay_new ' . $dir,
            default => 't.classification_group ' . $dir,
        };
        $sql = 'SELECT t.classification_group, t.base_salary, t.base_salary_extra_pay, t.base_salary_new, t.base_salary_extra_pay_new
                FROM salary_base_by_group t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.classification_group ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_destination_allowances') {
        $order = match ($sortBy) {
            'destination_allowance' => 't.destination_allowance ' . $dir,
            'destination_allowance_new' => 't.destination_allowance_new ' . $dir,
            default => 't.organic_level ' . $dir,
        };
        $sql = 'SELECT t.organic_level, t.destination_allowance, t.destination_allowance_new
                FROM destination_allowances t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.organic_level ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_seniority_pay_by_group') {
        $order = match ($sortBy) {
            'seniority_amount' => 't.seniority_amount ' . $dir,
            'seniority_extra_pay_amount' => 't.seniority_extra_pay_amount ' . $dir,
            'seniority_amount_new' => 't.seniority_amount_new ' . $dir,
            'seniority_extra_pay_amount_new' => 't.seniority_extra_pay_amount_new ' . $dir,
            default => 't.classification_group ' . $dir,
        };
        $sql = 'SELECT t.classification_group, t.seniority_amount, t.seniority_extra_pay_amount, t.seniority_amount_new, t.seniority_extra_pay_amount_new
                FROM seniority_pay_by_group t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.classification_group ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_specific_compensation_special_prices') {
        $order = match ($sortBy) {
            'special_specific_compensation_name' => 't.special_specific_compensation_name ' . $dir,
            'amount' => 't.amount ' . $dir,
            'amount_new' => 't.amount_new ' . $dir,
            default => 't.special_specific_compensation_id ' . $dir,
        };
        $sql = 'SELECT t.special_specific_compensation_id, t.special_specific_compensation_name, t.amount, t.amount_new
                FROM specific_compensation_special t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.special_specific_compensation_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_specific_compensation_general') {
        $order = match ($sortBy) {
            'general_specific_compensation_name' => 't.general_specific_compensation_name ' . $dir,
            'amount' => 't.amount ' . $dir,
            'decrease_amount' => 't.decrease_amount ' . $dir,
            'amount_new' => 't.amount_new ' . $dir,
            'decrease_amount_new' => 't.decrease_amount_new ' . $dir,
            default => 't.general_specific_compensation_id ' . $dir,
        };
        $sql = 'SELECT t.general_specific_compensation_id, t.general_specific_compensation_name, t.amount, t.decrease_amount, t.amount_new, t.decrease_amount_new
                FROM specific_compensation_general t
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.general_specific_compensation_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'maintenance_personal_transitory_bonus') {
        $order = match ($sortBy) {
            'last_name_2' => 'p.last_name_2 ' . $dir,
            'first_name' => 'p.first_name ' . $dir,
            'personal_transitory_bonus' => 'p.personal_transitory_bonus ' . $dir,
            'personal_transitory_bonus_new' => 'p.personal_transitory_bonus_new ' . $dir,
            default => 'p.last_name_1 ' . $dir,
        };
        $sql = 'SELECT p.person_id,
                TRIM(CONCAT_WS(\', \',
                    NULLIF(TRIM(CONCAT_WS(\' \', NULLIF(TRIM(p.last_name_1), \'\'), NULLIF(TRIM(p.last_name_2), \'\'))), \'\'),
                    NULLIF(TRIM(p.first_name), \'\')
                )) AS person_display_name,
                p.personal_transitory_bonus, p.personal_transitory_bonus_new
                FROM people p
                WHERE p.catalog_year = :y AND p.is_active = 1 AND p.personal_transitory_bonus <> 0';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $sql .= ' ORDER BY ' . $order . ', p.last_name_2 ASC, p.first_name ASC, p.person_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'people') {
        $order = match ($sortBy) {
            'last_name_1' => 'p.last_name_1 ' . $dir,
            'last_name_2' => 'p.last_name_2 ' . $dir,
            'first_name' => 'p.first_name ' . $dir,
            'national_id_number' => 'p.national_id_number ' . $dir,
            'email' => 'p.email ' . $dir,
            'job_position_id' => 'p.job_position_id ' . $dir,
            'position_id' => 'p.position_id ' . $dir,
            'legal_relation_name' => 'lr.legal_relation_name ' . $dir,
            'is_active' => 'p.is_active ' . $dir,
            default => 'p.person_id ' . $dir,
        };
        $sql = 'SELECT p.*,
                jp.job_title AS job_position_name,
                pos.position_name,
                lr.legal_relation_name
                FROM people p
                LEFT JOIN job_positions jp ON jp.catalog_year=p.catalog_year AND jp.job_position_id=p.job_position_id
                LEFT JOIN positions pos ON pos.catalog_year=p.catalog_year AND pos.position_id=p.position_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=p.catalog_year AND lr.legal_relation_id=p.legal_relation_id
                WHERE p.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $f = maintenance_people_filters_clause(maintenance_people_normalize_filters($filters));
        $params += $f['params'];
        $sql .= $f['sql'];
        $sql .= ' ORDER BY ' . $order . ', p.person_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'management_positions') {
        $order = match ($sortBy) {
            'position_name' => 't.position_name ' . $dir,
            'position_class_name' => 'pc.position_class_name ' . $dir,
            'scale_name' => 's.scale_name ' . $dir,
            'subscale_name' => 'ss.subscale_name ' . $dir,
            'class_name' => 'c.class_name ' . $dir,
            'category_name' => 'cat.category_name ' . $dir,
            'is_active' => 't.is_active ' . $dir,
            default => 't.position_id ' . $dir,
        };
        $sql = 'SELECT t.position_id, t.position_name, t.position_class_id, t.scale_id, t.subscale_id, t.class_id, t.category_id,
                t.labor_category, t.classification_group, t.access_type_id, t.access_system_id, t.budgeted_amount, t.is_offerable,
                t.opo_year, t.is_to_be_amortized, t.is_internal_promotion, t.created_at, t.creation_file_reference,
                t.call_for_applications_date, t.deleted_at, t.deletion_file_reference, t.notes, t.is_active,
                pc.position_class_name, s.scale_name, ss.subscale_name, c.class_name, cat.category_name
                FROM positions t
                LEFT JOIN position_classes pc ON pc.catalog_year=t.catalog_year AND pc.position_class_id=t.position_class_id
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                LEFT JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.scale_id=t.scale_id AND ss.subscale_id=t.subscale_id
                LEFT JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.scale_id=t.scale_id AND c.subscale_id=t.subscale_id AND c.class_id=t.class_id
                LEFT JOIN civil_service_categories cat ON cat.catalog_year=t.catalog_year AND cat.scale_id=t.scale_id AND cat.subscale_id=t.subscale_id AND cat.class_id=t.class_id AND cat.category_id=t.category_id
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $f = management_positions_filters_clause(management_positions_normalize_filters($filters));
        $params += $f['params'];
        $sql .= $f['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.position_id ASC ' . db_sql_limit_offset($limit, $offset);
    } elseif ($module === 'job_positions') {
        $order = match ($sortBy) {
            'job_title' => 't.job_title ' . $dir,
            'org_dependency_id' => 'COALESCE(jp_dep.job_title, \'\') ' . $dir . ', jp_dep.job_position_id ASC',
            'scale_name' => 's.scale_name ' . $dir,
            'legal_relation_name' => 'lr.legal_relation_name ' . $dir,
            'is_active' => '(t.deleted_at IS NULL) ' . $dir,
            'is_to_be_amortized' => 't.is_to_be_amortized ' . $dir,
            default => 't.job_position_id ' . $dir,
        };
        $sql = 'SELECT t.job_position_id, t.job_title, t.org_dependency_id, t.deleted_at, t.is_to_be_amortized,
                s.scale_name, lr.legal_relation_name,
                CASE WHEN t.deleted_at IS NULL THEN 1 ELSE 0 END AS is_active,
                TRIM(CAST(jp_dep.job_position_id AS CHAR)) AS responsible_job_code_raw,
                TRIM(COALESCE(jp_dep.job_title, \'\')) AS responsible_job_title
                FROM job_positions t
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.civil_service_scale_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=t.catalog_year AND lr.legal_relation_id=t.legal_relation_id
                LEFT JOIN job_positions jp_dep ON jp_dep.catalog_year = t.catalog_year AND jp_dep.job_position_id = t.org_dependency_id AND jp_dep.job_type_id = \'CM\' AND jp_dep.deleted_at IS NULL
                WHERE t.catalog_year = :y';
        $search = maintenance_list_q_search_clause($module, $q);
        $params += $search['params'];
        $sql .= $search['sql'];
        $f = maintenance_job_positions_filters_clause(maintenance_job_positions_normalize_filters($filters));
        $params += $f['params'];
        $sql .= $f['sql'];
        $sql .= ' ORDER BY ' . $order . ', t.job_position_id ASC ' . db_sql_limit_offset($limit, $offset);
    } else {
        return [];
    }
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
}

function maintenance_get_by_id(PDO $db, string $module, int $year, string $id): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }
    if ($module === 'maintenance_specific_compensation_special_prices' && !preg_match('/^\d+$/', $id)) {
        return null;
    }
    if ($module === 'maintenance_scales') {
        $sql = 'SELECT * FROM civil_service_scales WHERE catalog_year = :y AND scale_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_subscales') {
        $sql = 'SELECT * FROM civil_service_subscales WHERE catalog_year = :y AND subscale_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_classes') {
        $sql = 'SELECT * FROM civil_service_classes WHERE catalog_year = :y AND class_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_categories') {
        $sql = 'SELECT * FROM civil_service_categories WHERE catalog_year = :y AND category_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_legal_relationships') {
        $sql = 'SELECT * FROM legal_relations WHERE catalog_year = :y AND legal_relation_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_administrative_statuses') {
        $sql = 'SELECT * FROM administrative_statuses WHERE catalog_year = :y AND administrative_status_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_position_classes') {
        $sql = 'SELECT * FROM position_classes WHERE catalog_year = :y AND position_class_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_access_types') {
        $sql = 'SELECT * FROM access_types WHERE catalog_year = :y AND access_type_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_access_systems') {
        $sql = 'SELECT * FROM access_systems WHERE catalog_year = :y AND access_system_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_work_centers') {
        $sql = 'SELECT * FROM work_centers WHERE catalog_year = :y AND work_center_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_availability_types') {
        $sql = 'SELECT * FROM availability_options WHERE catalog_year = :y AND availability_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_provision_forms') {
        $sql = 'SELECT * FROM provision_methods WHERE catalog_year = :y AND provision_method_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_organic_level_1') {
        $sql = 'SELECT * FROM org_units_level_1 WHERE catalog_year = :y AND org_unit_level_1_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_organic_level_2') {
        $sql = 'SELECT * FROM org_units_level_2 WHERE catalog_year = :y AND org_unit_level_2_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_organic_level_3') {
        $sql = 'SELECT * FROM org_units_level_3 WHERE catalog_year = :y AND org_unit_level_3_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_programs') {
        $sql = 'SELECT t.*, jp.job_title AS responsible_job_title, t.program_id AS program_display_code
                FROM programs t
                LEFT JOIN job_positions jp ON jp.catalog_year = t.catalog_year AND jp.job_position_id = t.responsible_person_code AND jp.deleted_at IS NULL
                WHERE t.catalog_year = :y AND t.program_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_social_security_companies') {
        $sql = 'SELECT * FROM social_security_companies WHERE catalog_year = :y AND company_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_social_security_coefficients') {
        $sql = 'SELECT * FROM social_security_coefficients WHERE catalog_year = :y AND contribution_epigraph_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_social_security_base_limits') {
        $sql = 'SELECT * FROM social_security_base_limits WHERE catalog_year = :y AND contribution_group_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_salary_base_by_group') {
        $sql = 'SELECT * FROM salary_base_by_group WHERE catalog_year = :y AND classification_group = :id LIMIT 1';
    } elseif ($module === 'maintenance_destination_allowances') {
        $sql = 'SELECT * FROM destination_allowances WHERE catalog_year = :y AND organic_level = :id LIMIT 1';
    } elseif ($module === 'maintenance_seniority_pay_by_group') {
        $sql = 'SELECT * FROM seniority_pay_by_group WHERE catalog_year = :y AND classification_group = :id LIMIT 1';
    } elseif ($module === 'maintenance_specific_compensation_special_prices') {
        $sql = 'SELECT * FROM specific_compensation_special WHERE catalog_year = :y AND special_specific_compensation_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_specific_compensation_general') {
        $sql = 'SELECT * FROM specific_compensation_general WHERE catalog_year = :y AND general_specific_compensation_id = :id LIMIT 1';
    } elseif ($module === 'people') {
        $sql = 'SELECT p.*,
                jp.job_title AS job_position_name,
                pos.position_name,
                lr.legal_relation_name,
                ast.administrative_status_name
                FROM people p
                LEFT JOIN job_positions jp ON jp.catalog_year=p.catalog_year AND jp.job_position_id=p.job_position_id
                LEFT JOIN positions pos ON pos.catalog_year=p.catalog_year AND pos.position_id=p.position_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=p.catalog_year AND lr.legal_relation_id=p.legal_relation_id
                LEFT JOIN administrative_statuses ast ON ast.catalog_year=p.catalog_year AND ast.administrative_status_id=p.administrative_status_id
                WHERE p.catalog_year = :y AND p.person_id = :id LIMIT 1';
    } elseif ($module === 'maintenance_subprograms') {
        $sql = 'SELECT t.*, p.program_name,
                jp_t.job_title AS technical_job_title, jp_e.job_title AS elected_job_title
                FROM subprograms t
                INNER JOIN programs p ON p.catalog_year = t.catalog_year AND p.program_id = t.program_id
                LEFT JOIN job_positions jp_t ON jp_t.catalog_year = t.catalog_year AND jp_t.job_position_id = t.technical_manager_code
                LEFT JOIN job_positions jp_e ON jp_e.catalog_year = t.catalog_year AND jp_e.job_position_id = t.elected_manager_code
                WHERE t.catalog_year = :y AND t.subprogram_id = :id LIMIT 1';
    } elseif ($module === 'management_positions') {
        $sql = 'SELECT t.*, pc.position_class_name, s.scale_name, ss.subscale_name, c.class_name, cat.category_name
                FROM positions t
                LEFT JOIN position_classes pc ON pc.catalog_year=t.catalog_year AND pc.position_class_id=t.position_class_id
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.scale_id
                LEFT JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.scale_id=t.scale_id AND ss.subscale_id=t.subscale_id
                LEFT JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.scale_id=t.scale_id AND c.subscale_id=t.subscale_id AND c.class_id=t.class_id
                LEFT JOIN civil_service_categories cat ON cat.catalog_year=t.catalog_year AND cat.scale_id=t.scale_id AND cat.subscale_id=t.subscale_id AND cat.class_id=t.class_id AND cat.category_id=t.category_id
                WHERE t.catalog_year = :y AND t.position_id = :id LIMIT 1';
    } elseif ($module === 'job_positions') {
        $sql = 'SELECT t.*,
                s.scale_name, ss.subscale_name, c.class_name, cat.category_name,
                lr.legal_relation_name,
                ou3.org_unit_level_3_name AS department_name,
                sp.special_specific_compensation_name,
                g.general_specific_compensation_name,
                g.amount AS general_specific_compensation_amount,
                wc.work_center_name,
                av.availability_name,
                pm.provision_method_name
                FROM job_positions t
                LEFT JOIN civil_service_scales s ON s.catalog_year=t.catalog_year AND s.scale_id=t.civil_service_scale_id
                LEFT JOIN civil_service_subscales ss ON ss.catalog_year=t.catalog_year AND ss.scale_id=t.civil_service_scale_id AND ss.subscale_id=t.civil_service_subscale_id
                LEFT JOIN civil_service_classes c ON c.catalog_year=t.catalog_year AND c.scale_id=t.civil_service_scale_id AND c.subscale_id=t.civil_service_subscale_id AND c.class_id=t.civil_service_class_id
                LEFT JOIN civil_service_categories cat ON cat.catalog_year=t.catalog_year AND cat.scale_id=t.civil_service_scale_id AND cat.subscale_id=t.civil_service_subscale_id AND cat.class_id=t.civil_service_class_id AND cat.category_id=t.civil_service_category_id
                LEFT JOIN legal_relations lr ON lr.catalog_year=t.catalog_year AND lr.legal_relation_id=t.legal_relation_id
                LEFT JOIN org_units_level_3 ou3 ON ou3.catalog_year=t.catalog_year AND ou3.org_unit_level_3_id=t.org_unit_level_3_id
                LEFT JOIN specific_compensation_special sp ON sp.catalog_year=t.catalog_year AND sp.special_specific_compensation_id=t.special_specific_compensation_id
                LEFT JOIN specific_compensation_general g ON g.catalog_year=t.catalog_year AND g.general_specific_compensation_id=t.general_specific_compensation_id
                LEFT JOIN work_centers wc ON wc.catalog_year=t.catalog_year AND wc.work_center_id=t.work_center_id
                LEFT JOIN availability_options av ON av.catalog_year=t.catalog_year AND av.availability_id=t.availability_id
                LEFT JOIN provision_methods pm ON pm.catalog_year=t.catalog_year AND pm.provision_method_id=t.provision_method_id
                WHERE t.catalog_year = :y AND t.job_position_id = :id LIMIT 1';
    } else {
        return null;
    }
    $st = $db->prepare($sql);
    $st->execute(['y' => $year, 'id' => $id]);
    $row = $st->fetch();
    if ($row && $module === 'people') {
        $stSp = $db->prepare('SELECT subprogram_id, dedication
            FROM subprogram_people
            WHERE catalog_year = :y AND person_id = :pid
            ORDER BY subprogram_id ASC');
        $stSp->execute(['y' => $year, 'pid' => (int) ($row['person_id'] ?? 0)]);
        $row['subprogram_people'] = $stSp->fetchAll() ?: [];
    }
    if ($row && $module === 'job_positions') {
        $jid = (string) ($row['job_position_id'] ?? '');
        $stP = $db->prepare('SELECT person_id, last_name_1, last_name_2, first_name
            FROM people
            WHERE catalog_year = :y AND job_position_id = :jid
            ORDER BY last_name_1 ASC, last_name_2 ASC, first_name ASC, person_id ASC');
        $stP->execute(['y' => $year, 'jid' => $jid]);
        $row['assigned_people'] = $stP->fetchAll() ?: [];
        $row['is_active'] = !empty($row['deleted_at']) ? 0 : 1;
    }
    return $row ?: null;
}

function maintenance_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $decoded = json_decode($e->getMessage(), true);
    return is_array($decoded) ? $decoded : null;
}

function maintenance_id_exists(PDO $db, string $module, int $year, string $id, ?string $excludeId = null): bool
{
    if ($module === 'maintenance_scales') {
        $sql = 'SELECT scale_id FROM civil_service_scales WHERE catalog_year = :y AND scale_id = :id';
    } elseif ($module === 'maintenance_subscales') {
        $sql = 'SELECT subscale_id FROM civil_service_subscales WHERE catalog_year = :y AND subscale_id = :id';
    } elseif ($module === 'maintenance_classes') {
        $sql = 'SELECT class_id FROM civil_service_classes WHERE catalog_year = :y AND class_id = :id';
    } elseif ($module === 'maintenance_categories') {
        $sql = 'SELECT category_id FROM civil_service_categories WHERE catalog_year = :y AND category_id = :id';
    } elseif ($module === 'maintenance_legal_relationships') {
        $sql = 'SELECT legal_relation_id FROM legal_relations WHERE catalog_year = :y AND legal_relation_id = :id';
    } elseif ($module === 'maintenance_administrative_statuses') {
        $sql = 'SELECT administrative_status_id FROM administrative_statuses WHERE catalog_year = :y AND administrative_status_id = :id';
    } elseif ($module === 'maintenance_position_classes') {
        $sql = 'SELECT position_class_id FROM position_classes WHERE catalog_year = :y AND position_class_id = :id';
    } elseif ($module === 'maintenance_access_types') {
        $sql = 'SELECT access_type_id FROM access_types WHERE catalog_year = :y AND access_type_id = :id';
    } elseif ($module === 'maintenance_access_systems') {
        $sql = 'SELECT access_system_id FROM access_systems WHERE catalog_year = :y AND access_system_id = :id';
    } elseif ($module === 'maintenance_work_centers') {
        $sql = 'SELECT work_center_id FROM work_centers WHERE catalog_year = :y AND work_center_id = :id';
    } elseif ($module === 'maintenance_availability_types') {
        $sql = 'SELECT availability_id FROM availability_options WHERE catalog_year = :y AND availability_id = :id';
    } elseif ($module === 'maintenance_provision_forms') {
        $sql = 'SELECT provision_method_id FROM provision_methods WHERE catalog_year = :y AND provision_method_id = :id';
    } elseif ($module === 'maintenance_organic_level_1') {
        $sql = 'SELECT org_unit_level_1_id FROM org_units_level_1 WHERE catalog_year = :y AND org_unit_level_1_id = :id';
    } elseif ($module === 'maintenance_organic_level_2') {
        $sql = 'SELECT org_unit_level_2_id FROM org_units_level_2 WHERE catalog_year = :y AND org_unit_level_2_id = :id';
    } elseif ($module === 'maintenance_organic_level_3') {
        $sql = 'SELECT org_unit_level_3_id FROM org_units_level_3 WHERE catalog_year = :y AND org_unit_level_3_id = :id';
    } elseif ($module === 'maintenance_programs') {
        $sql = 'SELECT program_id FROM programs WHERE catalog_year = :y AND program_id = :id';
    } elseif ($module === 'maintenance_social_security_companies') {
        $sql = 'SELECT company_id FROM social_security_companies WHERE catalog_year = :y AND company_id = :id';
    } elseif ($module === 'maintenance_social_security_coefficients') {
        $sql = 'SELECT contribution_epigraph_id FROM social_security_coefficients WHERE catalog_year = :y AND contribution_epigraph_id = :id';
    } elseif ($module === 'maintenance_social_security_base_limits') {
        $sql = 'SELECT contribution_group_id FROM social_security_base_limits WHERE catalog_year = :y AND contribution_group_id = :id';
    } elseif ($module === 'maintenance_salary_base_by_group') {
        $sql = 'SELECT classification_group FROM salary_base_by_group WHERE catalog_year = :y AND classification_group = :id';
    } elseif ($module === 'maintenance_destination_allowances') {
        $sql = 'SELECT organic_level FROM destination_allowances WHERE catalog_year = :y AND organic_level = :id';
    } elseif ($module === 'maintenance_seniority_pay_by_group') {
        $sql = 'SELECT classification_group FROM seniority_pay_by_group WHERE catalog_year = :y AND classification_group = :id';
    } elseif ($module === 'maintenance_specific_compensation_special_prices') {
        $sql = 'SELECT special_specific_compensation_id FROM specific_compensation_special WHERE catalog_year = :y AND special_specific_compensation_id = :id';
    } elseif ($module === 'maintenance_specific_compensation_general') {
        $sql = 'SELECT general_specific_compensation_id FROM specific_compensation_general WHERE catalog_year = :y AND general_specific_compensation_id = :id';
    } elseif ($module === 'maintenance_subprograms') {
        $sql = 'SELECT subprogram_id FROM subprograms WHERE catalog_year = :y AND subprogram_id = :id';
    } elseif ($module === 'people') {
        $sql = 'SELECT person_id FROM people WHERE catalog_year = :y AND person_id = :id';
    } elseif ($module === 'management_positions') {
        $sql = 'SELECT position_id FROM positions WHERE catalog_year = :y AND position_id = :id';
    } elseif ($module === 'job_positions') {
        $sql = 'SELECT job_position_id FROM job_positions WHERE catalog_year = :y AND job_position_id = :id';
    } else {
        return false;
    }
    if ($excludeId !== null) {
        $field = match ($module) {
            'maintenance_scales' => 'scale_id',
            'maintenance_subscales' => 'subscale_id',
            'maintenance_classes' => 'class_id',
            'maintenance_categories' => 'category_id',
            'maintenance_subprograms' => 'subprogram_id',
            'management_positions' => 'position_id',
            'job_positions' => 'job_position_id',
            'maintenance_programs' => 'program_id',
            'maintenance_social_security_companies' => 'company_id',
            'maintenance_social_security_coefficients' => 'contribution_epigraph_id',
            'maintenance_social_security_base_limits' => 'contribution_group_id',
            'maintenance_salary_base_by_group' => 'classification_group',
            'maintenance_destination_allowances' => 'organic_level',
            'maintenance_seniority_pay_by_group' => 'classification_group',
            'maintenance_specific_compensation_special_prices' => 'special_specific_compensation_id',
            'maintenance_specific_compensation_general' => 'general_specific_compensation_id',
            'maintenance_access_types' => 'access_type_id',
            'maintenance_access_systems' => 'access_system_id',
            'maintenance_position_classes' => 'position_class_id',
            'maintenance_legal_relationships' => 'legal_relation_id',
            'maintenance_administrative_statuses' => 'administrative_status_id',
            'maintenance_organic_level_1' => 'org_unit_level_1_id',
            'maintenance_organic_level_2' => 'org_unit_level_2_id',
            'maintenance_organic_level_3' => 'org_unit_level_3_id',
            'maintenance_work_centers' => 'work_center_id',
            'maintenance_availability_types' => 'availability_id',
            'maintenance_provision_forms' => 'provision_method_id',
            default => 'category_id',
        };
        $sql .= ' AND ' . $field . ' <> :exclude_id';
    }
    $st = $db->prepare($sql . ' LIMIT 1');
    $params = ['y' => $year, 'id' => $id];
    if ($excludeId !== null) {
        $params['exclude_id'] = $excludeId;
    }
    $st->execute($params);
    return (bool) $st->fetch();
}

function maintenance_save_programs(PDO $db, int $year, int|string|null $originalId, array $data): void
{
    $subRaw = trim((string) ($data['subfunction_id'] ?? ''));
    $numRaw = trim((string) ($data['program_number'] ?? ''));
    $programName = trim((string) ($data['name'] ?? ''));
    $respRaw = trim((string) ($data['responsible_person_code'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $errors = [];
    if ($subRaw === '' || !preg_match('/^\d{1,3}$/', $subRaw)) {
        $errors['subfunction_id'] = $subRaw === '' ? 'La subfunció és obligatòria.' : 'La subfunció ha de ser numèrica (1 a 3 dígits).';
    }
    if ($numRaw === '' || !preg_match('/^\d$/', $numRaw)) {
        $errors['program_number'] = $numRaw === '' ? 'El número és obligatori.' : 'El número ha de ser exactament un dígit (0-9).';
    }
    if ($programName === '') {
        $errors['name'] = 'El nom del programa és obligatori.';
    }
    if ($respRaw !== '') {
        $stc = $db->prepare('SELECT 1 FROM job_positions WHERE catalog_year = :y AND job_position_id = :jid AND deleted_at IS NULL LIMIT 1');
        $stc->execute(['y' => $year, 'jid' => $respRaw]);
        if (!$stc->fetch()) {
            $errors['responsible_person_code'] = 'El lloc de treball seleccionat no existeix per a aquest any de catàleg.';
        }
    }
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $subStore = maintenance_programs_normalize_subfunction_id($subRaw);
    $numDigit = $numRaw;
    $programNumberInt = (int) $numDigit;
    $newPid = maintenance_programs_compute_program_id($subStore, $numDigit);
    $oldPid = ($originalId !== null && (string) $originalId !== '') ? trim((string) $originalId) : '';
    $isCreate = $oldPid === '';
    $excludeForDup = $isCreate ? null : $oldPid;
    if (maintenance_id_exists($db, 'maintenance_programs', $year, $newPid, $excludeForDup)) {
        throw new InvalidArgumentException(json_encode(['_general' => 'Ja existeix un programa amb aquest codi (subfunció + número) per a aquest any de catàleg.'], JSON_THROW_ON_ERROR));
    }
    if (!$isCreate && $newPid !== $oldPid) {
        $stCnt = $db->prepare('SELECT COUNT(*) AS c FROM subprograms WHERE catalog_year = :y AND program_id = :pid');
        $stCnt->execute(['y' => $year, 'pid' => $oldPid]);
        $nSub = (int) (($stCnt->fetch())['c'] ?? 0);
        if ($nSub > 0) {
            throw new InvalidArgumentException(json_encode(['_general' => 'No es pot canviar la subfunció ni el número mentre hi hagi subprogrames dependents d’aquest programa.'], JSON_THROW_ON_ERROR));
        }
    }
    $respStore = $respRaw !== '' ? $respRaw : null;
    $descStore = $description !== '' ? $description : null;
    if ($isCreate) {
        $st = $db->prepare('INSERT INTO programs (catalog_year, program_id, subfunction_id, program_number, program_name, responsible_person_code, description) VALUES (:y,:pid,:sf,:num,:pname,:resp,:desc)');
        $st->execute([
            'y' => $year,
            'pid' => $newPid,
            'sf' => $subStore,
            'num' => $programNumberInt,
            'pname' => $programName,
            'resp' => $respStore,
            'desc' => $descStore,
        ]);
        return;
    }
    if ($newPid === $oldPid) {
        $st = $db->prepare('UPDATE programs SET subfunction_id=:sf, program_number=:num, program_name=:pname, responsible_person_code=:resp, description=:desc WHERE catalog_year=:y AND program_id=:pid');
        $st->execute([
            'y' => $year,
            'pid' => $oldPid,
            'sf' => $subStore,
            'num' => $programNumberInt,
            'pname' => $programName,
            'resp' => $respStore,
            'desc' => $descStore,
        ]);
        return;
    }
    $st = $db->prepare('UPDATE programs SET program_id=:newpid, subfunction_id=:sf, program_number=:num, program_name=:pname, responsible_person_code=:resp, description=:desc WHERE catalog_year=:y AND program_id=:oldpid');
    $st->execute([
        'y' => $year,
        'oldpid' => $oldPid,
        'newpid' => $newPid,
        'sf' => $subStore,
        'num' => $programNumberInt,
        'pname' => $programName,
        'resp' => $respStore,
        'desc' => $descStore,
    ]);
}

function maintenance_subprograms_flag_int(mixed $v): int
{
    if ($v === true || $v === 1 || $v === '1') {
        return 1;
    }

    return 0;
}

function maintenance_save_subprograms(PDO $db, int $year, int|string|null $originalId, array $data): void
{
    $progRaw = trim((string) ($data['program_id'] ?? ''));
    $numRaw = trim((string) ($data['subprogram_number'] ?? ''));
    $subprogramName = trim((string) ($data['subprogram_name'] ?? $data['name'] ?? ''));
    $techRaw = trim((string) ($data['technical_manager_code'] ?? ''));
    $electedRaw = trim((string) ($data['elected_manager_code'] ?? ''));
    $natureRaw = trim((string) ($data['nature'] ?? ''));
    $mand = maintenance_subprograms_flag_int($data['is_mandatory_service'] ?? 0);
    $corp = maintenance_subprograms_flag_int($data['has_corporate_agreements'] ?? 0);
    $objectives = trim((string) ($data['objectives'] ?? ''));
    $activities = trim((string) ($data['activities'] ?? ''));
    $notes = trim((string) ($data['notes'] ?? ''));

    $errors = [];
    if ($progRaw === '') {
        $errors['program_id'] = 'Cal seleccionar un programa.';
    } else {
        $stP = $db->prepare('SELECT 1 FROM programs WHERE catalog_year = :y AND program_id = :pid LIMIT 1');
        $stP->execute(['y' => $year, 'pid' => $progRaw]);
        if (!$stP->fetch()) {
            $errors['program_id'] = 'El programa seleccionat no existeix per a aquest any de catàleg.';
        }
    }
    if ($numRaw === '' || !preg_match('/^\d{1,2}$/', $numRaw)) {
        $errors['subprogram_number'] = $numRaw === '' ? 'El número de subprograma és obligatori.' : 'El número ha de tenir entre 1 i 2 dígits numèrics.';
    }
    if ($subprogramName === '') {
        $errors['name'] = 'El nom del subprograma és obligatori.';
    }
    if ($natureRaw !== '' && !in_array($natureRaw, maintenance_subprograms_nature_allowed(), true)) {
        $errors['nature'] = 'El valor de naturalesa no és vàlid.';
    }
    if ($techRaw !== '' && !maintenance_job_position_is_cm($db, $year, $techRaw)) {
        $errors['technical_manager_code'] = 'El responsable tècnic ha de ser un lloc tipus CM vàlid per a aquest any.';
    }
    if ($electedRaw !== '' && !maintenance_job_position_is_cm($db, $year, $electedRaw)) {
        $errors['elected_manager_code'] = 'El responsable electe ha de ser un lloc tipus CM vàlid per a aquest any.';
    }
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $numStore = maintenance_subprograms_normalize_number($numRaw);
    $newSid = maintenance_subprograms_compute_subprogram_id($progRaw, $numStore);
    $oldSid = ($originalId !== null && (string) $originalId !== '') ? trim((string) $originalId) : '';
    $isCreate = $oldSid === '';
    $excludeForDup = $isCreate ? null : $oldSid;

    if (maintenance_id_exists($db, 'maintenance_subprograms', $year, $newSid, $excludeForDup)) {
        throw new InvalidArgumentException(json_encode(['_general' => 'Ja existeix un subprograma amb aquest codi per a aquest any de catàleg.'], JSON_THROW_ON_ERROR));
    }

    $stPeople = $db->prepare('SELECT COUNT(*) AS c FROM subprogram_people WHERE catalog_year = :y AND subprogram_id = :sid');
    if (!$isCreate) {
        $stPeople->execute(['y' => $year, 'sid' => $oldSid]);
        $nPeople = (int) (($stPeople->fetch())['c'] ?? 0);
        if ($newSid !== $oldSid && $nPeople > 0) {
            throw new InvalidArgumentException(json_encode(['_general' => 'No es pot canviar el programa ni el número de subprograma mentre hi hagi persones vinculades.'], JSON_THROW_ON_ERROR));
        }
    }

    $techStore = $techRaw !== '' ? $techRaw : null;
    $electedStore = $electedRaw !== '' ? $electedRaw : null;
    $natureStore = $natureRaw !== '' ? $natureRaw : null;
    $objStore = $objectives !== '' ? $objectives : null;
    $actStore = $activities !== '' ? $activities : null;
    $notesStore = $notes !== '' ? $notes : null;

    if ($isCreate) {
        $st = $db->prepare('INSERT INTO subprograms (catalog_year, subprogram_id, program_id, subprogram_number, subprogram_name, technical_manager_code, elected_manager_code, is_mandatory_service, has_corporate_agreements, nature, objectives, activities, notes) VALUES (:y,:sid,:pid,:snum,:sname,:tech,:elect,:mand,:corp,:nat,:obj,:act,:notes)');
        $st->execute([
            'y' => $year,
            'sid' => $newSid,
            'pid' => $progRaw,
            'snum' => $numStore,
            'sname' => $subprogramName,
            'tech' => $techStore,
            'elect' => $electedStore,
            'mand' => $mand,
            'corp' => $corp,
            'nat' => $natureStore,
            'obj' => $objStore,
            'act' => $actStore,
            'notes' => $notesStore,
        ]);

        return;
    }

    if ($newSid === $oldSid) {
        $st = $db->prepare('UPDATE subprograms SET program_id=:pid, subprogram_number=:snum, subprogram_name=:sname, technical_manager_code=:tech, elected_manager_code=:elect, is_mandatory_service=:mand, has_corporate_agreements=:corp, nature=:nat, objectives=:obj, activities=:act, notes=:notes WHERE catalog_year=:y AND subprogram_id=:oldsid');
        $st->execute([
            'y' => $year,
            'oldsid' => $oldSid,
            'pid' => $progRaw,
            'snum' => $numStore,
            'sname' => $subprogramName,
            'tech' => $techStore,
            'elect' => $electedStore,
            'mand' => $mand,
            'corp' => $corp,
            'nat' => $natureStore,
            'obj' => $objStore,
            'act' => $actStore,
            'notes' => $notesStore,
        ]);

        return;
    }

    $st = $db->prepare('UPDATE subprograms SET subprogram_id=:newsid, program_id=:pid, subprogram_number=:snum, subprogram_name=:sname, technical_manager_code=:tech, elected_manager_code=:elect, is_mandatory_service=:mand, has_corporate_agreements=:corp, nature=:nat, objectives=:obj, activities=:act, notes=:notes WHERE catalog_year=:y AND subprogram_id=:oldsid');
    $st->execute([
        'y' => $year,
        'oldsid' => $oldSid,
        'newsid' => $newSid,
        'pid' => $progRaw,
        'snum' => $numStore,
        'sname' => $subprogramName,
        'tech' => $techStore,
        'elect' => $electedStore,
        'mand' => $mand,
        'corp' => $corp,
        'nat' => $natureStore,
        'obj' => $objStore,
        'act' => $actStore,
        'notes' => $notesStore,
    ]);
}

function maintenance_save(PDO $db, string $module, int $year, int|string|null $originalId, array $data): void
{
    if (!in_array($module, maintenance_catalog_crud_modules(), true)) {
        throw new RuntimeException('Mòdul sense implementació de persistència.');
    }
    if ($module === 'maintenance_programs') {
        maintenance_save_programs($db, $year, $originalId, $data);
        return;
    }
    if ($module === 'maintenance_subprograms') {
        maintenance_save_subprograms($db, $year, $originalId, $data);
        return;
    }
    if ($module === 'maintenance_social_security_companies') {
        $companyId = strtoupper(trim((string) ($data['id'] ?? '')));
        $companyDescription = trim((string) ($data['name'] ?? ''));
        $cccRaw = trim((string) ($data['contribution_account_code'] ?? ''));
        $cccDigits = maintenance_company_ccc_digits_only($cccRaw);
        $errors = [];
        if ($companyId === '') {
            $errors['id'] = 'El codi és obligatori.';
        } elseif (!preg_match('/^[A-Za-z0-9]+$/', $companyId)) {
            $errors['id'] = 'El codi ha de ser alfanumèric.';
        }
        if ($companyDescription === '') {
            $errors['name'] = 'La denominació és obligatòria.';
        }
        if ($cccRaw !== '') {
            if (!preg_match('/^[0-9 ]+$/', $cccRaw)) {
                $errors['contribution_account_code'] = 'El CCC només pot contenir dígits i espais.';
            } elseif (strlen($cccDigits) !== MAINTENANCE_COMPANY_CCC_DIGITS) {
                $errors['contribution_account_code'] = 'El CCC ha de tenir exactament 11 dígits.';
            }
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $originalIdText = $originalId !== null ? strtoupper(trim((string) $originalId)) : null;
        if (maintenance_id_exists($db, $module, $year, $companyId, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        $cccStore = $cccRaw === '' ? null : $cccDigits;
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO social_security_companies (catalog_year, company_id, company_description, contribution_account_code) VALUES (:y,:id,:name,:ccc)');
            $st->execute(['y' => $year, 'id' => $companyId, 'name' => $companyDescription, 'ccc' => $cccStore]);
            return;
        }
        $st = $db->prepare('UPDATE social_security_companies SET company_id=:new_id, company_description=:name, contribution_account_code=:ccc WHERE catalog_year=:y AND company_id=:id');
        $st->execute(['y' => $year, 'id' => $originalIdText, 'new_id' => $companyId, 'name' => $companyDescription, 'ccc' => $cccStore]);
        return;
    }
    if ($module === 'maintenance_social_security_coefficients') {
        $epiRaw = trim((string) ($data['id'] ?? ''));
        $errors = [];
        if ($epiRaw === '') {
            $errors['id'] = 'L’epígraf és obligatori.';
        } elseif (!preg_match('/^\d{1,3}$/', $epiRaw)) {
            $errors['id'] = 'L’epígraf ha de ser numèric i tenir com a màxim 3 dígits.';
        }
        $epiStore = str_pad($epiRaw, MAINTENANCE_SOCIAL_SECURITY_EPIGRAPH_PAD, '0', STR_PAD_LEFT);
        $pctFields = ['company_1', 'company_2', 'company_3', 'company_4', 'company_5a', 'company_5b', 'company_5c', 'company_5d', 'company_5e', 'temporary_employment_company'];
        $pctStore = array_fill_keys($pctFields, null);
        foreach ($pctFields as $f) {
            $parsed = maintenance_parse_ss_coeff_visible_percent_field(trim((string) ($data[$f] ?? '')));
            if (!$parsed['ok']) {
                $errors[$f] = $parsed['error'];

                continue;
            }
            $pctStore[$f] = $parsed['value'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if (maintenance_id_exists($db, $module, $year, $epiStore, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest epígraf dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO social_security_coefficients (catalog_year, contribution_epigraph_id, company_1, company_2, company_3, company_4, company_5a, company_5b, company_5c, company_5d, company_5e, temporary_employment_company) VALUES (:y,:id,:c1,:c2,:c3,:c4,:c5a,:c5b,:c5c,:c5d,:c5e,:cet)');
            $st->execute([
                'y' => $year,
                'id' => $epiStore,
                'c1' => $pctStore['company_1'],
                'c2' => $pctStore['company_2'],
                'c3' => $pctStore['company_3'],
                'c4' => $pctStore['company_4'],
                'c5a' => $pctStore['company_5a'],
                'c5b' => $pctStore['company_5b'],
                'c5c' => $pctStore['company_5c'],
                'c5d' => $pctStore['company_5d'],
                'c5e' => $pctStore['company_5e'],
                'cet' => $pctStore['temporary_employment_company'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE social_security_coefficients SET contribution_epigraph_id=:new_id, company_1=:c1, company_2=:c2, company_3=:c3, company_4=:c4, company_5a=:c5a, company_5b=:c5b, company_5c=:c5c, company_5d=:c5d, company_5e=:c5e, temporary_employment_company=:cet WHERE catalog_year=:y AND contribution_epigraph_id=:id');
        $st->execute([
            'y' => $year,
            'id' => $originalIdText,
            'new_id' => $epiStore,
            'c1' => $pctStore['company_1'],
            'c2' => $pctStore['company_2'],
            'c3' => $pctStore['company_3'],
            'c4' => $pctStore['company_4'],
            'c5a' => $pctStore['company_5a'],
            'c5b' => $pctStore['company_5b'],
            'c5c' => $pctStore['company_5c'],
            'c5d' => $pctStore['company_5d'],
            'c5e' => $pctStore['company_5e'],
            'cet' => $pctStore['temporary_employment_company'],
        ]);
        return;
    }
    if ($module === 'maintenance_social_security_base_limits') {
        $groupRaw = trim((string) ($data['id'] ?? ''));
        $groupDescription = trim((string) ($data['name'] ?? ''));
        $minimumBaseRaw = trim((string) ($data['minimum_base'] ?? ''));
        $maximumBaseRaw = trim((string) ($data['maximum_base'] ?? ''));
        $periodLabel = trim((string) ($data['period_label'] ?? ''));
        $errors = [];

        if ($groupRaw === '') {
            $errors['id'] = 'El grup de cotització és obligatori.';
        } elseif (!preg_match('/^\d{1,2}$/', $groupRaw)) {
            $errors['id'] = 'El grup de cotització ha de ser numèric i tenir com a màxim 2 dígits.';
        }
        if ($groupDescription === '') {
            $errors['name'] = 'La denominació és obligatòria.';
        }

        $minParsed = maintenance_parse_optional_money_input($minimumBaseRaw);
        if (!$minParsed['ok']) {
            $errors['minimum_base'] = $minParsed['error'];
        }
        $maxParsed = maintenance_parse_optional_money_input($maximumBaseRaw);
        if (!$maxParsed['ok']) {
            $errors['maximum_base'] = $maxParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $groupStore = str_pad($groupRaw, MAINTENANCE_SOCIAL_SECURITY_BASE_GROUP_PAD, '0', STR_PAD_LEFT);
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if (maintenance_id_exists($db, $module, $year, $groupStore, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest grup de cotització dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        $minimumBaseStore = $minParsed['value'];
        $maximumBaseStore = $maxParsed['value'];
        $periodLabelStore = $periodLabel === '' ? null : $periodLabel;

        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO social_security_base_limits (catalog_year, contribution_group_id, contribution_group_description, minimum_base, maximum_base, period_label) VALUES (:y,:id,:name,:min_base,:max_base,:period)');
            $st->execute([
                'y' => $year,
                'id' => $groupStore,
                'name' => $groupDescription,
                'min_base' => $minimumBaseStore,
                'max_base' => $maximumBaseStore,
                'period' => $periodLabelStore,
            ]);
            return;
        }
        $st = $db->prepare('UPDATE social_security_base_limits SET contribution_group_id=:new_id, contribution_group_description=:name, minimum_base=:min_base, maximum_base=:max_base, period_label=:period WHERE catalog_year=:y AND contribution_group_id=:id');
        $st->execute([
            'y' => $year,
            'id' => $originalIdText,
            'new_id' => $groupStore,
            'name' => $groupDescription,
            'min_base' => $minimumBaseStore,
            'max_base' => $maximumBaseStore,
            'period' => $periodLabelStore,
        ]);
        return;
    }
    if ($module === 'maintenance_salary_base_by_group') {
        $group = strtoupper(trim((string) ($data['id'] ?? '')));
        $baseSalaryRaw = trim((string) ($data['base_salary'] ?? ''));
        $baseSalaryExtraRaw = trim((string) ($data['base_salary_extra_pay'] ?? ''));
        $baseSalaryNewRaw = trim((string) ($data['base_salary_new'] ?? ''));
        $baseSalaryExtraNewRaw = trim((string) ($data['base_salary_extra_pay_new'] ?? ''));
        $errors = [];
        if ($group === '') {
            $errors['id'] = 'El grup de classificació és obligatori.';
        }
        $baseParsed = maintenance_parse_optional_money_input($baseSalaryRaw);
        if (!$baseParsed['ok']) {
            $errors['base_salary'] = $baseParsed['error'];
        } elseif ($baseParsed['value'] === null) {
            $errors['base_salary'] = 'El sou base és obligatori.';
        }
        $baseExtraParsed = maintenance_parse_optional_money_input($baseSalaryExtraRaw);
        if (!$baseExtraParsed['ok']) {
            $errors['base_salary_extra_pay'] = $baseExtraParsed['error'];
        } elseif ($baseExtraParsed['value'] === null) {
            $errors['base_salary_extra_pay'] = 'El sou base afectació pagues és obligatori.';
        }
        $baseNewParsed = maintenance_parse_optional_money_input($baseSalaryNewRaw);
        if (!$baseNewParsed['ok']) {
            $errors['base_salary_new'] = $baseNewParsed['error'];
        }
        $baseExtraNewParsed = maintenance_parse_optional_money_input($baseSalaryExtraNewRaw);
        if (!$baseExtraNewParsed['ok']) {
            $errors['base_salary_extra_pay_new'] = $baseExtraNewParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $originalIdText = $originalId !== null ? strtoupper(trim((string) $originalId)) : null;
        if (maintenance_id_exists($db, $module, $year, $group, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest grup de classificació dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO salary_base_by_group (catalog_year, classification_group, base_salary, base_salary_extra_pay, base_salary_new, base_salary_extra_pay_new) VALUES (:y,:grp,:base,:base_extra,:base_new,:base_extra_new)');
            $st->execute([
                'y' => $year,
                'grp' => $group,
                'base' => $baseParsed['value'],
                'base_extra' => $baseExtraParsed['value'],
                'base_new' => $baseNewParsed['value'],
                'base_extra_new' => $baseExtraNewParsed['value'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE salary_base_by_group SET classification_group=:new_grp, base_salary=:base, base_salary_extra_pay=:base_extra, base_salary_new=:base_new, base_salary_extra_pay_new=:base_extra_new WHERE catalog_year=:y AND classification_group=:grp');
        $st->execute([
            'y' => $year,
            'grp' => $originalIdText,
            'new_grp' => $group,
            'base' => $baseParsed['value'],
            'base_extra' => $baseExtraParsed['value'],
            'base_new' => $baseNewParsed['value'],
            'base_extra_new' => $baseExtraNewParsed['value'],
        ]);
        return;
    }
    if ($module === 'maintenance_destination_allowances') {
        $organicLevel = strtoupper(trim((string) ($data['id'] ?? '')));
        $destinationAllowanceRaw = trim((string) ($data['destination_allowance'] ?? ''));
        $destinationAllowanceNewRaw = trim((string) ($data['destination_allowance_new'] ?? ''));
        $errors = [];
        if ($organicLevel === '') {
            $errors['id'] = 'El nivell orgànic és obligatori.';
        }
        $destParsed = maintenance_parse_optional_money_input($destinationAllowanceRaw);
        if (!$destParsed['ok']) {
            $errors['destination_allowance'] = $destParsed['error'];
        } elseif ($destParsed['value'] === null) {
            $errors['destination_allowance'] = 'El complement destinació és obligatori.';
        }
        $destNewParsed = maintenance_parse_optional_money_input($destinationAllowanceNewRaw);
        if (!$destNewParsed['ok']) {
            $errors['destination_allowance_new'] = $destNewParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $originalIdText = $originalId !== null ? strtoupper(trim((string) $originalId)) : null;
        if (maintenance_id_exists($db, $module, $year, $organicLevel, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest nivell orgànic dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO destination_allowances (catalog_year, organic_level, destination_allowance, destination_allowance_new) VALUES (:y,:org,:dest,:dest_new)');
            $st->execute([
                'y' => $year,
                'org' => $organicLevel,
                'dest' => $destParsed['value'],
                'dest_new' => $destNewParsed['value'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE destination_allowances SET organic_level=:new_org, destination_allowance=:dest, destination_allowance_new=:dest_new WHERE catalog_year=:y AND organic_level=:org');
        $st->execute([
            'y' => $year,
            'org' => $originalIdText,
            'new_org' => $organicLevel,
            'dest' => $destParsed['value'],
            'dest_new' => $destNewParsed['value'],
        ]);
        return;
    }
    if ($module === 'maintenance_seniority_pay_by_group') {
        $group = strtoupper(trim((string) ($data['id'] ?? '')));
        $seniorityAmountRaw = trim((string) ($data['seniority_amount'] ?? ''));
        $seniorityExtraAmountRaw = trim((string) ($data['seniority_extra_pay_amount'] ?? ''));
        $seniorityAmountNewRaw = trim((string) ($data['seniority_amount_new'] ?? ''));
        $seniorityExtraAmountNewRaw = trim((string) ($data['seniority_extra_pay_amount_new'] ?? ''));
        $errors = [];
        if ($group === '') {
            $errors['id'] = 'El grup de classificació és obligatori.';
        }
        $seniorityParsed = maintenance_parse_optional_money_input($seniorityAmountRaw);
        if (!$seniorityParsed['ok']) {
            $errors['seniority_amount'] = $seniorityParsed['error'];
        } elseif ($seniorityParsed['value'] === null) {
            $errors['seniority_amount'] = 'El trienni és obligatori.';
        }
        $seniorityExtraParsed = maintenance_parse_optional_money_input($seniorityExtraAmountRaw);
        if (!$seniorityExtraParsed['ok']) {
            $errors['seniority_extra_pay_amount'] = $seniorityExtraParsed['error'];
        } elseif ($seniorityExtraParsed['value'] === null) {
            $errors['seniority_extra_pay_amount'] = 'El trienni afectació pagues és obligatori.';
        }
        $seniorityNewParsed = maintenance_parse_optional_money_input($seniorityAmountNewRaw);
        if (!$seniorityNewParsed['ok']) {
            $errors['seniority_amount_new'] = $seniorityNewParsed['error'];
        }
        $seniorityExtraNewParsed = maintenance_parse_optional_money_input($seniorityExtraAmountNewRaw);
        if (!$seniorityExtraNewParsed['ok']) {
            $errors['seniority_extra_pay_amount_new'] = $seniorityExtraNewParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $originalIdText = $originalId !== null ? strtoupper(trim((string) $originalId)) : null;
        if (maintenance_id_exists($db, $module, $year, $group, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest grup de classificació dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO seniority_pay_by_group (catalog_year, classification_group, seniority_amount, seniority_extra_pay_amount, seniority_amount_new, seniority_extra_pay_amount_new) VALUES (:y,:grp,:s,:se,:sn,:sen)');
            $st->execute([
                'y' => $year,
                'grp' => $group,
                's' => $seniorityParsed['value'],
                'se' => $seniorityExtraParsed['value'],
                'sn' => $seniorityNewParsed['value'],
                'sen' => $seniorityExtraNewParsed['value'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE seniority_pay_by_group SET classification_group=:new_grp, seniority_amount=:s, seniority_extra_pay_amount=:se, seniority_amount_new=:sn, seniority_extra_pay_amount_new=:sen WHERE catalog_year=:y AND classification_group=:grp');
        $st->execute([
            'y' => $year,
            'grp' => $originalIdText,
            'new_grp' => $group,
            's' => $seniorityParsed['value'],
            'se' => $seniorityExtraParsed['value'],
            'sn' => $seniorityNewParsed['value'],
            'sen' => $seniorityExtraNewParsed['value'],
        ]);
        return;
    }
    if ($module === 'maintenance_specific_compensation_special_prices') {
        $sidRaw = trim((string) (($data['special_specific_compensation_id'] ?? '') !== '' ? $data['special_specific_compensation_id'] : ($data['id'] ?? '')));
        $sname = trim((string) ($data['special_specific_compensation_name'] ?? ''));
        $amountRaw = trim((string) ($data['amount'] ?? ''));
        $amountNewRaw = trim((string) ($data['amount_new'] ?? ''));
        $errors = [];
        if ($sidRaw === '') {
            $errors['id'] = 'El codi és obligatori.';
        } elseif (!preg_match('/^\d+$/', $sidRaw)) {
            $errors['id'] = 'El codi ha de ser numèric.';
        }
        if ($sname === '') {
            $errors['special_specific_compensation_name'] = 'La denominació és obligatòria.';
        }
        $amountParsed = maintenance_parse_optional_money_input($amountRaw);
        if (!$amountParsed['ok']) {
            $errors['amount'] = $amountParsed['error'];
        } elseif ($amountParsed['value'] === null) {
            $errors['amount'] = 'El complement específic especial és obligatori.';
        }
        $amountNewParsed = maintenance_parse_optional_money_input($amountNewRaw);
        if (!$amountNewParsed['ok']) {
            $errors['amount_new'] = $amountNewParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $sidInt = (int) $sidRaw;
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if ($originalIdText !== null && $originalIdText !== '' && !preg_match('/^\d+$/', $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'El codi original no és vàlid.'], JSON_THROW_ON_ERROR));
        }
        $originalIdNorm = ($originalIdText !== null && $originalIdText !== '') ? (string) (int) $originalIdText : null;
        $sidStr = (string) $sidInt;
        if ($originalIdNorm === null || $originalIdNorm === '') {
            if ($sidInt === 0) {
                throw new InvalidArgumentException(json_encode(['id' => 'El codi 0 està reservat i no es pot donar d\'alta des d\'aquest formulari.'], JSON_THROW_ON_ERROR));
            }
        } else {
            if ((int) $originalIdNorm === 0) {
                throw new InvalidArgumentException(json_encode(['_general' => 'El codi 0 està reservat i no es pot modificar des d\'aquest formulari.'], JSON_THROW_ON_ERROR));
            }
            if ($sidInt === 0) {
                throw new InvalidArgumentException(json_encode(['id' => 'El codi 0 està reservat i no es pot donar d\'alta des d\'aquest formulari.'], JSON_THROW_ON_ERROR));
            }
        }
        if (maintenance_id_exists($db, $module, $year, $sidStr, $originalIdNorm)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdNorm === null || $originalIdNorm === '') {
            $st = $db->prepare('INSERT INTO specific_compensation_special
                (catalog_year, special_specific_compensation_id, special_specific_compensation_name, amount, amount_new)
                VALUES (:y,:sid,:name,:a,:an)');
            $st->execute([
                'y' => $year,
                'sid' => $sidInt,
                'name' => $sname,
                'a' => $amountParsed['value'],
                'an' => $amountNewParsed['value'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE specific_compensation_special
            SET special_specific_compensation_id = :new_sid,
                special_specific_compensation_name = :name,
                amount = :a,
                amount_new = :an
            WHERE catalog_year = :y
              AND special_specific_compensation_id = :sid');
        $st->execute([
            'y' => $year,
            'sid' => (int) $originalIdNorm,
            'new_sid' => $sidInt,
            'name' => $sname,
            'a' => $amountParsed['value'],
            'an' => $amountNewParsed['value'],
        ]);
        return;
    }
    if ($module === 'maintenance_specific_compensation_general') {
        $gidRaw = trim((string) (($data['general_specific_compensation_id'] ?? '') !== '' ? $data['general_specific_compensation_id'] : ($data['id'] ?? '')));
        $gname = trim((string) ($data['general_specific_compensation_name'] ?? ''));
        $amountRaw = trim((string) ($data['amount'] ?? ''));
        $decreaseRaw = trim((string) ($data['decrease_amount'] ?? ''));
        $amountNewRaw = trim((string) ($data['amount_new'] ?? ''));
        $decreaseNewRaw = trim((string) ($data['decrease_amount_new'] ?? ''));
        $errors = [];
        if ($gidRaw === '') {
            $errors['general_specific_compensation_id'] = 'El codi és obligatori.';
        } elseif (!preg_match('/^\d+$/', $gidRaw)) {
            $errors['general_specific_compensation_id'] = 'El codi ha de ser numèric.';
        }
        if ($gname === '') {
            $errors['general_specific_compensation_name'] = 'La descripció del complement és obligatòria.';
        }
        $amountParsed = maintenance_parse_optional_money_input($amountRaw);
        if (!$amountParsed['ok']) {
            $errors['amount'] = $amountParsed['error'];
        } elseif ($amountParsed['value'] === null) {
            $errors['amount'] = 'L’import complement és obligatori.';
        }
        $decreaseParsed = maintenance_parse_optional_money_input($decreaseRaw);
        if (!$decreaseParsed['ok']) {
            $errors['decrease_amount'] = $decreaseParsed['error'];
        } elseif ($decreaseParsed['value'] === null) {
            $errors['decrease_amount'] = 'L’import de la disminució és obligatori.';
        }
        $amountNewParsed = maintenance_parse_optional_money_input($amountNewRaw);
        if (!$amountNewParsed['ok']) {
            $errors['amount_new'] = $amountNewParsed['error'];
        }
        $decreaseNewParsed = maintenance_parse_optional_money_input($decreaseNewRaw);
        if (!$decreaseNewParsed['ok']) {
            $errors['decrease_amount_new'] = $decreaseNewParsed['error'];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $gid = (string) ((int) $gidRaw);
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if (maintenance_id_exists($db, $module, $year, $gid, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['general_specific_compensation_id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO specific_compensation_general
                (catalog_year, general_specific_compensation_id, general_specific_compensation_name, amount, decrease_amount, amount_new, decrease_amount_new)
                VALUES (:y,:id,:name,:a,:d,:an,:dn)');
            $st->execute([
                'y' => $year,
                'id' => $gid,
                'name' => $gname,
                'a' => $amountParsed['value'],
                'd' => $decreaseParsed['value'],
                'an' => $amountNewParsed['value'],
                'dn' => $decreaseNewParsed['value'],
            ]);
            return;
        }
        $st = $db->prepare('UPDATE specific_compensation_general
            SET general_specific_compensation_id = :new_id,
                general_specific_compensation_name = :name,
                amount = :a,
                decrease_amount = :d,
                amount_new = :an,
                decrease_amount_new = :dn
            WHERE catalog_year = :y
              AND general_specific_compensation_id = :id');
        $st->execute([
            'y' => $year,
            'id' => $originalIdText,
            'new_id' => $gid,
            'name' => $gname,
            'a' => $amountParsed['value'],
            'd' => $decreaseParsed['value'],
            'an' => $amountNewParsed['value'],
            'dn' => $decreaseNewParsed['value'],
        ]);
        return;
    }
    if ($module === 'job_positions') {
        $catalogCode = trim((string) ($data['catalog_code'] ?? ''));
        $jid = trim((string) (($data['job_position_id'] ?? '') !== '' ? $data['job_position_id'] : ($data['id'] ?? '')));
        $jobTitle = trim((string) ($data['job_title'] ?? ''));
        $errors = [];
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if ($jid === '' || strlen($jid) > 30) {
            $errors['job_position_id'] = $jid === '' ? 'El codi del lloc és obligatori.' : 'El codi del lloc és massa llarg (màx. 30 caràcters).';
        }
        if ($jobTitle === '') {
            $errors['job_title'] = 'La denominació és obligatòria.';
        }
        if ($originalIdText !== null && $originalIdText !== '' && $jid !== $originalIdText) {
            $errors['job_position_id'] = 'No es pot canviar el codi del lloc; només la resta de dades.';
        }
        $allowedCatalogTypes = ['', '01', '02', '03', '04', '05'];
        if (!in_array($catalogCode, $allowedCatalogTypes, true)) {
            $errors['catalog_code'] = 'El tipus de catàleg no és vàlid.';
        }
        $legalRaw = strtoupper(trim((string) ($data['legal_relation_id'] ?? '')));
        $legalId = $legalRaw !== '' ? $legalRaw : null;
        $allowedLegal = ['E', 'F', 'I', 'L', 'P', 'T', 'D'];
        if ($legalId !== null && !in_array($legalId, $allowedLegal, true)) {
            $errors['legal_relation_id'] = 'La relació jurídica no és vàlida.';
        }
        $mode = maintenance_job_position_legal_relation_mode($legalId);
        $scaleId = trim((string) ($data['civil_service_scale_id'] ?? ''));
        $subscaleId = trim((string) ($data['civil_service_subscale_id'] ?? ''));
        $classId = trim((string) ($data['civil_service_class_id'] ?? ''));
        $categoryId = trim((string) ($data['civil_service_category_id'] ?? ''));
        $laborCat = trim((string) ($data['labor_category'] ?? ''));
        if ($mode === 'civil') {
            $laborCat = '';
            if ($scaleId === '') {
                $errors['civil_service_scale_id'] = 'L’escala és obligatòria per a aquesta relació jurídica.';
            }
            if ($subscaleId === '') {
                $errors['civil_service_subscale_id'] = 'La subescala és obligatòria.';
            }
            if ($classId === '') {
                $errors['civil_service_class_id'] = 'La classe és obligatòria.';
            }
            if ($categoryId === '') {
                $errors['civil_service_category_id'] = 'La categoria és obligatòria.';
            }
        } elseif ($mode === 'labor') {
            $scaleId = $subscaleId = $classId = $categoryId = '';
            if ($laborCat === '') {
                $errors['labor_category'] = 'La categoria laboral és obligatòria per a aquesta relació jurídica.';
            }
        } else {
            $scaleId = $subscaleId = $classId = $categoryId = '';
            $laborCat = '';
        }
        $orgDepPick = null_if_empty((string) ($data['org_dependency_id'] ?? ''));
        if ($orgDepPick !== null) {
            $stOd = $db->prepare("SELECT 1 FROM job_positions WHERE catalog_year = :y AND job_position_id = :jid AND UPPER(TRIM(job_type_id)) = 'CM' LIMIT 1");
            $stOd->execute(['y' => $year, 'jid' => $orgDepPick]);
            if (!$stOd->fetch()) {
                $errors['org_dependency_id'] = 'El responsable ha de ser un lloc de comandament (CM).';
            }
        }
        $createdAt = maintenance_parse_optional_date_input((string) ($data['created_at'] ?? ''), 'creació');
        if (!$createdAt['ok']) {
            $errors['created_at'] = $createdAt['error'];
        }
        $deletedAt = maintenance_parse_optional_date_input((string) ($data['deleted_at'] ?? ''), 'baixa');
        if (!$deletedAt['ok']) {
            $errors['deleted_at'] = $deletedAt['error'];
        }
        $specIdPick = trim((string) ($data['special_specific_compensation_id'] ?? ''));
        $specAmt = ['ok' => true, 'value' => null];
        if ($specIdPick !== '') {
            $stSpecRow = $db->prepare('SELECT amount FROM specific_compensation_special WHERE catalog_year = :y AND special_specific_compensation_id = :sid LIMIT 1');
            $stSpecRow->execute(['y' => $year, 'sid' => (int) $specIdPick]);
            $specRow = $stSpecRow->fetch();
            if (!$specRow) {
                $errors['special_specific_compensation_id'] = 'El complement específic especial no existeix.';
            } else {
                $rawAm = $specRow['amount'] ?? null;
                $specAmt['value'] = ($rawAm !== null && $rawAm !== '' && is_numeric($rawAm)) ? (string) $rawAm : null;
            }
        }
        $jobEvalRaw = trim((string) ($data['job_evaluation'] ?? ''));
        $jobEval = null;
        if ($jobEvalRaw !== '') {
            if (!preg_match('/^-?\d+$/', $jobEvalRaw)) {
                $errors['job_evaluation'] = 'La valoració ha de ser un enter.';
            } else {
                $jobEval = (int) $jobEvalRaw;
            }
        }
        if ($mode === 'civil' && $errors === []) {
            $stSub = $db->prepare('SELECT 1 FROM civil_service_subscales WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid LIMIT 1');
            $stSub->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId]);
            if (!$stSub->fetch()) {
                $errors['civil_service_subscale_id'] = 'La subescala no correspon a l’escala.';
            }
            $stCls = $db->prepare('SELECT 1 FROM civil_service_classes WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid AND class_id=:cid LIMIT 1');
            $stCls->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId, 'cid' => (int) $classId]);
            if (!$stCls->fetch()) {
                $errors['civil_service_class_id'] = 'La classe no correspon a escala/subescala.';
            }
            $stCat = $db->prepare('SELECT 1 FROM civil_service_categories WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid AND class_id=:cid AND category_id=:cat LIMIT 1');
            $stCat->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId, 'cid' => (int) $classId, 'cat' => (int) $categoryId]);
            if (!$stCat->fetch()) {
                $errors['civil_service_category_id'] = 'La categoria no correspon a la classe seleccionada.';
            }
        }
        $apRaw = $data['assigned_person_ids'] ?? [];
        if (!is_array($apRaw)) {
            $apRaw = [];
        }
        $wantedPersonIds = [];
        foreach ($apRaw as $item) {
            $ps = is_array($item) ? trim((string) ($item['person_id'] ?? '')) : trim((string) $item);
            if ($ps === '') {
                continue;
            }
            if (!preg_match('/^\d+$/', $ps)) {
                $errors['assigned_people'] = 'Identificadors de persona invàlids.';
                break;
            }
            $wantedPersonIds[(int) $ps] = true;
        }
        $wantedList = array_keys($wantedPersonIds);
        sort($wantedList);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        if (maintenance_id_exists($db, $module, $year, $jid, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['job_position_id' => 'Ja existeix aquest codi de lloc en aquest exercici.'], JSON_THROW_ON_ERROR));
        }
        foreach ($wantedList as $wpid) {
            $stPe = $db->prepare('SELECT 1 FROM people WHERE catalog_year=:y AND person_id=:pid LIMIT 1');
            $stPe->execute(['y' => $year, 'pid' => $wpid]);
            if (!$stPe->fetch()) {
                throw new InvalidArgumentException(json_encode(['assigned_people' => 'Hi ha persones inexistents a la llista d’ocupants.'], JSON_THROW_ON_ERROR));
            }
        }
        $payload = [
            'y' => $year,
            'jid' => $jid,
            'org_unit_level_3_id' => null_if_empty((string) ($data['org_unit_level_3_id'] ?? '')),
            'job_number' => null_if_empty((string) ($data['job_number'] ?? '')),
            'job_title' => $jobTitle,
            'org_dependency_id' => null_if_empty((string) ($data['org_dependency_id'] ?? '')),
            'contribution_epigraph_id' => null_if_empty((string) ($data['contribution_epigraph_id'] ?? '')),
            'contribution_group_id' => null_if_empty((string) ($data['contribution_group_id'] ?? '')),
            'legal_relation_id' => $legalId,
            'civil_service_scale_id' => $scaleId !== '' ? (int) $scaleId : null,
            'civil_service_subscale_id' => $subscaleId !== '' ? (int) $subscaleId : null,
            'civil_service_class_id' => $classId !== '' ? (int) $classId : null,
            'civil_service_category_id' => $categoryId !== '' ? (int) $categoryId : null,
            'labor_category' => $laborCat !== '' ? $laborCat : null,
            'job_type_id' => null_if_empty((string) ($data['job_type_id'] ?? '')),
            'classification_group' => null_if_empty((string) ($data['classification_group'] ?? '')),
            'classification_group_new' => null_if_empty((string) ($data['classification_group_new'] ?? '')),
            'organic_level' => null_if_empty((string) ($data['organic_level'] ?? '')),
            'general_specific_compensation_id' => ($v = trim((string) ($data['general_specific_compensation_id'] ?? ''))) !== '' ? (int) $v : null,
            'special_specific_compensation_id' => ($v = trim((string) ($data['special_specific_compensation_id'] ?? ''))) !== '' ? (int) $v : null,
            'special_specific_compensation_amount' => $specAmt['value'],
            'notes' => null_if_empty((string) ($data['notes'] ?? '')),
            'catalog_code' => $catalogCode !== '' ? $catalogCode : null,
            'workday_type' => null_if_empty((string) ($data['workday_type'] ?? '')),
            'working_time_dedication' => null_if_empty((string) ($data['working_time_dedication'] ?? '')),
            'schedule_text' => null_if_empty((string) ($data['schedule_text'] ?? '')),
            'has_night_schedule' => !empty($data['has_night_schedule']) ? 1 : 0,
            'has_holiday_schedule' => !empty($data['has_holiday_schedule']) ? 1 : 0,
            'has_shift_schedule' => !empty($data['has_shift_schedule']) ? 1 : 0,
            'has_special_dedication' => !empty($data['has_special_dedication']) ? 1 : 0,
            'special_dedication_type' => null_if_empty((string) ($data['special_dedication_type'] ?? '')),
            'availability_id' => null_if_empty((string) ($data['availability_id'] ?? '')),
            'mission' => null_if_empty((string) ($data['mission'] ?? '')),
            'generic_functions' => null_if_empty((string) ($data['generic_functions'] ?? '')),
            'specific_functions' => null_if_empty((string) ($data['specific_functions'] ?? '')),
            'qualification_requirements' => null_if_empty((string) ($data['qualification_requirements'] ?? '')),
            'other_requirements' => null_if_empty((string) ($data['other_requirements'] ?? '')),
            'training_requirements' => null_if_empty((string) ($data['training_requirements'] ?? '')),
            'experience_requirements' => null_if_empty((string) ($data['experience_requirements'] ?? '')),
            'other_merits' => null_if_empty((string) ($data['other_merits'] ?? '')),
            'provision_method_id' => null_if_empty((string) ($data['provision_method_id'] ?? '')),
            'effort' => null_if_empty((string) ($data['effort'] ?? '')),
            'hardship' => null_if_empty((string) ($data['hardship'] ?? '')),
            'danger' => null_if_empty((string) ($data['danger'] ?? '')),
            'incompatibilities' => null_if_empty((string) ($data['incompatibilities'] ?? '')),
            'provincial_notes' => null_if_empty((string) ($data['provincial_notes'] ?? '')),
            'work_center_id' => ($v = trim((string) ($data['work_center_id'] ?? ''))) !== '' ? (int) $v : null,
            'created_at' => $createdAt['value'],
            'creation_reason' => null_if_empty((string) ($data['creation_reason'] ?? '')),
            'creation_file_reference' => null_if_empty((string) ($data['creation_file_reference'] ?? '')),
            'deleted_at' => $deletedAt['value'],
            'deletion_reason' => null_if_empty((string) ($data['deletion_reason'] ?? '')),
            'deletion_file_reference' => null_if_empty((string) ($data['deletion_file_reference'] ?? '')),
            'job_evaluation' => $jobEval,
            'is_to_be_amortized' => !empty($data['is_to_be_amortized']) ? 1 : 0,
            'classification_group_slash' => null_if_empty((string) ($data['classification_group_slash'] ?? '')),
        ];
        $db->beginTransaction();
        try {
            if ($originalIdText === null || $originalIdText === '') {
                $st = $db->prepare('INSERT INTO job_positions (
                    catalog_year, job_position_id, org_unit_level_3_id, job_number, job_title, org_dependency_id,
                    contribution_epigraph_id, contribution_group_id, legal_relation_id,
                    civil_service_scale_id, civil_service_subscale_id, civil_service_class_id, civil_service_category_id,
                    labor_category, job_type_id, classification_group, classification_group_new, organic_level,
                    general_specific_compensation_id, special_specific_compensation_id, special_specific_compensation_amount,
                    notes, catalog_code, workday_type, working_time_dedication, schedule_text,
                    has_night_schedule, has_holiday_schedule, has_shift_schedule, has_special_dedication,
                    special_dedication_type, availability_id, mission, generic_functions, specific_functions,
                    qualification_requirements, other_requirements, training_requirements, experience_requirements, other_merits,
                    provision_method_id, effort, hardship, danger, incompatibilities, provincial_notes, work_center_id,
                    created_at, creation_reason, creation_file_reference, deleted_at, deletion_reason, deletion_file_reference,
                    job_evaluation, is_to_be_amortized, classification_group_slash
                ) VALUES (
                    :y, :jid, :org_unit_level_3_id, :job_number, :job_title, :org_dependency_id,
                    :contribution_epigraph_id, :contribution_group_id, :legal_relation_id,
                    :civil_service_scale_id, :civil_service_subscale_id, :civil_service_class_id, :civil_service_category_id,
                    :labor_category, :job_type_id, :classification_group, :classification_group_new, :organic_level,
                    :general_specific_compensation_id, :special_specific_compensation_id, :special_specific_compensation_amount,
                    :notes, :catalog_code, :workday_type, :working_time_dedication, :schedule_text,
                    :has_night_schedule, :has_holiday_schedule, :has_shift_schedule, :has_special_dedication,
                    :special_dedication_type, :availability_id, :mission, :generic_functions, :specific_functions,
                    :qualification_requirements, :other_requirements, :training_requirements, :experience_requirements, :other_merits,
                    :provision_method_id, :effort, :hardship, :danger, :incompatibilities, :provincial_notes, :work_center_id,
                    :created_at, :creation_reason, :creation_file_reference, :deleted_at, :deletion_reason, :deletion_file_reference,
                    :job_evaluation, :is_to_be_amortized, :classification_group_slash
                )');
                $st->execute($payload);
            } else {
                $payload['orig_jid'] = $originalIdText;
                $st = $db->prepare('UPDATE job_positions SET
                    job_position_id=:jid, org_unit_level_3_id=:org_unit_level_3_id, job_number=:job_number, job_title=:job_title, org_dependency_id=:org_dependency_id,
                    contribution_epigraph_id=:contribution_epigraph_id, contribution_group_id=:contribution_group_id, legal_relation_id=:legal_relation_id,
                    civil_service_scale_id=:civil_service_scale_id, civil_service_subscale_id=:civil_service_subscale_id, civil_service_class_id=:civil_service_class_id, civil_service_category_id=:civil_service_category_id,
                    labor_category=:labor_category, job_type_id=:job_type_id, classification_group=:classification_group, classification_group_new=:classification_group_new, organic_level=:organic_level,
                    general_specific_compensation_id=:general_specific_compensation_id, special_specific_compensation_id=:special_specific_compensation_id, special_specific_compensation_amount=:special_specific_compensation_amount,
                    notes=:notes, catalog_code=:catalog_code, workday_type=:workday_type, working_time_dedication=:working_time_dedication, schedule_text=:schedule_text,
                    has_night_schedule=:has_night_schedule, has_holiday_schedule=:has_holiday_schedule, has_shift_schedule=:has_shift_schedule, has_special_dedication=:has_special_dedication,
                    special_dedication_type=:special_dedication_type, availability_id=:availability_id, mission=:mission, generic_functions=:generic_functions, specific_functions=:specific_functions,
                    qualification_requirements=:qualification_requirements, other_requirements=:other_requirements, training_requirements=:training_requirements, experience_requirements=:experience_requirements, other_merits=:other_merits,
                    provision_method_id=:provision_method_id, effort=:effort, hardship=:hardship, danger=:danger, incompatibilities=:incompatibilities, provincial_notes=:provincial_notes, work_center_id=:work_center_id,
                    created_at=:created_at, creation_reason=:creation_reason, creation_file_reference=:creation_file_reference, deleted_at=:deleted_at, deletion_reason=:deletion_reason, deletion_file_reference=:deletion_file_reference,
                    job_evaluation=:job_evaluation, is_to_be_amortized=:is_to_be_amortized, classification_group_slash=:classification_group_slash
                    WHERE catalog_year=:y AND job_position_id=:orig_jid');
                $st->execute($payload);
            }
            $stRel = $db->prepare('SELECT person_id FROM people WHERE catalog_year=:y AND job_position_id=:jid');
            $stRel->execute(['y' => $year, 'jid' => $jid]);
            $currentAssigned = [];
            foreach ($stRel->fetchAll(PDO::FETCH_COLUMN) ?: [] as $cp) {
                $currentAssigned[(int) $cp] = true;
            }
            $wantedSet = array_fill_keys($wantedList, true);
            foreach (array_keys($currentAssigned) as $cpid) {
                if (!isset($wantedSet[$cpid])) {
                    $stClr = $db->prepare('UPDATE people SET job_position_id=NULL WHERE catalog_year=:y AND person_id=:pid AND job_position_id=:jid');
                    $stClr->execute(['y' => $year, 'pid' => $cpid, 'jid' => $jid]);
                }
            }
            foreach ($wantedList as $wpid) {
                $stAs = $db->prepare('UPDATE people SET job_position_id=:jid WHERE catalog_year=:y AND person_id=:pid');
                $stAs->execute(['y' => $year, 'jid' => $jid, 'pid' => $wpid]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return;
    }
    if ($module === 'people') {
        $personIdRaw = trim((string) ($data['id'] ?? ''));
        $errors = [];
        if ($personIdRaw === '' || !preg_match('/^\d{1,5}$/', $personIdRaw)) {
            $errors['id'] = 'El codi és obligatori, numèric i de màxim 5 dígits.';
        }
        $lastName1 = trim((string) ($data['last_name_1'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        if ($lastName1 === '') $errors['last_name_1'] = 'El primer cognom és obligatori.';
        if ($firstName === '') $errors['first_name'] = 'El nom és obligatori.';
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'adreça electrònica no és vàlida.';
        }
        $birthDate = maintenance_parse_optional_date_input((string) ($data['birth_date'] ?? ''), 'naixement');
        if (!$birthDate['ok']) $errors['birth_date'] = $birthDate['error'];
        $hiredAt = maintenance_parse_optional_date_input((string) ($data['hired_at'] ?? ''), 'alta');
        if (!$hiredAt['ok']) $errors['hired_at'] = $hiredAt['error'];
        $terminatedAt = maintenance_parse_optional_date_input((string) ($data['terminated_at'] ?? ''), 'baixa');
        if (!$terminatedAt['ok']) $errors['terminated_at'] = $terminatedAt['error'];
        $dedication = maintenance_parse_optional_visual_percent_to_fraction_0_1((string) ($data['dedication'] ?? ''));
        $budgetedAmount = maintenance_parse_optional_visual_percent_to_fraction_0_1((string) ($data['budgeted_amount'] ?? ''));
        $ssCoeff = maintenance_parse_optional_visual_percent_to_fraction_0_1((string) ($data['social_security_contribution_coefficient'] ?? ''));
        if (!$dedication['ok']) $errors['dedication'] = $dedication['error'];
        if (!$budgetedAmount['ok']) $errors['budgeted_amount'] = $budgetedAmount['error'];
        if (!$ssCoeff['ok']) $errors['social_security_contribution_coefficient'] = $ssCoeff['error'];
        $positionId = trim((string) ($data['position_id'] ?? ''));
        if ($positionId !== '') {
            $stPos = $db->prepare('SELECT 1 FROM positions WHERE catalog_year=:y AND position_id=:id LIMIT 1');
            $stPos->execute(['y' => $year, 'id' => (int) $positionId]);
            if (!$stPos->fetch()) $errors['position_id'] = 'La plaça seleccionada no existeix.';
        }
        $legalRelationId = trim((string) ($data['legal_relation_id'] ?? ''));
        if ($legalRelationId !== '') {
            $stLr = $db->prepare('SELECT 1 FROM legal_relations WHERE catalog_year=:y AND legal_relation_id=:id LIMIT 1');
            $stLr->execute(['y' => $year, 'id' => (int) $legalRelationId]);
            if (!$stLr->fetch()) $errors['legal_relation_id'] = 'La relació jurídica no existeix.';
        }
        $adminStatusId = trim((string) ($data['administrative_status_id'] ?? ''));
        if ($adminStatusId !== '') {
            $stAs = $db->prepare('SELECT 1 FROM administrative_statuses WHERE catalog_year=:y AND administrative_status_id=:id LIMIT 1');
            $stAs->execute(['y' => $year, 'id' => (int) $adminStatusId]);
            if (!$stAs->fetch()) $errors['administrative_status_id'] = 'La situació administrativa no existeix.';
        }
        $subprogramPeople = $data['subprogram_people'] ?? [];
        if (!is_array($subprogramPeople)) $subprogramPeople = [];
        $normalizedSubprogramPeople = [];
        foreach ($subprogramPeople as $idx => $sp) {
            if (!is_array($sp)) continue;
            $spId = trim((string) ($sp['subprogram_id'] ?? ''));
            $spDedRaw = trim((string) ($sp['dedication'] ?? ''));
            if ($spId === '' && $spDedRaw === '') continue;
            if ($spId === '') {
                $errors['subprogram_people'] = 'Cal indicar subprograma a totes les files informades.';
                break;
            }
            $spDed = maintenance_parse_optional_visual_percent_0_100($spDedRaw);
            if (!$spDed['ok'] || $spDed['value'] === null) {
                $errors['subprogram_people'] = 'La dedicació dels subprogrames ha d\'estar entre 0 i 100.';
                break;
            }
            $stSp = $db->prepare('SELECT 1 FROM subprograms WHERE catalog_year=:y AND subprogram_id=:sid LIMIT 1');
            $stSp->execute(['y' => $year, 'sid' => $spId]);
            if (!$stSp->fetch()) {
                $errors['subprogram_people'] = 'Hi ha subprogrames inexistents.';
                break;
            }
            $normalizedSubprogramPeople[] = ['subprogram_id' => $spId, 'dedication' => $spDed['value']];
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $productivityBonusParsed = maintenance_parse_optional_money_input((string) ($data['productivity_bonus'] ?? ''));
        if (!$productivityBonusParsed['ok']) {
            throw new InvalidArgumentException(json_encode(['productivity_bonus' => 'Import invàlid (màxim 2 decimals).'], JSON_THROW_ON_ERROR));
        }
        $legacySocialSecurityParsed = maintenance_parse_optional_money_input((string) ($data['legacy_social_security'] ?? ''));
        if (!$legacySocialSecurityParsed['ok']) {
            throw new InvalidArgumentException(json_encode(['legacy_social_security' => 'Import invàlid (màxim 2 decimals).'], JSON_THROW_ON_ERROR));
        }
        $personId = (string) ((int) $personIdRaw);
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if (maintenance_id_exists($db, $module, $year, $personId, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        $terminatedAtValue = (string) ($terminatedAt['value'] ?? '');
        $isActive = $terminatedAtValue === '' ? 1 : 0;
        $parseOptionalFloat = static function ($raw): ?float {
            $s = trim((string) $raw);
            if ($s === '') {
                return null;
            }
            return (float) str_replace(',', '.', $s);
        };
        $a1Pct = $parseOptionalFloat($data['group_a1_current_year_percentage'] ?? '');
        $a2Pct = $parseOptionalFloat($data['group_a2_current_year_percentage'] ?? '');
        $c1Pct = $parseOptionalFloat($data['group_c1_current_year_percentage'] ?? '');
        $c2Pct = $parseOptionalFloat($data['group_c2_current_year_percentage'] ?? '');
        $ePct = $parseOptionalFloat($data['group_e_current_year_percentage'] ?? '');
        $payload = [
            'y' => $year,
            'id' => (int) $personId,
            'legacy_person_id' => ($v = trim((string) ($data['legacy_person_id'] ?? ''))) !== '' ? (int) $v : null,
            'last_name_1' => $lastName1,
            'last_name_2' => null_if_empty((string) ($data['last_name_2'] ?? '')),
            'first_name' => $firstName,
            'birth_date' => $birthDate['value'],
            'national_id_number' => null_if_empty((string) ($data['national_id_number'] ?? '')),
            'social_security_number' => null_if_empty((string) ($data['social_security_number'] ?? '')),
            'job_position_id' => null_if_empty((string) ($data['job_position_id'] ?? '')),
            'position_id' => $positionId !== '' ? (int) $positionId : null,
            'dedication' => $dedication['value'],
            'budgeted_amount' => $budgetedAmount['value'],
            'legal_relation_id' => $legalRelationId !== '' ? (int) $legalRelationId : null,
            'administrative_status_id' => $adminStatusId !== '' ? (int) $adminStatusId : null,
            'status_text' => null_if_empty((string) ($data['status_text'] ?? '')),
            'company_id' => null_if_empty((string) ($data['company_id'] ?? '')),
            'social_security_contribution_coefficient' => $ssCoeff['value'],
            'productivity_bonus' => $productivityBonusParsed['value'],
            'seniority_amount' => ($v = trim((string) ($data['seniority_amount'] ?? ''))) !== '' ? (float) str_replace(',', '.', $v) : null,
            'seniority_extra_pay_amount' => ($v = trim((string) ($data['seniority_extra_pay_amount'] ?? ''))) !== '' ? (float) str_replace(',', '.', $v) : null,
            'annual_budgeted_seniority' => ($v = trim((string) ($data['annual_budgeted_seniority'] ?? ''))) !== '' ? (float) str_replace(',', '.', $v) : null,
            'personal_transitory_bonus' => ($v = trim((string) ($data['personal_transitory_bonus'] ?? ''))) !== '' ? (float) str_replace(',', '.', $v) : null,
            'legacy_social_security' => $legacySocialSecurityParsed['value'],
            'hired_at' => $hiredAt['value'],
            'terminated_at' => $terminatedAt['value'],
            'notes' => null_if_empty((string) ($data['notes'] ?? '')),
            'personal_grade' => null_if_empty((string) ($data['personal_grade'] ?? '')),
            'group_a1_previous_triennia' => ($v = trim((string) ($data['group_a1_previous_triennia'] ?? ''))) !== '' ? (int) $v : null,
            'group_a1_current_year_triennia' => ($a1Pct !== null && $a1Pct > 0) ? 1 : null,
            'group_a1_current_year_percentage' => $a1Pct,
            'group_a2_previous_triennia' => ($v = trim((string) ($data['group_a2_previous_triennia'] ?? ''))) !== '' ? (int) $v : null,
            'group_a2_current_year_triennia' => ($a2Pct !== null && $a2Pct > 0) ? 1 : null,
            'group_a2_current_year_percentage' => $a2Pct,
            'group_c1_previous_triennia' => ($v = trim((string) ($data['group_c1_previous_triennia'] ?? ''))) !== '' ? (int) $v : null,
            'group_c1_current_year_triennia' => ($c1Pct !== null && $c1Pct > 0) ? 1 : null,
            'group_c1_current_year_percentage' => $c1Pct,
            'group_c2_previous_triennia' => ($v = trim((string) ($data['group_c2_previous_triennia'] ?? ''))) !== '' ? (int) $v : null,
            'group_c2_current_year_triennia' => ($c2Pct !== null && $c2Pct > 0) ? 1 : null,
            'group_c2_current_year_percentage' => $c2Pct,
            'group_e_previous_triennia' => ($v = trim((string) ($data['group_e_previous_triennia'] ?? ''))) !== '' ? (int) $v : null,
            'group_e_current_year_triennia' => ($ePct !== null && $ePct > 0) ? 1 : null,
            'group_e_current_year_percentage' => $ePct,
            'email' => null_if_empty($email),
            'is_active' => $isActive,
        ];
        $db->beginTransaction();
        try {
            if ($originalIdText === null || $originalIdText === '') {
                $st = $db->prepare('INSERT INTO people (
                    catalog_year, person_id, legacy_person_id, last_name_1, last_name_2, first_name, is_active, birth_date, national_id_number, social_security_number,
                    job_position_id, position_id, dedication, budgeted_amount, legal_relation_id, administrative_status_id, status_text, company_id,
                    social_security_contribution_coefficient, productivity_bonus, seniority_amount, seniority_extra_pay_amount, annual_budgeted_seniority,
                    personal_transitory_bonus, legacy_social_security, hired_at, terminated_at, notes, personal_grade,
                    group_a1_previous_triennia, group_a1_current_year_triennia, group_a1_current_year_percentage,
                    group_a2_previous_triennia, group_a2_current_year_triennia, group_a2_current_year_percentage,
                    group_c1_previous_triennia, group_c1_current_year_triennia, group_c1_current_year_percentage,
                    group_c2_previous_triennia, group_c2_current_year_triennia, group_c2_current_year_percentage,
                    group_e_previous_triennia, group_e_current_year_triennia, group_e_current_year_percentage,
                    email
                ) VALUES (
                    :y, :id, :legacy_person_id, :last_name_1, :last_name_2, :first_name, :is_active, :birth_date, :national_id_number, :social_security_number,
                    :job_position_id, :position_id, :dedication, :budgeted_amount, :legal_relation_id, :administrative_status_id, :status_text, :company_id,
                    :social_security_contribution_coefficient, :productivity_bonus, :seniority_amount, :seniority_extra_pay_amount, :annual_budgeted_seniority,
                    :personal_transitory_bonus, :legacy_social_security, :hired_at, :terminated_at, :notes, :personal_grade,
                    :group_a1_previous_triennia, :group_a1_current_year_triennia, :group_a1_current_year_percentage,
                    :group_a2_previous_triennia, :group_a2_current_year_triennia, :group_a2_current_year_percentage,
                    :group_c1_previous_triennia, :group_c1_current_year_triennia, :group_c1_current_year_percentage,
                    :group_c2_previous_triennia, :group_c2_current_year_triennia, :group_c2_current_year_percentage,
                    :group_e_previous_triennia, :group_e_current_year_triennia, :group_e_current_year_percentage,
                    :email
                )');
                $st->execute($payload);
            } else {
                $payload['original_id'] = (int) $originalIdText;
                $st = $db->prepare('UPDATE people SET
                    person_id=:id, legacy_person_id=:legacy_person_id, last_name_1=:last_name_1, last_name_2=:last_name_2, first_name=:first_name, is_active=:is_active, birth_date=:birth_date, national_id_number=:national_id_number, social_security_number=:social_security_number,
                    job_position_id=:job_position_id, position_id=:position_id, dedication=:dedication, budgeted_amount=:budgeted_amount, legal_relation_id=:legal_relation_id, administrative_status_id=:administrative_status_id, status_text=:status_text, company_id=:company_id,
                    social_security_contribution_coefficient=:social_security_contribution_coefficient, productivity_bonus=:productivity_bonus, seniority_amount=:seniority_amount, seniority_extra_pay_amount=:seniority_extra_pay_amount, annual_budgeted_seniority=:annual_budgeted_seniority,
                    personal_transitory_bonus=:personal_transitory_bonus, legacy_social_security=:legacy_social_security, hired_at=:hired_at, terminated_at=:terminated_at, notes=:notes, personal_grade=:personal_grade,
                    group_a1_previous_triennia=:group_a1_previous_triennia, group_a1_current_year_triennia=:group_a1_current_year_triennia, group_a1_current_year_percentage=:group_a1_current_year_percentage,
                    group_a2_previous_triennia=:group_a2_previous_triennia, group_a2_current_year_triennia=:group_a2_current_year_triennia, group_a2_current_year_percentage=:group_a2_current_year_percentage,
                    group_c1_previous_triennia=:group_c1_previous_triennia, group_c1_current_year_triennia=:group_c1_current_year_triennia, group_c1_current_year_percentage=:group_c1_current_year_percentage,
                    group_c2_previous_triennia=:group_c2_previous_triennia, group_c2_current_year_triennia=:group_c2_current_year_triennia, group_c2_current_year_percentage=:group_c2_current_year_percentage,
                    group_e_previous_triennia=:group_e_previous_triennia, group_e_current_year_triennia=:group_e_current_year_triennia, group_e_current_year_percentage=:group_e_current_year_percentage,
                    email=:email
                    WHERE catalog_year=:y AND person_id=:original_id');
                $st->execute($payload);
                $stDelOld = $db->prepare('DELETE FROM subprogram_people WHERE catalog_year=:y AND person_id=:old_id');
                $stDelOld->execute(['y' => $year, 'old_id' => (int) $originalIdText]);
            }
            $stDel = $db->prepare('DELETE FROM subprogram_people WHERE catalog_year=:y AND person_id=:pid');
            $stDel->execute(['y' => $year, 'pid' => (int) $personId]);
            if ($normalizedSubprogramPeople !== []) {
                $stInsSp = $db->prepare('INSERT INTO subprogram_people (catalog_year, subprogram_id, person_id, dedication, legacy_person_id)
                    VALUES (:y, :sid, :pid, :ded, :legacy)');
                foreach ($normalizedSubprogramPeople as $sp) {
                    $stInsSp->execute([
                        'y' => $year,
                        'sid' => $sp['subprogram_id'],
                        'pid' => (int) $personId,
                        'ded' => (float) $sp['dedication'],
                        'legacy' => ($v = trim((string) ($data['legacy_person_id'] ?? ''))) !== '' ? (int) $v : null,
                    ]);
                }
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        return;
    }
    if ($module === 'management_positions') {
        $positionIdRaw = trim((string) ($data['id'] ?? ''));
        $positionName = trim((string) (($data['position_name'] ?? '') !== '' ? $data['position_name'] : ($data['name'] ?? '')));
        $positionClassId = (int) ($data['position_class_id'] ?? 0);
        $scaleId = trim((string) ($data['scale_id'] ?? ''));
        $subscaleId = trim((string) ($data['subscale_id'] ?? ''));
        $classId = trim((string) ($data['class_id'] ?? ''));
        $categoryId = trim((string) ($data['category_id'] ?? ''));
        $laborCategory = trim((string) ($data['labor_category'] ?? ''));
        $deletedAtRaw = trim((string) ($data['deleted_at'] ?? ''));
        $errors = [];
        if ($positionIdRaw === '' || !preg_match('/^\d{1,4}$/', $positionIdRaw)) {
            $errors['id'] = 'El codi és obligatori, numèric i de màxim 4 dígits.';
        }
        if ($positionName === '') {
            $errors['name'] = 'La denominació és obligatòria.';
        }
        if ($positionClassId < 1) {
            $errors['position_class_id'] = 'La classe de plaça és obligatòria.';
        }
        if ($positionClassId === 1) {
            if ($scaleId === '') $errors['scale_id'] = 'L’escala és obligatòria.';
            if ($subscaleId === '') $errors['subscale_id'] = 'La subescala és obligatòria.';
            if ($classId === '') $errors['class_id'] = 'La classe és obligatòria.';
            if ($categoryId === '') $errors['category_id'] = 'La categoria és obligatòria.';
            $laborCategory = '';
        } elseif ($positionClassId === 2) {
            if ($laborCategory === '') $errors['labor_category'] = 'La categoria laboral és obligatòria.';
            $scaleId = $subscaleId = $classId = $categoryId = '';
        } else {
            $scaleId = $subscaleId = $classId = $categoryId = '';
            $laborCategory = '';
        }
        $createdAtParsed = maintenance_parse_optional_date_input((string) ($data['created_at'] ?? ''), 'creació');
        if (!$createdAtParsed['ok']) $errors['created_at'] = $createdAtParsed['error'];
        $callDateParsed = maintenance_parse_optional_date_input((string) ($data['call_for_applications_date'] ?? ''), 'convocatòria');
        if (!$callDateParsed['ok']) $errors['call_for_applications_date'] = $callDateParsed['error'];
        $deletedAtParsed = maintenance_parse_optional_date_input($deletedAtRaw, 'baixa');
        if (!$deletedAtParsed['ok']) $errors['deleted_at'] = $deletedAtParsed['error'];
        $budgetedParsed = maintenance_parse_optional_visual_percent_to_fraction((string) ($data['budgeted_amount'] ?? ''));
        if (!$budgetedParsed['ok']) $errors['budgeted_amount'] = $budgetedParsed['error'];
        if ($budgetedParsed['ok'] && $budgetedParsed['value'] === null) {
            $errors['budgeted_amount'] = 'El camp Pressupostat és obligatori.';
        } elseif ($budgetedParsed['ok'] && $budgetedParsed['value'] !== null) {
            $bv = (float) $budgetedParsed['value'];
            if (!is_finite($bv) || $bv < 0.0 || $bv > 1.0) {
                $errors['budgeted_amount'] = 'El percentatge ha d\'estar entre 0 i 100.';
            }
        }
        if ($positionClassId > 0) {
            $stPc = $db->prepare('SELECT 1 FROM position_classes WHERE catalog_year=:y AND position_class_id=:id LIMIT 1');
            $stPc->execute(['y' => $year, 'id' => $positionClassId]);
            if (!$stPc->fetch()) $errors['position_class_id'] = 'La classe de plaça no existeix.';
        }
        if ($positionClassId === 1 && $errors === []) {
            $stSub = $db->prepare('SELECT 1 FROM civil_service_subscales WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid LIMIT 1');
            $stSub->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId]);
            if (!$stSub->fetch()) $errors['subscale_id'] = 'La subescala no correspon a l’escala.';
            $stCls = $db->prepare('SELECT 1 FROM civil_service_classes WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid AND class_id=:cid LIMIT 1');
            $stCls->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId, 'cid' => (int) $classId]);
            if (!$stCls->fetch()) $errors['class_id'] = 'La classe no correspon a escala/subescala.';
            $stCat = $db->prepare('SELECT 1 FROM civil_service_categories WHERE catalog_year=:y AND scale_id=:sc AND subscale_id=:sid AND class_id=:cid AND category_id=:cat LIMIT 1');
            $stCat->execute(['y' => $year, 'sc' => (int) $scaleId, 'sid' => (int) $subscaleId, 'cid' => (int) $classId, 'cat' => (int) $categoryId]);
            if (!$stCat->fetch()) $errors['category_id'] = 'La categoria no correspon a escala/subescala/classe.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
        $positionId = (string) ((int) $positionIdRaw);
        $deletedAt = (string) ($deletedAtParsed['value'] ?? '');
        $isActive = $deletedAt === '' ? 1 : 0;
        $originalIdText = $originalId !== null ? trim((string) $originalId) : null;
        if (maintenance_id_exists($db, $module, $year, $positionId, $originalIdText)) {
            throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
        }
        $payload = [
            'y' => $year,
            'id' => (int) $positionId,
            'name' => $positionName,
            'position_class_id' => $positionClassId,
            'scale_id' => $scaleId !== '' ? (int) $scaleId : null,
            'subscale_id' => $subscaleId !== '' ? (int) $subscaleId : null,
            'class_id' => $classId !== '' ? (int) $classId : null,
            'category_id' => $categoryId !== '' ? (int) $categoryId : null,
            'labor_category' => $laborCategory !== '' ? $laborCategory : null,
            'classification_group' => null_if_empty((string) ($data['classification_group'] ?? '')),
            'access_type_id' => ($v = trim((string) ($data['access_type_id'] ?? ''))) !== '' ? (int) $v : null,
            'access_system_id' => ($v = trim((string) ($data['access_system_id'] ?? ''))) !== '' ? (int) $v : null,
            'budgeted_amount' => $budgetedParsed['value'],
            'is_offerable' => !empty($data['is_offerable']) ? 1 : 0,
            'opo_year' => ($v = trim((string) ($data['opo_year'] ?? ''))) !== '' ? (int) $v : null,
            'is_to_be_amortized' => !empty($data['is_to_be_amortized']) ? 1 : 0,
            'is_internal_promotion' => !empty($data['is_internal_promotion']) ? 1 : 0,
            'created_at' => $createdAtParsed['value'],
            'creation_file_reference' => null_if_empty((string) ($data['creation_file_reference'] ?? '')),
            'call_for_applications_date' => $callDateParsed['value'],
            'deleted_at' => $deletedAt !== '' ? $deletedAt : null,
            'deletion_file_reference' => null_if_empty((string) ($data['deletion_file_reference'] ?? '')),
            'notes' => null_if_empty((string) ($data['notes'] ?? '')),
            'is_active' => $isActive,
        ];
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO positions
                (catalog_year, position_id, position_name, position_class_id, scale_id, subscale_id, class_id, category_id, labor_category, classification_group, access_type_id, access_system_id, budgeted_amount, is_offerable, opo_year, is_to_be_amortized, is_internal_promotion, created_at, creation_file_reference, call_for_applications_date, deleted_at, deletion_file_reference, notes, is_active)
                VALUES
                (:y,:id,:name,:position_class_id,:scale_id,:subscale_id,:class_id,:category_id,:labor_category,:classification_group,:access_type_id,:access_system_id,:budgeted_amount,:is_offerable,:opo_year,:is_to_be_amortized,:is_internal_promotion,:created_at,:creation_file_reference,:call_for_applications_date,:deleted_at,:deletion_file_reference,:notes,:is_active)');
            $st->execute($payload);
            return;
        }
        $payload['original_id'] = (int) $originalIdText;
        $st = $db->prepare('UPDATE positions SET
            position_id=:id, position_name=:name, position_class_id=:position_class_id, scale_id=:scale_id, subscale_id=:subscale_id, class_id=:class_id, category_id=:category_id, labor_category=:labor_category, classification_group=:classification_group, access_type_id=:access_type_id, access_system_id=:access_system_id, budgeted_amount=:budgeted_amount, is_offerable=:is_offerable, opo_year=:opo_year, is_to_be_amortized=:is_to_be_amortized, is_internal_promotion=:is_internal_promotion, created_at=:created_at, creation_file_reference=:creation_file_reference, call_for_applications_date=:call_for_applications_date, deleted_at=:deleted_at, deletion_file_reference=:deletion_file_reference, notes=:notes, is_active=:is_active
            WHERE catalog_year=:y AND position_id=:original_id');
        $st->execute($payload);
        return;
    }
    $isAvailabilityTypes = ($module === 'maintenance_availability_types');
    $isProvisionForms = ($module === 'maintenance_provision_forms');
    $isAlphaSortModule = $isAvailabilityTypes || $isProvisionForms;
    $organic2ParentId = '';
    $organic3Level2Id = '';
    $idRaw = trim((string) ($data['id'] ?? ''));
    $id = $isAlphaSortModule ? $idRaw : (string) ((int) $idRaw);
    $name = trim((string) ($data['name'] ?? ''));
    $errors = [];
    if ($module === 'maintenance_legal_relationships' && $idRaw !== '') {
        if (!preg_match('/^\d+$/', $idRaw)) {
            $errors['id'] = 'El codi ha de ser numèric.';
        }
    }
    if (!isset($errors['id']) && ($id === '' || (!$isAlphaSortModule && (int) $id < 1))) {
        $errors['id'] = 'El codi és obligatori.';
    }
    if ($name === '') {
        $errors['name'] = 'El nom és obligatori.';
    }
    if ($isAlphaSortModule) {
        $sortOrderRaw = trim((string) ($data['sort_order'] ?? ''));
        if ($sortOrderRaw !== '' && !preg_match('/^-?\d+$/', $sortOrderRaw)) {
            $errors['sort_order'] = 'L’ordre ha de ser un enter.';
        }
    }
    if (in_array($module, ['maintenance_subscales', 'maintenance_categories', 'maintenance_classes'], true)) {
        $scaleId = (int) ($data['scale_id'] ?? 0);
        if ($scaleId < 1) {
            $errors['scale_id'] = 'Cal seleccionar una escala.';
        }
    }
    if ($module === 'maintenance_classes') {
        if ((int) ($data['subscale_id'] ?? 0) < 1) {
            $errors['subscale_id'] = 'Cal seleccionar una subescala.';
        }
        if (!isset($errors['scale_id']) && !isset($errors['subscale_id'])) {
            $st = $db->prepare('SELECT 1 FROM civil_service_subscales WHERE catalog_year=:y AND subscale_id=:sid AND scale_id=:sc LIMIT 1');
            $st->execute(['y' => $year, 'sid' => (int) $data['subscale_id'], 'sc' => (int) $data['scale_id']]);
            if (!$st->fetch()) {
                $errors['subscale_id'] = 'La subescala no correspon a l’escala seleccionada.';
            }
        }
    }
    if ($module === 'maintenance_categories') {
        if ((int) ($data['subscale_id'] ?? 0) < 1) {
            $errors['subscale_id'] = 'Cal seleccionar una subescala.';
        }
        if ((int) ($data['class_id'] ?? 0) < 1) {
            $errors['class_id'] = 'Cal seleccionar una classe.';
        }
        if (!isset($errors['scale_id']) && !isset($errors['subscale_id'])) {
            $st = $db->prepare('SELECT 1 FROM civil_service_subscales WHERE catalog_year=:y AND subscale_id=:sid AND scale_id=:sc LIMIT 1');
            $st->execute(['y' => $year, 'sid' => (int) $data['subscale_id'], 'sc' => (int) $data['scale_id']]);
            if (!$st->fetch()) {
                $errors['subscale_id'] = 'La subescala no correspon a l’escala seleccionada.';
            }
        }
        if (!isset($errors['scale_id']) && !isset($errors['subscale_id']) && !isset($errors['class_id'])) {
            $st = $db->prepare('SELECT 1 FROM civil_service_classes WHERE catalog_year=:y AND class_id=:cid AND scale_id=:sc AND subscale_id=:sid LIMIT 1');
            $st->execute([
                'y' => $year,
                'cid' => (int) $data['class_id'],
                'sc' => (int) $data['scale_id'],
                'sid' => (int) $data['subscale_id'],
            ]);
            if (!$st->fetch()) {
                $errors['class_id'] = 'La classe no correspon amb escala/subescala.';
            }
        }
    }
    if ($module === 'maintenance_organic_level_2') {
        $organic2ParentId = trim((string) ($data['org_unit_level_1_id'] ?? ''));
        if ($organic2ParentId === '') {
            $errors['org_unit_level_1_id'] = 'Cal seleccionar un orgànic de nivell 1.';
        } elseif (!isset($errors['id'])) {
            $st = $db->prepare('SELECT 1 FROM org_units_level_1 WHERE catalog_year = :y AND org_unit_level_1_id = :p LIMIT 1');
            $st->execute(['y' => $year, 'p' => $organic2ParentId]);
            if (!$st->fetch()) {
                $errors['org_unit_level_1_id'] = 'L’orgànic nivell 1 seleccionat no existeix per a aquest any de catàleg.';
            }
        }
    }
    if ($module === 'maintenance_organic_level_3') {
        $organic3Level2Id = trim((string) ($data['org_unit_level_2_id'] ?? ''));
        if ($organic3Level2Id === '') {
            $errors['org_unit_level_2_id'] = 'Cal seleccionar un orgànic de nivell 2.';
        } elseif (!isset($errors['id'])) {
            $st = $db->prepare('SELECT 1 FROM org_units_level_2 WHERE catalog_year = :y AND org_unit_level_2_id = :p LIMIT 1');
            $st->execute(['y' => $year, 'p' => $organic3Level2Id]);
            if (!$st->fetch()) {
                $errors['org_unit_level_2_id'] = 'L’orgànic nivell 2 seleccionat no existeix per a aquest any de catàleg.';
            }
        }
    }
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $originalIdText = $originalId !== null ? (string) $originalId : null;
    if ($isAlphaSortModule) {
        $originalIdText = isset($data['original_id_text']) ? trim((string) $data['original_id_text']) : $originalIdText;
    } elseif ($module === 'maintenance_organic_level_2' || $module === 'maintenance_organic_level_3') {
        $originalIdText = trim((string) ($data['original_id_text'] ?? ''));
        if ($originalIdText === '' && $originalId !== null) {
            $originalIdText = (string) (int) $originalId;
        }
        $originalIdText = $originalIdText !== '' ? $originalIdText : null;
    }
    if (maintenance_id_exists($db, $module, $year, $id, $originalIdText)) {
        throw new InvalidArgumentException(json_encode(['id' => 'Ja existeix aquest codi dins del mateix any de catàleg.'], JSON_THROW_ON_ERROR));
    }

    if ($module === 'maintenance_scales') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO civil_service_scales (catalog_year, scale_id, scale_name, scale_short_name, scale_full_name) VALUES (:y,:id,:name,:s,:f)');
            $st->execute(['y' => $year, 'id' => $id, 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? '')), 'f' => null_if_empty((string) ($data['full_name'] ?? ''))]);
            return;
        }
        $st = $db->prepare('UPDATE civil_service_scales SET scale_id=:new_id, scale_name=:name, scale_short_name=:s, scale_full_name=:f WHERE catalog_year=:y AND scale_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => $id, 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? '')), 'f' => null_if_empty((string) ($data['full_name'] ?? ''))]);
        return;
    }

    if ($module === 'maintenance_subscales') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO civil_service_subscales (catalog_year, subscale_id, scale_id, subscale_name, subscale_short_name) VALUES (:y,:id,:scale,:name,:s)');
            $st->execute(['y' => $year, 'id' => $id, 'scale' => (int) $data['scale_id'], 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? ''))]);
            return;
        }
        $st = $db->prepare('UPDATE civil_service_subscales SET subscale_id=:new_id, scale_id=:scale, subscale_name=:name, subscale_short_name=:s WHERE catalog_year=:y AND subscale_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => $id, 'scale' => (int) $data['scale_id'], 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? ''))]);
        return;
    }

    if ($module === 'maintenance_classes') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO civil_service_classes (catalog_year, class_id, scale_id, subscale_id, class_name, class_short_name) VALUES (:y,:id,:sc,:sid,:name,:short)');
            $st->execute([
                'y' => $year,
                'id' => $id,
                'sc' => (int) $data['scale_id'],
                'sid' => (int) $data['subscale_id'],
                'name' => $name,
                'short' => null_if_empty((string) ($data['short_name'] ?? '')),
            ]);
            return;
        }
        $st = $db->prepare('UPDATE civil_service_classes SET class_id=:new_id, scale_id=:sc, subscale_id=:sid, class_name=:name, class_short_name=:short WHERE catalog_year=:y AND class_id=:id');
        $st->execute([
            'y' => $year,
            'id' => $originalId,
            'new_id' => $id,
            'sc' => (int) $data['scale_id'],
            'sid' => (int) $data['subscale_id'],
            'name' => $name,
            'short' => null_if_empty((string) ($data['short_name'] ?? '')),
        ]);
        return;
    }

    if ($module === 'maintenance_access_types') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO access_types (catalog_year, access_type_id, access_type_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE access_types SET access_type_id=:new_id, access_type_name=:name WHERE catalog_year=:y AND access_type_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_access_systems') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO access_systems (catalog_year, access_system_id, access_system_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE access_systems SET access_system_id=:new_id, access_system_name=:name WHERE catalog_year=:y AND access_system_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_administrative_statuses') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO administrative_statuses (catalog_year, administrative_status_id, administrative_status_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE administrative_statuses SET administrative_status_id=:new_id, administrative_status_name=:name WHERE catalog_year=:y AND administrative_status_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_legal_relationships') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO legal_relations (catalog_year, legal_relation_id, legal_relation_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE legal_relations SET legal_relation_id=:new_id, legal_relation_name=:name WHERE catalog_year=:y AND legal_relation_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_position_classes') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO position_classes (catalog_year, position_class_id, position_class_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE position_classes SET position_class_id=:new_id, position_class_name=:name WHERE catalog_year=:y AND position_class_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_organic_level_1') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO org_units_level_1 (catalog_year, org_unit_level_1_id, org_unit_level_1_name) VALUES (:y,:id,:name)');
            $st->execute(['y' => $year, 'id' => (int) $id, 'name' => $name]);
            return;
        }
        $st = $db->prepare('UPDATE org_units_level_1 SET org_unit_level_1_id=:new_id, org_unit_level_1_name=:name WHERE catalog_year=:y AND org_unit_level_1_id=:id');
        $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => (int) $id, 'name' => $name]);
        return;
    }
    if ($module === 'maintenance_organic_level_2') {
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO org_units_level_2 (catalog_year, org_unit_level_2_id, org_unit_level_2_name, org_unit_level_1_id) VALUES (:y,:id,:name,:p)');
            $st->execute(['y' => $year, 'id' => $id, 'name' => $name, 'p' => $organic2ParentId]);
            return;
        }
        $st = $db->prepare('UPDATE org_units_level_2 SET org_unit_level_2_id=:new_id, org_unit_level_2_name=:name, org_unit_level_1_id=:p WHERE catalog_year=:y AND org_unit_level_2_id=:id');
        $st->execute(['y' => $year, 'id' => $originalIdText, 'new_id' => $id, 'name' => $name, 'p' => $organic2ParentId]);
        return;
    }
    if ($module === 'maintenance_organic_level_3') {
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO org_units_level_3 (catalog_year, org_unit_level_3_id, org_unit_level_3_name, org_unit_level_2_id) VALUES (:y,:id,:name,:p)');
            $st->execute(['y' => $year, 'id' => $id, 'name' => $name, 'p' => $organic3Level2Id]);
            return;
        }
        $st = $db->prepare('UPDATE org_units_level_3 SET org_unit_level_3_id=:new_id, org_unit_level_3_name=:name, org_unit_level_2_id=:p WHERE catalog_year=:y AND org_unit_level_3_id=:id');
        $st->execute(['y' => $year, 'id' => $originalIdText, 'new_id' => $id, 'name' => $name, 'p' => $organic3Level2Id]);
        return;
    }
    if ($module === 'maintenance_work_centers') {
        if ($originalId === null) {
            $st = $db->prepare('INSERT INTO work_centers (catalog_year, work_center_id, work_center_name, address, postal_code, city, phone, fax) VALUES (:y,:id,:name,:address,:postal_code,:city,:phone,:fax)');
            $st->execute([
                'y' => $year,
                'id' => (int) $id,
                'name' => $name,
                'address' => null_if_empty((string) ($data['address'] ?? '')),
                'postal_code' => null_if_empty((string) ($data['postal_code'] ?? '')),
                'city' => null_if_empty((string) ($data['city'] ?? '')),
                'phone' => null_if_empty((string) ($data['phone'] ?? '')),
                'fax' => null_if_empty((string) ($data['fax'] ?? '')),
            ]);
            return;
        }
        $st = $db->prepare('UPDATE work_centers SET work_center_id=:new_id, work_center_name=:name, address=:address, postal_code=:postal_code, city=:city, phone=:phone, fax=:fax WHERE catalog_year=:y AND work_center_id=:id');
        $st->execute([
            'y' => $year,
            'id' => $originalId,
            'new_id' => (int) $id,
            'name' => $name,
            'address' => null_if_empty((string) ($data['address'] ?? '')),
            'postal_code' => null_if_empty((string) ($data['postal_code'] ?? '')),
            'city' => null_if_empty((string) ($data['city'] ?? '')),
            'phone' => null_if_empty((string) ($data['phone'] ?? '')),
            'fax' => null_if_empty((string) ($data['fax'] ?? '')),
        ]);
        return;
    }
    if ($module === 'maintenance_availability_types') {
        $sortOrderRaw = trim((string) ($data['sort_order'] ?? ''));
        $sortOrder = ($sortOrderRaw === '') ? null : (int) $sortOrderRaw;
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO availability_options (catalog_year, availability_id, availability_name, sort_order) VALUES (:y,:id,:name,:sort_order)');
            $st->execute(['y' => $year, 'id' => $id, 'name' => $name, 'sort_order' => $sortOrder]);
            return;
        }
        $st = $db->prepare('UPDATE availability_options SET availability_id=:new_id, availability_name=:name, sort_order=:sort_order WHERE catalog_year=:y AND availability_id=:id');
        $st->execute(['y' => $year, 'id' => $originalIdText, 'new_id' => $id, 'name' => $name, 'sort_order' => $sortOrder]);
        return;
    }
    if ($module === 'maintenance_provision_forms') {
        $sortOrderRaw = trim((string) ($data['sort_order'] ?? ''));
        $sortOrder = ($sortOrderRaw === '') ? null : (int) $sortOrderRaw;
        if ($originalIdText === null || $originalIdText === '') {
            $st = $db->prepare('INSERT INTO provision_methods (catalog_year, provision_method_id, provision_method_name, sort_order) VALUES (:y,:id,:name,:sort_order)');
            $st->execute(['y' => $year, 'id' => $id, 'name' => $name, 'sort_order' => $sortOrder]);
            return;
        }
        $st = $db->prepare('UPDATE provision_methods SET provision_method_id=:new_id, provision_method_name=:name, sort_order=:sort_order WHERE catalog_year=:y AND provision_method_id=:id');
        $st->execute(['y' => $year, 'id' => $originalIdText, 'new_id' => $id, 'name' => $name, 'sort_order' => $sortOrder]);
        return;
    }

    if ($module !== 'maintenance_categories') {
        throw new RuntimeException('Mòdul sense implementació de persistència.');
    }

    if ($originalId === null) {
        $st = $db->prepare('INSERT INTO civil_service_categories (catalog_year, category_id, scale_id, subscale_id, class_id, category_name, category_short_name) VALUES (:y,:id,:scale,:sub,:class,:name,:s)');
        $st->execute(['y' => $year, 'id' => $id, 'scale' => (int) $data['scale_id'], 'sub' => (int) $data['subscale_id'], 'class' => (int) $data['class_id'], 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? ''))]);
        return;
    }
    $st = $db->prepare('UPDATE civil_service_categories SET category_id=:new_id, scale_id=:scale, subscale_id=:sub, class_id=:class, category_name=:name, category_short_name=:s WHERE catalog_year=:y AND category_id=:id');
    $st->execute(['y' => $year, 'id' => $originalId, 'new_id' => $id, 'scale' => (int) $data['scale_id'], 'sub' => (int) $data['subscale_id'], 'class' => (int) $data['class_id'], 'name' => $name, 's' => null_if_empty((string) ($data['short_name'] ?? ''))]);
}

function maintenance_delete(PDO $db, string $module, int $year, string $id): void
{
    if (trim($id) === '') {
        throw new InvalidArgumentException('ID invàlid.');
    }
    if ($module === 'maintenance_scales') {
        $st = $db->prepare('DELETE FROM civil_service_scales WHERE catalog_year=:y AND scale_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_subscales') {
        $st = $db->prepare('DELETE FROM civil_service_subscales WHERE catalog_year=:y AND subscale_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_classes') {
        $st = $db->prepare('DELETE FROM civil_service_classes WHERE catalog_year=:y AND class_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_categories') {
        $st = $db->prepare('DELETE FROM civil_service_categories WHERE catalog_year=:y AND category_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_access_types') {
        $st = $db->prepare('DELETE FROM access_types WHERE catalog_year=:y AND access_type_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_access_systems') {
        $st = $db->prepare('DELETE FROM access_systems WHERE catalog_year=:y AND access_system_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_administrative_statuses') {
        $st = $db->prepare('DELETE FROM administrative_statuses WHERE catalog_year=:y AND administrative_status_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_position_classes') {
        $st = $db->prepare('DELETE FROM position_classes WHERE catalog_year=:y AND position_class_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_legal_relationships') {
        $st = $db->prepare('DELETE FROM legal_relations WHERE catalog_year=:y AND legal_relation_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_work_centers') {
        $st = $db->prepare('DELETE FROM work_centers WHERE catalog_year=:y AND work_center_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_availability_types') {
        $st = $db->prepare('DELETE FROM availability_options WHERE catalog_year=:y AND availability_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_provision_forms') {
        $st = $db->prepare('DELETE FROM provision_methods WHERE catalog_year=:y AND provision_method_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_programs') {
        $stc = $db->prepare('SELECT COUNT(*) AS c FROM subprograms WHERE catalog_year=:y AND program_id=:pid');
        $stc->execute(['y' => $year, 'pid' => $id]);
        if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
            throw new RuntimeException('No es pot eliminar perquè hi ha subprogrames dependents d’aquest programa.');
        }
        $st = $db->prepare('DELETE FROM programs WHERE catalog_year=:y AND program_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_social_security_companies') {
        $stCol = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'people' AND COLUMN_NAME = 'company_id'");
        $stCol->execute();
        $hasCompanyId = (int) (($stCol->fetch())['c'] ?? 0) > 0;
        if ($hasCompanyId) {
            $stc = $db->prepare('SELECT COUNT(*) AS c FROM people WHERE catalog_year=:y AND company_id=:id');
            $stc->execute(['y' => $year, 'id' => $id]);
            if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
                throw new RuntimeException('No es pot eliminar l’empresa perquè hi ha persones vinculades.');
            }
        }
        $st = $db->prepare('DELETE FROM social_security_companies WHERE catalog_year=:y AND company_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_social_security_coefficients') {
        $stCol = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_positions' AND COLUMN_NAME = 'contribution_epigraph_id'");
        $stCol->execute();
        $hasEpigraph = (int) (($stCol->fetch())['c'] ?? 0) > 0;
        if ($hasEpigraph) {
            $stc = $db->prepare('SELECT COUNT(*) AS c FROM job_positions WHERE catalog_year=:y AND contribution_epigraph_id=:id');
            $stc->execute(['y' => $year, 'id' => $id]);
            if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
                throw new RuntimeException('No es pot eliminar el coeficient perquè hi ha llocs de treball vinculats.');
            }
        }
        $st = $db->prepare('DELETE FROM social_security_coefficients WHERE catalog_year=:y AND contribution_epigraph_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_social_security_base_limits') {
        $stCol = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_positions' AND COLUMN_NAME = 'contribution_group_id'");
        $stCol->execute();
        $hasContributionGroupId = (int) (($stCol->fetch())['c'] ?? 0) > 0;
        if ($hasContributionGroupId) {
            $stc = $db->prepare('SELECT COUNT(*) AS c FROM job_positions WHERE catalog_year=:y AND contribution_group_id=:id');
            $stc->execute(['y' => $year, 'id' => $id]);
            if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
                throw new RuntimeException('No es pot eliminar el grup de cotització perquè hi ha llocs de treball vinculats.');
            }
        }
        $st = $db->prepare('DELETE FROM social_security_base_limits WHERE catalog_year=:y AND contribution_group_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_salary_base_by_group') {
        $st = $db->prepare('DELETE FROM salary_base_by_group WHERE catalog_year=:y AND classification_group=:id LIMIT 1');
    } elseif ($module === 'maintenance_destination_allowances') {
        $st = $db->prepare('DELETE FROM destination_allowances WHERE catalog_year=:y AND organic_level=:id LIMIT 1');
    } elseif ($module === 'maintenance_seniority_pay_by_group') {
        $st = $db->prepare('DELETE FROM seniority_pay_by_group WHERE catalog_year=:y AND classification_group=:id LIMIT 1');
    } elseif ($module === 'maintenance_specific_compensation_special_prices') {
        if (!preg_match('/^\d+$/', trim($id))) {
            throw new InvalidArgumentException('ID invàlid.');
        }
        $sidDel = (int) trim($id);
        if ($sidDel === 0) {
            throw new RuntimeException('El codi 0 està reservat i no es pot eliminar des d\'aquest formulari.');
        }
        $stCol = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_positions' AND COLUMN_NAME = 'special_specific_compensation_id'");
        $stCol->execute();
        if ((int) (($stCol->fetch())['c'] ?? 0) > 0) {
            $stc = $db->prepare('SELECT COUNT(*) AS c FROM job_positions WHERE catalog_year = :y AND special_specific_compensation_id = :sid AND deleted_at IS NULL');
            $stc->execute(['y' => $year, 'sid' => $sidDel]);
            if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
                throw new RuntimeException('No es pot eliminar el complement específic especial perquè hi ha llocs de treball que el fan servir.');
            }
        }
        $id = (string) $sidDel;
        $st = $db->prepare('DELETE FROM specific_compensation_special WHERE catalog_year=:y AND special_specific_compensation_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_specific_compensation_general') {
        $st = $db->prepare('DELETE FROM specific_compensation_general WHERE catalog_year=:y AND general_specific_compensation_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_subprograms') {
        $stc = $db->prepare('SELECT COUNT(*) AS c FROM subprogram_people WHERE catalog_year=:y AND subprogram_id=:id');
        $stc->execute(['y' => $year, 'id' => $id]);
        if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
            throw new RuntimeException('No es pot eliminar el subprograma perquè hi ha persones vinculades.');
        }
        $st = $db->prepare('DELETE FROM subprograms WHERE catalog_year=:y AND subprogram_id=:id LIMIT 1');
    } elseif ($module === 'people') {
        $db->beginTransaction();
        try {
            $stDelSp = $db->prepare('DELETE FROM subprogram_people WHERE catalog_year=:y AND person_id=:id');
            $stDelSp->execute(['y' => $year, 'id' => $id]);
            $stDel = $db->prepare('DELETE FROM people WHERE catalog_year=:y AND person_id=:id LIMIT 1');
            $stDel->execute(['y' => $year, 'id' => $id]);
            if ($stDel->rowCount() === 0) {
                throw new RuntimeException('Registre no trobat.');
            }
            $db->commit();
            return;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    } elseif ($module === 'management_positions') {
        $stc = $db->prepare('SELECT COUNT(*) AS c FROM people WHERE catalog_year=:y AND position_id=:id');
        $stc->execute(['y' => $year, 'id' => $id]);
        if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
            throw new RuntimeException('No es pot eliminar la plaça perquè hi ha persones vinculades.');
        }
        $st = $db->prepare('DELETE FROM positions WHERE catalog_year=:y AND position_id=:id LIMIT 1');
    } elseif ($module === 'job_positions') {
        $jid = trim($id);
        $stc = $db->prepare('SELECT COUNT(*) AS c FROM programs WHERE catalog_year=:y AND responsible_person_code=:jid');
        $stc->execute(['y' => $year, 'jid' => $jid]);
        if ((int) (($stc->fetch())['c'] ?? 0) > 0) {
            throw new RuntimeException('No es pot eliminar el lloc perquè consta com a responsable d’algun programa.');
        }
        $stc2 = $db->prepare('SELECT COUNT(*) AS c FROM subprograms WHERE catalog_year=:y AND (technical_manager_code=:jid OR elected_manager_code=:jid)');
        $stc2->execute(['y' => $year, 'jid' => $jid]);
        if ((int) (($stc2->fetch())['c'] ?? 0) > 0) {
            throw new RuntimeException('No es pot eliminar el lloc perquè consta com a responsable tècnic o electe en algun subprograma.');
        }
        $db->beginTransaction();
        try {
            $stClr = $db->prepare('UPDATE people SET job_position_id=NULL WHERE catalog_year=:y AND job_position_id=:jid');
            $stClr->execute(['y' => $year, 'jid' => $jid]);
            $st = $db->prepare('DELETE FROM job_positions WHERE catalog_year=:y AND job_position_id=:id LIMIT 1');
            $st->execute(['y' => $year, 'id' => $jid]);
            if ($st->rowCount() === 0) {
                throw new RuntimeException('Registre no trobat.');
            }
            $db->commit();
            return;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    } elseif ($module === 'maintenance_organic_level_1') {
        $st = $db->prepare('DELETE FROM org_units_level_1 WHERE catalog_year=:y AND org_unit_level_1_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_organic_level_2') {
        $st = $db->prepare('DELETE FROM org_units_level_2 WHERE catalog_year=:y AND org_unit_level_2_id=:id LIMIT 1');
    } elseif ($module === 'maintenance_organic_level_3') {
        $st = $db->prepare('DELETE FROM org_units_level_3 WHERE catalog_year=:y AND org_unit_level_3_id=:id LIMIT 1');
    } else {
        throw new RuntimeException('Mòdul sense implementació de persistència.');
    }
    $st->execute(['y' => $year, 'id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Registre no trobat.');
    }
}

function maintenance_copy_management_position(PDO $db, int $catalogYear, int $sourcePositionId, int $newPositionId, string $newPositionName): void
{
    if ($sourcePositionId < 1) {
        throw new InvalidArgumentException('La plaça origen no és vàlida.');
    }
    if ($newPositionId < 1 || $newPositionId > 9999) {
        throw new InvalidArgumentException('El codi de la nova plaça ha de ser numèric i de màxim 4 dígits.');
    }
    $newPositionName = trim($newPositionName);
    if ($newPositionName === '') {
        throw new InvalidArgumentException('La denominació de la nova plaça és obligatòria.');
    }

    $db->beginTransaction();
    try {
        $stSource = $db->prepare('SELECT
                position_class_id,
                scale_id,
                subscale_id,
                class_id,
                category_id,
                labor_category,
                classification_group,
                access_type_id,
                access_system_id,
                budgeted_amount,
                is_offerable,
                opo_year,
                is_to_be_amortized,
                is_internal_promotion,
                creation_file_reference,
                call_for_applications_date,
                deleted_at,
                deletion_file_reference,
                notes
            FROM positions
            WHERE catalog_year = :y_source
              AND position_id = :source_id
            LIMIT 1');
        $stSource->execute([
            'y_source' => $catalogYear,
            'source_id' => $sourcePositionId,
        ]);
        $source = $stSource->fetch(PDO::FETCH_ASSOC);
        if (!$source) {
            throw new InvalidArgumentException('La plaça origen no existeix en aquest exercici.');
        }

        $stDup = $db->prepare('SELECT 1
            FROM positions
            WHERE catalog_year = :y_dup
              AND position_id = :new_id_dup
            LIMIT 1');
        $stDup->execute([
            'y_dup' => $catalogYear,
            'new_id_dup' => $newPositionId,
        ]);
        if ($stDup->fetchColumn()) {
            throw new InvalidArgumentException('Ja existeix una plaça amb aquest codi en aquest exercici.');
        }

        $deletedAt = isset($source['deleted_at']) ? trim((string) $source['deleted_at']) : '';
        $isActive = $deletedAt === '' ? 1 : 0;

        $stInsert = $db->prepare('INSERT INTO positions (
                catalog_year,
                position_id,
                position_name,
                position_class_id,
                scale_id,
                subscale_id,
                class_id,
                category_id,
                labor_category,
                classification_group,
                access_type_id,
                access_system_id,
                budgeted_amount,
                is_offerable,
                opo_year,
                is_to_be_amortized,
                is_internal_promotion,
                created_at,
                creation_file_reference,
                call_for_applications_date,
                deleted_at,
                deletion_file_reference,
                notes,
                is_active
            ) VALUES (
                :y_ins,
                :new_id,
                :new_name,
                :position_class_id,
                :scale_id,
                :subscale_id,
                :class_id,
                :category_id,
                :labor_category,
                :classification_group,
                :access_type_id,
                :access_system_id,
                :budgeted_amount,
                :is_offerable,
                :opo_year,
                :is_to_be_amortized,
                :is_internal_promotion,
                :created_at,
                :creation_file_reference,
                :call_for_applications_date,
                :deleted_at,
                :deletion_file_reference,
                :notes,
                :is_active
            )');
        $stInsert->execute([
            'y_ins' => $catalogYear,
            'new_id' => $newPositionId,
            'new_name' => $newPositionName,
            'position_class_id' => (int) ($source['position_class_id'] ?? 0),
            'scale_id' => $source['scale_id'] !== null ? (int) $source['scale_id'] : null,
            'subscale_id' => $source['subscale_id'] !== null ? (int) $source['subscale_id'] : null,
            'class_id' => $source['class_id'] !== null ? (int) $source['class_id'] : null,
            'category_id' => $source['category_id'] !== null ? (int) $source['category_id'] : null,
            'labor_category' => null_if_empty((string) ($source['labor_category'] ?? '')),
            'classification_group' => null_if_empty((string) ($source['classification_group'] ?? '')),
            'access_type_id' => $source['access_type_id'] !== null ? (int) $source['access_type_id'] : null,
            'access_system_id' => $source['access_system_id'] !== null ? (int) $source['access_system_id'] : null,
            'budgeted_amount' => $source['budgeted_amount'] !== null ? (string) $source['budgeted_amount'] : null,
            'is_offerable' => !empty($source['is_offerable']) ? 1 : 0,
            'opo_year' => $source['opo_year'] !== null ? (int) $source['opo_year'] : null,
            'is_to_be_amortized' => !empty($source['is_to_be_amortized']) ? 1 : 0,
            'is_internal_promotion' => !empty($source['is_internal_promotion']) ? 1 : 0,
            'created_at' => null,
            'creation_file_reference' => null_if_empty((string) ($source['creation_file_reference'] ?? '')),
            'call_for_applications_date' => $source['call_for_applications_date'] !== null ? (string) $source['call_for_applications_date'] : null,
            'deleted_at' => $deletedAt !== '' ? $deletedAt : null,
            'deletion_file_reference' => null_if_empty((string) ($source['deletion_file_reference'] ?? '')),
            'notes' => null_if_empty((string) ($source['notes'] ?? '')),
            'is_active' => $isActive,
        ]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_salary_base_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $factor = $pct / 100.0;
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE salary_base_by_group
            SET base_salary_new = ROUND(base_salary + (base_salary * :f1), 2),
                base_salary_extra_pay_new = ROUND(base_salary_extra_pay + (base_salary_extra_pay * :f2), 2)
            WHERE catalog_year = :y1');
        $st->execute(['y1' => $year, 'f1' => $factor, 'f2' => $factor]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_salary_base_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE salary_base_by_group
            SET base_salary_new = NULL,
                base_salary_extra_pay_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_salary_base_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM salary_base_by_group
        WHERE catalog_year = :y
          AND (base_salary_new IS NULL OR base_salary_extra_pay_new IS NULL)');
    $stCheck->execute(['y' => $year]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE salary_base_by_group
            SET base_salary = base_salary_new,
                base_salary_extra_pay = base_salary_extra_pay_new,
                base_salary_new = NULL,
                base_salary_extra_pay_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_destination_allowance_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $factor = $pct / 100.0;
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE destination_allowances
            SET destination_allowance_new = ROUND(destination_allowance + (destination_allowance * :f1), 2)
            WHERE catalog_year = :y1');
        $st->execute(['y1' => $year, 'f1' => $factor]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_destination_allowance_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE destination_allowances
            SET destination_allowance_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_destination_allowance_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM destination_allowances
        WHERE catalog_year = :y
          AND destination_allowance_new IS NULL');
    $stCheck->execute(['y' => $year]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE destination_allowances
            SET destination_allowance = destination_allowance_new,
                destination_allowance_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_seniority_pay_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $factor = $pct / 100.0;
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE seniority_pay_by_group
            SET seniority_amount_new = ROUND(seniority_amount + (seniority_amount * :f1), 2),
                seniority_extra_pay_amount_new = ROUND(seniority_extra_pay_amount + (seniority_extra_pay_amount * :f2), 2)
            WHERE catalog_year = :y1');
        $st->execute(['y1' => $year, 'f1' => $factor, 'f2' => $factor]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_seniority_pay_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE seniority_pay_by_group
            SET seniority_amount_new = NULL,
                seniority_extra_pay_amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_seniority_pay_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM seniority_pay_by_group
        WHERE catalog_year = :y
          AND (seniority_amount_new IS NULL OR seniority_extra_pay_amount_new IS NULL)');
    $stCheck->execute(['y' => $year]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE seniority_pay_by_group
            SET seniority_amount = seniority_amount_new,
                seniority_extra_pay_amount = seniority_extra_pay_amount_new,
                seniority_amount_new = NULL,
                seniority_extra_pay_amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * Recalcula annual_budgeted_seniority a people per l'any actiu.
 *
 * @return array{ok:true,updated:int,scope:string}|array{ok:false,error:string}
 */
function maintenance_seniority_pay_update_people(PDO $db, int $year, string $scope): array
{
    $scopeNorm = strtolower(trim($scope));
    if (!in_array($scopeNorm, ['active', 'all'], true)) {
        return ['ok' => false, 'error' => 'Abast d’actualització no vàlid.'];
    }

    $requiredGroups = ['A1', 'A2', 'C1', 'C2', 'E'];
    $placeholders = [];
    $params = ['y' => $year];
    foreach ($requiredGroups as $idx => $grp) {
        $k = 'g' . $idx;
        $placeholders[] = ':' . $k;
        $params[$k] = $grp;
    }

    $stGroups = $db->prepare('SELECT classification_group, seniority_amount, seniority_extra_pay_amount
        FROM seniority_pay_by_group
        WHERE catalog_year = :y
          AND UPPER(classification_group) IN (' . implode(',', $placeholders) . ')');
    $stGroups->execute($params);
    $rows = $stGroups->fetchAll() ?: [];

    $byGroup = [];
    foreach ($rows as $r) {
        $g = strtoupper(trim((string) ($r['classification_group'] ?? '')));
        if ($g !== '') {
            $byGroup[$g] = $r;
        }
    }
    foreach ($requiredGroups as $grp) {
        if (!isset($byGroup[$grp])) {
            return ['ok' => false, 'error' => 'No es poden actualitzar els triennis de persones perquè falten imports de triennis per algun grup de classificació.'];
        }
    }

    $imports = [];
    foreach ($requiredGroups as $grp) {
        $base = $byGroup[$grp]['seniority_amount'] ?? null;
        $extra = $byGroup[$grp]['seniority_extra_pay_amount'] ?? null;
        if ($base === null || $extra === null) {
            return ['ok' => false, 'error' => 'No es poden actualitzar els triennis de persones perquè hi ha imports de triennis sense informar.'];
        }
        $imports[$grp] = [
            'base' => (float) $base,
            'extra' => (float) $extra,
        ];
    }

    $db->beginTransaction();
    try {
        $where = 'p.catalog_year = :y0';
        if ($scopeNorm === 'active') {
            $where .= ' AND p.is_active = :a0';
        }

        $stCount = $db->prepare('SELECT COUNT(*) AS c FROM people p WHERE ' . $where);
        $countParams = ['y0' => $year];
        if ($scopeNorm === 'active') {
            $countParams['a0'] = 1;
        }
        $stCount->execute($countParams);
        $targetCount = (int) (($stCount->fetch())['c'] ?? 0);

        $sql = 'UPDATE people p
            SET p.annual_budgeted_seniority =
                COALESCE(p.group_a1_previous_triennia, 0) * :tri_a1 * 12
              + COALESCE(p.group_a2_previous_triennia, 0) * :tri_a2 * 12
              + COALESCE(p.group_c1_previous_triennia, 0) * :tri_c1 * 12
              + COALESCE(p.group_c2_previous_triennia, 0) * :tri_c2 * 12
              + COALESCE(p.group_e_previous_triennia, 0)  * :tri_e  * 12

              + COALESCE(p.group_a1_previous_triennia, 0) * :tri_a1p * 2
              + COALESCE(p.group_a2_previous_triennia, 0) * :tri_a2p * 2
              + COALESCE(p.group_c1_previous_triennia, 0) * :tri_c1p * 2
              + COALESCE(p.group_c2_previous_triennia, 0) * :tri_c2p * 2
              + COALESCE(p.group_e_previous_triennia, 0)  * :tri_ep  * 2

              + ROUND(COALESCE(p.group_a1_current_year_percentage, 0) * :tri_a1b / 100, 2) * 14
              + ROUND(COALESCE(p.group_a2_current_year_percentage, 0) * :tri_a2b / 100, 2) * 14
              + ROUND(COALESCE(p.group_c1_current_year_percentage, 0) * :tri_c1b / 100, 2) * 14
              + ROUND(COALESCE(p.group_c2_current_year_percentage, 0) * :tri_c2b / 100, 2) * 14
              + ROUND(COALESCE(p.group_e_current_year_percentage, 0)  * :tri_eb  / 100, 2) * 14
            WHERE ' . $where;

        $stUpd = $db->prepare($sql);
        $updParams = [
            'tri_a1' => $imports['A1']['base'],
            'tri_a2' => $imports['A2']['base'],
            'tri_c1' => $imports['C1']['base'],
            'tri_c2' => $imports['C2']['base'],
            'tri_e' => $imports['E']['base'],
            'tri_a1p' => $imports['A1']['extra'],
            'tri_a2p' => $imports['A2']['extra'],
            'tri_c1p' => $imports['C1']['extra'],
            'tri_c2p' => $imports['C2']['extra'],
            'tri_ep' => $imports['E']['extra'],
            'tri_a1b' => $imports['A1']['base'],
            'tri_a2b' => $imports['A2']['base'],
            'tri_c1b' => $imports['C1']['base'],
            'tri_c2b' => $imports['C2']['base'],
            'tri_eb' => $imports['E']['base'],
            'y0' => $year,
        ];
        if ($scopeNorm === 'active') {
            $updParams['a0'] = 1;
        }
        $stUpd->execute($updParams);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['ok' => true, 'updated' => $targetCount, 'scope' => $scopeNorm];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_special_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $factor = $pct / 100.0;
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_special
            SET amount_new = ROUND(amount + (amount * :f1), 2)
            WHERE catalog_year = :y1');
        $st->execute(['y1' => $year, 'f1' => $factor]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_special_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_special
            SET amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_special_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM specific_compensation_special
        WHERE catalog_year = :y
          AND amount_new IS NULL');
    $stCheck->execute(['y' => $year]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }

    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_special
            SET amount = amount_new,
                amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * Actualitza en massa el complement específic especial als llocs de treball.
 *
 * @return array{updated:int}
 */
function maintenance_specific_comp_special_update_job_positions(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE job_positions jp
            JOIN specific_compensation_special sp
              ON sp.catalog_year = :y1
             AND jp.catalog_year = :y2
             AND jp.special_specific_compensation_id = sp.special_specific_compensation_id
            SET jp.special_specific_compensation_amount = sp.amount
            WHERE jp.catalog_year = :y3');
        $st->execute([
            'y1' => $year,
            'y2' => $year,
            'y3' => $year,
        ]);
        $updated = $st->rowCount();
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['updated' => (int) $updated];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_general_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $factor = $pct / 100.0;
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_general
            SET amount_new = ROUND(amount + (amount * :f1), 2),
                decrease_amount_new = ROUND(decrease_amount + (decrease_amount * :f2), 2)
            WHERE catalog_year = :y1');
        $st->execute(['y1' => $year, 'f1' => $factor, 'f2' => $factor]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_general_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_general
            SET amount_new = NULL,
                decrease_amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_specific_comp_general_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM specific_compensation_general
        WHERE catalog_year = :y
          AND (amount_new IS NULL OR decrease_amount_new IS NULL)');
    $stCheck->execute(['y' => $year]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE specific_compensation_general
            SET amount = amount_new,
                decrease_amount = decrease_amount_new,
                amount_new = NULL,
                decrease_amount_new = NULL
            WHERE catalog_year = :y');
        $st->execute(['y' => $year]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
    return ['ok' => true];
}

/**
 * Increment en massa del CPT personal transitori (people.personal_transitory_bonus_new).
 *
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_personal_transitory_bonus_increment_imports(PDO $db, int $year, string $rawPercent): array
{
    $t = trim($rawPercent);
    if ($t === '') {
        return ['ok' => false, 'error' => 'Cal indicar un percentatge d’increment.'];
    }
    $t = str_replace(['%', ' '], '', $t);
    $t = str_replace(',', '.', $t);
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $t)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'El percentatge indicat no és vàlid.'];
    }
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE people
            SET personal_transitory_bonus_new = ROUND(personal_transitory_bonus + (personal_transitory_bonus * :p1 / 100), 2)
            WHERE catalog_year = :p2
              AND is_active = :p3
              AND personal_transitory_bonus <> 0');
        $st->execute(['p1' => $pct, 'p2' => $year, 'p3' => 1]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_personal_transitory_bonus_cancel_increment(PDO $db, int $year): array
{
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE people
            SET personal_transitory_bonus_new = NULL
            WHERE catalog_year = :p1
              AND is_active = :p2
              AND personal_transitory_bonus <> 0');
        $st->execute(['p1' => $year, 'p2' => 1]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string}
 */
function maintenance_personal_transitory_bonus_apply_imports(PDO $db, int $year): array
{
    $stCheck = $db->prepare('SELECT COUNT(*) AS c
        FROM people
        WHERE catalog_year = :p1
          AND is_active = :p2
          AND personal_transitory_bonus <> 0
          AND personal_transitory_bonus_new IS NULL');
    $stCheck->execute(['p1' => $year, 'p2' => 1]);
    $missing = (int) (($stCheck->fetch())['c'] ?? 0);
    if ($missing > 0) {
        return ['ok' => false, 'error' => 'No es poden actualitzar els imports perquè hi ha registres sense imports incrementats.'];
    }
    $db->beginTransaction();
    try {
        $st = $db->prepare('UPDATE people
            SET personal_transitory_bonus = personal_transitory_bonus_new,
                personal_transitory_bonus_new = NULL
            WHERE catalog_year = :p1
              AND is_active = :p2
              AND personal_transitory_bonus <> 0');
        $st->execute(['p1' => $year, 'p2' => 1]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    return ['ok' => true];
}

/**
 * Actualitza people.personal_transitory_bonus_new per una persona (llistat CPT personal transitori).
 *
 * @return array{ok:true, value_display:string, value_for_input:string}|array{ok:false, error:string}
 */
function maintenance_personal_transitory_bonus_update_new(PDO $db, int $year, int $personId, string $rawValue): array
{
    if ($personId < 1) {
        return ['ok' => false, 'error' => 'Identificador de persona no vàlid.'];
    }
    $rawClean = trim(str_replace(["\xC2\xA0", '€'], '', $rawValue));
    $rawClean = preg_replace('/\s+/u', '', $rawClean) ?? $rawClean;
    if ($rawClean === '') {
        $dbVal = null;
    } else {
        $parsed = maintenance_parse_optional_money_input($rawClean);
        if (!$parsed['ok']) {
            return ['ok' => false, 'error' => (string) ($parsed['error'] ?? 'Import no vàlid.')];
        }
        if ($parsed['value'] === null) {
            $dbVal = null;
        } else {
            $dbVal = (string) $parsed['value'];
        }
    }

    $stExist = $db->prepare('SELECT 1 FROM people WHERE catalog_year = :y AND person_id = :pid AND is_active = 1 AND personal_transitory_bonus <> 0 LIMIT 1');
    $stExist->execute(['y' => $year, 'pid' => $personId]);
    if (!$stExist->fetchColumn()) {
        return ['ok' => false, 'error' => 'Persona no trobada o fora de l’àmbit d’aquest mòdul.'];
    }

    $stUp = $db->prepare('UPDATE people SET personal_transitory_bonus_new = :p_val WHERE catalog_year = :p_y AND person_id = :p_pid AND is_active = 1 AND personal_transitory_bonus <> 0');
    if ($dbVal === null) {
        $stUp->bindValue('p_val', null, PDO::PARAM_NULL);
    } else {
        $stUp->bindValue('p_val', $dbVal, PDO::PARAM_STR);
    }
    $stUp->bindValue('p_y', $year, PDO::PARAM_INT);
    $stUp->bindValue('p_pid', $personId, PDO::PARAM_INT);
    $stUp->execute();

    $stRead = $db->prepare('SELECT personal_transitory_bonus_new FROM people WHERE catalog_year = :y AND person_id = :pid LIMIT 1');
    $stRead->execute(['y' => $year, 'pid' => $personId]);
    $stored = $stRead->fetchColumn();
    if ($dbVal === null) {
        if ($stored !== null && trim((string) $stored) !== '') {
            return ['ok' => false, 'error' => 'No s’ha pogut desar el valor.'];
        }
    } else {
        if ($stored === null || abs((float) $stored - (float) $dbVal) > 0.000001) {
            return ['ok' => false, 'error' => 'No s’ha pogut desar el valor.'];
        }
    }

    $valueInput = ($stored === null || trim((string) $stored) === '') ? '' : number_format((float) $stored, 2, ',', '.');

    return [
        'ok' => true,
        'value_display' => maintenance_format_currency_eur_2_display($stored === null ? null : (string) $stored),
        'value_for_input' => $valueInput,
    ];
}

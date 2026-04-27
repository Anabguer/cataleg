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
    return ['maintenance_scales', 'maintenance_subscales', 'maintenance_classes', 'maintenance_categories', 'maintenance_legal_relationships', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_programs', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits', 'maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus', 'maintenance_subprograms'];
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
    $st = $db->prepare("SELECT job_position_id AS id, job_title AS name FROM job_positions WHERE catalog_year = :y AND job_type_id = 'CM' AND deleted_at IS NULL ORDER BY job_position_id ASC");
    $st->execute(['y' => $year]);

    return $st->fetchAll() ?: [];
}

function maintenance_job_position_is_cm(PDO $db, int $year, string $jobPositionId): bool
{
    $st = $db->prepare("SELECT 1 FROM job_positions WHERE catalog_year = :y AND job_position_id = :jid AND job_type_id = 'CM' LIMIT 1");
    $st->execute(['y' => $year, 'jid' => $jobPositionId]);

    return (bool) $st->fetch();
}

function maintenance_count(PDO $db, string $module, int $year, string $q): int
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

function maintenance_list(PDO $db, string $module, int $year, string $q, string $sortBy, string $sortDir, int $limit, int $offset): array
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
    } elseif ($module === 'maintenance_subprograms') {
        $sql = 'SELECT t.*, p.program_name,
                jp_t.job_title AS technical_job_title, jp_e.job_title AS elected_job_title
                FROM subprograms t
                INNER JOIN programs p ON p.catalog_year = t.catalog_year AND p.program_id = t.program_id
                LEFT JOIN job_positions jp_t ON jp_t.catalog_year = t.catalog_year AND jp_t.job_position_id = t.technical_manager_code
                LEFT JOIN job_positions jp_e ON jp_e.catalog_year = t.catalog_year AND jp_e.job_position_id = t.elected_manager_code
                WHERE t.catalog_year = :y AND t.subprogram_id = :id LIMIT 1';
    } else {
        return null;
    }
    $st = $db->prepare($sql);
    $st->execute(['y' => $year, 'id' => $id]);
    $row = $st->fetch();
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

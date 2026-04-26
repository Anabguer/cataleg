<?php
declare(strict_types=1);

require_once __DIR__ . '/maintenance_columns.php';

function maintenance_modules_config(): array
{
    return [
        'maintenance_scales' => ['title' => 'Escales', 'table' => 'civil_service_scales', 'implemented' => true],
        'maintenance_subscales' => ['title' => 'Subescales', 'table' => 'civil_service_subscales', 'implemented' => true],
        'maintenance_classes' => ['title' => 'Classes', 'table' => 'civil_service_classes', 'implemented' => true],
        'maintenance_categories' => ['title' => 'Categories', 'table' => 'civil_service_categories', 'implemented' => true],
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
    return ['maintenance_scales', 'maintenance_subscales', 'maintenance_classes', 'maintenance_categories', 'maintenance_legal_relationships', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_programs', 'maintenance_subprograms'];
}

/** Mòduls amb persistència CRUD al catàleg funcionarial. */
function maintenance_catalog_crud_modules(): array
{
    return maintenance_catalog_list_modules();
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

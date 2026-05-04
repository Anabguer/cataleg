<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once APP_ROOT . '/includes/maintenance/aux_catalog.php';
require_once APP_ROOT . '/includes/maintenance/maintenance_view_helpers.php';

$module = get_string('module');
$config = maintenance_module_config($module);
if ($config === null) {
    redirect(app_url('dashboard.php'));
}

require_can_view($module);

$year = catalog_year_current();
if ($year === null) {
    redirect(app_url('dashboard.php'));
}

$db = db();

$q = get_string('q');
$managementPositionsFilters = [
    'f_position_id' => get_string('f_position_id'),
    'f_position_name' => get_string('f_position_name'),
    'f_position_class_id' => get_string('f_position_class_id'),
    'f_scale_id' => get_string('f_scale_id'),
    'f_subscale_id' => get_string('f_subscale_id'),
    'f_class_id' => get_string('f_class_id'),
    'f_category_id' => get_string('f_category_id'),
    'f_is_active' => get_string('f_is_active'),
];
$peopleFilters = [
    'f_person_id' => get_string('f_person_id'),
    'f_last_name_1' => get_string('f_last_name_1'),
    'f_last_name_2' => get_string('f_last_name_2'),
    'f_first_name' => get_string('f_first_name'),
    'f_national_id_number' => get_string('f_national_id_number'),
    'f_email' => get_string('f_email'),
    'f_job_position_id' => get_string('f_job_position_id'),
    'f_position_id' => get_string('f_position_id'),
    'f_legal_relation_id' => get_string('f_legal_relation_id'),
    'f_is_active' => get_string('f_is_active'),
];
$jobPositionsFilters = [
    'f_job_code' => get_string('f_job_code'),
    'f_job_title' => get_string('f_job_title'),
    'f_org_dependency_id' => get_string('f_org_dependency_id'),
    'f_is_active' => get_string('f_is_active'),
    'f_scale_id' => get_string('f_scale_id'),
    'f_legal_relation_id' => get_string('f_legal_relation_id'),
    'f_is_to_be_amortized' => get_string('f_is_to_be_amortized'),
];
$reportsFilters = [
    'f_report_group' => get_string('f_report_group'),
    'f_report_code' => get_string('f_report_code'),
    'f_report_name' => get_string('f_report_name'),
    'f_show_in_general_selector' => get_string('f_show_in_general_selector'),
    'f_is_active' => get_string('f_is_active'),
];
$catalogsFilters = [
    'f_catalog_code' => get_string('f_catalog_code'),
    'f_catalog_description' => get_string('f_catalog_description'),
];
$sort = maintenance_sort_normalize($module, get_string('sort_by'), get_string('sort_dir'));
$perPage = (int) get_string('per_page');
if ($perPage < 1) {
    $perPage = 20;
}
$page = (int) get_string('page');
if ($page < 1) {
    $page = 1;
}

$rows = [];
$total = 0;
$totalPages = 1;
$offset = 0;
if ($config['implemented'] ?? false) {
    $activeFilters = $module === 'management_positions'
        ? $managementPositionsFilters
        : ($module === 'people'
            ? $peopleFilters
            : ($module === 'job_positions'
                ? $jobPositionsFilters
                : ($module === 'reports'
                    ? maintenance_reports_normalize_filters($reportsFilters)
                    : ($module === 'catalogs' ? maintenance_catalogs_normalize_filters($catalogsFilters) : []))));
    $total = maintenance_count($db, $module, $year, $q, $activeFilters);
    $pn = maintenance_normalize_pagination($page, $perPage, $total);
    $page = $pn['page'];
    $perPage = $pn['per_page'];
    $totalPages = $pn['total_pages'];
    $offset = $pn['offset'];
    $rows = maintenance_list($db, $module, $year, $q, $sort['by'], $sort['dir'], $perPage, $offset, $activeFilters);
}

$scales = maintenance_scales_options($db, $year);
$subscales = maintenance_subscales_options($db, $year);
$classes = maintenance_classes_options($db, $year);
$positionClasses = $db->query('SELECT position_class_id AS id, position_class_name AS name FROM position_classes WHERE catalog_year = ' . (int) $year . ' ORDER BY position_class_id ASC')->fetchAll() ?: [];
$categories = $db->query('SELECT category_id AS id, class_id, subscale_id, scale_id, category_name AS name FROM civil_service_categories WHERE catalog_year = ' . (int) $year . ' ORDER BY scale_id ASC, subscale_id ASC, class_id ASC, category_id ASC')->fetchAll() ?: [];
$accessTypes = $db->query('SELECT access_type_id AS id, access_type_name AS name FROM access_types WHERE catalog_year = ' . (int) $year . ' ORDER BY access_type_id ASC')->fetchAll() ?: [];
$accessSystems = $db->query('SELECT access_system_id AS id, access_system_name AS name FROM access_systems WHERE catalog_year = ' . (int) $year . ' ORDER BY access_system_id ASC')->fetchAll() ?: [];
$legalRelations = $db->query('SELECT legal_relation_id AS id, legal_relation_name AS name FROM legal_relations WHERE catalog_year = ' . (int) $year . ' ORDER BY legal_relation_id ASC')->fetchAll() ?: [];
$administrativeStatuses = $db->query('SELECT administrative_status_id AS id, administrative_status_name AS name FROM administrative_statuses WHERE catalog_year = ' . (int) $year . ' ORDER BY administrative_status_id ASC')->fetchAll() ?: [];
$positionsForPeople = $db->query('SELECT position_id AS id, position_name AS name FROM positions WHERE catalog_year = ' . (int) $year . ' ORDER BY position_id ASC')->fetchAll() ?: [];
$subprogramsForPeople = $db->query('SELECT subprogram_id AS id, subprogram_name AS name FROM subprograms WHERE catalog_year = ' . (int) $year . ' ORDER BY subprogram_id ASC')->fetchAll() ?: [];
$socialSecurityCompanies = $db->query('SELECT company_id AS id, company_description AS name FROM social_security_companies WHERE catalog_year = ' . (int) $year . ' ORDER BY company_id ASC')->fetchAll() ?: [];
$peoplePersonalGrades = maintenance_people_personal_grade_options($db, $year);
$peopleSeniorityPayByGroup = maintenance_people_seniority_pay_by_group($db, $year);
$organicLevel1 = maintenance_org_units_level_1_options($db, $year);
$organicLevel2 = maintenance_org_units_level_2_options($db, $year);
$jobPositions = maintenance_job_positions_options($db, $year);
$programsForSelect = maintenance_programs_options_for_select($db, $year);
$jobPositionsCm = maintenance_job_positions_cm_options($db, $year);
$jobPositionsPeoplePicker = $module === 'job_positions' ? maintenance_job_positions_people_picker_options($db, $year) : [];
$jobPositionLegalModes = $module === 'job_positions' ? maintenance_job_position_legal_relation_modes_for_year($db, $year) : [];
$jobPositionLegalOptions = $module === 'job_positions' ? maintenance_job_position_legal_relation_options() : [];
$jobPositionSpecialCompAmounts = $module === 'job_positions' ? maintenance_job_positions_special_comp_amount_map($db, $year) : [];
$jobPositionGeneralCompAmounts = $module === 'job_positions' ? maintenance_job_positions_general_comp_amount_map($db, $year) : [];
$jobPositionSalaryGroupAmounts = $module === 'job_positions' ? maintenance_job_positions_salary_group_amount_map($db, $year) : [];
$jobPositionOrganicLevelAmounts = $module === 'job_positions' ? maintenance_job_positions_organic_level_amount_map($db, $year) : [];
$jobPositionTypes = $module === 'job_positions' ? maintenance_job_position_type_options($db, $year) : [];
$pageTitle = (string) $config['title'];
$activeNav = $module;
$extraCss = ['css/module-users.css'];
$extraScripts = ['maintenance.js'];
$maintenancePageInlineConfig = [
    'apiUrl' => app_url('maintenance_api.php'),
    'csrfToken' => csrf_token(),
    'module' => $module,
    'year' => $year,
    'implemented' => (bool) ($config['implemented'] ?? false),
    'canView' => can_view_form($module),
    'canCreate' => can_create_form($module),
    'canEdit' => can_edit_form($module),
    'canDelete' => can_delete_form($module),
    'scales' => $scales,
    'subscales' => $subscales,
    'classes' => $classes,
    'categories' => $categories,
    'positionClasses' => $positionClasses,
    'accessTypes' => $accessTypes,
    'accessSystems' => $accessSystems,
    'legalRelations' => $legalRelations,
    'administrativeStatuses' => $administrativeStatuses,
    'positionsForPeople' => $positionsForPeople,
    'subprogramsForPeople' => $subprogramsForPeople,
    'socialSecurityCompanies' => $socialSecurityCompanies,
    'peoplePersonalGrades' => $peoplePersonalGrades,
    'peopleSeniorityPayByGroup' => $peopleSeniorityPayByGroup,
    'peoplePersonalGradeAmounts' => $module === 'people' ? maintenance_people_personal_grade_amount_map($db, $year) : [],
    'organicLevel1' => $organicLevel1,
    'organicLevel2' => $organicLevel2,
    'jobPositions' => $jobPositions,
    'programsForSelect' => $programsForSelect,
    'jobPositionsCm' => $jobPositionsCm,
    'managementPositionsFilters' => management_positions_normalize_filters($managementPositionsFilters),
    'peopleFilters' => maintenance_people_normalize_filters($peopleFilters),
    'jobPositionsFilters' => maintenance_job_positions_normalize_filters($jobPositionsFilters),
    'reportsFilters' => maintenance_reports_normalize_filters($reportsFilters),
    'catalogsFilters' => maintenance_catalogs_normalize_filters($catalogsFilters),
    'jobPositionsPeoplePicker' => $jobPositionsPeoplePicker,
    'jobPositionLegalModes' => $jobPositionLegalModes,
    'jobPositionLegalOptions' => $jobPositionLegalOptions,
    'jobPositionSpecialCompAmounts' => $jobPositionSpecialCompAmounts,
    'jobPositionGeneralCompAmounts' => $jobPositionGeneralCompAmounts,
    'jobPositionSalaryGroupAmounts' => $jobPositionSalaryGroupAmounts,
    'jobPositionOrganicLevelAmounts' => $jobPositionOrganicLevelAmounts,
    'jobPositionTypes' => $jobPositionTypes,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/maintenance/index.php';
require APP_ROOT . '/includes/footer.php';

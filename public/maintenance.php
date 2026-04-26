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
    $total = maintenance_count($db, $module, $year, $q);
    $pn = maintenance_normalize_pagination($page, $perPage, $total);
    $page = $pn['page'];
    $perPage = $pn['per_page'];
    $totalPages = $pn['total_pages'];
    $offset = $pn['offset'];
    $rows = maintenance_list($db, $module, $year, $q, $sort['by'], $sort['dir'], $perPage, $offset);
}

$scales = maintenance_scales_options($db, $year);
$subscales = maintenance_subscales_options($db, $year);
$classes = maintenance_classes_options($db, $year);
$organicLevel1 = maintenance_org_units_level_1_options($db, $year);
$organicLevel2 = maintenance_org_units_level_2_options($db, $year);
$jobPositions = maintenance_job_positions_options($db, $year);
$programsForSelect = maintenance_programs_options_for_select($db, $year);
$jobPositionsCm = maintenance_job_positions_cm_options($db, $year);

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
    'organicLevel1' => $organicLevel1,
    'organicLevel2' => $organicLevel2,
    'jobPositions' => $jobPositions,
    'programsForSelect' => $programsForSelect,
    'jobPositionsCm' => $jobPositionsCm,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/maintenance/index.php';
require APP_ROOT . '/includes/footer.php';

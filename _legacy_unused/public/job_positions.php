<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('job_positions');
require_once APP_ROOT . '/includes/job_positions/job_positions.php';
require_once APP_ROOT . '/includes/job_positions/job_positions_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'area_id' => get_string('area_id'),
    'section_id' => get_string('section_id'),
    'unit_id' => get_string('unit_id'),
    'is_catalog' => get_string('is_catalog'),
    'active' => get_string('active'),
];
$sortIn = job_positions_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
$sortBy = $sortIn['by'];
$sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page');
if ($perPage < 1) {
    $perPage = 20;
}
if ($perPage > 100) {
    $perPage = 100;
}
$page = (int) get_string('page');
if ($page < 1) {
    $page = 1;
}
$totalRows = job_positions_count($db, $filters);
$pg = job_positions_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = job_positions_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$areasSelect = job_positions_areas_for_select($db);
$sectionsSelect = job_positions_sections_for_select($db);
$unitsSelect = job_positions_units_for_select($db);
$jobPositionsAutoUnits = job_positions_auto_units_for_select($db);

$canCreate = can_create_form('job_positions');
$canEdit = can_edit_form('job_positions');
$canDelete = can_delete_form('job_positions');

$pageTitle = 'Llocs de treball';
$activeNav = 'job_positions';
$extraCss = ['css/module-users.css'];
$extraScripts = ['job_positions.js'];
$jobPositionsPageInlineConfig = [
    'apiUrl' => app_url('job_positions_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'nextAutoUnitCode' => job_positions_next_auto_unit_code($db),
    'autoUnits' => array_map(
        static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'unit_code' => (int) $r['unit_code'],
                'name' => (string) $r['name'],
            ];
        },
        $jobPositionsAutoUnits
    ),
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/job_positions/index.php';
require APP_ROOT . '/includes/footer.php';

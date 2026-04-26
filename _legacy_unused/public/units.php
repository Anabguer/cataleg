<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('units');
require_once APP_ROOT . '/includes/units/units.php';
require_once APP_ROOT . '/includes/units/units_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'area_id' => get_string('area_id'), 'section_id' => get_string('section_id'), 'active' => get_string('active')];
$sortIn = units_normalize_sort(get_string('sort_by'), get_string('sort_dir')); $sortBy = $sortIn['by']; $sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page'); if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100;
$page = (int) get_string('page'); if ($page < 1) $page = 1;
$totalUnits = units_count($db, $filters);
$pg = units_normalize_pagination($page, $perPage, $totalUnits);
$page = $pg['page']; $perPage = $pg['per_page']; $totalPages = $pg['total_pages']; $offset = $pg['offset'];
$unitRows = units_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);
$areasSelect = units_areas_for_select($db);
$sectionsSelect = units_sections_for_select($db);

$canCreate = can_create_form('units');
$canEdit = can_edit_form('units');
$canDelete = can_delete_form('units');

$pageTitle = 'Unitats';
$activeNav = 'units';
$extraCss = ['css/module-users.css'];
$extraScripts = ['units.js'];
$unitsPageInlineConfig = [
    'apiUrl' => app_url('units_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/units/index.php';
require APP_ROOT . '/includes/footer.php';

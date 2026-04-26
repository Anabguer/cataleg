<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('areas');
require_once APP_ROOT . '/includes/areas/areas.php';
require_once APP_ROOT . '/includes/areas/areas_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'active' => get_string('active')];
$sortIn = areas_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
$sortBy = $sortIn['by'];
$sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page'); if ($perPage < 1) { $perPage = 20; } if ($perPage > 100) { $perPage = 100; }
$page = (int) get_string('page'); if ($page < 1) { $page = 1; }
$totalAreas = areas_count($db, $filters);
$pg = areas_normalize_pagination($page, $perPage, $totalAreas);
$page = $pg['page']; $perPage = $pg['per_page']; $totalPages = $pg['total_pages']; $offset = $pg['offset'];
$areaRows = areas_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('areas');
$canEdit = can_edit_form('areas');
$canDelete = can_delete_form('areas');

$pageTitle = 'Àrees';
$activeNav = 'areas';
$extraCss = ['css/module-users.css'];
$extraScripts = ['areas.js'];
$areasPageInlineConfig = [
    'apiUrl' => app_url('areas_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/areas/index.php';
require APP_ROOT . '/includes/footer.php';

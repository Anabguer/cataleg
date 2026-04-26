<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_authorizers');
require_once APP_ROOT . '/includes/training_authorizers/training_authorizers.php';
require_once APP_ROOT . '/includes/training_authorizers/training_authorizers_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'area_id' => get_string('area_id'),
    'active' => get_string('active'),
];
$sortIn = training_authorizers_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
$sortBy = $sortIn['by'];
$sortDir = $sortIn['dir'];

$perPage = (int) get_string('per_page');
if ($perPage < 1) { $perPage = 20; }
if ($perPage > 100) { $perPage = 100; }
$page = (int) get_string('page');
if ($page < 1) { $page = 1; }

$totalRows = training_authorizers_count($db, $filters);
$pg = training_authorizers_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];

$rows = training_authorizers_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);
$areasSelect = training_authorizers_areas_for_select($db);

$canCreate = can_create_form('training_authorizers');
$canEdit = can_edit_form('training_authorizers');
$canDelete = can_delete_form('training_authorizers');

$pageTitle = 'Autoritzadors de formació';
$activeNav = 'training_authorizers';
$extraCss = ['css/module-users.css'];
$extraScripts = ['training_authorizers.js'];
$trainingAuthorizersPageInlineConfig = [
    'apiUrl' => app_url('training_authorizers_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_authorizers/index.php';
require APP_ROOT . '/includes/footer.php';

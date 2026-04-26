<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_reports');
require_once APP_ROOT . '/includes/training_reports/training_reports.php';
require_once APP_ROOT . '/includes/training_reports/training_reports_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'active' => get_string('active'),
    'show_in_general_selector' => get_string('show_in_general_selector'),
];
$sortIn = training_reports_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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
$totalRows = training_reports_count($db, $filters);
$pg = training_reports_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = training_reports_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('training_reports');
$canEdit = can_edit_form('training_reports');
$canDelete = can_delete_form('training_reports');

$pageTitle = 'Manteniment d\'informes';
$activeNav = 'training_reports';
$extraCss = ['css/module-users.css'];
$extraScripts = ['training_reports.js'];
$trainingReportsPageInlineConfig = [
    'apiUrl' => app_url('training_reports_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_reports/index.php';
require APP_ROOT . '/includes/footer.php';

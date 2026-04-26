<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('people');
require_once APP_ROOT . '/includes/people/people.php';
require_once APP_ROOT . '/includes/people/people_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'active' => get_string('active'),
    'job_position_id' => get_string('job_position_id'),
    'has_job_position' => get_string('has_job_position'),
    'is_catalog' => get_string('is_catalog'),
];
$sortIn = people_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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
$totalRows = people_count($db, $filters);
$pg = people_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = people_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$jobPositionsSelect = people_job_positions_for_filter($db);
$jobPositionsFormModal = people_job_positions_for_form_modal($db);

$canCreate = can_create_form('people');
$canEdit = can_edit_form('people');
$canDelete = can_delete_form('people');

$pageTitle = 'Persones';
$activeNav = 'people';
$extraCss = ['css/module-users.css'];
$extraScripts = ['people.js'];
$peoplePageInlineConfig = [
    'apiUrl' => app_url('people_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'nextPersonCode' => people_next_person_code($db),
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/people/index.php';
require APP_ROOT . '/includes/footer.php';

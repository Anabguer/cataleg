<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_organizers');
require_once APP_ROOT . '/includes/training_organizers/training_organizers.php';
require_once APP_ROOT . '/includes/training_organizers/training_organizers_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'active' => get_string('active')];
$sortIn = training_organizers_normalize_sort(get_string('sort_by'), get_string('sort_dir')); $sortBy = $sortIn['by']; $sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page'); if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100;
$page = (int) get_string('page'); if ($page < 1) $page = 1;
$totalRows = training_organizers_count($db, $filters);
$pg = training_organizers_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page']; $perPage = $pg['per_page']; $totalPages = $pg['total_pages']; $offset = $pg['offset'];
$rows = training_organizers_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('training_organizers');
$canEdit = can_edit_form('training_organizers');
$canDelete = can_delete_form('training_organizers');

$pageTitle = 'Organitzadors';
$activeNav = 'training_organizers';
$extraCss = ['css/module-users.css'];
$extraScripts = ['training_organizers.js'];
$trainingOrganizersPageInlineConfig = [
    'apiUrl' => app_url('training_organizers_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_organizers/index.php';
require APP_ROOT . '/includes/footer.php';

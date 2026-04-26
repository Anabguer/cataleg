<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_catalog_actions');
require_once APP_ROOT . '/includes/training_catalog_actions/training_catalog_actions.php';
require_once APP_ROOT . '/includes/training_catalog_actions/training_catalog_actions_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'active' => get_string('active'),
    'knowledge_area_id' => get_string('knowledge_area_id'),
    'status' => get_string('status'),
];
$sortIn = training_catalog_actions_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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
$totalRows = training_catalog_actions_count($db, $filters);
$pg = training_catalog_actions_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = training_catalog_actions_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);
$knowledgeAreas = training_catalog_actions_knowledge_areas_for_filter($db);
$knowledgeAreasFormModal = training_catalog_actions_knowledge_areas_for_form_modal($db);

$canCreate = can_create_form('training_catalog_actions');
$canEdit = can_edit_form('training_catalog_actions');
$canDelete = can_delete_form('training_catalog_actions');

$pageTitle = 'Catàleg d’accions formatives';
$activeNav = 'training_catalog_actions';
$extraCss = ['css/module-users.css'];
$extraScripts = ['training_catalog_actions.js'];
$nextCode = training_catalog_actions_next_action_code($db);
$trainingCatalogActionsPageInlineConfig = [
    'apiUrl' => app_url('training_catalog_actions_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'nextCodeDisplay' => format_padded_code($nextCode, 5),
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_catalog_actions/index.php';
require APP_ROOT . '/includes/footer.php';

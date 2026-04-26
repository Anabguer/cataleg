<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('knowledge_areas');
require_once APP_ROOT . '/includes/knowledge_areas/knowledge_areas.php';
require_once APP_ROOT . '/includes/knowledge_areas/knowledge_areas_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'active' => get_string('active')];
$sortIn = knowledge_areas_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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
$totalRows = knowledge_areas_count($db, $filters);
$pg = knowledge_areas_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = knowledge_areas_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('knowledge_areas');
$canEdit = can_edit_form('knowledge_areas');
$canDelete = can_delete_form('knowledge_areas');

$pageTitle = 'Àrees de coneixement';
$activeNav = 'knowledge_areas';
$extraCss = ['css/module-users.css'];
$extraScripts = ['knowledge_areas.js'];
$nextCode = knowledge_areas_next_code($db);
$knowledgeAreasPageInlineConfig = [
    'apiUrl' => app_url('knowledge_areas_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'nextCodeDisplay' => format_padded_code($nextCode, 3),
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/knowledge_areas/index.php';
require APP_ROOT . '/includes/footer.php';

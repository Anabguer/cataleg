<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('sections');
require_once APP_ROOT . '/includes/sections/sections.php';
require_once APP_ROOT . '/includes/sections/sections_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'area_id' => get_string('area_id'), 'active' => get_string('active')];
$sortIn = sections_normalize_sort(get_string('sort_by'), get_string('sort_dir')); $sortBy = $sortIn['by']; $sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page'); if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100;
$page = (int) get_string('page'); if ($page < 1) $page = 1;
$totalSections = sections_count($db, $filters);
$pg = sections_normalize_pagination($page, $perPage, $totalSections);
$page = $pg['page']; $perPage = $pg['per_page']; $totalPages = $pg['total_pages']; $offset = $pg['offset'];
$sectionRows = sections_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);
$areasSelect = sections_areas_for_select($db);

$canCreate = can_create_form('sections');
$canEdit = can_edit_form('sections');
$canDelete = can_delete_form('sections');

$pageTitle = 'Seccions';
$activeNav = 'sections';
$extraCss = ['css/module-users.css'];
$extraScripts = ['sections.js'];
$sectionsPageInlineConfig = [
    'apiUrl' => app_url('sections_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/sections/index.php';
require APP_ROOT . '/includes/footer.php';

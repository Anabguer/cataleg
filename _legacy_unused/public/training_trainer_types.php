<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_trainer_types');
require_once APP_ROOT . '/includes/training_trainer_types/training_trainer_types.php';
require_once APP_ROOT . '/includes/training_trainer_types/training_trainer_types_view_helpers.php';

$db = db();
$filters = ['q' => get_string('q'), 'active' => get_string('active')];
$sortIn = training_trainer_types_normalize_sort(get_string('sort_by'), get_string('sort_dir')); $sortBy = $sortIn['by']; $sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page'); if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100;
$page = (int) get_string('page'); if ($page < 1) $page = 1;
$totalRows = training_trainer_types_count($db, $filters);
$pg = training_trainer_types_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page']; $perPage = $pg['per_page']; $totalPages = $pg['total_pages']; $offset = $pg['offset'];
$rows = training_trainer_types_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('training_trainer_types');
$canEdit = can_edit_form('training_trainer_types');
$canDelete = can_delete_form('training_trainer_types');

$pageTitle = 'Tipus de formador';
$activeNav = 'training_trainer_types';
$extraCss = ['css/module-users.css'];
$extraScripts = ['training_trainer_types.js'];
$trainingTrainerTypesPageInlineConfig = [
    'apiUrl' => app_url('training_trainer_types_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_trainer_types/index.php';
require APP_ROOT . '/includes/footer.php';

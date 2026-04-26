<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('users');
require_once APP_ROOT . '/includes/users/users.php';
require_once APP_ROOT . '/includes/users/users_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
    'role_id' => get_string('role_id'),
    'active' => get_string('active'),
];

$sortIn = users_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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

$totalUsers = users_count($db, $filters);
$pagination = users_normalize_pagination($page, $perPage, $totalUsers);
$page = $pagination['page'];
$perPage = $pagination['per_page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$userRows = users_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);
$roles = users_roles_for_select($db);

$canCreate = can_create_form('users');
$canEdit = can_edit_form('users');
$canDelete = can_delete_form('users');
$currentUserId = auth_user_id();

$pageTitle = 'Usuaris';
$activeNav = 'users';
$extraCss = ['css/module-users.css'];
$extraScripts = ['users.js'];

$usersPageInlineConfig = [
    'apiUrl' => app_url('users_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'currentUserId' => $currentUserId,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/users/index.php';
require APP_ROOT . '/includes/footer.php';

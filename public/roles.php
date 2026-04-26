<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('roles');
require_once APP_ROOT . '/includes/roles/roles.php';
require_once APP_ROOT . '/includes/roles/roles_view_helpers.php';

$db = db();
$filters = [
    'q' => get_string('q'),
];

$sortIn = roles_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
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

$totalRoles = roles_count($db, $filters);
$pagination = roles_normalize_pagination($page, $perPage, $totalRoles);
$page = $pagination['page'];
$perPage = $pagination['per_page'];
$totalPages = $pagination['total_pages'];
$offset = $pagination['offset'];

$roleRows = roles_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$canCreate = can_create_form('roles');
$canEdit = can_edit_form('roles');
$canDelete = can_delete_form('roles');

$pageTitle = 'Rols';
$activeNav = 'roles';
$extraCss = ['css/module-users.css'];
$extraScripts = ['roles.js'];

$rolesProtectedSlug = permissions_administrator_role_slug();
$rolesActorIsSystemAdmin = permissions_actor_is_system_administrator();

$rolesPageInlineConfig = [
    'apiUrl' => app_url('roles_api.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'protectedRoleSlug' => $rolesProtectedSlug,
    'actorIsSystemAdmin' => $rolesActorIsSystemAdmin,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/roles/index.php';
require APP_ROOT . '/includes/footer.php';

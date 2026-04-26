<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('permissions');
require_once APP_ROOT . '/includes/permissions/permissions.php';

$db = db();
$rolesOverview = permissions_roles_overview($db);
$roles = permissions_roles_for_select($db);
$requestedRoleId = (int) get_string('role_id');
$currentRoleId = permissions_normalize_role_id($roles, $requestedRoleId);
$currentRole = $currentRoleId > 0 ? permissions_get_role_by_id($db, $currentRoleId) : null;
$forms = $currentRoleId > 0 ? permissions_forms_with_role($db, $currentRoleId) : [];
$permissionGroups = permissions_group_forms($forms);
$roleUsers = $currentRoleId > 0 ? permissions_users_by_role($db, $currentRoleId) : [];
$usersPool = permissions_users_pool($db);
$canEditPermissions = can_edit_form('permissions');
$currentRoleIsAdmin = $currentRole !== null
    && (string) ($currentRole['slug'] ?? '') === permissions_administrator_role_slug();
$canEditAdminRoleMatrix = !$currentRoleIsAdmin || permissions_actor_is_system_administrator();
$canEditPermissionsUi = $canEditPermissions && $canEditAdminRoleMatrix;

$pageTitle = 'Permisos';
$activeNav = 'permissions';
$extraCss = ['css/module-users.css'];
$extraScripts = ['permissions.js'];
$permissionsPageInlineConfig = [
    'apiUrl' => app_url('permissions_api.php'),
    'canEdit' => $canEditPermissions,
    'canEditEffective' => $canEditPermissionsUi,
    'currentRoleId' => $currentRoleId,
    'adminRoleSlug' => permissions_administrator_role_slug(),
    'actorIsSystemAdmin' => permissions_actor_is_system_administrator(),
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/permissions/index.php';
require APP_ROOT . '/includes/footer.php';

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/permissions/permissions.php';

auth_require_login();
permissions_load_for_session();

header('Content-Type: application/json; charset=utf-8');

function permissions_api_json(bool $success, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $success], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function permissions_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $action = get_string('action');
        if (!can_view_form('permissions')) {
            permissions_api_json(false, ['errors' => ['_general' => 'Sense permís per veure permisos.']], 403);
            exit;
        }
        if ($action !== 'get') {
            permissions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
            exit;
        }

        $roleId = (int) get_string('role_id');
        if ($roleId < 1) {
            permissions_api_json(false, ['errors' => ['_general' => 'Rol no vàlid']], 400);
            exit;
        }

        $db = db();
        $role = permissions_get_role_by_id($db, $roleId);
        if (!$role) {
            permissions_api_json(false, ['errors' => ['_general' => 'Rol no trobat']], 404);
            exit;
        }
        $forms = permissions_forms_with_role($db, $roleId);
        $groups = permissions_group_forms($forms);
        $perms = [];
        foreach ($forms as $f) {
            $p = $f['permissions'];
            $perms[] = [
                'form_id' => (int) $f['id'],
                'can_view' => (int) $p['can_view'],
                'can_create' => (int) $p['can_create'],
                'can_edit' => (int) $p['can_edit'],
                'can_delete' => (int) $p['can_delete'],
            ];
        }

        $roleUsers = permissions_users_by_role($db, $roleId);
        $usersPool = permissions_users_pool($db);
        $rolesOverview = permissions_roles_overview($db);

        permissions_api_json(true, [
            'role_id' => $roleId,
            'role' => $role,
            'groups' => $groups,
            'group_options' => permissions_form_group_options(),
            'permissions' => $perms,
            'role_users' => $roleUsers,
            'users_pool' => $usersPool,
            'roles_overview' => $rolesOverview,
        ]);
        exit;
    }

    if ($method !== 'POST') {
        permissions_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    $in = permissions_api_read_json();
    $action = (string) ($in['action'] ?? '');
    if (!can_edit_form('permissions')) {
        permissions_api_json(false, ['errors' => ['_general' => 'Sense permís per editar permisos.']], 403);
        exit;
    }

    $db = db();

    if ($action === 'save') {
        $roleId = (int) ($in['role_id'] ?? 0);
        if ($roleId < 1) {
            permissions_api_json(false, ['errors' => ['_general' => 'Rol no vàlid']], 400);
            exit;
        }
        if (permissions_target_role_is_system_administrator($db, $roleId) && !permissions_actor_is_system_administrator()) {
            permissions_api_json(false, ['errors' => ['_general' => 'Només un usuari amb rol administrador del sistema pot modificar els permisos d’aquest rol.']], 403);
            exit;
        }
        $matrix = $in['permissions'] ?? [];
        if (!is_array($matrix)) {
            $matrix = [];
        }
        $formsConfig = $in['forms_config'] ?? [];
        if (!is_array($formsConfig)) {
            $formsConfig = [];
        }
        $db->beginTransaction();
        try {
            permissions_save_matrix($db, $roleId, $matrix);
            permissions_save_forms_configuration($db, $formsConfig);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        permissions_api_json(true, ['message' => 'Permisos desats correctament.']);
        exit;
    }

    if ($action === 'assign_user') {
        $roleId = (int) ($in['role_id'] ?? 0);
        $userId = (int) ($in['user_id'] ?? 0);
        if ($roleId < 1 || $userId < 1) {
            permissions_api_json(false, ['errors' => ['_general' => 'Dades no vàlides']], 400);
            exit;
        }
        if (permissions_target_role_is_system_administrator($db, $roleId) && !permissions_actor_is_system_administrator()) {
            permissions_api_json(false, ['errors' => ['_general' => 'Només un usuari amb rol administrador del sistema pot modificar els permisos d’aquest rol.']], 403);
            exit;
        }
        permissions_assign_user_to_role($db, $userId, $roleId);
        permissions_api_json(true, ['message' => 'Usuari assignat al rol.']);
        exit;
    }

    if ($action === 'remove_user') {
        $fromRoleId = (int) ($in['role_id'] ?? 0);
        $userId = (int) ($in['user_id'] ?? 0);
        if ($fromRoleId < 1 || $userId < 1) {
            permissions_api_json(false, ['errors' => ['_general' => 'Dades no vàlides']], 400);
            exit;
        }
        if (permissions_target_role_is_system_administrator($db, $fromRoleId) && !permissions_actor_is_system_administrator()) {
            permissions_api_json(false, ['errors' => ['_general' => 'Només un usuari amb rol administrador del sistema pot modificar els permisos d’aquest rol.']], 403);
            exit;
        }
        permissions_unassign_user_role($db, $userId, $fromRoleId);
        permissions_api_json(true, ['message' => 'Usuari sense rol assignat.']);
        exit;
    }

    permissions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    if ($e instanceof InvalidArgumentException) {
        permissions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 400);
        exit;
    }
    $msg = 'Error intern';
    if ($e instanceof RuntimeException) {
        $msg = $e->getMessage();
    }
    permissions_api_json(false, ['errors' => ['_general' => $msg]], 500);
}


<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/roles/roles.php';

auth_require_login();
permissions_load_for_session();

header('Content-Type: application/json; charset=utf-8');

function roles_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function roles_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function roles_api_csrf_verify(array $in): bool
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');
    return isset($_SESSION['csrf_token']) && is_string($token)
        && hash_equals((string) $_SESSION['csrf_token'], (string) $token);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $action = get_string('action');
        if ($action === 'get') {
            require_can_view('roles');
            $id = (int) get_string('id');
            if ($id < 1) {
                roles_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
                exit;
            }
            $r = roles_get_by_id(db(), $id);
            if (!$r) {
                roles_api_json(false, ['errors' => ['_general' => 'Rol no trobat']], 404);
                exit;
            }
            roles_api_json(true, ['role' => $r]);
            exit;
        }

        roles_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
        exit;
    }

    if ($method !== 'POST') {
        roles_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    $in = roles_api_read_json();
    if (!roles_api_csrf_verify($in)) {
        roles_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }

    $action = (string) ($in['action'] ?? '');
    $db = db();

    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        $isCreate = $id < 1;

        if ($isCreate) {
            if (!can_create_form('roles')) {
                roles_api_json(false, ['errors' => ['_general' => 'Sense permís per crear rols.']], 403);
                exit;
            }
        } else {
            if (!can_edit_form('roles')) {
                roles_api_json(false, ['errors' => ['_general' => 'Sense permís per editar rols.']], 403);
                exit;
            }
            $target = roles_get_by_id($db, $id);
            if (
                $target !== null
                && (string) $target['slug'] === permissions_administrator_role_slug()
                && !permissions_actor_is_system_administrator()
            ) {
                roles_api_json(false, ['errors' => ['_general' => 'Només un usuari amb rol administrador del sistema pot editar aquest rol.']], 403);
                exit;
            }
        }

        $payload = [
            'name' => $in['name'] ?? '',
            'slug' => $in['slug'] ?? '',
            'description' => $in['description'] ?? '',
        ];

        try {
            if ($isCreate) {
                $newId = roles_create($db, $payload);
                roles_api_json(true, ['id' => $newId, 'message' => 'Rol creat correctament.']);
            } else {
                roles_update($db, $id, $payload);
                roles_api_json(true, ['id' => $id, 'message' => 'Rol actualitzat correctament.']);
            }
        } catch (Throwable $e) {
            $errors = roles_parse_validation_exception($e);
            if ($errors !== null) {
                roles_api_json(false, ['errors' => $errors], 422);
            } else {
                roles_api_json(false, ['errors' => ['_general' => 'Error en desar el rol.']], 500);
            }
        }
        exit;
    }

    if ($action === 'delete') {
        if (!can_delete_form('roles')) {
            roles_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar rols.']], 403);
            exit;
        }
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1) {
            roles_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        try {
            roles_delete($db, $id);
            roles_api_json(true, ['message' => 'Rol eliminat correctament.']);
        } catch (Throwable $e) {
            $errors = roles_parse_validation_exception($e);
            if ($errors !== null) {
                roles_api_json(false, ['errors' => $errors], 422);
            } else {
                roles_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar el rol.']], 500);
            }
        }
        exit;
    }

    roles_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    roles_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}


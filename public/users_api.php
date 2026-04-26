<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/users/users.php';

auth_require_login();
permissions_load_for_session();

header('Content-Type: application/json; charset=utf-8');

function users_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function users_api_require_view(): void
{
    if (!can_view_form('users')) {
        users_api_json(false, ['errors' => ['_general' => 'Sense permís per veure usuaris.']], 403);
        exit;
    }
}

function users_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function users_api_csrf_verify(array $in): bool
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
            users_api_require_view();
            $id = (int) get_string('id');
            if ($id < 1) {
                users_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
                exit;
            }
            $u = users_get_by_id(db(), $id);
            if (!$u) {
                users_api_json(false, ['errors' => ['_general' => 'Usuari no trobat']], 404);
                exit;
            }
            unset($u['password_hash']);
            $u['is_active'] = (bool) $u['is_active'];
            $u['role_id'] = $u['role_id'] !== null ? (int) $u['role_id'] : null;
            users_api_json(true, ['user' => $u]);
            exit;
        }
        users_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
        exit;
    }

    if ($method !== 'POST') {
        users_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    $in = users_api_read_json();
    if (!users_api_csrf_verify($in)) {
        users_api_json(false, ['errors' => ['_general' => 'Token de seguretat invàlid o caducat.']], 403);
        exit;
    }

    $action = (string) ($in['action'] ?? '');
    $pdo = db();

    if ($action === 'save') {
        $id = null_if_empty_int($in['id'] ?? null);
        if ($id === null) {
            if (!can_create_form('users')) {
                users_api_json(false, ['errors' => ['_general' => 'Sense permís per crear usuaris.']], 403);
                exit;
            }
        } else {
            if (!can_edit_form('users')) {
                users_api_json(false, ['errors' => ['_general' => 'Sense permís per editar usuaris.']], 403);
                exit;
            }
        }
        try {
            if ($id === null) {
                $newId = users_create($pdo, $in);
                users_api_json(true, ['message' => 'Usuari creat.', 'id' => $newId]);
            } else {
                users_update($pdo, $id, $in);
                users_api_json(true, ['message' => 'Usuari actualitzat.', 'id' => $id]);
            }
        } catch (InvalidArgumentException $e) {
            $errors = users_parse_validation_exception($e);
            if ($errors !== null) {
                users_api_json(false, ['errors' => $errors], 422);
            }
            users_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            users_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        }
        exit;
    }

    if ($action === 'delete') {
        if (!can_delete_form('users')) {
            users_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar usuaris.']], 403);
            exit;
        }
        $id = null_if_empty_int($in['id'] ?? null);
        if ($id === null) {
            users_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        $me = auth_user_id();
        if ($me === null) {
            users_api_json(false, ['errors' => ['_general' => 'Sessió invàlida']], 401);
            exit;
        }
        try {
            users_delete($pdo, $id, $me);
            users_api_json(true, ['message' => 'Usuari eliminat.']);
        } catch (InvalidArgumentException $e) {
            users_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            users_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        }
        exit;
    }

    users_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    users_api_json(false, ['errors' => ['_general' => 'Error intern.']], 500);
}

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/job_positions/job_positions.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function job_positions_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function job_positions_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $d = $raw !== '' ? json_decode($raw, true) : [];
    return is_array($d) ? $d : [];
}

function job_positions_api_csrf_verify(array $in): bool
{
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');
    return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('job_positions');
        if (get_string('action') !== 'get') {
            job_positions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
            exit;
        }
        $id = (int) get_string('id');
        $row = job_positions_get_by_id(db(), $id);
        if (!$row) {
            job_positions_api_json(false, ['errors' => ['_general' => 'Lloc de treball no trobat']], 404);
            exit;
        }
        job_positions_api_json(true, ['job_position' => $row]);
        exit;
    }
    if ($method !== 'POST') {
        job_positions_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }
    $in = job_positions_api_read_json();
    if (!job_positions_api_csrf_verify($in)) {
        job_positions_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }
    $action = (string) ($in['action'] ?? '');
    $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('job_positions')) {
            job_positions_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403);
            exit;
        }
        if ($id > 0 && !can_edit_form('job_positions')) {
            job_positions_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403);
            exit;
        }
        try {
            if ($id < 1) {
                $payload = [
                    'assignment_mode' => $in['assignment_mode'] ?? '',
                    'existing_unit_id' => $in['existing_unit_id'] ?? '',
                    'position_number' => $in['position_number'] ?? '',
                    'name' => $in['name'] ?? '',
                    'is_active' => $in['is_active'] ?? '0',
                ];
                $newId = job_positions_create($db, $payload);
                job_positions_api_json(true, ['id' => $newId, 'message' => 'Lloc de treball creat correctament.']);
            } else {
                $payload = [
                    'position_number' => $in['position_number'] ?? '',
                    'name' => $in['name'] ?? '',
                    'is_active' => $in['is_active'] ?? '0',
                ];
                job_positions_update($db, $id, $payload);
                job_positions_api_json(true, ['id' => $id, 'message' => 'Lloc de treball actualitzat correctament.']);
            }
        } catch (Throwable $e) {
            $errors = job_positions_parse_validation_exception($e);
            if ($errors !== null) {
                job_positions_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                job_positions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                job_positions_api_json(false, ['errors' => ['_general' => 'Error en desar el lloc de treball.']], 500);
            }
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('job_positions')) {
            job_positions_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403);
            exit;
        }
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1) {
            job_positions_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        try {
            job_positions_delete($db, $id);
            job_positions_api_json(true, ['message' => 'Lloc de treball eliminat correctament.']);
        } catch (Throwable $e) {
            $errors = job_positions_parse_validation_exception($e);
            if ($errors !== null) {
                job_positions_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                job_positions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                job_positions_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar el lloc de treball.']], 500);
            }
        }
        exit;
    }
    job_positions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    job_positions_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

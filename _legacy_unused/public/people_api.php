<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/people/people.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function people_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function people_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $d = $raw !== '' ? json_decode($raw, true) : [];
    return is_array($d) ? $d : [];
}

function people_api_csrf_verify(array $in): bool
{
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');
    return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('people');
        if (get_string('action') !== 'get') {
            people_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
            exit;
        }
        $id = (int) get_string('id');
        $row = people_get_by_id(db(), $id);
        if (!$row) {
            people_api_json(false, ['errors' => ['_general' => 'Persona no trobada']], 404);
            exit;
        }
        people_api_json(true, ['person' => people_person_for_api($row)]);
        exit;
    }
    if ($method !== 'POST') {
        people_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }
    $in = people_api_read_json();
    if (!people_api_csrf_verify($in)) {
        people_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }
    $action = (string) ($in['action'] ?? '');
    $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('people')) {
            people_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403);
            exit;
        }
        if ($id > 0 && !can_edit_form('people')) {
            people_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403);
            exit;
        }
        $payload = [
            'last_name_1' => $in['last_name_1'] ?? '',
            'last_name_2' => $in['last_name_2'] ?? '',
            'first_name' => $in['first_name'] ?? '',
            'dni' => $in['dni'] ?? '',
            'email' => $in['email'] ?? '',
            'job_position_id' => $in['job_position_id'] ?? '',
            'is_active' => $in['is_active'] ?? '0',
        ];
        try {
            if ($id < 1) {
                $newId = people_create($db, $payload);
                people_api_json(true, ['id' => $newId, 'message' => 'Persona creada correctament.']);
            } else {
                people_update($db, $id, $payload);
                people_api_json(true, ['id' => $id, 'message' => 'Persona actualitzada correctament.']);
            }
        } catch (Throwable $e) {
            $errors = people_parse_validation_exception($e);
            if ($errors !== null) {
                people_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                people_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                people_api_json(false, ['errors' => ['_general' => 'Error en desar la persona.']], 500);
            }
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('people')) {
            people_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403);
            exit;
        }
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1) {
            people_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        try {
            people_delete($db, $id);
            people_api_json(true, ['message' => 'Persona eliminada correctament.']);
        } catch (Throwable $e) {
            $errors = people_parse_validation_exception($e);
            if ($errors !== null) {
                people_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                people_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                people_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar la persona.']], 500);
            }
        }
        exit;
    }
    people_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    people_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_trainer_types/training_trainer_types.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function training_trainer_types_api_json(bool $ok, array $payload = [], int $code = 200): void { http_response_code($code); echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
function training_trainer_types_api_read_json(): array { $raw = file_get_contents('php://input') ?: ''; $d = $raw !== '' ? json_decode($raw, true) : []; return is_array($d) ? $d : []; }
function training_trainer_types_api_csrf_verify(array $in): bool { $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? ''); return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('training_trainer_types');
        if (get_string('action') !== 'get') { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400); exit; }
        $id = (int) get_string('id'); $row = training_trainer_types_get_by_id(db(), $id);
        if (!$row) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Tipus de formador no trobat']], 404); exit; }
        training_trainer_types_api_json(true, ['trainer_type' => $row]); exit;
    }
    if ($method !== 'POST') { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405); exit; }
    $in = training_trainer_types_api_read_json();
    if (!training_trainer_types_api_csrf_verify($in)) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403); exit; }
    $action = (string) ($in['action'] ?? '');
    $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('training_trainer_types')) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403); exit; }
        if ($id > 0 && !can_edit_form('training_trainer_types')) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403); exit; }
        $payload = ['trainer_type_code' => $in['trainer_type_code'] ?? '', 'name' => $in['name'] ?? '', 'is_active' => $in['is_active'] ?? '0'];
        try {
            if ($id < 1) { $newId = training_trainer_types_create($db, $payload); training_trainer_types_api_json(true, ['id' => $newId, 'message' => 'Tipus de formador creat correctament.']); }
            else { training_trainer_types_update($db, $id, $payload); training_trainer_types_api_json(true, ['id' => $id, 'message' => 'Tipus de formador actualitzat correctament.']); }
        } catch (Throwable $e) {
            $errors = training_trainer_types_parse_validation_exception($e);
            if ($errors !== null) training_trainer_types_api_json(false, ['errors' => $errors], 422);
            training_trainer_types_api_json(false, ['errors' => ['_general' => 'Error en desar el tipus de formador.']], 500);
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('training_trainer_types')) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403); exit; }
        $id = isset($in['id']) ? (int) $in['id'] : 0; if ($id < 1) { training_trainer_types_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400); exit; }
        try { training_trainer_types_delete($db, $id); training_trainer_types_api_json(true, ['message' => 'Tipus de formador eliminat correctament.']); }
        catch (Throwable $e) {
            $errors = training_trainer_types_parse_validation_exception($e);
            if ($errors !== null) training_trainer_types_api_json(false, ['errors' => $errors], 422);
            training_trainer_types_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar el tipus de formador.']], 500);
        }
        exit;
    }
    training_trainer_types_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    training_trainer_types_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

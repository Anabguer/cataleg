<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_organizers/training_organizers.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function training_organizers_api_json(bool $ok, array $payload = [], int $code = 200): void { http_response_code($code); echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
function training_organizers_api_read_json(): array { $raw = file_get_contents('php://input') ?: ''; $d = $raw !== '' ? json_decode($raw, true) : []; return is_array($d) ? $d : []; }
function training_organizers_api_csrf_verify(array $in): bool { $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? ''); return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('training_organizers');
        if (get_string('action') !== 'get') { training_organizers_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400); exit; }
        $id = (int) get_string('id'); $row = training_organizers_get_by_id(db(), $id);
        if (!$row) { training_organizers_api_json(false, ['errors' => ['_general' => 'Organitzador no trobat']], 404); exit; }
        training_organizers_api_json(true, ['organizer' => $row]); exit;
    }
    if ($method !== 'POST') { training_organizers_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405); exit; }
    $in = training_organizers_api_read_json();
    if (!training_organizers_api_csrf_verify($in)) { training_organizers_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403); exit; }
    $action = (string) ($in['action'] ?? '');
    $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('training_organizers')) { training_organizers_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403); exit; }
        if ($id > 0 && !can_edit_form('training_organizers')) { training_organizers_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403); exit; }
        $payload = ['organizer_code' => $in['organizer_code'] ?? '', 'name' => $in['name'] ?? '', 'is_active' => $in['is_active'] ?? '0'];
        try {
            if ($id < 1) { $newId = training_organizers_create($db, $payload); training_organizers_api_json(true, ['id' => $newId, 'message' => 'Organitzador creat correctament.']); }
            else { training_organizers_update($db, $id, $payload); training_organizers_api_json(true, ['id' => $id, 'message' => 'Organitzador actualitzat correctament.']); }
        } catch (Throwable $e) {
            $errors = training_organizers_parse_validation_exception($e);
            if ($errors !== null) training_organizers_api_json(false, ['errors' => $errors], 422);
            training_organizers_api_json(false, ['errors' => ['_general' => 'Error en desar l’organitzador.']], 500);
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('training_organizers')) { training_organizers_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403); exit; }
        $id = isset($in['id']) ? (int) $in['id'] : 0; if ($id < 1) { training_organizers_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400); exit; }
        try { training_organizers_delete($db, $id); training_organizers_api_json(true, ['message' => 'Organitzador eliminat correctament.']); }
        catch (Throwable $e) {
            $errors = training_organizers_parse_validation_exception($e);
            if ($errors !== null) training_organizers_api_json(false, ['errors' => $errors], 422);
            training_organizers_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar l’organitzador.']], 500);
        }
        exit;
    }
    training_organizers_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    training_organizers_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/units/units.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function units_api_json(bool $ok, array $payload = [], int $code = 200): void { http_response_code($code); echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
function units_api_read_json(): array { $raw = file_get_contents('php://input') ?: ''; $d = $raw !== '' ? json_decode($raw, true) : []; return is_array($d) ? $d : []; }
function units_api_csrf_verify(array $in): bool { $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? ''); return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('units');
        if (get_string('action') !== 'get') { units_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400); exit; }
        $id = (int) get_string('id'); $row = units_get_by_id(db(), $id);
        if (!$row) { units_api_json(false, ['errors' => ['_general' => 'Unitat no trobada']], 404); exit; }
        units_api_json(true, ['unit' => $row]); exit;
    }
    if ($method !== 'POST') { units_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405); exit; }
    $in = units_api_read_json(); if (!units_api_csrf_verify($in)) { units_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403); exit; }
    $action = (string) ($in['action'] ?? ''); $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('units')) { units_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403); exit; }
        if ($id > 0 && !can_edit_form('units')) { units_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403); exit; }
        $payload = ['unit_code' => $in['unit_code'] ?? '', 'name' => $in['name'] ?? '', 'section_id' => $in['section_id'] ?? '', 'is_active' => $in['is_active'] ?? '0'];
        try {
            if ($id < 1) { $newId = units_create($db, $payload); units_api_json(true, ['id' => $newId, 'message' => 'Unitat creada correctament.']); }
            else { units_update($db, $id, $payload); units_api_json(true, ['id' => $id, 'message' => 'Unitat actualitzada correctament.']); }
        } catch (Throwable $e) {
            $errors = units_parse_validation_exception($e);
            if ($errors !== null) {
                units_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                units_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                units_api_json(false, ['errors' => ['_general' => 'Error en desar la unitat.']], 500);
            }
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('units')) { units_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403); exit; }
        $id = isset($in['id']) ? (int) $in['id'] : 0; if ($id < 1) { units_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400); exit; }
        try {
            units_delete($db, $id);
            units_api_json(true, ['message' => 'Unitat eliminada correctament.']);
        } catch (Throwable $e) {
            $errors = units_parse_validation_exception($e);
            if ($errors !== null) {
                units_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                units_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                units_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar la unitat.']], 500);
            }
        }
        exit;
    }
    units_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    units_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

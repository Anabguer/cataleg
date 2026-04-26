<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/sections/sections.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function sections_api_json(bool $ok, array $payload = [], int $code = 200): void { http_response_code($code); echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
function sections_api_read_json(): array { $raw = file_get_contents('php://input') ?: ''; $d = $raw !== '' ? json_decode($raw, true) : []; return is_array($d) ? $d : []; }
function sections_api_csrf_verify(array $in): bool { $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? ''); return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('sections');
        if (get_string('action') !== 'get') { sections_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400); exit; }
        $id = (int) get_string('id');
        $row = sections_get_by_id(db(), $id);
        if (!$row) { sections_api_json(false, ['errors' => ['_general' => 'Secció no trobada']], 404); exit; }
        sections_api_json(true, ['section' => $row]); exit;
    }
    if ($method !== 'POST') { sections_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405); exit; }
    $in = sections_api_read_json();
    if (!sections_api_csrf_verify($in)) { sections_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403); exit; }
    $action = (string) ($in['action'] ?? ''); $db = db();
    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('sections')) { sections_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403); exit; }
        if ($id > 0 && !can_edit_form('sections')) { sections_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403); exit; }
        $payload = ['section_code' => $in['section_code'] ?? '', 'name' => $in['name'] ?? '', 'area_id' => $in['area_id'] ?? '', 'is_active' => $in['is_active'] ?? '0'];
        try {
            if ($id < 1) { $newId = sections_create($db, $payload); sections_api_json(true, ['id' => $newId, 'message' => 'Secció creada correctament.']); }
            else { sections_update($db, $id, $payload); sections_api_json(true, ['id' => $id, 'message' => 'Secció actualitzada correctament.']); }
        } catch (Throwable $e) {
            $errors = sections_parse_validation_exception($e);
            if ($errors !== null) {
                sections_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                sections_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                sections_api_json(false, ['errors' => ['_general' => 'Error en desar la secció.']], 500);
            }
        }
        exit;
    }
    if ($action === 'delete') {
        if (!can_delete_form('sections')) { sections_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403); exit; }
        $id = isset($in['id']) ? (int) $in['id'] : 0; if ($id < 1) { sections_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400); exit; }
        try {
            sections_delete($db, $id);
            sections_api_json(true, ['message' => 'Secció eliminada correctament.']);
        } catch (Throwable $e) {
            $errors = sections_parse_validation_exception($e);
            if ($errors !== null) {
                sections_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                sections_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                sections_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar la secció.']], 500);
            }
        }
        exit;
    }
    sections_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    sections_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

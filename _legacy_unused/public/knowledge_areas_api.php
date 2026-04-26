<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/knowledge_areas/knowledge_areas.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function knowledge_areas_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function knowledge_areas_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $d = $raw !== '' ? json_decode($raw, true) : [];
    return is_array($d) ? $d : [];
}

function knowledge_areas_api_csrf_verify(string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals((string) $_SESSION['csrf_token'], $token);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        require_can_view('knowledge_areas');
        if (get_string('action') !== 'get') {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
            exit;
        }
        $id = (int) get_string('id');
        $row = knowledge_areas_get_by_id(db(), $id);
        if (!$row) {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'Àrea no trobada']], 404);
            exit;
        }
        knowledge_areas_api_json(true, ['knowledge_area' => knowledge_areas_row_for_api($row)]);
        exit;
    }
    if ($method !== 'POST') {
        knowledge_areas_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    // multipart/form-data: PHP omple $_POST / $_FILES. Alguns servidors no passen CONTENT_TYPE
    // o usen HTTP_CONTENT_TYPE; si només llegíem JSON, $in quedava buit i "name" fallava (422).
    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isMultipart = stripos($ct, 'multipart/form-data') !== false;
    $hasImageUploadField = isset($_FILES['image']) && is_array($_FILES['image']);
    if ($isMultipart || $hasImageUploadField || (isset($_POST['action']) && $_POST['action'] === 'save')) {
        $in = $_POST;
        $isMultipart = $isMultipart || $hasImageUploadField;
        $csrf = (string) ($in['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    } else {
        $in = knowledge_areas_api_read_json();
        $csrf = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? ''));
    }

    if (!knowledge_areas_api_csrf_verify($csrf)) {
        knowledge_areas_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }

    $action = (string) ($in['action'] ?? '');
    $db = db();

    if ($action === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('knowledge_areas')) {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403);
            exit;
        }
        if ($id > 0 && !can_edit_form('knowledge_areas')) {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403);
            exit;
        }
        $payload = [
            'name' => $in['name'] ?? '',
            'is_active' => $in['is_active'] ?? '0',
        ];
        $removeImage = isset($in['remove_image']) && (string) $in['remove_image'] === '1';
        $file = (isset($_FILES['image']) && is_array($_FILES['image'])) ? $_FILES['image'] : null;
        try {
            if ($id < 1) {
                $newId = knowledge_areas_create($db, $payload, $file);
                knowledge_areas_api_json(true, ['id' => $newId, 'message' => 'Àrea creada correctament.']);
            } else {
                knowledge_areas_update($db, $id, $payload, $file, $removeImage);
                knowledge_areas_api_json(true, ['id' => $id, 'message' => 'Àrea actualitzada correctament.']);
            }
        } catch (Throwable $e) {
            $errors = knowledge_areas_parse_validation_exception($e);
            if ($errors !== null) {
                $errors = knowledge_areas_enrich_save_errors_with_debug($errors);
                knowledge_areas_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                $re = ['_general' => $e->getMessage()];
                $re = knowledge_areas_enrich_save_errors_with_debug($re);
                knowledge_areas_api_json(false, ['errors' => $re], 422);
            } else {
                knowledge_areas_api_json(false, ['errors' => ['_general' => 'Error en desar l’àrea.']], 500);
            }
        }
        exit;
    }

    if ($action === 'delete') {
        if (!can_delete_form('knowledge_areas')) {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403);
            exit;
        }
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1) {
            knowledge_areas_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        try {
            knowledge_areas_delete($db, $id);
            knowledge_areas_api_json(true, ['message' => 'Àrea eliminada correctament.']);
        } catch (Throwable $e) {
            $errors = knowledge_areas_parse_validation_exception($e);
            if ($errors !== null) {
                knowledge_areas_api_json(false, ['errors' => $errors], 422);
            } elseif ($e instanceof RuntimeException) {
                knowledge_areas_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            } else {
                knowledge_areas_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar l’àrea.']], 500);
            }
        }
        exit;
    }

    knowledge_areas_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    knowledge_areas_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

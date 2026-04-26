<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_documents.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_evaluations.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function training_actions_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function training_actions_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $d = $raw !== '' ? json_decode($raw, true) : [];

    return is_array($d) ? $d : [];
}

function training_actions_api_csrf_verify(array $in): bool
{
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');

    return isset($_SESSION['csrf_token']) && is_string($t) && hash_equals((string) $_SESSION['csrf_token'], (string) $t);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        require_can_view('training_actions');
        $action = get_string('action');
        $db = db();

        if ($action === 'get') {
            $id = (int) get_string('id');
            if ($id < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
                exit;
            }
            $row = training_actions_get_by_id($db, $id);
            if (!$row) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            training_actions_api_json(true, ['action' => training_actions_row_for_api($db, $row)]);
            exit;
        }

        if ($action === 'next_preview') {
            $py = (int) get_string('program_year');
            if ($py < 1990 || $py > 2100) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Any no vàlid']], 400);
                exit;
            }
            $n = training_actions_next_action_number($db, $py);
            training_actions_api_json(true, [
                'program_year' => $py,
                'next_action_number' => $n,
                'display_code' => training_actions_format_display_code($py, $n),
            ]);
            exit;
        }

        if ($action === 'catalog_list') {
            if (!can_view_form('training_catalog_actions')) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per al catàleg.']], 403);
                exit;
            }
            $q = get_string('q');
            $list = training_actions_catalog_list_for_picker($db, $q, 80);
            training_actions_api_json(true, ['items' => $list]);
            exit;
        }

        if ($action === 'catalog_pick') {
            if (!can_view_form('training_catalog_actions')) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per al catàleg.']], 403);
                exit;
            }
            $cid = (int) get_string('id');
            $pick = training_actions_catalog_pick_payload($db, $cid);
            if (!$pick) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció del catàleg no trobada']], 404);
                exit;
            }
            training_actions_api_json(true, ['pick' => $pick]);
            exit;
        }

        if ($action === 'attendees_list') {
            $aid = (int) get_string('training_action_id');
            if ($aid < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció invàlida']], 400);
                exit;
            }
            if (!training_actions_get_by_id($db, $aid)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            $items = training_actions_attendees_list($db, $aid);
            training_actions_api_json(true, ['items' => $items]);
            exit;
        }

        if ($action === 'attendee_get') {
            $eid = (int) get_string('id');
            if ($eid < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
                exit;
            }
            $row = training_actions_attendee_get_by_id($db, $eid);
            if (!$row) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Assistent no trobat']], 404);
                exit;
            }
            training_actions_api_json(true, ['attendee' => $row]);
            exit;
        }

        if ($action === 'people_search') {
            $q = trim(get_string('q'));
            $items = people_search_for_training_picker($db, $q, 80);
            training_actions_api_json(true, ['items' => $items]);
            exit;
        }

        if ($action === 'people_picker_list') {
            $items = people_list_for_attendee_picker($db, 500);
            training_actions_api_json(true, ['items' => $items]);
            exit;
        }

        if ($action === 'documents_list') {
            $aid = (int) get_string('training_action_id');
            if ($aid < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció invàlida']], 400);
                exit;
            }
            if (!training_actions_get_by_id($db, $aid)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            $items = training_action_documents_list($db, $aid);
            training_actions_api_json(true, ['items' => $items]);
            exit;
        }

        if ($action === 'document_get') {
            $aid = (int) get_string('training_action_id');
            $did = (int) get_string('id');
            if ($aid < 1 || $did < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Dades invàlides']], 400);
                exit;
            }
            if (!training_actions_get_by_id($db, $aid)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            $doc = training_action_document_get_by_id($db, $did, $aid);
            if (!$doc) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Document no trobat']], 404);
                exit;
            }
            training_actions_api_json(true, ['document' => $doc]);
            exit;
        }

        if ($action === 'evaluations_list') {
            $aid = (int) get_string('training_action_id');
            if ($aid < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció invàlida']], 400);
                exit;
            }
            if (!training_actions_get_by_id($db, $aid)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            $items = training_actions_evaluations_list_for_action($db, $aid);
            training_actions_api_json(true, [
                'items' => $items,
                'likert_legend' => TRAINING_ACTION_EVALUATION_LIKERT_LEGEND,
            ]);
            exit;
        }

        if ($action === 'evaluation_get') {
            $aid = (int) get_string('training_action_id');
            $eid = (int) get_string('id');
            if ($aid < 1 || $eid < 1) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Dades invàlides']], 400);
                exit;
            }
            if (!training_actions_get_by_id($db, $aid)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada']], 404);
                exit;
            }
            $detail = training_actions_evaluation_get_detail($db, $eid, $aid);
            if (!$detail) {
                training_actions_api_json(false, ['errors' => ['_general' => 'Avaluació no trobada']], 404);
                exit;
            }
            training_actions_api_json(true, $detail);
            exit;
        }

        training_actions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
        exit;
    }

    if ($method !== 'POST') {
        training_actions_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    $in = training_actions_api_read_json();
    if (!training_actions_api_csrf_verify($in)) {
        training_actions_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }

    $postAction = (string) ($in['action'] ?? '');
    $db = db();

    if ($postAction === 'save') {
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1 && !can_create_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per crear.']], 403);
            exit;
        }
        if ($id > 0 && !can_edit_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per editar.']], 403);
            exit;
        }
        try {
            if ($id < 1) {
                $newId = training_actions_create($db, $in);
                training_actions_api_json(true, ['id' => $newId, 'message' => 'Acció creada correctament.']);
            } else {
                training_actions_update($db, $id, $in);
                training_actions_api_json(true, ['id' => $id, 'message' => 'Acció actualitzada correctament.']);
            }
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            if ($errors !== null) {
                training_actions_api_json(false, ['errors' => $errors], 422);
            } else {
                training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            }
        } catch (Throwable $e) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Error en desar.']], 500);
        }
        exit;
    }

    if ($postAction === 'delete') {
        if (!can_delete_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar.']], 403);
            exit;
        }
        $id = isset($in['id']) ? (int) $in['id'] : 0;
        if ($id < 1) {
            training_actions_api_json(false, ['errors' => ['_general' => 'ID invàlid']], 400);
            exit;
        }
        try {
            training_actions_delete($db, $id);
            training_actions_api_json(true, ['message' => 'Acció eliminada correctament.']);
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            training_actions_api_json(false, ['errors' => $errors ?? ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        } catch (Throwable $e) {
            if (function_exists('db_is_integrity_constraint_violation') && db_is_integrity_constraint_violation($e)) {
                training_actions_api_json(false, ['errors' => ['_general' => 'No es pot eliminar: hi ha convocatòries o registres vinculats.']], 422);
            } else {
                training_actions_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar.']], 500);
            }
        }
        exit;
    }

    if ($postAction === 'attendee_save') {
        if (!can_edit_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per editar assistents.']], 403);
            exit;
        }
        $trainingActionId = isset($in['training_action_id']) ? (int) $in['training_action_id'] : 0;
        if ($trainingActionId < 1 || !training_actions_get_by_id($db, $trainingActionId)) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada.']], 404);
            exit;
        }
        $eid = isset($in['id']) ? (int) $in['id'] : 0;
        try {
            if ($eid < 1) {
                $newId = training_actions_attendee_create($db, $trainingActionId, $in);
                training_actions_api_json(true, ['id' => $newId, 'message' => 'Assistent afegit correctament.']);
            } else {
                training_actions_attendee_update($db, $eid, $trainingActionId, $in);
                training_actions_api_json(true, ['id' => $eid, 'message' => 'Assistent actualitzat correctament.']);
            }
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            if ($errors !== null) {
                training_actions_api_json(false, ['errors' => $errors], 422);
            } else {
                training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            }
        } catch (RuntimeException $e) {
            training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
        } catch (Throwable $e) {
            if (function_exists('db_is_integrity_constraint_violation') && db_is_integrity_constraint_violation($e)) {
                training_actions_api_json(false, ['errors' => ['person_id' => 'Aquesta persona ja consta com a assistent.']], 422);
            } else {
                training_actions_api_json(false, ['errors' => ['_general' => 'Error en desar l’assistent.']], 500);
            }
        }
        exit;
    }

    if ($postAction === 'attendee_delete') {
        if (!can_edit_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar assistents.']], 403);
            exit;
        }
        $trainingActionId = isset($in['training_action_id']) ? (int) $in['training_action_id'] : 0;
        $eid = isset($in['id']) ? (int) $in['id'] : 0;
        if ($trainingActionId < 1 || $eid < 1) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Dades invàlides.']], 400);
            exit;
        }
        if (!training_actions_get_by_id($db, $trainingActionId)) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada.']], 404);
            exit;
        }
        try {
            training_actions_attendee_delete($db, $eid, $trainingActionId);
            training_actions_api_json(true, ['message' => 'Assistent eliminat correctament.']);
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            training_actions_api_json(false, ['errors' => $errors ?? ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        } catch (Throwable $e) {
            training_actions_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar.']], 500);
        }
        exit;
    }

    if ($postAction === 'document_save') {
        if (!can_edit_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per editar documents.']], 403);
            exit;
        }
        $trainingActionId = isset($in['training_action_id']) ? (int) $in['training_action_id'] : 0;
        $did = isset($in['id']) ? (int) $in['id'] : 0;
        if ($trainingActionId < 1 || $did < 1) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Dades invàlides.']], 400);
            exit;
        }
        if (!training_actions_get_by_id($db, $trainingActionId)) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada.']], 404);
            exit;
        }
        try {
            training_action_document_update($db, $did, $trainingActionId, $in);
            $doc = training_action_document_get_by_id($db, $did, $trainingActionId);
            training_actions_api_json(true, ['document' => $doc, 'message' => 'Document actualitzat correctament.']);
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            training_actions_api_json(false, ['errors' => $errors ?? ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        } catch (Throwable $e) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Error en desar el document.']], 500);
        }
        exit;
    }

    if ($postAction === 'document_delete') {
        if (!can_delete_form('training_actions')) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar documents.']], 403);
            exit;
        }
        $trainingActionId = isset($in['training_action_id']) ? (int) $in['training_action_id'] : 0;
        $did = isset($in['id']) ? (int) $in['id'] : 0;
        if ($trainingActionId < 1 || $did < 1) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Dades invàlides.']], 400);
            exit;
        }
        if (!training_actions_get_by_id($db, $trainingActionId)) {
            training_actions_api_json(false, ['errors' => ['_general' => 'Acció no trobada.']], 404);
            exit;
        }
        try {
            training_action_document_delete($db, $did, $trainingActionId);
            training_actions_api_json(true, ['message' => 'Document eliminat correctament.']);
        } catch (InvalidArgumentException $e) {
            $errors = training_actions_parse_validation_exception($e);
            training_actions_api_json(false, ['errors' => $errors ?? ['_general' => $e->getMessage()]], 422);
        } catch (RuntimeException $e) {
            training_actions_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 404);
        } catch (Throwable $e) {
            training_actions_api_json(false, ['errors' => ['_general' => 'No s’ha pogut eliminar.']], 500);
        }
        exit;
    }

    training_actions_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    training_actions_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}

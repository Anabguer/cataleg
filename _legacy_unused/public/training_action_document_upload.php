<?php
declare(strict_types=1);

/**
 * Pujada de document genèric d’acció formativa (pestanya Documents).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_documents.php';

auth_require_login();
permissions_load_for_session();

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Mètode no permès']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!can_edit_form('training_actions')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Sense permís.']], JSON_UNESCAPED_UNICODE);
    exit;
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['csrf_token']) || !is_string($csrf) || !hash_equals((string) $_SESSION['csrf_token'], (string) $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'CSRF no vàlid']], JSON_UNESCAPED_UNICODE);
    exit;
}

$trainingActionId = isset($_POST['training_action_id']) ? (int) $_POST['training_action_id'] : 0;
if ($trainingActionId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Acció invàlida']], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = db();
if (!training_actions_get_by_id($db, $trainingActionId)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Acció no trobada']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['document']) || !is_array($_FILES['document'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Cap fitxer rebut']], JSON_UNESCAPED_UNICODE);
    exit;
}

$meta = [
    'file_name' => isset($_POST['file_name']) ? (string) $_POST['file_name'] : '',
    'document_notes' => isset($_POST['document_notes']) ? (string) $_POST['document_notes'] : '',
    'is_visible' => isset($_POST['is_visible']) ? $_POST['is_visible'] : '0',
];

[$newId, $err] = training_action_document_store_upload($db, $_FILES['document'], $trainingActionId, $meta);
if ($newId < 1) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => ['_general' => $err ?? 'Error en desar']], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = training_action_document_get_by_id($db, $newId, $trainingActionId);

echo json_encode(
    [
        'ok' => true,
        'document' => $row,
        'message' => 'Document pujat correctament.',
    ],
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);

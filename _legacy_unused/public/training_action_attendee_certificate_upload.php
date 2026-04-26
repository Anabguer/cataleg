<?php
declare(strict_types=1);

/**
 * Pujada de certificat / justificant d’assistència (training_action_documents + enllaç des de l’assistent).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_attendees.php';

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
$st = $db->prepare('SELECT id FROM training_actions WHERE id = :id LIMIT 1');
$st->execute(['id' => $trainingActionId]);
if (!$st->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Acció no trobada']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['certificate']) || !is_array($_FILES['certificate'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Cap fitxer rebut']], JSON_UNESCAPED_UNICODE);
    exit;
}

[$newId, $err] = training_actions_store_attendance_certificate_upload($db, $_FILES['certificate'], $trainingActionId);
if ($newId < 1) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => ['_general' => $err ?? 'Error en desar']], JSON_UNESCAPED_UNICODE);
    exit;
}

$st = $db->prepare('SELECT file_name FROM training_action_documents WHERE id = :id LIMIT 1');
$st->execute(['id' => $newId]);
$fn = (string) ($st->fetch()['file_name'] ?? '');

echo json_encode(
    [
        'ok' => true,
        'document_id' => $newId,
        'file_name' => $fn,
        'message' => 'Document pujat correctament.',
    ],
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);

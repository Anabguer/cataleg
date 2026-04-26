<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_evaluations.php';

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

$raw = file_get_contents('php://input') ?: '';
$in = $raw !== '' ? json_decode($raw, true) : [];
if (!is_array($in)) {
    $in = [];
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');
if (!isset($_SESSION['csrf_token']) || !is_string($csrf) || !hash_equals((string) $_SESSION['csrf_token'], (string) $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'CSRF no vàlid']], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = db();

$trainingActionId = isset($in['training_action_id']) ? (int) $in['training_action_id'] : 0;
if ($trainingActionId < 1 || !training_actions_get_by_id($db, $trainingActionId)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Acció no trobada']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!empty($in['send_all'])) {
        $r = training_actions_evaluation_send_all_attended($db, $trainingActionId);
        echo json_encode(
            [
                'ok' => $r['ok'],
                'sent' => $r['sent'],
                'errors' => $r['errors'],
                'message' => $r['sent'] > 0
                    ? 'Enviats: ' . $r['sent'] . '.'
                    : 'No s’ha pogut enviar cap qüestionari.',
            ],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        exit;
    }

    $attendeeId = isset($in['attendee_id']) ? (int) $in['attendee_id'] : 0;
    if ($attendeeId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'errors' => ['_general' => 'Assistent invàlid']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $r = training_actions_evaluation_send_email_for_attendee($db, $trainingActionId, $attendeeId);
    if (!$r['ok']) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'errors' => ['_general' => $r['message']]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'message' => $r['message']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    $msg = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : 'Error intern';
    echo json_encode(
        ['ok' => false, 'errors' => ['_general' => $msg]],
        JSON_UNESCAPED_UNICODE
    );
}

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

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['csrf_token']) || !is_string($csrf) || !hash_equals((string) $_SESSION['csrf_token'], (string) $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'CSRF no vàlid']], JSON_UNESCAPED_UNICODE);
    exit;
}

$trainingActionId = isset($_POST['training_action_id']) ? (int) $_POST['training_action_id'] : 0;
if ($trainingActionId < 1 || !training_actions_get_by_id(db(), $trainingActionId)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'Acció no trobada']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['files'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => ['_general' => 'No s’han rebut fitxers.']], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = db();
$results = [];
$okCount = 0;
$skippedOther = [];

$names = $_FILES['files']['name'];
$tmps = $_FILES['files']['tmp_name'];
$errs = $_FILES['files']['error'];
if (!is_array($tmps)) {
    $names = [$names];
    $tmps = [$tmps];
    $errs = [$errs];
}

$n = is_array($tmps) ? count($tmps) : 0;

for ($i = 0; $i < $n; $i++) {
    $err = is_array($errs) ? ($errs[$i] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        $results[] = ['file' => is_array($names) ? ($names[$i] ?? '?') : '?', 'ok' => false, 'message' => 'Error de pujada.'];
        continue;
    }
    $tmp = is_array($tmps) ? ($tmps[$i] ?? '') : '';
    $name = is_array($names) ? ($names[$i] ?? 'fitxer.xlsx') : 'fitxer.xlsx';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'xlsx' && $ext !== 'xlsm') {
        $results[] = ['file' => $name, 'ok' => false, 'message' => 'Només .xlsx / .xlsm'];
        continue;
    }

    try {
        $r = training_actions_evaluation_import_xlsx_path($db, $trainingActionId, $tmp, $name);
        if (!empty($r['skipped_other_action'])) {
            $skippedOther[] = $name;
        }
        if ($r['ok']) {
            ++$okCount;
        }
        $results[] = ['file' => $name, 'ok' => $r['ok'], 'message' => $r['message']];
    } catch (Throwable $e) {
        $results[] = [
            'file' => $name,
            'ok' => false,
            'message' => training_actions_evaluation_import_file_error_message($e, $name),
        ];
    }
}

echo json_encode(
    [
        'ok' => $okCount > 0 || $results === [],
        'imported' => $okCount,
        'results' => $results,
        'skipped_other_action' => $skippedOther,
        'message' => $okCount > 0 ? 'Importats: ' . $okCount . '.' : 'Cap fitxer importat.',
    ],
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);

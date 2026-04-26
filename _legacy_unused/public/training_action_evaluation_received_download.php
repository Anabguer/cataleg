<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_evaluations.php';

auth_require_login();
permissions_load_for_session();

if (!can_view_form('training_actions')) {
    http_response_code(403);
    echo 'Sense permís.';
    exit;
}

$evaluationId = (int) get_string('evaluation_id');
$trainingActionId = (int) get_string('training_action_id');
if ($evaluationId < 1 || $trainingActionId < 1) {
    http_response_code(400);
    exit;
}

$db = db();
$st = $db->prepare(
    'SELECT received_relative_path, received_file_name FROM training_action_evaluations
     WHERE id = :id AND training_action_id = :aid LIMIT 1'
);
$st->execute(['id' => $evaluationId, 'aid' => $trainingActionId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['received_relative_path'])) {
    http_response_code(404);
    exit;
}

$rel = str_replace(['\\', "\0"], '', (string) $row['received_relative_path']);
$rel = str_replace('\\', '/', $rel);
foreach (explode('/', $rel) as $seg) {
    if ($seg === '..') {
        http_response_code(400);
        exit;
    }
}

$full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
$real = realpath($full);
$root = realpath(APP_ROOT);
if ($real === false || $root === false) {
    http_response_code(404);
    exit;
}
$prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (strncmp($real, $prefix, strlen($prefix)) !== 0 && $real !== $root) {
    http_response_code(403);
    exit;
}
if (!is_file($real)) {
    http_response_code(404);
    exit;
}

$fn = isset($row['received_file_name']) && (string) $row['received_file_name'] !== ''
    ? (string) $row['received_file_name']
    : basename($real);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: inline; filename="' . str_replace('"', '', $fn) . '"');
header('Content-Length: ' . (string) filesize($real));
readfile($real);
exit;

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';

auth_require_login();
permissions_load_for_session();

if (!can_view_form('training_actions')) {
    http_response_code(403);
    echo 'Sense permís.';
    exit;
}

$trainingActionId = (int) get_string('training_action_id');
$attendeeId = (int) get_string('attendee_id');
if ($trainingActionId < 1 || $attendeeId < 1) {
    http_response_code(400);
    exit;
}

$db = db();
$st = $db->prepare(
    'SELECT tae.sent_relative_path, tae.sent_file_name
     FROM training_action_evaluations tae
     INNER JOIN training_action_attendees taa ON taa.id = tae.training_action_attendee_id
     WHERE tae.training_action_id = :aid
       AND tae.training_action_attendee_id = :eid
       AND taa.training_action_id = :aid2
     LIMIT 1'
);
$st->execute(['aid' => $trainingActionId, 'eid' => $attendeeId, 'aid2' => $trainingActionId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['sent_relative_path'])) {
    http_response_code(404);
    exit;
}

$rel = str_replace(['\\', "\0"], '', (string) $row['sent_relative_path']);
$rel = str_replace('\\', '/', $rel);
foreach (explode('/', $rel) as $seg) {
    if ($seg === '..') {
        http_response_code(400);
        exit;
    }
}

$norm = strtolower($rel);
if (strpos($norm, 'assets/excel/enviats/') !== 0) {
    http_response_code(403);
    exit;
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

$fn = isset($row['sent_file_name']) && (string) $row['sent_file_name'] !== ''
    ? (string) $row['sent_file_name']
    : basename($real);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: inline; filename="' . str_replace('"', '', $fn) . '"');
header('Content-Length: ' . (string) filesize($real));
readfile($real);
exit;

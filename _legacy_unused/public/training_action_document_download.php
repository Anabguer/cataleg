<?php
declare(strict_types=1);

/**
 * Visualització segura d’un document d’acció formativa (certificat, etc.) al navegador.
 * El fitxer físic viu a training_action_documents.relative_path; només FK des de l’assistent.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

auth_require_login();
permissions_load_for_session();
require_can_view('training_actions');

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    echo 'ID invàlid';
    exit;
}

$db = db();
$st = $db->prepare(
    'SELECT tad.relative_path, tad.file_name
     FROM training_action_documents tad
     WHERE tad.id = :id
     LIMIT 1'
);
$st->execute(['id' => $id]);
$row = $st->fetch();
if (!$row) {
    http_response_code(404);
    echo 'Document no trobat';
    exit;
}

$rel = str_replace(['\\', "\0"], '', (string) $row['relative_path']);
$rel = str_replace('\\', '/', $rel);
// Rebutjar només el segment ".." (no usar strpbrk amb ".", que coincideix amb l’extensió .pdf)
$parts = explode('/', $rel);
foreach ($parts as $seg) {
    if ($seg === '..') {
        http_response_code(400);
        exit;
    }
}
if ($rel === '') {
    http_response_code(400);
    exit;
}

if ($rel[0] === '/' || (strlen($rel) > 2 && $rel[1] === ':' && ($rel[2] === '\\' || $rel[2] === '/'))) {
    http_response_code(400);
    exit;
}

$full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
$realFile = realpath($full);
$realRoot = realpath(APP_ROOT);
if ($realFile === false || $realRoot === false) {
    http_response_code(404);
    echo 'Fitxer no disponible';
    exit;
}
$rootPrefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$insideRoot =
    strncmp($realFile, $rootPrefix, strlen($rootPrefix)) === 0 || $realFile === $realRoot;
if (!$insideRoot) {
    http_response_code(404);
    echo 'Fitxer no disponible';
    exit;
}

if (!is_file($realFile) || !is_readable($realFile)) {
    http_response_code(404);
    echo 'Fitxer no disponible';
    exit;
}

$fn = (string) ($row['file_name'] ?? basename($realFile));
$fn = basename(str_replace(["\0", '/'], '', $fn));
if ($fn === '') {
    $fn = 'document';
}

$ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $detected = @mime_content_type($realFile);
    if (is_string($detected) && $detected !== '' && $detected !== 'application/octet-stream') {
        $mime = $detected;
    }
}
if ($mime === 'application/octet-stream') {
    switch ($ext) {
        case 'pdf':
            $mime = 'application/pdf';
            break;
        case 'jpg':
        case 'jpeg':
            $mime = 'image/jpeg';
            break;
        case 'png':
            $mime = 'image/png';
            break;
        case 'webp':
            $mime = 'image/webp';
            break;
        case 'gif':
            $mime = 'image/gif';
            break;
        default:
            $mime = 'application/octet-stream';
            break;
    }
}

$ascii = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($fn, PATHINFO_FILENAME));
if ($ascii === '' || $ascii === '_') {
    $ascii = 'document';
}
$asciiName = $ascii . ($ext !== '' ? '.' . $ext : '');
$utf8Star = "filename*=UTF-8''" . rawurlencode($fn);

header('Content-Type: ' . $mime);
header(
    'Content-Disposition: inline; filename="' . str_replace(['\\', '"'], ['', ''], $asciiName)
        . '"; ' . $utf8Star
);
header('Content-Length: ' . (string) filesize($realFile));
header('X-Content-Type-Options: nosniff');
readfile($realFile);
exit;

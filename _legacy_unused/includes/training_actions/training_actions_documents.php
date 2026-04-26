<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/people/people.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_attendees.php';

/** Document genèric pujat des de la pestanya Documents (no certificat d’assistent). */
const TRAINING_ACTION_DOCUMENT_TYPE_ACTION = 'action_document';

/** Mida màxima (bytes), mateixa línia que certificats. */
const TRAINING_ACTION_DOCUMENT_MAX_BYTES = 10485760;

/**
 * @return list<array<string,mixed>>
 */
function training_action_documents_list(PDO $db, int $trainingActionId): array
{
    if ($trainingActionId < 1) {
        return [];
    }
    $sql = 'SELECT tad.*,
                   taa.id AS link_attendee_id,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2
            FROM training_action_documents tad
            LEFT JOIN training_action_attendees taa
                ON taa.attendance_certificate_document_id = tad.id
            LEFT JOIN people p ON p.id = taa.person_id
            WHERE tad.training_action_id = :aid
            ORDER BY tad.created_at DESC, tad.id DESC';
    $st = $db->prepare($sql);
    $st->execute(['aid' => $trainingActionId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $out = [];
    foreach ($rows as $r) {
        $out[] = training_action_document_row_for_api($db, $r);
    }

    return $out;
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function training_action_document_row_for_api(PDO $db, array $row): array
{
    $id = (int) $row['id'];
    $downloadUrl = app_url('training_action_document_download.php?id=' . $id);
    $type = isset($row['document_type']) && (string) $row['document_type'] !== ''
        ? (string) $row['document_type']
        : null;
    $isCert = $type === TRAINING_ACTION_DOCUMENT_TYPE_ATTENDANCE_CERTIFICATE;
    $attendeeId = isset($row['link_attendee_id']) ? (int) $row['link_attendee_id'] : 0;
    $personLabel = null;
    if ($isCert && $attendeeId > 0 && isset($row['person_code'])) {
        $personLabel = people_format_surname_first_with_code($row);
    }
    $origin = $isCert
        ? ($personLabel !== null ? 'Certificat assistent: ' . $personLabel : 'Certificat d’assistència')
        : 'Document de l’acció';

    return [
        'id' => $id,
        'training_action_id' => (int) $row['training_action_id'],
        'file_name' => (string) $row['file_name'],
        'document_notes' => isset($row['document_notes']) && $row['document_notes'] !== null && (string) $row['document_notes'] !== ''
            ? (string) $row['document_notes']
            : null,
        'is_visible' => isset($row['is_visible']) ? (int) $row['is_visible'] : 0,
        'document_type' => $type,
        'download_url' => $downloadUrl,
        'origin_label' => $origin,
        'linked_attendee_id' => $attendeeId > 0 ? $attendeeId : null,
        'linked_person_display' => $personLabel,
    ];
}

function training_action_document_get_by_id(PDO $db, int $id, int $trainingActionId): ?array
{
    if ($id < 1 || $trainingActionId < 1) {
        return null;
    }
    $sql = 'SELECT tad.*,
                   taa.id AS link_attendee_id,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2
            FROM training_action_documents tad
            LEFT JOIN training_action_attendees taa
                ON taa.attendance_certificate_document_id = tad.id
            LEFT JOIN people p ON p.id = taa.person_id
            WHERE tad.id = :id AND tad.training_action_id = :aid
            LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    $r = $st->fetch(PDO::FETCH_ASSOC);

    return $r ? training_action_document_row_for_api($db, $r) : null;
}

/**
 * @param array<string,mixed> $data
 */
function training_action_document_validate_update_payload(array $data): array
{
    $errors = [];
    $fn = trim((string) ($data['file_name'] ?? ''));
    if ($fn === '') {
        $errors['file_name'] = 'El nom del document és obligatori.';
    }
    $notes = trim((string) ($data['document_notes'] ?? ''));
    $vis = isset($data['is_visible']) && ((string) $data['is_visible'] === '1' || $data['is_visible'] === true || $data['is_visible'] === 1)
        ? 1
        : 0;

    return [$errors, $fn, $notes !== '' ? $notes : null, $vis];
}

/**
 * @param array<string,mixed> $data
 */
function training_action_document_update(PDO $db, int $id, int $trainingActionId, array $data): void
{
    if ($id < 1 || $trainingActionId < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'Dades invàlides.'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('SELECT id, document_type FROM training_action_documents WHERE id = :id AND training_action_id = :aid LIMIT 1');
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Document no trobat.');
    }
    [$errors, $fn, $notes, $vis] = training_action_document_validate_update_payload($data);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $db->prepare(
        'UPDATE training_action_documents SET file_name = :fn, document_notes = :notes, is_visible = :vis, updated_at = CURRENT_TIMESTAMP
         WHERE id = :id AND training_action_id = :aid LIMIT 1'
    )->execute([
        'fn' => $fn,
        'notes' => $notes,
        'vis' => $vis,
        'id' => $id,
        'aid' => $trainingActionId,
    ]);
}

/**
 * Elimina fitxer físic i fila. La FK des de assistents passa a NULL (ON DELETE SET NULL).
 */
function training_action_document_delete(PDO $db, int $id, int $trainingActionId): void
{
    if ($id < 1 || $trainingActionId < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'Dades invàlides.'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('SELECT id, relative_path FROM training_action_documents WHERE id = :id AND training_action_id = :aid LIMIT 1');
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Document no trobat.');
    }
    $rel = str_replace(['\\', "\0"], '', (string) $row['relative_path']);
    $rel = str_replace('\\', '/', $rel);
    foreach (explode('/', $rel) as $seg) {
        if ($seg === '..') {
            throw new RuntimeException('Ruta no vàlida.');
        }
    }
    $db->prepare(
        'UPDATE training_action_attendees SET attendance_certificate_document_id = NULL, updated_at = CURRENT_TIMESTAMP
         WHERE attendance_certificate_document_id = :id AND training_action_id = :aid'
    )->execute(['id' => $id, 'aid' => $trainingActionId]);

    if ($rel !== '') {
        $full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $realFile = realpath($full);
        $realRoot = realpath(APP_ROOT);
        if ($realFile !== false && $realRoot !== false) {
            $rootPrefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $insideRoot =
                strncmp($realFile, $rootPrefix, strlen($rootPrefix)) === 0 || $realFile === $realRoot;
            if ($insideRoot && is_file($realFile)) {
                @unlink($realFile);
            }
        }
    }
    $db->prepare('DELETE FROM training_action_documents WHERE id = :id AND training_action_id = :aid LIMIT 1')->execute([
        'id' => $id,
        'aid' => $trainingActionId,
    ]);
}

/**
 * Pujada d’un document genèric (pestanya Documents).
 *
 * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
 * @param array{file_name?:string,document_notes?:string,is_visible?:string|int|bool} $meta
 * @return array{0:int,1:?string}
 */
function training_action_document_store_upload(PDO $db, array $file, int $trainingActionId, array $meta): array
{
    if ($trainingActionId < 1) {
        return [0, 'Acció no vàlida.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [0, 'Error en la pujada del fitxer.'];
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > TRAINING_ACTION_DOCUMENT_MAX_BYTES) {
        return [0, 'El fitxer és massa gran o buit (màx. 10 MB).'];
    }
    $origName = isset($file['name']) && is_string($file['name']) ? $file['name'] : 'document';
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        return [0, 'Tipus de fitxer no permès (PDF o imatge).'];
    }

    $folderSeg = training_actions_documents_folder_segment($db, $trainingActionId);
    if ($folderSeg === null || $folderSeg === '') {
        return [0, 'Acció no trobada.'];
    }

    $relDir = 'assets/documents/' . $folderSeg;
    $absDir = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
    if (!is_dir($absDir)) {
        if (!mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return [0, 'No s’ha pogut crear el directori de documents.'];
        }
    }

    $base = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($origName, PATHINFO_FILENAME));
    if ($base === '' || $base === '_') {
        $base = 'document';
    }
    $storedName = $base . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $absPath = $absDir . DIRECTORY_SEPARATOR . $storedName;
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return [0, 'Fitxer de pujada no vàlid.'];
    }
    if (!move_uploaded_file($tmp, $absPath)) {
        return [0, 'No s’ha pogut desar el fitxer.'];
    }

    $relativePath = $relDir . '/' . $storedName;
    $displayName = trim((string) ($meta['file_name'] ?? ''));
    if ($displayName === '') {
        $displayName = basename($origName);
    }
    if ($displayName === '' || strpbrk($displayName, "/\0") !== false) {
        $displayName = $storedName;
    }
    $notes = trim((string) ($meta['document_notes'] ?? ''));
    $notes = $notes !== '' ? $notes : null;
    $vis = isset($meta['is_visible']) && ((string) $meta['is_visible'] === '1' || $meta['is_visible'] === true || $meta['is_visible'] === 1)
        ? 1
        : 0;

    try {
        $st = $db->prepare(
            'INSERT INTO training_action_documents (
                training_action_id, file_name, relative_path, document_type, document_notes, is_visible
            ) VALUES (:aid, :fn, :rp, :dt, :notes, :vis)'
        );
        $st->execute([
            'aid' => $trainingActionId,
            'fn' => $displayName,
            'rp' => $relativePath,
            'dt' => TRAINING_ACTION_DOCUMENT_TYPE_ACTION,
            'notes' => $notes,
            'vis' => $vis,
        ]);
    } catch (PDOException $e) {
        @unlink($absPath);
        $msg = $e->getMessage();
        if (strpos($msg, 'document_notes') !== false || strpos($msg, 'is_visible') !== false || strpos($msg, 'Unknown column') !== false) {
            $st = $db->prepare(
                'INSERT INTO training_action_documents (training_action_id, file_name, relative_path, document_type)
                 VALUES (:aid, :fn, :rp, :dt)'
            );
            $st->execute([
                'aid' => $trainingActionId,
                'fn' => $displayName,
                'rp' => $relativePath,
                'dt' => TRAINING_ACTION_DOCUMENT_TYPE_ACTION,
            ]);
        } else {
            throw $e;
        }
    }

    return [(int) $db->lastInsertId(), null];
}

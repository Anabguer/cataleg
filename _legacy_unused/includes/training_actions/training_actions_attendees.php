<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/people/people.php';

/**
 * Assistents (training_action_attendees + training_action_documents).
 */

/** Tipus de document emmagatzemat a training_action_documents per certificats vinculats a assistents. */
const TRAINING_ACTION_DOCUMENT_TYPE_ATTENDANCE_CERTIFICATE = 'attendance_certificate';

/** Mida màxima d’arxiu de certificat (bytes). */
const TRAINING_ACTION_CERTIFICATE_MAX_BYTES = 10485760;

/**
 * Carpeta dins assets/documents sense punt (ex. 2025001 per a codi 2025.001).
 */
function training_actions_documents_folder_segment(PDO $db, int $trainingActionId): ?string
{
    if ($trainingActionId < 1) {
        return null;
    }
    $st = $db->prepare('SELECT program_year, action_number FROM training_actions WHERE id = :id LIMIT 1');
    $st->execute(['id' => $trainingActionId]);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }
    $py = (int) $r['program_year'];
    $an = (int) $r['action_number'];

    return (string) $py . str_pad((string) max(0, $an), 3, '0', STR_PAD_LEFT);
}

/**
 * Desa un fitxer pujat com a document d’acció (certificat d’assistència) i retorna l’id.
 *
 * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
 * @return array{0:int,1:?string} [id, error message]
 */
function training_actions_store_attendance_certificate_upload(PDO $db, array $file, int $trainingActionId): array
{
    if ($trainingActionId < 1) {
        return [0, 'Acció no vàlida.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [0, 'Error en la pujada del fitxer.'];
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > TRAINING_ACTION_CERTIFICATE_MAX_BYTES) {
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
        $base = 'certificat';
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
    $displayName = basename($origName);
    if ($displayName === '' || strpbrk($displayName, "/\0") !== false) {
        $displayName = $storedName;
    }

    try {
        $st = $db->prepare(
            'INSERT INTO training_action_documents (training_action_id, file_name, relative_path, document_type)
             VALUES (:aid, :fn, :rp, :dt)'
        );
        $st->execute([
            'aid' => $trainingActionId,
            'fn' => $displayName,
            'rp' => $relativePath,
            'dt' => TRAINING_ACTION_DOCUMENT_TYPE_ATTENDANCE_CERTIFICATE,
        ]);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'document_type') !== false || strpos($msg, 'Unknown column') !== false) {
            $st = $db->prepare(
                'INSERT INTO training_action_documents (training_action_id, file_name, relative_path)
                 VALUES (:aid, :fn, :rp)'
            );
            $st->execute([
                'aid' => $trainingActionId,
                'fn' => $displayName,
                'rp' => $relativePath,
            ]);
        } else {
            @unlink($absPath);
            throw $e;
        }
    }

    return [(int) $db->lastInsertId(), null];
}

function training_actions_documents_delete_attendance_certificate_if_orphan(PDO $db, int $documentId): void
{
    if ($documentId < 1) {
        return;
    }
    $st = $db->prepare(
        'SELECT id, relative_path, document_type FROM training_action_documents WHERE id = :id LIMIT 1'
    );
    $st->execute(['id' => $documentId]);
    $row = $st->fetch();
    if (!$row) {
        return;
    }
    $dt = isset($row['document_type']) && is_string($row['document_type']) ? trim($row['document_type']) : '';
    if ($dt !== TRAINING_ACTION_DOCUMENT_TYPE_ATTENDANCE_CERTIFICATE) {
        return;
    }
    $st = $db->prepare(
        'SELECT COUNT(*) AS c FROM training_action_attendees WHERE attendance_certificate_document_id = :id'
    );
    $st->execute(['id' => $documentId]);
    $c = (int) ($st->fetch()['c'] ?? 0);
    if ($c > 0) {
        return;
    }
    $rel = str_replace(['\\', "\0"], '', (string) $row['relative_path']);
    $rel = str_replace('\\', '/', $rel);
    $bad = false;
    foreach (explode('/', $rel) as $seg) {
        if ($seg === '..') {
            $bad = true;
            break;
        }
    }
    if ($rel !== '' && !$bad) {
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
    $stDel = $db->prepare(
        'DELETE FROM training_action_documents WHERE id = :id AND document_type = :dt LIMIT 1'
    );
    $stDel->execute([
        'id' => $documentId,
        'dt' => TRAINING_ACTION_DOCUMENT_TYPE_ATTENDANCE_CERTIFICATE,
    ]);
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function training_actions_attendee_row_for_api(PDO $db, array $row): array
{
    $docId = null_if_empty_int($row['attendance_certificate_document_id'] ?? null);
    $downloadUrl = null;
    if ($docId !== null && $docId > 0) {
        $downloadUrl = app_url('training_action_document_download.php?id=' . $docId);
    }
    $sentRel = isset($row['questionnaire_sent_relative_path']) ? trim((string) $row['questionnaire_sent_relative_path']) : '';
    $sentName = isset($row['questionnaire_sent_file_name']) ? trim((string) $row['questionnaire_sent_file_name']) : '';
    $questionnaireSentDownloadUrl = null;
    if ($sentRel !== '') {
        $aid = (int) $row['training_action_id'];
        $attId = (int) $row['id'];
        $questionnaireSentDownloadUrl = app_url(
            'training_action_evaluation_sent_download.php?training_action_id=' . $aid . '&attendee_id=' . $attId
        );
    }
    $jobLabel = people_job_position_label_from_joined_row($row);

    return [
        'id' => (int) $row['id'],
        'training_action_id' => (int) $row['training_action_id'],
        'person_id' => (int) $row['person_id'],
        'person_code' => (int) $row['person_code'],
        'person_display' => people_format_surname_first_with_code($row),
        'job_position_label' => $jobLabel,
        'email' => isset($row['email']) && (string) $row['email'] !== '' ? (string) $row['email'] : null,
        'request_flag' => (int) ($row['request_flag'] ?? 0),
        'pre_registration_flag' => (int) ($row['pre_registration_flag'] ?? 0),
        'registration_flag' => (int) ($row['registration_flag'] ?? 0),
        'attendance_flag' => (int) ($row['attendance_flag'] ?? 0),
        'non_attendance_reason' => isset($row['non_attendance_reason']) && (string) $row['non_attendance_reason'] !== ''
            ? (string) $row['non_attendance_reason'] : null,
        'attendance_certificate_document_id' => $docId,
        'certificate_file_name' => isset($row['certificate_file_name']) && (string) $row['certificate_file_name'] !== ''
            ? (string) $row['certificate_file_name'] : null,
        'certificate_download_url' => $downloadUrl,
        'questionnaire_sent_file_name' => $sentName !== '' ? $sentName : null,
        'questionnaire_sent_download_url' => $questionnaireSentDownloadUrl,
    ];
}

/**
 * @return list<array<string,mixed>>
 */
function training_actions_attendees_list(PDO $db, int $trainingActionId): array
{
    if ($trainingActionId < 1) {
        return [];
    }
    $sql = 'SELECT taa.*,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2, p.email, p.job_position_id,
                   jp.name AS job_position_name, jp.position_number AS job_position_number,
                   u.unit_code AS job_unit_code,
                   tad.file_name AS certificate_file_name,
                   tae.sent_file_name AS questionnaire_sent_file_name,
                   tae.sent_relative_path AS questionnaire_sent_relative_path
            FROM training_action_attendees taa
            INNER JOIN people p ON p.id = taa.person_id
            LEFT JOIN job_positions jp ON jp.id = p.job_position_id
            LEFT JOIN org_units u ON u.id = jp.unit_id
            LEFT JOIN training_action_documents tad ON tad.id = taa.attendance_certificate_document_id
            LEFT JOIN training_action_evaluations tae
                ON tae.training_action_attendee_id = taa.id AND tae.training_action_id = taa.training_action_id
            WHERE taa.training_action_id = :aid
            ORDER BY p.last_name_1 ASC, p.last_name_2 ASC, p.first_name ASC, taa.id ASC';
    $st = $db->prepare($sql);
    $st->execute(['aid' => $trainingActionId]);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = training_actions_attendee_row_for_api($db, $r);
    }

    return $out;
}

/**
 * @return array<string,mixed>|null
 */
function training_actions_attendee_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $sql = 'SELECT taa.*,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2, p.email, p.job_position_id,
                   jp.name AS job_position_name, jp.position_number AS job_position_number,
                   u.unit_code AS job_unit_code,
                   tad.file_name AS certificate_file_name,
                   tae.sent_file_name AS questionnaire_sent_file_name,
                   tae.sent_relative_path AS questionnaire_sent_relative_path
            FROM training_action_attendees taa
            INNER JOIN people p ON p.id = taa.person_id
            LEFT JOIN job_positions jp ON jp.id = p.job_position_id
            LEFT JOIN org_units u ON u.id = jp.unit_id
            LEFT JOIN training_action_documents tad ON tad.id = taa.attendance_certificate_document_id
            LEFT JOIN training_action_evaluations tae
                ON tae.training_action_attendee_id = taa.id AND tae.training_action_id = taa.training_action_id
            WHERE taa.id = :id LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id]);
    $r = $st->fetch();

    return $r ? training_actions_attendee_row_for_api($db, $r) : null;
}

function training_actions_document_belongs_to_action(PDO $db, int $documentId, int $trainingActionId): bool
{
    if ($documentId < 1 || $trainingActionId < 1) {
        return false;
    }
    $st = $db->prepare(
        'SELECT 1 FROM training_action_documents WHERE id = :id AND training_action_id = :aid LIMIT 1'
    );
    $st->execute(['id' => $documentId, 'aid' => $trainingActionId]);

    return (bool) $st->fetch();
}

/**
 * @param array<string,mixed> $data
 * @return array<string,mixed>
 */
function training_actions_attendee_normalize_payload(array $data): array
{
    /** @param mixed $v */
    $flag = static function ($v): int {
        return isset($v) && (string) $v === '1' ? 1 : 0;
    };
    $reason = null_if_empty(trim((string) ($data['non_attendance_reason'] ?? '')));
    $att = $flag($data['attendance_flag'] ?? 0);
    if ($att === 1) {
        $reason = null;
    }

    return [
        'person_id' => null_if_empty_int($data['person_id'] ?? null),
        'request_flag' => $flag($data['request_flag'] ?? 0),
        'pre_registration_flag' => $flag($data['pre_registration_flag'] ?? 0),
        'registration_flag' => $flag($data['registration_flag'] ?? 0),
        'attendance_flag' => $att,
        'non_attendance_reason' => $reason,
        'attendance_certificate_document_id' => null_if_empty_int($data['attendance_certificate_document_id'] ?? null),
    ];
}

/**
 * @param array<string,mixed> $data
 * @return array<string,string>
 */
function training_actions_attendee_validate_save(PDO $db, array $data, ?int $existingId, int $trainingActionId): array
{
    $errors = [];
    $norm = training_actions_attendee_normalize_payload($data);

    $pid = $norm['person_id'];
    if ($pid === null || $pid < 1) {
        $errors['person_id'] = 'Persona obligatòria.';
    } else {
        $st = $db->prepare('SELECT id FROM people WHERE id = :id LIMIT 1');
        $st->execute(['id' => $pid]);
        if (!$st->fetch()) {
            $errors['person_id'] = 'Persona no trobada.';
        }
    }

    $docId = $norm['attendance_certificate_document_id'];
    if ($docId !== null && $docId > 0) {
        if (!training_actions_document_belongs_to_action($db, $docId, $trainingActionId)) {
            $errors['attendance_certificate_document_id'] = 'Document no vàlid per a aquesta acció.';
        }
    }

    if ($pid !== null && $pid > 0 && $trainingActionId > 0) {
        $sql = 'SELECT id FROM training_action_attendees WHERE training_action_id = :aid AND person_id = :pid';
        $params = ['aid' => $trainingActionId, 'pid' => $pid];
        if ($existingId !== null && $existingId > 0) {
            $sql .= ' AND id <> :eid';
            $params['eid'] = $existingId;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['person_id'] = 'Aquesta persona ja consta com a assistent d’aquesta acció.';
        }
    }

    return $errors;
}

/**
 * @param array<string,mixed> $data
 */
function training_actions_attendee_create(PDO $db, int $trainingActionId, array $data): int
{
    if ($trainingActionId < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'Acció no vàlida.'], JSON_THROW_ON_ERROR));
    }
    $norm = training_actions_attendee_normalize_payload($data);
    $errors = training_actions_attendee_validate_save($db, $data, null, $trainingActionId);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('INSERT INTO training_action_attendees (
            training_action_id, person_id,
            request_flag, pre_registration_flag, registration_flag, attendance_flag,
            non_attendance_reason, attendance_certificate_document_id
        ) VALUES (
            :aid, :pid, :rf, :prf, :rgf, :af, :nar, :doc
        )');
    $st->execute([
        'aid' => $trainingActionId,
        'pid' => (int) $norm['person_id'],
        'rf' => $norm['request_flag'],
        'prf' => $norm['pre_registration_flag'],
        'rgf' => $norm['registration_flag'],
        'af' => $norm['attendance_flag'],
        'nar' => $norm['non_attendance_reason'],
        'doc' => $norm['attendance_certificate_document_id'],
    ]);

    return (int) $db->lastInsertId();
}

/**
 * @param array<string,mixed> $data
 */
function training_actions_attendee_update(PDO $db, int $id, int $trainingActionId, array $data): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid.'], JSON_THROW_ON_ERROR));
    }
    $norm = training_actions_attendee_normalize_payload($data);
    $errors = training_actions_attendee_validate_save($db, $data, $id, $trainingActionId);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare(
        'SELECT attendance_certificate_document_id FROM training_action_attendees WHERE id = :id AND training_action_id = :aid LIMIT 1'
    );
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    $prev = $st->fetch();
    if (!$prev) {
        throw new RuntimeException('Assistent no trobat.');
    }
    $oldDocId = null_if_empty_int($prev['attendance_certificate_document_id'] ?? null);

    $st = $db->prepare('UPDATE training_action_attendees SET
            person_id = :pid,
            request_flag = :rf,
            pre_registration_flag = :prf,
            registration_flag = :rgf,
            attendance_flag = :af,
            non_attendance_reason = :nar,
            attendance_certificate_document_id = :doc
        WHERE id = :id AND training_action_id = :aid LIMIT 1');
    $st->execute([
        'id' => $id,
        'aid' => $trainingActionId,
        'pid' => (int) $norm['person_id'],
        'rf' => $norm['request_flag'],
        'prf' => $norm['pre_registration_flag'],
        'rgf' => $norm['registration_flag'],
        'af' => $norm['attendance_flag'],
        'nar' => $norm['non_attendance_reason'],
        'doc' => $norm['attendance_certificate_document_id'],
    ]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Assistent no trobat.');
    }
    $newDocId = $norm['attendance_certificate_document_id'];
    if (
        $oldDocId !== null && $oldDocId > 0
        && ($newDocId === null || $newDocId !== $oldDocId)
    ) {
        training_actions_documents_delete_attendance_certificate_if_orphan($db, $oldDocId);
    }
}

function training_actions_attendee_delete(PDO $db, int $id, int $trainingActionId): void
{
    if ($id < 1 || $trainingActionId < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid.'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare(
        'SELECT attendance_certificate_document_id FROM training_action_attendees WHERE id = :id AND training_action_id = :aid LIMIT 1'
    );
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Assistent no trobat.');
    }
    $docId = null_if_empty_int($row['attendance_certificate_document_id'] ?? null);

    $st = $db->prepare('DELETE FROM training_action_attendees WHERE id = :id AND training_action_id = :aid LIMIT 1');
    $st->execute(['id' => $id, 'aid' => $trainingActionId]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Assistent no trobat.');
    }
    if ($docId !== null && $docId > 0) {
        training_actions_documents_delete_attendance_certificate_if_orphan($db, $docId);
    }
}

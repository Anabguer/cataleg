<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/mail.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_attendees.php';

const TRAINING_ACTION_EVALUATION_TEMPLATE_REL = 'assets/excel/plantilla/Plantilla_Questionari_Avaluacio.xlsx';

/** Escala Likert Q01–Q20: answer_order i llegenda. */
const TRAINING_ACTION_EVALUATION_LIKERT_LEGEND = [
    1 => 'Gens d’acord',
    2 => 'Poc d’acord',
    3 => 'Bastant d’acord',
    4 => 'Molt d’acord',
];

function training_actions_evaluations_spreadsheet_autoload(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $p = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($p)) {
        throw new RuntimeException(
            'Falta PhpSpreadsheet. Executeu al directori del projecte: composer install (cal phpoffice/phpspreadsheet).'
        );
    }
    require_once $p;
    $done = true;
}

function training_actions_evaluation_sheet_password(): string
{
    return defined('EXCEL_QUESTIONARI_SHEET_PASSWORD') ? (string) EXCEL_QUESTIONARI_SHEET_PASSWORD : '';
}

function training_actions_evaluation_template_path(): string
{
    return APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, TRAINING_ACTION_EVALUATION_TEMPLATE_REL);
}

/**
 * @return array{enviats:string,rebuts:string,segment:?string}
 */
function training_actions_evaluation_excel_dirs(PDO $db, int $trainingActionId): array
{
    $seg = training_actions_documents_folder_segment($db, $trainingActionId);
    if ($seg === null || $seg === '') {
        return ['enviats' => '', 'rebuts' => '', 'segment' => null];
    }
    $base = APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'excel';
    $env = $base . DIRECTORY_SEPARATOR . 'enviats' . DIRECTORY_SEPARATOR . $seg;
    $reb = $base . DIRECTORY_SEPARATOR . 'rebuts' . DIRECTORY_SEPARATOR . $seg;

    return ['enviats' => $env, 'rebuts' => $reb, 'segment' => $seg];
}

function training_actions_evaluation_ensure_dir(string $absDir): bool
{
    if (is_dir($absDir)) {
        return true;
    }

    return mkdir($absDir, 0775, true) || is_dir($absDir);
}

/**
 * @return list<array<string,mixed>>
 */
function training_actions_evaluation_questions_all(PDO $db): array
{
    $st = $db->query('SELECT * FROM evaluation_questions WHERE is_active = 1 ORDER BY question_number ASC');

    return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

function training_actions_evaluation_match_display_code(string $cellValue, int $programYear, int $actionNumber): bool
{
    $expected = training_actions_format_display_code($programYear, $actionNumber);
    $a = preg_replace('/\s+/u', '', trim($cellValue));
    $b = preg_replace('/\s+/u', '', $expected);

    return mb_strtolower($a, 'UTF-8') === mb_strtolower($b, 'UTF-8');
}

/**
 * Converteix el valor de cel·la a escalar llegible (RichText, etc.).
 */
/** @param mixed $v @return mixed */
function training_actions_evaluation_normalize_cell_value($v)
{
    if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
        return $v->getPlainText();
    }

    return $v;
}

/**
 * Fórmula de la plantilla que referencia el full QUESTIONARI (p. ex. =QUESTIONARI!C82). No és text d’usuari.
 */
function training_actions_evaluation_is_questionari_template_formula(string $s): bool
{
    $t = trim($s);
    if ($t === '' || ($t[0] ?? '') !== '=') {
        return false;
    }

    return str_starts_with(mb_strtoupper($t, 'UTF-8'), '=QUESTIONARI!');
}

/**
 * Llegeix una cel·la EXPORT de forma robusta (calculat, valor en brut, sense errors Excel opacs).
 */
/** @return mixed */
function training_actions_evaluation_sheet_cell_value(
    \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
    string $coordinate
) {
    $cell = $sheet->getCell($coordinate);
    $v = $cell->getCalculatedValue();
    if ($v === null || $v === '') {
        $v = $cell->getValue();
    }
    $v = training_actions_evaluation_normalize_cell_value($v);
    if (is_string($v)) {
        $t = trim($v);
        if ($t !== '' && ($t[0] ?? '') === '#') {
            return null;
        }
        if ($t !== '' && training_actions_evaluation_is_questionari_template_formula($t)) {
            return null;
        }
    }

    return $v;
}

/**
 * Carrega un .xlsx/.xlsm amb opcions de lectura adequades per importació.
 */
function training_actions_evaluation_load_workbook_for_import(string $absolutePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    training_actions_evaluations_spreadsheet_autoload();
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($absolutePath);
    if (method_exists($reader, 'setReadDataOnly')) {
        $reader->setReadDataOnly(true);
    }

    return $reader->load($absolutePath);
}

/**
 * Missatge usable quan falla la lectura del fitxer (protecció, xifrat, corrupte).
 * No intenta eludir DRM/IRM: només classifica el missatge d’excepció per orientar l’usuari.
 */
function training_actions_evaluation_import_file_error_message(\Throwable $e, string $fileName): string
{
    $msg = $e->getMessage();
    if (str_contains($msg, 'catàleg') || str_contains($msg, 'preguntes')) {
        return $msg;
    }
    $lower = mb_strtolower($msg, 'UTF-8');
    $sensitivityHints = [
        'password',
        'encrypted',
        'encryption',
        'ole',
        'rights management',
        'azure information protection',
        'microsoft information protection',
        'rms',
        'aip',
        'irm',
        'decrypt',
        'sensitivity',
        'purview',
        'content is protected',
        'protected content',
    ];
    foreach ($sensitivityHints as $hint) {
        if (str_contains($lower, $hint)) {
            return 'No s’ha pogut llegir «' . $fileName . '»: el fitxer pot tenir etiqueta de confidencialitat, xifratge o polítiques d’empresa (Microsoft 365) que no permeten obrir-lo des d’aquest servidor. '
                . 'Opcions vàlides: desar una còpia sense etiqueta de confidencialitat, exportar només la fulla EXPORT a un .xlsx nou, o fer servir un flux aprovat per l’organització (p. ex. Power Automate amb compte autoritzat) que generi una còpia llegible per a importació.';
        }
    }
    if (str_contains($lower, 'zip') || str_contains($lower, 'corrupt') || str_contains($lower, 'parse')) {
        return 'Fitxer Excel invàlid o corrupte: «' . $fileName . '».';
    }

    return 'No s’ha pogut llegir «' . $fileName . '»: ' . $msg;
}

/**
 * Clau estable per comparar les etiquetes Likert (minúscules, sense espais ni apostrofs).
 * Q01..Q20 a EXPORT venen com a text («Gens d'acord», etc.); cal mapejar sense dependre del tipus d’apostrof.
 */
function training_actions_evaluation_likert_label_match_key(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/[\x{0027}\x{2019}\x{2018}\x{201B}\x{00B4}\x{0060}]/u', '', $s);
    $s = preg_replace('/\s+/u', '', $s);

    return $s;
}

/**
 * Genera Excel, el desa a enviats/ i actualitza o crea training_action_evaluations (camps d’enviament).
 *
 * @return array{ok:bool,message:string,path:?string,file_name:?string}
 */
function training_actions_evaluation_generate_and_record_sent(
    PDO $db,
    int $trainingActionId,
    int $attendeeId
): array {
    training_actions_evaluations_spreadsheet_autoload();

    if ($trainingActionId < 1 || $attendeeId < 1) {
        return ['ok' => false, 'message' => 'Dades invàlides.', 'path' => null, 'file_name' => null];
    }

    $att = training_actions_attendee_get_by_id($db, $attendeeId);
    if (!$att || (int) $att['training_action_id'] !== $trainingActionId) {
        return ['ok' => false, 'message' => 'Assistent no trobat.', 'path' => null, 'file_name' => null];
    }
    if ((int) $att['attendance_flag'] !== 1) {
        return ['ok' => false, 'message' => 'Només es pot enviar el qüestionari si l’assistència està marcada.', 'path' => null, 'file_name' => null];
    }

    $action = training_actions_get_by_id($db, $trainingActionId);
    if (!$action) {
        return ['ok' => false, 'message' => 'Acció no trobada.', 'path' => null, 'file_name' => null];
    }

    $tpl = training_actions_evaluation_template_path();
    if (!is_readable($tpl)) {
        return ['ok' => false, 'message' => 'No s’ha trobat la plantilla Excel a assets/excel/plantilla/.', 'path' => null, 'file_name' => null];
    }

    $dirs = training_actions_evaluation_excel_dirs($db, $trainingActionId);
    if ($dirs['segment'] === null) {
        return ['ok' => false, 'message' => 'No s’ha pogut resoldre la carpeta de l’acció.', 'path' => null, 'file_name' => null];
    }
    if (!training_actions_evaluation_ensure_dir($dirs['enviats'])) {
        return ['ok' => false, 'message' => 'No s’ha pogut crear el directori d’Excel enviats.', 'path' => null, 'file_name' => null];
    }

    $py = (int) $action['program_year'];
    $an = (int) $action['action_number'];
    $displayCode = training_actions_format_display_code($py, $an);
    $personCode = (int) $att['person_code'];

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tpl);
    $sheet = $spreadsheet->getSheetByName('QUESTIONARI');
    if ($sheet === null) {
        return ['ok' => false, 'message' => 'La plantilla no té la fulla QUESTIONARI.', 'path' => null, 'file_name' => null];
    }

    $pwd = training_actions_evaluation_sheet_password();
    $sheet->getProtection()->setSheet(false);

    $sheet->setCellValue('C8', (string) $action['name']);
    $sheet->setCellValue('C9', (string) ($action['trainers_text'] ?? ''));
    $loc = isset($action['location_name']) && (string) $action['location_name'] !== ''
        ? (string) $action['location_name']
        : '';
    $sheet->setCellValue('C10', $loc);
    $sheet->setCellValue('C11', $displayCode);
    $sheet->setCellValue('C12', $personCode);

    if ($pwd !== '') {
        $protection = $sheet->getProtection();
        $protection->setPassword($pwd);
        $protection->setSheet(true);
    } else {
        $sheet->getProtection()->setSheet(true);
    }

    $fileName = 'Questionari_' . $personCode . '_' . date('Ymd_His') . '.xlsx';
    $relPath = 'assets/excel/enviats/' . $dirs['segment'] . '/' . $fileName;
    $fullPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($fullPath);

    $snapName = (string) $action['name'];
    $snapTrainer = (string) ($action['trainers_text'] ?? '');
    $snapLoc = $loc;

    $stFind = $db->prepare(
        'SELECT id FROM training_action_evaluations WHERE training_action_id = :aid AND training_action_attendee_id = :eid LIMIT 1'
    );
    $stFind->execute(['aid' => $trainingActionId, 'eid' => $attendeeId]);
    $existing = $stFind->fetch();

    if ($existing) {
        $db->prepare(
            'UPDATE training_action_evaluations SET
                action_code_snapshot = :acs,
                person_code_snapshot = :pcs,
                sent_file_name = :sfn,
                sent_relative_path = :srp,
                action_name_snapshot = :ans,
                trainer_snapshot = :ts,
                location_snapshot = :ls,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id LIMIT 1'
        )->execute([
            'id' => (int) $existing['id'],
            'acs' => $displayCode,
            'pcs' => $personCode,
            'sfn' => $fileName,
            'srp' => $relPath,
            'ans' => $snapName,
            'ts' => $snapTrainer,
            'ls' => $snapLoc,
        ]);
    } else {
        $db->prepare(
            'INSERT INTO training_action_evaluations (
                training_action_id, training_action_attendee_id, action_code_snapshot, person_code_snapshot,
                sent_file_name, sent_relative_path, action_name_snapshot, trainer_snapshot, location_snapshot
            ) VALUES (
                :aid, :eid, :acs, :pcs, :sfn, :srp, :ans, :ts, :ls
            )'
        )->execute([
            'aid' => $trainingActionId,
            'eid' => $attendeeId,
            'acs' => $displayCode,
            'pcs' => $personCode,
            'sfn' => $fileName,
            'srp' => $relPath,
            'ans' => $snapName,
            'ts' => $snapTrainer,
            'ls' => $snapLoc,
        ]);
    }

    return ['ok' => true, 'message' => 'Excel generat.', 'path' => $fullPath, 'file_name' => $fileName];
}

/**
 * @return array{ok:bool,message:string}
 */
function training_actions_evaluation_send_email_for_attendee(PDO $db, int $trainingActionId, int $attendeeId): array
{
    $gen = training_actions_evaluation_generate_and_record_sent($db, $trainingActionId, $attendeeId);
    if (!$gen['ok'] || $gen['path'] === null) {
        return ['ok' => false, 'message' => $gen['message']];
    }

    $att = training_actions_attendee_get_by_id($db, $attendeeId);
    if (!$att || $att['email'] === null || trim((string) $att['email']) === '') {
        return ['ok' => false, 'message' => 'L’assistent no té correu electrònic.'];
    }

    $action = training_actions_get_by_id($db, $trainingActionId);
    $actionName = $action ? (string) $action['name'] : 'Acció formativa';
    $subject = 'Qüestionari d’avaluació — ' . $actionName;
    $body = "S’adjunta el qüestionari d’avaluació en format Excel.\r\n\r\nGràcies.";

    $mail = app_mail_send_with_attachment(
        (string) $att['email'],
        $subject,
        $body,
        $gen['path'],
        (string) $gen['file_name']
    );
    if (!$mail['ok']) {
        return ['ok' => false, 'message' => $mail['error'] ?? 'Error d’enviament de correu.'];
    }

    return ['ok' => true, 'message' => 'Qüestionari generat i enviat per correu.'];
}

/**
 * @return array{ok:bool,sent:int,errors:list<string>}
 */
function training_actions_evaluation_send_all_attended(PDO $db, int $trainingActionId): array
{
    $list = training_actions_attendees_list($db, $trainingActionId);
    $sent = 0;
    $errors = [];
    foreach ($list as $row) {
        if ((int) ($row['attendance_flag'] ?? 0) !== 1) {
            continue;
        }
        $r = training_actions_evaluation_send_email_for_attendee($db, $trainingActionId, (int) $row['id']);
        if ($r['ok']) {
            ++$sent;
        } else {
            $errors[] = ($row['person_display'] ?? '?') . ': ' . $r['message'];
        }
    }

    if ($sent === 0 && $errors === []) {
        $errors[] = 'No hi ha assistents amb assistència marcada o sense correu.';
    }

    return ['ok' => $sent > 0, 'sent' => $sent, 'errors' => $errors];
}

/**
 * Importa un .xlsx des d’una ruta temporal; només si coincideix amb trainingActionId.
 *
 * @return array{ok:bool,message:string,skipped_other_action?:bool,file_name?:string}
 */
function training_actions_evaluation_import_xlsx_path(
    PDO $db,
    int $trainingActionId,
    string $tmpPath,
    string $originalName
): array {
    $action = training_actions_get_by_id($db, $trainingActionId);
    if (!$action) {
        return ['ok' => false, 'message' => 'Acció no trobada.'];
    }
    $py = (int) $action['program_year'];
    $an = (int) $action['action_number'];

    if (!is_readable($tmpPath)) {
        return ['ok' => false, 'message' => 'Fitxer no llegible.'];
    }

    try {
        $spreadsheet = training_actions_evaluation_load_workbook_for_import($tmpPath);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => training_actions_evaluation_import_file_error_message($e, $originalName)];
    }

    $sheet = $spreadsheet->getSheetByName('EXPORT');
    if ($sheet === null) {
        return ['ok' => false, 'message' => 'No s’ha trobat la fulla EXPORT a «' . $originalName . '».'];
    }

    $codeCell = training_actions_evaluation_sheet_cell_value($sheet, 'A2');
    $personCell = training_actions_evaluation_sheet_cell_value($sheet, 'AH2');
    $codeStr = $codeCell === null ? '' : trim((string) $codeCell);
    $personCode = (int) preg_replace('/\D/', '', (string) $personCell);

    if (!training_actions_evaluation_match_display_code($codeStr, $py, $an)) {
        return [
            'ok' => false,
            'message' => 'Codi d’acció no coincideix amb l’acció oberta (' . $codeStr . ').',
            'skipped_other_action' => true,
            'file_name' => $originalName,
        ];
    }

    if ($personCode < 1) {
        return ['ok' => false, 'message' => 'Codi de persona no vàlid a AH2: ' . $originalName];
    }

    $stP = $db->prepare('SELECT id FROM people WHERE person_code = :pc LIMIT 1');
    $stP->execute(['pc' => $personCode]);
    $pr = $stP->fetch();
    if (!$pr) {
        return ['ok' => false, 'message' => 'Persona amb codi ' . $personCode . ' no trobada.'];
    }
    $personId = (int) $pr['id'];

    $stA = $db->prepare(
        'SELECT id FROM training_action_attendees WHERE training_action_id = :aid AND person_id = :pid LIMIT 1'
    );
    $stA->execute(['aid' => $trainingActionId, 'pid' => $personId]);
    $ar = $stA->fetch();
    if (!$ar) {
        return ['ok' => false, 'message' => 'No hi ha assistent per aquesta acció i persona.'];
    }
    $attendeeId = (int) $ar['id'];

    $global = training_actions_evaluation_parse_decimal(training_actions_evaluation_sheet_cell_value($sheet, 'E2'));
    $attReason = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'F2'));
    $mainMot = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'G2'));
    $recommend = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'H2'));
    $strengths = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'J2'), true);
    $weaknesses = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'K2'), true);
    $application = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'L2'), true);
    $other = training_actions_evaluation_cell_string(training_actions_evaluation_sheet_cell_value($sheet, 'M2'), true);

    $dirs = training_actions_evaluation_excel_dirs($db, $trainingActionId);
    if ($dirs['rebuts'] === '' || $dirs['segment'] === null) {
        return ['ok' => false, 'message' => 'Carpeta rebuts no vàlida.'];
    }
    if (!training_actions_evaluation_ensure_dir($dirs['rebuts'])) {
        return ['ok' => false, 'message' => 'No s’ha pogut crear la carpeta de rebuts.'];
    }

    $safeOrig = preg_replace('/[^a-zA-Z0-9._-]+/', '_', basename($originalName));
    if ($safeOrig === '' || $safeOrig === '_') {
        $safeOrig = 'importat.xlsx';
    }
    $destName = date('Ymd_His') . '_' . $safeOrig;
    $relReceived = 'assets/excel/rebuts/' . $dirs['segment'] . '/' . $destName;
    $destFull = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relReceived);
    if (!copy($tmpPath, $destFull)) {
        return ['ok' => false, 'message' => 'No s’ha pogut copiar el fitxer a rebuts.'];
    }

    $db->beginTransaction();
    try {
        $stOld = $db->prepare(
            'SELECT id FROM training_action_evaluations WHERE training_action_id = :aid AND training_action_attendee_id = :eid LIMIT 1'
        );
        $stOld->execute(['aid' => $trainingActionId, 'eid' => $attendeeId]);
        $old = $stOld->fetch();

        $snapName = (string) $action['name'];
        $snapTrainer = (string) ($action['trainers_text'] ?? '');
        $loc = isset($action['location_name']) && (string) $action['location_name'] !== ''
            ? (string) $action['location_name']
            : '';

        $displayCode = training_actions_format_display_code($py, $an);

        if ($old) {
            $evalId = (int) $old['id'];
            $db->prepare('DELETE FROM training_action_evaluation_answers WHERE evaluation_id = :id')->execute(['id' => $evalId]);
            // UPDATE: no toquem sent_file_name ni sent_relative_path (referència de l’Excel enviat).
            $db->prepare(
                'UPDATE training_action_evaluations SET
                    response_date = NULL,
                    action_code_snapshot = :acs,
                    person_code_snapshot = :pcs,
                    received_file_name = :rfn,
                    received_relative_path = :rrp,
                    action_name_snapshot = :ans,
                    trainer_snapshot = :ts,
                    location_snapshot = :ls,
                    global_score_1_10 = :gs,
                    attendance_reason = :ar,
                    main_motivation = :mm,
                    would_recommend = :wr,
                    strengths = :st,
                    weaknesses = :wk,
                    application = :ap,
                    other_comments = :oc,
                    imported_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id LIMIT 1'
            )->execute([
                'id' => $evalId,
                'acs' => $displayCode,
                'pcs' => $personCode,
                'rfn' => $destName,
                'rrp' => $relReceived,
                'ans' => $snapName,
                'ts' => $snapTrainer,
                'ls' => $loc,
                'gs' => $global,
                'ar' => $attReason,
                'mm' => $mainMot,
                'wr' => $recommend,
                'st' => $strengths,
                'wk' => $weaknesses,
                'ap' => $application,
                'oc' => $other,
            ]);
        } else {
            $db->prepare(
                'INSERT INTO training_action_evaluations (
                    training_action_id, training_action_attendee_id, response_date,
                    action_code_snapshot, person_code_snapshot,
                    received_file_name, received_relative_path,
                    action_name_snapshot, trainer_snapshot, location_snapshot,
                    global_score_1_10, attendance_reason, main_motivation, would_recommend,
                    strengths, weaknesses, application, other_comments, imported_at
                ) VALUES (
                    :aid, :eid, NULL,
                    :acs, :pcs,
                    :rfn, :rrp,
                    :ans, :ts, :ls,
                    :gs, :ar, :mm, :wr,
                    :st, :wk, :ap, :oc, CURRENT_TIMESTAMP
                )'
            )->execute([
                'aid' => $trainingActionId,
                'eid' => $attendeeId,
                'acs' => $displayCode,
                'pcs' => $personCode,
                'rfn' => $destName,
                'rrp' => $relReceived,
                'ans' => $snapName,
                'ts' => $snapTrainer,
                'ls' => $loc,
                'gs' => $global,
                'ar' => $attReason,
                'mm' => $mainMot,
                'wr' => $recommend,
                'st' => $strengths,
                'wk' => $weaknesses,
                'ap' => $application,
                'oc' => $other,
            ]);
            $evalId = (int) $db->lastInsertId();
        }

        $questions = training_actions_evaluation_questions_all($db);
        if (count($questions) !== 20) {
            throw new RuntimeException('El catàleg de preguntes ha de tenir 20 ítems actius.');
        }

        // Q01..Q20: fulla EXPORT, fila 2, columnes N..AG (14–33).
        $colStart = 14;
        $exportDataRow = 2;
        foreach ($questions as $idx => $q) {
            $colIdx = $colStart + $idx;
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $raw = training_actions_evaluation_sheet_cell_value($sheet, $letter . $exportDataRow);
            $likert = training_actions_evaluation_parse_likert($raw);
            $qid = (int) $q['id'];
            $qnum = (int) $q['question_number'];
            $qtext = (string) $q['question_text'];
            $okLikert = $likert >= 1 && $likert <= 4;
            $legend = $okLikert ? (TRAINING_ACTION_EVALUATION_LIKERT_LEGEND[$likert] ?? null) : null;

            $db->prepare(
                'INSERT INTO training_action_evaluation_answers (
                    evaluation_id, question_id, question_number_snapshot, question_text_snapshot,
                    answer_order, answer_text, numeric_value, imported_at
                ) VALUES (
                    :eid, :qid, :qns, :qtx,
                    :ao, :at, :nv, CURRENT_TIMESTAMP
                )'
            )->execute([
                'eid' => $evalId,
                'qid' => $qid,
                'qns' => $qnum,
                'qtx' => $qtext,
                'ao' => $okLikert ? $likert : null,
                'at' => $legend,
                'nv' => $okLikert ? (float) $likert : null,
            ]);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return ['ok' => true, 'message' => 'Importat: ' . $originalName, 'file_name' => $originalName];
}

/** @param mixed $v */
function training_actions_evaluation_parse_decimal($v): ?float
{
    if ($v === null || $v === '') {
        return null;
    }
    if (is_string($v)) {
        $ts = trim($v);
        if ($ts !== '' && training_actions_evaluation_is_questionari_template_formula($ts)) {
            return null;
        }
    }
    if (is_numeric($v)) {
        return round((float) $v, 2);
    }
    $s = str_replace(',', '.', trim((string) $v));

    return is_numeric($s) ? round((float) $s, 2) : null;
}

/** @param mixed $v */
function training_actions_evaluation_cell_string($v, bool $long = false): ?string
{
    if ($v === null) {
        return null;
    }
    $s = trim((string) $v);
    if ($s !== '' && training_actions_evaluation_is_questionari_template_formula($s)) {
        return null;
    }

    return $s === '' ? null : ($long ? $s : mb_substr($s, 0, 255, 'UTF-8'));
}

/** @param mixed $raw */
function training_actions_evaluation_parse_likert($raw): int
{
    if ($raw === null || $raw === '') {
        return 0;
    }
    if (is_numeric($raw)) {
        $n = (int) round((float) $raw);
        if ($n >= 1 && $n <= 4) {
            return $n;
        }
    }
    $key = training_actions_evaluation_likert_label_match_key((string) $raw);
    /** @var array<string,int> */
    static $likertByKey = [
        'gensdacord' => 1,
        'pocdacord' => 2,
        'bastantdacord' => 3,
        'moltdacord' => 4,
    ];

    return $likertByKey[$key] ?? 0;
}

/**
 * @return list<array<string,mixed>>
 */
function training_actions_evaluations_list_for_action(PDO $db, int $trainingActionId): array
{
    if ($trainingActionId < 1) {
        return [];
    }
    $sql = 'SELECT tae.*,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2
            FROM training_action_evaluations tae
            INNER JOIN training_action_attendees taa ON taa.id = tae.training_action_attendee_id
            INNER JOIN people p ON p.id = taa.person_id
            WHERE tae.training_action_id = :aid
            ORDER BY p.last_name_1, p.last_name_2, p.first_name, tae.id';
    $st = $db->prepare($sql);
    $st->execute(['aid' => $trainingActionId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
    $answersByEval = [];
    if ($ids !== []) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st2 = $db->prepare(
            "SELECT evaluation_id, question_number_snapshot, numeric_value, answer_order
             FROM training_action_evaluation_answers WHERE evaluation_id IN ($in)"
        );
        $st2->execute($ids);
        while ($a = $st2->fetch(PDO::FETCH_ASSOC)) {
            $eid = (int) $a['evaluation_id'];
            $qn = (int) $a['question_number_snapshot'];
            $answersByEval[$eid][$qn] = [
                'numeric_value' => $a['numeric_value'] !== null ? (float) $a['numeric_value'] : null,
                'answer_order' => $a['answer_order'] !== null ? (int) $a['answer_order'] : null,
            ];
        }
    }

    $out = [];
    foreach ($rows as $r) {
        $eid = (int) $r['id'];
        $qmap = [];
        for ($i = 1; $i <= 20; $i++) {
            $qmap[$i] = isset($answersByEval[$eid][$i])
                ? ($answersByEval[$eid][$i]['answer_order'] ?? $answersByEval[$eid][$i]['numeric_value'])
                : null;
        }
        $out[] = [
            'id' => $eid,
            'training_action_attendee_id' => (int) $r['training_action_attendee_id'],
            'person_display' => people_format_surname_first_with_code($r),
            'global_score_1_10' => $r['global_score_1_10'] !== null ? (float) $r['global_score_1_10'] : null,
            'imported_at' => $r['imported_at'] ?? null,
            'received_relative_path' => $r['received_relative_path'] ?? null,
            'q' => $qmap,
        ];
    }

    return $out;
}

/**
 * @return array<string,mixed>|null
 */
function training_actions_evaluation_get_detail(PDO $db, int $evaluationId, int $trainingActionId): ?array
{
    if ($evaluationId < 1 || $trainingActionId < 1) {
        return null;
    }
    $sql = 'SELECT tae.*,
                   p.person_code, p.first_name, p.last_name_1, p.last_name_2
            FROM training_action_evaluations tae
            INNER JOIN training_action_attendees taa ON taa.id = tae.training_action_attendee_id
            INNER JOIN people p ON p.id = taa.person_id
            WHERE tae.id = :id AND tae.training_action_id = :aid LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $evaluationId, 'aid' => $trainingActionId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $stA = $db->prepare(
        'SELECT * FROM training_action_evaluation_answers WHERE evaluation_id = :eid ORDER BY question_number_snapshot ASC'
    );
    $stA->execute(['eid' => $evaluationId]);
    $ans = $stA->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $receivedUrl = null;
    $rp = isset($row['received_relative_path']) ? (string) $row['received_relative_path'] : '';
    if ($rp !== '') {
        $receivedUrl = app_url('training_action_evaluation_received_download.php?evaluation_id=' . $evaluationId . '&training_action_id=' . $trainingActionId);
    }

    return [
        'evaluation' => $row,
        'person_display' => people_format_surname_first_with_code($row),
        'answers' => $ans,
        'received_download_url' => $receivedUrl,
        'likert_legend' => TRAINING_ACTION_EVALUATION_LIKERT_LEGEND,
    ];
}

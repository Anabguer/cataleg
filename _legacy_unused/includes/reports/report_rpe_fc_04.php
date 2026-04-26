<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/people/people.php';
require_once APP_ROOT . '/includes/reports/report_rpe_fc_02.php';

/**
 * RPEFC-04 — Persones inscrites sense assistència.
 *
 * Accions de l’any amb almenys una fila a `training_action_attendees` amb
 * `registration_flag = 1` i `attendance_flag = 0`. La durada és la real de l’acció (sense producte).
 * El paràmetre «dades inscrits» només controla si es llisten les persones sota cada acció.
 */

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_04_resolved_title(array $reportRow, bool $dadesInscrits): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }
    $suffix = $dadesInscrits ? ' (amb inscrits)' : ' (sense inscrits)';

    return $base . $suffix;
}

/**
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_04_fetch_actions(PDO $db, int $programYear, bool $includeDraft): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $existsSub = 'SELECT 1
                  FROM training_action_attendees taa
                  WHERE taa.training_action_id = ta.id
                    AND taa.registration_flag = 1
                    AND taa.attendance_flag = 0';

    $sql = 'SELECT
                ta.id,
                ta.program_year,
                ta.action_number,
                ta.name,
                ta.actual_duration_hours
            FROM training_actions ta
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
              AND EXISTS (' . $existsSub . ')
            ORDER BY ta.action_number ASC, ta.id ASC';

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Inscrits sense assistència (registration 1, attendance 0).
 *
 * @param list<int> $actionIds
 * @return array<int, list<array<string,mixed>>>
 */
function report_rpe_fc_04_fetch_inscrits_sense_assistencia_by_action(PDO $db, array $actionIds): array
{
    $clean = [];
    foreach ($actionIds as $id) {
        $n = (int) $id;
        if ($n > 0) {
            $clean[$n] = true;
        }
    }
    $ids = array_keys($clean);
    if ($ids === []) {
        return [];
    }

    sort($ids, SORT_NUMERIC);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT
                taa.training_action_id,
                p.person_code,
                p.last_name_1,
                p.last_name_2,
                p.first_name
            FROM training_action_attendees taa
            INNER JOIN people p ON p.id = taa.person_id
            WHERE taa.registration_flag = 1
              AND taa.attendance_flag = 0
              AND taa.training_action_id IN ($placeholders)
            ORDER BY
                taa.training_action_id ASC,
                p.last_name_1 ASC,
                p.last_name_2 ASC,
                p.first_name ASC,
                p.person_code ASC,
                p.id ASC";

    $st = $db->prepare($sql);
    $st->execute($ids);

    $out = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $aid = (int) ($row['training_action_id'] ?? 0);
        if ($aid < 1) {
            continue;
        }
        if (!isset($out[$aid])) {
            $out[$aid] = [];
        }
        $out[$aid][] = [
            'person_code' => (int) ($row['person_code'] ?? 0),
            'display_name' => people_format_surname_first($row),
        ];
    }

    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_04_load_actions(PDO $db, int $programYear, bool $includeDraft, bool $withInscritDetails): array
{
    $raw = report_rpe_fc_04_fetch_actions($db, $programYear, $includeDraft);
    if ($raw === []) {
        return [];
    }

    $ids = [];
    foreach ($raw as $r) {
        $ids[] = (int) ($r['id'] ?? 0);
    }
    $byAction = report_rpe_fc_04_fetch_inscrits_sense_assistencia_by_action($db, $ids);

    $out = [];
    foreach ($raw as $r) {
        $id = (int) ($r['id'] ?? 0);
        $py = (int) ($r['program_year'] ?? 0);
        $an = (int) ($r['action_number'] ?? 0);
        $durFields = report_rpe_fc_action_actual_duration_display_fields($r);
        $people = $withInscritDetails ? ($byAction[$id] ?? []) : [];

        $out[] = [
            'training_action_id' => $id,
            'action_display_code' => training_actions_format_display_code($py, $an),
            'action_name' => (string) ($r['name'] ?? ''),
            'actual_duration_hours_f' => $durFields['actual_duration_hours_f'],
            'attendee_count' => 0,
            'performed_hours_f' => $durFields['performed_hours_f'],
            'duration_parts' => null,
            'duration_simple' => $durFields['duration_simple'],
            'attendees' => $people,
        ];
    }

    return $out;
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_04_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $dadesInscrits): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $actions = report_rpe_fc_04_load_actions($db, $programYear, $includeDraft, $dadesInscrits);
    $totalHours = report_rpe_fc_02_total_hours($actions);

    $title = report_rpe_fc_04_resolved_title($reportRow, $dadesInscrits);

    $withAttendees = $dadesInscrits;

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));

    $ctxHeader = [
        'report_code' => (string) $reportRow['report_code'],
        'report_title' => $title,
        'program_year' => $programYear,
        'generated_at' => $generatedAt,
    ];

    ob_start();
    report_header_render($ctxHeader);
    $headerHtml = ob_get_clean();

    ob_start();
    require APP_ROOT . '/views/reports/rpe_fc_04_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
        'dades_inscrits' => $dadesInscrits ? '1' : '0',
    ];
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/people/people.php';
require_once APP_ROOT . '/includes/reports/report_rpe_fc_01.php';
require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';

/**
 * RPEFC-02 — Accions formatives realitzades.
 *
 * Només inclou accions amb almenys una assistència registrada (`training_action_attendees.attendance_flag = 1`).
 * Columna Durada: hores realitzades = assistències (flag 1) × durada real de l’acció, amb text «(X × Y) Z hores».
 * El paràmetre «amb assistents» només controla si es llisten les persones sota cada acció.
 */

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_02_resolved_title(array $reportRow, bool $withAttendees): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }
    $suffix = $withAttendees ? ' (amb assistents)' : ' (sense assistents)';

    return $base . $suffix;
}

/**
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_02_fetch_actions(PDO $db, int $programYear, bool $includeDraft, bool $mustHaveAttendanceFlag1 = true): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $attendanceSub = 'SELECT 1
                  FROM training_action_attendees taa
                  WHERE taa.training_action_id = ta.id
                    AND taa.attendance_flag = 1';
    $attendanceClause = $mustHaveAttendanceFlag1
        ? 'EXISTS (' . $attendanceSub . ')'
        : 'NOT EXISTS (' . $attendanceSub . ')';

    $sql = 'SELECT
                ta.id,
                ta.program_year,
                ta.action_number,
                ta.name,
                ta.actual_duration_hours,
                (
                    SELECT COUNT(*)
                    FROM training_action_attendees taa_c
                    WHERE taa_c.training_action_id = ta.id
                      AND taa_c.attendance_flag = 1
                ) AS attendance_count
            FROM training_actions ta
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
              AND ' . $attendanceClause . '
            ORDER BY ta.action_number ASC, ta.id ASC';

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param list<int> $actionIds
 * @return array<int, list<array<string,mixed>>>
 */
function report_rpe_fc_02_fetch_attendees_by_action(PDO $db, array $actionIds): array
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
            WHERE taa.attendance_flag = 1
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
 * Parts de la cel·la Durada: expressió «(X × Y)» i resultat «Z hores»; null si no aplica.
 *
 * @return array{expr: string, result: string}|null
 */
function report_rpe_fc_02_duration_cell_parts(int $attendeeCount, ?float $actualHours): ?array
{
    if ($attendeeCount < 1 || $actualHours === null) {
        return null;
    }
    $yStr = report_rpa_fc_01_format_hours($actualHours);
    $product = $attendeeCount * $actualHours;

    return [
        'expr' => '(' . $attendeeCount . ' × ' . $yStr . ')',
        'result' => report_rpa_fc_01_format_hours($product) . ' hores',
    ];
}

/**
 * Durada real simple (RPEFC-03 / RPEFC-04): text de cel·la i valor per sumar al total (sense producte).
 *
 * @param array<string,mixed> $r Fila amb `actual_duration_hours`
 * @return array{actual_duration_hours_f: ?float, performed_hours_f: ?float, duration_simple: string}
 */
function report_rpe_fc_action_actual_duration_display_fields(array $r): array
{
    $dur = report_rpe_fc_01_actual_hours_positive(isset($r['actual_duration_hours']) ? (string) $r['actual_duration_hours'] : null);

    return [
        'actual_duration_hours_f' => $dur,
        'performed_hours_f' => $dur,
        'duration_simple' => $dur !== null ? report_rpa_fc_01_format_hours($dur) . ' hores' : '—',
    ];
}

/**
 * @param list<array<string,mixed>> $rawActions
 * @param array<int, list<array<string,mixed>>> $attendeesByAction
 * @param string $durationMode `performed_product` (RPEFC-02) o `actual_hours_simple` (RPEFC-03: durada real per fila, sense producte)
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_02_enrich_actions(array $rawActions, array $attendeesByAction, bool $withAttendees, string $durationMode = 'performed_product'): array
{
    $out = [];
    foreach ($rawActions as $r) {
        $id = (int) ($r['id'] ?? 0);
        $py = (int) ($r['program_year'] ?? 0);
        $an = (int) ($r['action_number'] ?? 0);
        $cnt = (int) ($r['attendance_count'] ?? 0);

        if ($durationMode === 'actual_hours_simple') {
            $durFields = report_rpe_fc_action_actual_duration_display_fields($r);
            $dur = $durFields['actual_duration_hours_f'];
            $performed = $durFields['performed_hours_f'];
            $parts = null;
            $listAtt = [];
            $simple = $durFields['duration_simple'];
        } else {
            $dur = report_rpe_fc_01_actual_hours_positive(isset($r['actual_duration_hours']) ? (string) $r['actual_duration_hours'] : null);
            $performed = ($cnt > 0 && $dur !== null) ? ($cnt * $dur) : null;
            $parts = report_rpe_fc_02_duration_cell_parts($cnt, $dur);
            $listAtt = $withAttendees ? ($attendeesByAction[$id] ?? []) : [];
            $simple = null;
        }

        $item = [
            'training_action_id' => $id,
            'action_display_code' => training_actions_format_display_code($py, $an),
            'action_name' => (string) ($r['name'] ?? ''),
            'actual_duration_hours_f' => $dur,
            'attendee_count' => $cnt,
            'performed_hours_f' => $performed,
            'duration_parts' => $parts,
            'attendees' => $listAtt,
        ];
        if ($durationMode === 'actual_hours_simple') {
            $item['duration_simple'] = $simple;
        }
        $out[] = $item;
    }

    return $out;
}

/**
 * Suma de `performed_hours_f` per fila: producte assistències×durada (RPEFC-02) o durada real vàlida (RPEFC-03).
 *
 * @param list<array<string,mixed>> $actions
 */
function report_rpe_fc_02_total_hours(array $actions): float
{
    $sum = 0.0;
    foreach ($actions as $a) {
        $p = $a['performed_hours_f'] ?? null;
        if ($p !== null) {
            $sum += (float) $p;
        }
    }

    return $sum;
}

/**
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_02_load_actions(
    PDO $db,
    int $programYear,
    bool $includeDraft,
    bool $withAttendees,
    bool $mustHaveAttendanceFlag1 = true,
    string $durationMode = 'performed_product'
): array {
    $raw = report_rpe_fc_02_fetch_actions($db, $programYear, $includeDraft, $mustHaveAttendanceFlag1);
    $byAction = [];
    if ($withAttendees && $raw !== [] && $durationMode === 'performed_product') {
        $ids = [];
        foreach ($raw as $r) {
            $ids[] = (int) ($r['id'] ?? 0);
        }
        $byAction = report_rpe_fc_02_fetch_attendees_by_action($db, $ids);
    }

    return report_rpe_fc_02_enrich_actions($raw, $byAction, $withAttendees, $durationMode);
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_02_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $withAttendees): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $actions = report_rpe_fc_02_load_actions($db, $programYear, $includeDraft, $withAttendees);
    $totalHours = report_rpe_fc_02_total_hours($actions);

    $title = report_rpe_fc_02_resolved_title($reportRow, $withAttendees);

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
    require APP_ROOT . '/views/reports/rpe_fc_02_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
        'amb_assistents' => $withAttendees ? '1' : '0',
    ];
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/people/people.php';
require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';

/**
 * RPEFC-01 — Formació realitzada per persones.
 *
 * Criteri: només assistències reals (`training_action_attendees.attendance_flag = 1`).
 * No s’utilitzen inscripció ni preinscripció.
 *
 * Dates previstes: totes les files de `training_action_dates` per acció (ordenades per `sort_order`, `session_date`, `id`).
 * Presentació: fins a 3 dates per línia (separades per espais), línies successives si n’hi ha més.
 * Durada real: `actual_duration_hours` (> 0 vàlid; si no → «—» sense inventar).
 *
 * Ordenació persones: cognoms i nom (`last_name_1`, `last_name_2`, `first_name`), coherent amb `people_format_surname_first`.
 * Dins de cada persona: subprograma, i accions per `action_number`.
 */

/**
 * Títol de l’informe des del catàleg (sense sufix de tipus de formació: aquest informe no filtra per tipus).
 *
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_01_resolved_title(array $reportRow): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }

    return $base;
}

/**
 * Durada real > 0 per mostrar i concatenar «hores».
 */
function report_rpe_fc_01_actual_hours_positive(?string $raw): ?float
{
    if ($raw === null || $raw === '') {
        return null;
    }
    $f = (float) $raw;

    return $f > 0.0 ? $f : null;
}

/**
 * Totes les dates previstes (una entrada per fila a `training_action_dates`), formatades d/m/Y.
 *
 * @param list<int> $actionIds
 * @return array<int, list<string>> training_action_id => llista de dates en ordre de calendari / sort_order
 */
function report_rpe_fc_01_fetch_planned_dates_by_action(PDO $db, array $actionIds): array
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
    $sql = "SELECT training_action_id, session_date
            FROM training_action_dates
            WHERE training_action_id IN ($placeholders)
            ORDER BY training_action_id ASC, sort_order ASC, session_date ASC, id ASC";

    $st = $db->prepare($sql);
    $st->execute($ids);

    $out = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $aid = (int) ($row['training_action_id'] ?? 0);
        $sd = $row['session_date'] ?? null;
        if ($aid < 1) {
            continue;
        }
        if (!isset($out[$aid])) {
            $out[$aid] = [];
        }
        if (is_string($sd) && $sd !== '') {
            $out[$aid][] = report_rpa_fc_01_format_date_ymd($sd);
        }
    }

    return $out;
}

/**
 * Dates ja formatades (d/m/Y): fins a $perLine per línia, separades per « - »; ordre conservat.
 *
 * @param list<string> $formattedDates
 * @return list<string>
 */
function report_rpe_fc_01_dates_display_lines(array $formattedDates, int $perLine = 3): array
{
    if ($formattedDates === []) {
        return [];
    }
    $n = max(1, $perLine);
    $chunks = array_chunk($formattedDates, $n);
    $out = [];
    foreach ($chunks as $chunk) {
        $out[] = implode(' - ', $chunk);
    }

    return $out;
}

/**
 * Text per cel·la Excel: línies de fins a 3 dates, separades per salt de línia.
 *
 * @param list<string> $formattedDates Dates d/m/Y en ordre
 */
function report_rpe_fc_01_planned_dates_cell_text(array $formattedDates): string
{
    $lines = report_rpe_fc_01_dates_display_lines($formattedDates, 3);
    if ($lines === []) {
        return '—';
    }

    return implode("\n", $lines);
}

/**
 * @return list<array<string,mixed>>
 */
function report_rpe_fc_01_fetch_rows(PDO $db, int $programYear, bool $includeDraft): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $sql = "SELECT
                p.id AS person_id,
                p.person_code,
                p.last_name_1,
                p.last_name_2,
                p.first_name,
                ta.id AS training_action_id,
                ta.program_year,
                ta.action_number,
                ta.name AS action_name,
                ta.actual_duration_hours,
                ta.subprogram_id,
                sp.subprogram_code AS subprogram_code,
                sp.name AS subprogram_name
            FROM training_action_attendees taa
            INNER JOIN people p ON p.id = taa.person_id
            INNER JOIN training_actions ta ON ta.id = taa.training_action_id
            LEFT JOIN training_subprograms sp ON sp.id = ta.subprogram_id
            WHERE taa.attendance_flag = 1
              AND ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY
                p.last_name_1 ASC,
                p.last_name_2 ASC,
                p.first_name ASC,
                p.person_code ASC,
                p.id ASC,
                COALESCE(sp.subprogram_code, 999999) ASC,
                sp.name ASC,
                ta.action_number ASC,
                ta.id ASC";

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array<string,mixed> $r
 * @param array<int, list<string>> $datesByAction
 * @return array<string,mixed>
 */
function report_rpe_fc_01_enrich_row(array $r, array $datesByAction): array
{
    $dur = report_rpe_fc_01_actual_hours_positive(isset($r['actual_duration_hours']) ? (string) $r['actual_duration_hours'] : null);
    $aid = (int) ($r['training_action_id'] ?? 0);
    $plannedLines = $datesByAction[$aid] ?? [];

    return array_merge($r, [
        'person_display_name' => people_format_surname_first($r),
        'action_display_code' => training_actions_format_display_code((int) $r['program_year'], (int) $r['action_number']),
        'planned_date_lines' => $plannedLines,
        'actual_duration_hours_f' => $dur,
    ]);
}

/**
 * @param array<string,mixed> $r
 */
function report_rpe_fc_01_subprogram_key(array $r): string
{
    if (!isset($r['subprogram_id']) || $r['subprogram_id'] === null || $r['subprogram_id'] === '') {
        return 's_none';
    }

    return 's_' . (int) $r['subprogram_id'];
}

/**
 * @param list<array<string,mixed>> $enrichedRows Files ja passades per `report_rpe_fc_01_enrich_row`
 * @return list<array<string,mixed>>
 */
function report_rpe_fc_01_build_people_blocks(array $enrichedRows): array
{
    /** @var array<int, array<string,mixed>> $people */
    $people = [];
    /** @var list<int> $order */
    $order = [];

    foreach ($enrichedRows as $r) {
        $pid = (int) ($r['person_id'] ?? 0);
        if ($pid < 1) {
            continue;
        }

        if (!isset($people[$pid])) {
            $people[$pid] = [
                'person_id' => $pid,
                'person_code' => (int) ($r['person_code'] ?? 0),
                'display_name' => (string) ($r['person_display_name'] ?? ''),
                'subprograms' => [],
                'subprogram_order' => [],
            ];
            $order[] = $pid;
        }

        $sk = report_rpe_fc_01_subprogram_key($r);
        if (!isset($people[$pid]['subprograms'][$sk])) {
            $code = $r['subprogram_code'] ?? null;
            $name = trim((string) ($r['subprogram_name'] ?? ''));
            if ($name === '') {
                $name = 'Sense subprograma';
            }
            $codeDisp = $code !== null && $code !== ''
                ? format_padded_code((int) $code, 3)
                : '—';

            $people[$pid]['subprograms'][$sk] = [
                'subprogram_id' => $r['subprogram_id'],
                'code_display' => $codeDisp,
                'name' => $name,
                'actions' => [],
            ];
            $people[$pid]['subprogram_order'][] = $sk;
        }

        $people[$pid]['subprograms'][$sk]['actions'][] = $r;
    }

    $out = [];
    foreach ($order as $pid) {
        $p = $people[$pid];
        $subs = [];
        foreach ($p['subprogram_order'] as $sk) {
            $subs[] = $p['subprograms'][$sk];
        }
        $p['subprograms'] = $subs;
        unset($p['subprogram_order']);
        $out[] = $p;
    }

    return $out;
}

/**
 * Carrega i agrupa dades per a HTML i Excel.
 *
 * @return list<array<string,mixed>>
 */
function report_rpe_fc_01_load_people_blocks(PDO $db, int $programYear, bool $includeDraft): array
{
    $rows = report_rpe_fc_01_fetch_rows($db, $programYear, $includeDraft);
    $actionIds = [];
    foreach ($rows as $raw) {
        $actionIds[] = (int) ($raw['training_action_id'] ?? 0);
    }
    $datesByAction = report_rpe_fc_01_fetch_planned_dates_by_action($db, $actionIds);
    $enriched = [];
    foreach ($rows as $raw) {
        $enriched[] = report_rpe_fc_01_enrich_row($raw, $datesByAction);
    }

    return report_rpe_fc_01_build_people_blocks($enriched);
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_01_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $peopleBlocks = report_rpe_fc_01_load_people_blocks($db, $programYear, $includeDraft);

    $title = report_rpe_fc_01_resolved_title($reportRow);

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
    require APP_ROOT . '/views/reports/rpe_fc_01_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
    ];
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';

/**
 * RPAFC-02 — Calendari previst.
 * La lògica de dates (session_date dins de l’any) segueix el mateix criteri que
 * training_actions_calendar_year_data() al tauler, amb filtres addicionals d’informe.
 */

/**
 * @return array{d1:string,d2:string}
 */
function report_rpa_fc_02_year_bounds(int $year): array
{
    return [
        'd1' => sprintf('%04d-01-01', $year),
        'd2' => sprintf('%04d-12-31', $year),
    ];
}

/**
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_02_fetch_actions(PDO $db, int $programYear, bool $includeDraft, string $trainingTypeFilter): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $parts = report_training_type_subprogram_join_named($trainingTypeFilter);
    $joinSub = $parts['join'];

    $sql = "SELECT
                ta.id,
                ta.program_year,
                ta.action_number,
                ta.name,
                COALESCE(NULLIF(TRIM(sp.training_type), ''), '') AS training_type_raw
            FROM training_actions ta
            $joinSub
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY ta.action_number ASC, ta.id ASC";

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    foreach ($parts['named_binds'] as $pname => $pval) {
        $st->bindValue(':' . $pname, $pval, PDO::PARAM_STR);
    }
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param list<int> $actionIds
 * @return array<int, list<string>> session_date Y-m-d per acció
 */
function report_rpa_fc_02_fetch_dates_by_action(PDO $db, array $actionIds, int $year, bool $includeDraft, string $trainingTypeFilter): array
{
    if ($actionIds === [] || $year < 1990 || $year > 2100) {
        return [];
    }

    $bounds = report_rpa_fc_02_year_bounds($year);
    $placeholders = implode(',', array_fill(0, count($actionIds), '?'));

    $parts = report_training_type_subprogram_join_positional($trainingTypeFilter);
    $joinSub = $parts['join'];

    $sql = "SELECT tad.training_action_id, tad.session_date
            FROM training_action_dates tad
            INNER JOIN training_actions ta ON ta.id = tad.training_action_id
            $joinSub
            WHERE tad.training_action_id IN ($placeholders)
              AND ta.program_year = ?
              AND (? = 1 OR ta.is_active = 1)
              AND tad.session_date >= ? AND tad.session_date <= ?
            ORDER BY tad.training_action_id ASC, tad.session_date ASC, tad.sort_order ASC, tad.id ASC";

    $st = $db->prepare($sql);
    $params = $parts['leading_positional'];
    $params = array_merge($params, array_map(static fn (int $id): int => $id, $actionIds));
    $params[] = $year;
    $params[] = $includeDraft ? 1 : 0;
    $params[] = $bounds['d1'];
    $params[] = $bounds['d2'];
    $st->execute($params);

    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $aid = (int) ($row['training_action_id'] ?? 0);
        $sd = (string) ($row['session_date'] ?? '');
        if ($aid < 1 || $sd === '') {
            continue;
        }
        if (!isset($out[$aid])) {
            $out[$aid] = [];
        }
        if (!in_array($sd, $out[$aid], true)) {
            $out[$aid][] = $sd;
        }
    }

    return $out;
}

/**
 * @param list<array<string,mixed>> $actions
 * @param array<int, list<string>> $datesByAction
 * @return array{
 *   rows: list<array{
 *     id:int,
 *     display_code:string,
 *     name:string,
 *     training_type_label:string,
 *     dates_used:list<string>,
 *     months: array<int,bool>
 *   }>,
 *   calendar_day_marks: array<string,bool>
 * }
 */
function report_rpa_fc_02_build_rows(array $actions, array $datesByAction, bool $initialDateOnly, int $year): array
{
    $rows = [];
    $calendarDayMarks = [];

    foreach ($actions as $a) {
        $id = (int) ($a['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $py = (int) ($a['program_year'] ?? 0);
        $an = (int) ($a['action_number'] ?? 0);
        $name = (string) ($a['name'] ?? '');
        $ttRaw = (string) ($a['training_type_raw'] ?? '');
        $typeLabel = $ttRaw !== '' && report_training_type_is_allowed($ttRaw)
            ? report_training_type_label_ca($ttRaw)
            : '—';
        $rawDates = $datesByAction[$id] ?? [];
        if ($rawDates !== []) {
            sort($rawDates, SORT_STRING);
        }

        $datesUsed = $rawDates;
        if ($initialDateOnly && $rawDates !== []) {
            $datesUsed = [$rawDates[0]];
        }

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = false;
        }
        foreach ($datesUsed as $d) {
            if (strlen($d) >= 7 && (int) substr($d, 0, 4) === $year) {
                $mo = (int) substr($d, 5, 2);
                if ($mo >= 1 && $mo <= 12) {
                    $months[$mo] = true;
                }
            }
            $calendarDayMarks[$d] = true;
        }

        $rows[] = [
            'id' => $id,
            'display_code' => training_actions_format_display_code($py, $an),
            'name' => $name,
            'training_type_label' => $typeLabel,
            'dates_used' => $datesUsed,
            'months' => $months,
        ];
    }

    return [
        'rows' => $rows,
        'calendar_day_marks' => $calendarDayMarks,
    ];
}

/**
 * Genera setmanes per a un mes (dilluns com a primer dia). Cel·les null = buit.
 *
 * @return list<list<?int>>
 */
function report_rpa_fc_02_month_weeks(int $year, int $month): array
{
    if ($month < 1 || $month > 12) {
        return [];
    }
    $ts = strtotime(sprintf('%04d-%02d-01', $year, $month));
    if ($ts === false) {
        return [];
    }
    $firstDow = (int) date('N', $ts);
    $daysInMonth = (int) date('t', $ts);
    $leading = $firstDow - 1;

    $cells = [];
    for ($i = 0; $i < $leading; $i++) {
        $cells[] = null;
    }
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $cells[] = $d;
    }
    while (count($cells) % 7 !== 0) {
        $cells[] = null;
    }

    $weeks = [];
    $chunk = array_chunk($cells, 7);
    foreach ($chunk as $w) {
        $weeks[] = $w;
    }

    return $weeks;
}

/**
 * Resum mensual: nombre de dies amb almenys una marca (per a Excel).
 *
 * @param array<string,bool> $calendarDayMarks
 * @return array<int,int> mes 1..12 => comptador
 */
function report_rpa_fc_02_month_day_counts(int $year, array $calendarDayMarks): array
{
    $counts = [];
    for ($m = 1; $m <= 12; $m++) {
        $counts[$m] = 0;
    }
    foreach (array_keys($calendarDayMarks) as $ymd) {
        if (strlen($ymd) < 7) {
            continue;
        }
        if ((int) substr($ymd, 0, 4) !== $year) {
            continue;
        }
        $mo = (int) substr($ymd, 5, 2);
        if ($mo >= 1 && $mo <= 12) {
            $counts[$mo]++;
        }
    }

    return $counts;
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_02_render(
    PDO $db,
    array $reportRow,
    int $programYear,
    bool $includeDraft,
    string $trainingTypeFilter,
    bool $initialDateOnly
): void {
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $actions = report_rpa_fc_02_fetch_actions($db, $programYear, $includeDraft, $trainingTypeFilter);
    $ids = array_map(static fn (array $a): int => (int) ($a['id'] ?? 0), $actions);
    $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    $datesByAction = report_rpa_fc_02_fetch_dates_by_action($db, $ids, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_02_build_rows($actions, $datesByAction, $initialDateOnly, $programYear);
    $rows = $built['rows'];
    $calendarDayMarks = $built['calendar_day_marks'];

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

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
    require APP_ROOT . '/views/reports/rpa_fc_02_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
        'initial_date_only' => $initialDateOnly ? '1' : '0',
    ];
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $excelQ['training_type'] = $trainingTypeFilter;
    }
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

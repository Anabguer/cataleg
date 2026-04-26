<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';

/**
 * RPAFC-04 — Preinscripcions a accions formatives.
 *
 * Filtre de tipus: mateix criteri que RPAFC-01/02/03 (training_subprograms.training_type via join comú).
 * Persones: training_action_attendees.pre_registration_flag = 1 (RPAFC-01).
 *
 * Hores previstes (per acció) = planned_places × planned_duration_hours.
 * Si places = 0 o durada NULL / ≤ 0: «—» i no entra a la suma d’hores previstes.
 *
 * Ordenació dins de cada subprograma: ta.name (descripció), ascendent.
 */

/** Nota al peu (HTML i Excel): abast del filtre + aclariment de preinscripcions. */
function report_rpa_fc_04_footnote_ca(string $trainingTypeFilter): string
{
    return report_training_type_scope_legend_ca($trainingTypeFilter)
        . ' Es llisten les persones preinscrites per acció.';
}

/**
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_04_fetch_actions(PDO $db, int $programYear, bool $includeDraft, string $trainingTypeFilter): array
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
                ta.planned_places,
                ta.planned_duration_hours,
                ta.subprogram_id,
                sp.subprogram_code AS subprogram_code,
                sp.name AS subprogram_name
            FROM training_actions ta
            $joinSub
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY
                COALESCE(sp.subprogram_code, 999999) ASC,
                sp.name ASC,
                ta.name ASC,
                ta.id ASC";

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
 * @param array<string,mixed> $r
 */
function report_rpa_fc_04_subprogram_key(array $r): string
{
    return 's_' . (int) ($r['subprogram_id'] ?? 0);
}

/**
 * Durada utilitzable per a la columna «Durada prevista» i per a productes.
 */
function report_rpa_fc_04_duration_hours_for_display(?float $hours): ?float
{
    if ($hours === null) {
        return null;
    }
    if ($hours <= 0.0) {
        return null;
    }

    return $hours;
}

/**
 * Hores previstes = planned_places × planned_duration_hours (només si places > 0 i durada vàlida > 0).
 */
function report_rpa_fc_04_hours_previstes(int $plannedPlaces, ?float $durationHours): ?float
{
    if ($plannedPlaces <= 0) {
        return null;
    }
    $d = report_rpa_fc_04_duration_hours_for_display($durationHours);

    return $d === null ? null : $plannedPlaces * $d;
}

/**
 * @param array<string,mixed> $r
 * @param array<int, list<string>> $preByAction
 * @return array<string,mixed>
 */
function report_rpa_fc_04_enrich_action(array $r, array $preByAction): array
{
    $aid = (int) ($r['id'] ?? 0);
    $names = $preByAction[$aid] ?? [];
    $preCount = count($names);
    $pd = $r['planned_duration_hours'] ?? null;
    $durRaw = ($pd !== null && $pd !== '') ? (float) $pd : null;
    $durF = report_rpa_fc_04_duration_hours_for_display($durRaw);
    $places = (int) ($r['planned_places'] ?? 0);
    $hoursPrevistes = report_rpa_fc_04_hours_previstes($places, $durRaw);

    return array_merge($r, [
        'pre_count' => $preCount,
        'pre_names' => $names,
        'duration_hours' => $durF,
        'hours_previstes' => $hoursPrevistes,
    ]);
}

/**
 * @param list<array<string,mixed>> $rows
 * @param array<int, list<string>> $preByAction
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_04_build_blocks(array $rows, array $preByAction): array
{
    $blocks = [];
    $curKey = null;
    /** @var array<string,mixed>|null $block */
    $block = null;

    foreach ($rows as $r) {
        $sk = report_rpa_fc_04_subprogram_key($r);
        if ($curKey !== $sk) {
            if ($block !== null) {
                $blocks[] = $block;
            }
            $curKey = $sk;
            $code = $r['subprogram_code'] ?? null;
            $name = trim((string) ($r['subprogram_name'] ?? ''));
            if ($name === '') {
                $name = 'Sense subprograma';
            }
            $codeDisp = $code !== null && $code !== ''
                ? format_padded_code((int) $code, 3)
                : '—';
            $block = [
                'subprogram_id' => $r['subprogram_id'],
                'code_display' => $codeDisp,
                'name' => $name,
                'actions' => [],
            ];
        }
        if ($block !== null) {
            $block['actions'][] = report_rpa_fc_04_enrich_action($r, $preByAction);
        }
    }
    if ($block !== null) {
        $blocks[] = $block;
    }

    foreach ($blocks as &$b) {
        $acts = $b['actions'] ?? [];
        usort($acts, static function (array $a, array $b): int {
            return strcasecmp(trim((string) ($a['name'] ?? '')), trim((string) ($b['name'] ?? '')));
        });
        $b['actions'] = $acts;
    }
    unset($b);

    return $blocks;
}

/**
 * @param list<array<string,mixed>> $actions Accions enriquides (pre_count, duration_hours, hours_previstes)
 * @return array{
 *   places_sum:int,
 *   pre_sum:int,
 *   hours_planned_sum:float,
 *   hours_previstes_sum:float
 * }
 */
function report_rpa_fc_04_totals_for_actions(array $actions): array
{
    $placesSum = 0;
    $preSum = 0;
    $hoursPlanned = 0.0;
    $hoursPrevistes = 0.0;

    foreach ($actions as $a) {
        $placesSum += (int) ($a['planned_places'] ?? 0);
        $preSum += (int) ($a['pre_count'] ?? 0);
        $d = $a['duration_hours'] ?? null;
        if ($d !== null) {
            $hoursPlanned += (float) $d;
        }
        $hp = $a['hours_previstes'] ?? null;
        if ($hp !== null) {
            $hoursPrevistes += (float) $hp;
        }
    }

    return [
        'places_sum' => $placesSum,
        'pre_sum' => $preSum,
        'hours_planned_sum' => $hoursPlanned,
        'hours_previstes_sum' => $hoursPrevistes,
    ];
}

/**
 * @param list<array<string,mixed>> $blocks
 * @return array{
 *   places_sum:int,
 *   pre_sum:int,
 *   hours_planned_sum:float,
 *   hours_previstes_sum:float
 * }
 */
function report_rpa_fc_04_totals_global(array $blocks): array
{
    $all = [];
    foreach ($blocks as $b) {
        foreach ($b['actions'] as $a) {
            $all[] = $a;
        }
    }

    return report_rpa_fc_04_totals_for_actions($all);
}

/**
 * Promig d’hores per plaça preinscrita: Σ hores previstes / Σ places previstes.
 *
 * @param array{hours_previstes_sum:float,places_sum:int} $t
 */
function report_rpa_fc_04_avg_hours_per_place(array $t): ?float
{
    $places = (int) ($t['places_sum'] ?? 0);
    if ($places < 1) {
        return null;
    }

    return (float) $t['hours_previstes_sum'] / (float) $places;
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_04_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $rows = report_rpa_fc_04_fetch_actions($db, $programYear, $includeDraft, $trainingTypeFilter);
    $ids = [];
    foreach ($rows as $r) {
        $ids[] = (int) ($r['id'] ?? 0);
    }
    $preByAction = $ids !== [] ? report_rpa_fc_01_fetch_pre_registered_by_action($db, $ids) : [];
    $blocks = report_rpa_fc_04_build_blocks($rows, $preByAction);
    $globalTotals = report_rpa_fc_04_totals_global($blocks);
    $avgHoursPerPlace = report_rpa_fc_04_avg_hours_per_place($globalTotals);

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
    require APP_ROOT . '/views/reports/rpa_fc_04_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
    ];
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $excelQ['training_type'] = $trainingTypeFilter;
    }
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

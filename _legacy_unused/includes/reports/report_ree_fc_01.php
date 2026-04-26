<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';

/**
 * REEFC-01 — Estat Execució Formació.
 *
 * Supòsits de model (training_actions / training_action_attendees):
 * - «Durada real» → actual_duration_hours (> 0 vàlida per a càlculs).
 * - «Durada prevista» → planned_duration_hours (> 0 vàlida per al %).
 * - Inscrits (Ins.) → COUNT(*) amb registration_flag = 1.
 * - Assistents (Ass.) → COUNT(*) amb attendance_flag = 1.
 * - Preinscrits → COUNT(*) amb pre_registration_flag = 1 (denominador del % d’execució).
 *
 * Hores (fila): Ass × DuradaReal; si Ass = 0 o durada no vàlida → «—» (no entra a la suma d’hores).
 *
 * Durada promig global: Σ(actual_duration × Ass) / Σ(Ass) només sobre files amb durada real vàlida i Ass > 0.
 * Si Σ(Ass) en aquest subconjunt = 0 → «—».
 *
 * Percentatge execució: mateix numerador / Σ(planned_duration × preinscrits) amb planned vàlid i pre > 0.
 * Si denominador = 0 → «—».
 *
 * Ordenació dins de cada àrea: per codi d’acció (`action_number` ascendent; el codi mostrat és any + número, ex. 2025.001).
 */

/** Nota al peu (HTML i Excel): abast del filtre de tipus. */
function report_ree_fc_01_footnote_ca(string $trainingTypeFilter): string
{
    return report_training_type_scope_legend_ca($trainingTypeFilter);
}

/**
 * @return list<array<string,mixed>>
 */
function report_ree_fc_01_fetch_rows(PDO $db, int $programYear, bool $includeDraft, string $trainingTypeFilter): array
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
                ta.execution_status,
                ta.planned_duration_hours,
                ta.actual_duration_hours,
                ta.subprogram_id,
                ta.knowledge_area_id,
                sp.subprogram_code AS subprogram_code,
                sp.name AS subprogram_name,
                ka.knowledge_area_code AS knowledge_area_code,
                ka.name AS knowledge_area_name,
                (SELECT COUNT(*) FROM training_action_attendees x
                 WHERE x.training_action_id = ta.id AND x.registration_flag = 1) AS ins_count,
                (SELECT COUNT(*) FROM training_action_attendees x
                 WHERE x.training_action_id = ta.id AND x.attendance_flag = 1) AS ass_count,
                (SELECT COUNT(*) FROM training_action_attendees x
                 WHERE x.training_action_id = ta.id AND x.pre_registration_flag = 1) AS pre_count
            FROM training_actions ta
            $joinSub
            LEFT JOIN knowledge_areas ka ON ka.id = ta.knowledge_area_id
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY
                COALESCE(sp.subprogram_code, 999999) ASC,
                sp.name ASC,
                COALESCE(ka.knowledge_area_code, 999999) ASC,
                ka.name ASC,
                ta.action_number ASC,
                ta.id ASC";

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    foreach ($parts['named_binds'] as $pname => $pval) {
        $st->bindValue(':' . $pname, $pval, PDO::PARAM_STR);
    }
    $st->execute();

    $raw = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($raw as $r) {
        $out[] = report_ree_fc_01_enrich_row($r);
    }

    return $out;
}

/**
 * Durada en hores vàlida per a productes i sumes (> 0).
 */
function report_ree_fc_01_positive_hours(?string $raw): ?float
{
    if ($raw === null || $raw === '') {
        return null;
    }
    $f = (float) $raw;

    return $f > 0.0 ? $f : null;
}

/**
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function report_ree_fc_01_enrich_row(array $r): array
{
    $ins = (int) ($r['ins_count'] ?? 0);
    $ass = (int) ($r['ass_count'] ?? 0);
    $pre = (int) ($r['pre_count'] ?? 0);
    $actualF = report_ree_fc_01_positive_hours(isset($r['actual_duration_hours']) ? (string) $r['actual_duration_hours'] : null);
    $plannedF = report_ree_fc_01_positive_hours(isset($r['planned_duration_hours']) ? (string) $r['planned_duration_hours'] : null);

    $hoursExec = null;
    if ($ass > 0 && $actualF !== null) {
        $hoursExec = $actualF * (float) $ass;
    }

    return array_merge($r, [
        'ins_count' => $ins,
        'ass_count' => $ass,
        'pre_count' => $pre,
        'actual_duration_hours_f' => $actualF,
        'planned_duration_hours_f' => $plannedF,
        'hours_exec' => $hoursExec,
    ]);
}

/**
 * @param list<array<string,mixed>> $actions Accions enriquides (report_ree_fc_01_enrich_row)
 * @return array{
 *   ins_sum:int,
 *   ass_sum:int,
 *   hours_sum:float,
 *   sum_dur_times_ass:float,
 *   sum_ass_for_avg:float,
 *   sum_planned_times_pre:float
 * }
 */
function report_ree_fc_01_metrics_for_actions(array $actions): array
{
    $insSum = 0;
    $assSum = 0;
    $hoursSum = 0.0;
    $sumDurAss = 0.0;
    $sumAssForAvg = 0.0;
    $sumPlannedPre = 0.0;

    foreach ($actions as $a) {
        $insSum += (int) ($a['ins_count'] ?? 0);
        $assSum += (int) ($a['ass_count'] ?? 0);
        $h = $a['hours_exec'] ?? null;
        if ($h !== null) {
            $hoursSum += (float) $h;
        }
        $dur = $a['actual_duration_hours_f'] ?? null;
        $ass = (int) ($a['ass_count'] ?? 0);
        if ($dur !== null && $ass > 0) {
            $sumDurAss += $dur * (float) $ass;
            $sumAssForAvg += (float) $ass;
        }
        $pd = $a['planned_duration_hours_f'] ?? null;
        $pre = (int) ($a['pre_count'] ?? 0);
        if ($pd !== null && $pre > 0) {
            $sumPlannedPre += $pd * (float) $pre;
        }
    }

    return [
        'ins_sum' => $insSum,
        'ass_sum' => $assSum,
        'hours_sum' => $hoursSum,
        'sum_dur_times_ass' => $sumDurAss,
        'sum_ass_for_avg' => $sumAssForAvg,
        'sum_planned_times_pre' => $sumPlannedPre,
    ];
}

/**
 * @param array<string,mixed> $a
 * @param array<string,mixed> $b
 * @return array<string,mixed>
 */
function report_ree_fc_01_merge_metrics(array $a, array $b): array
{
    return [
        'ins_sum' => (int) ($a['ins_sum'] ?? 0) + (int) ($b['ins_sum'] ?? 0),
        'ass_sum' => (int) ($a['ass_sum'] ?? 0) + (int) ($b['ass_sum'] ?? 0),
        'hours_sum' => (float) ($a['hours_sum'] ?? 0.0) + (float) ($b['hours_sum'] ?? 0.0),
        'sum_dur_times_ass' => (float) ($a['sum_dur_times_ass'] ?? 0.0) + (float) ($b['sum_dur_times_ass'] ?? 0.0),
        'sum_ass_for_avg' => (float) ($a['sum_ass_for_avg'] ?? 0.0) + (float) ($b['sum_ass_for_avg'] ?? 0.0),
        'sum_planned_times_pre' => (float) ($a['sum_planned_times_pre'] ?? 0.0) + (float) ($b['sum_planned_times_pre'] ?? 0.0),
    ];
}

/**
 * @param list<array<string,mixed>> $blocks
 * @return list<array<string,mixed>>
 */
function report_ree_fc_01_blocks_with_rollups(array $blocks): array
{
    foreach ($blocks as &$sub) {
        $subRoll = [
            'ins_sum' => 0,
            'ass_sum' => 0,
            'hours_sum' => 0.0,
            'sum_dur_times_ass' => 0.0,
            'sum_ass_for_avg' => 0.0,
            'sum_planned_times_pre' => 0.0,
        ];
        foreach ($sub['areas'] ?? [] as &$area) {
            $acts = $area['actions'] ?? [];
            $area['rollup'] = report_ree_fc_01_metrics_for_actions($acts);
            $subRoll = report_ree_fc_01_merge_metrics($subRoll, $area['rollup']);
        }
        unset($area);
        $sub['rollup'] = $subRoll;
    }
    unset($sub);

    return $blocks;
}

/**
 * @param list<array<string,mixed>> $blocks Blocs subprograma → areas → actions
 */
function report_ree_fc_01_metrics_global(array $blocks): array
{
    $all = [];
    foreach ($blocks as $b) {
        foreach ($b['areas'] ?? [] as $ar) {
            foreach ($ar['actions'] ?? [] as $act) {
                $all[] = $act;
            }
        }
    }

    return report_ree_fc_01_metrics_for_actions($all);
}

/**
 * @return array{durada_promig:?float, percent_execucio:?float}
 */
function report_ree_fc_01_final_kpis(array $globalMetrics): array
{
    $sumAss = (float) ($globalMetrics['sum_ass_for_avg'] ?? 0.0);
    $sumDurAss = (float) ($globalMetrics['sum_dur_times_ass'] ?? 0.0);
    $denP = (float) ($globalMetrics['sum_planned_times_pre'] ?? 0.0);

    $duradaPromig = $sumAss > 0.0 ? ($sumDurAss / $sumAss) : null;
    $percent = $denP > 0.0 ? ($sumDurAss / $denP) : null;

    return [
        'durada_promig' => $duradaPromig,
        'percent_execucio' => $percent,
    ];
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_ree_fc_01_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $rows = report_ree_fc_01_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_01_build_blocks($rows);
    $blocks = report_ree_fc_01_blocks_with_rollups($built['blocks']);
    $globalMetrics = report_ree_fc_01_metrics_global($blocks);
    $finalKpis = report_ree_fc_01_final_kpis($globalMetrics);

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
    require APP_ROOT . '/views/reports/ree_fc_01_body.php';
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

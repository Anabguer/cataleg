<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';

/**
 * Import «tercer» (no municipal): no hi ha columna pròpia a BD.
 * Es calcula només quan hi ha cost total previst: planned_total_cost − COALESCE(planned_municipal_cost, 0).
 *
 * @return float|null null si no hi ha cost total previst
 */
function report_rpa_fc_03_third_party_amount(?float $plannedTotalCost, ?float $plannedMunicipalCost): ?float
{
    if ($plannedTotalCost === null) {
        return null;
    }

    return $plannedTotalCost - (float) ($plannedMunicipalCost ?? 0.0);
}

/**
 * @param array<string,mixed> $row Fila amb planned_total_cost, planned_municipal_cost (valors PDO)
 */
function report_rpa_fc_03_row_third_party(array $row): ?float
{
    $pt = $row['planned_total_cost'] ?? null;
    $pm = $row['planned_municipal_cost'] ?? null;
    $ptf = ($pt !== null && $pt !== '') ? (float) $pt : null;
    $pmf = ($pm !== null && $pm !== '') ? (float) $pm : null;

    return report_rpa_fc_03_third_party_amount($ptf, $pmf);
}

function report_rpa_fc_03_format_money(?float $v): string
{
    if ($v === null) {
        return '—';
    }

    return number_format($v, 2, ',', '.') . ' €';
}

/**
 * Dades per a RPAFC-03 — Resum econòmic del programa anual de formació.
 *
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_03_fetch_rows(PDO $db, int $programYear, bool $includeDraft, string $trainingTypeFilter): array
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
                ta.planned_total_cost,
                ta.planned_municipal_cost,
                ta.subprogram_id,
                ta.knowledge_area_id,
                sp.subprogram_code AS subprogram_code,
                sp.name AS subprogram_name,
                ka.knowledge_area_code AS knowledge_area_code,
                ka.name AS knowledge_area_name,
                tf.name AS funding_name
            FROM training_actions ta
            $joinSub
            LEFT JOIN knowledge_areas ka ON ka.id = ta.knowledge_area_id
            LEFT JOIN training_funding tf ON tf.id = ta.funding_id
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY
                COALESCE(sp.subprogram_code, 999999) ASC,
                sp.name ASC,
                COALESCE(ka.knowledge_area_code, 999999) ASC,
                ka.name ASC,
                ta.action_number ASC";

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
 * Resum econòmic i de places/hores sobre una llista d’accions.
 *
 * @param list<array<string,mixed>> $actions
 * @return array{
 *   action_count:int,
 *   places_sum:int,
 *   hours_sum:float,
 *   total_cost_sum:?float,
 *   municipal_cost_sum:?float,
 *   third_party_sum:?float
 * }
 */
function report_rpa_fc_03_economic_summary(array $actions): array
{
    $actionCount = count($actions);
    $placesSum = 0;
    $hoursSum = 0.0;
    $totalCostSum = 0.0;
    $hasTotalCost = false;
    $municipalCostSum = 0.0;
    $hasMunicipal = false;
    $thirdSum = 0.0;
    $hasThird = false;

    foreach ($actions as $a) {
        $placesSum += (int) ($a['planned_places'] ?? 0);
        $pd = $a['planned_duration_hours'] ?? null;
        if ($pd !== null && $pd !== '') {
            $hoursSum += (float) $pd;
        }

        $pt = $a['planned_total_cost'] ?? null;
        if ($pt !== null && $pt !== '') {
            $totalCostSum += (float) $pt;
            $hasTotalCost = true;
        }

        $pm = $a['planned_municipal_cost'] ?? null;
        if ($pm !== null && $pm !== '') {
            $municipalCostSum += (float) $pm;
            $hasMunicipal = true;
        }

        $third = report_rpa_fc_03_row_third_party($a);
        if ($third !== null) {
            $thirdSum += $third;
            $hasThird = true;
        }
    }

    return [
        'action_count' => $actionCount,
        'places_sum' => $placesSum,
        'hours_sum' => $hoursSum,
        'total_cost_sum' => $hasTotalCost ? $totalCostSum : null,
        'municipal_cost_sum' => $hasMunicipal ? $municipalCostSum : null,
        'third_party_sum' => $hasThird ? $thirdSum : null,
    ];
}

/**
 * @param list<array<string,mixed>> $rows
 * @return array{blocks: list<array<string,mixed>>, total_summary: array<string,mixed>}
 */
function report_rpa_fc_03_build_blocks(array $rows): array
{
    $blocks = [];
    $curSubKey = null;
    $curAreaKey = null;
    /** @var array<string,mixed>|null $subBlock */
    $subBlock = null;
    /** @var array<string,mixed>|null $areaBlock */
    $areaBlock = null;

    $flushArea = static function () use (&$subBlock, &$areaBlock): void {
        if ($subBlock !== null && $areaBlock !== null) {
            $subBlock['areas'][] = $areaBlock;
        }
        $areaBlock = null;
    };

    $flushSub = static function () use (&$blocks, &$subBlock, &$areaBlock, $flushArea): void {
        $flushArea();
        if ($subBlock !== null) {
            $blocks[] = $subBlock;
        }
        $subBlock = null;
    };

    foreach ($rows as $r) {
        $sk = report_rpa_fc_03_subprogram_key($r);
        $ak = report_rpa_fc_03_area_key($r);

        if ($curSubKey !== $sk) {
            $flushSub();
            $curSubKey = $sk;
            $curAreaKey = null;
            $subBlock = report_rpa_fc_03_new_subprogram_block($r);
        }

        if ($curAreaKey !== $ak) {
            $flushArea();
            $curAreaKey = $ak;
            $areaBlock = report_rpa_fc_03_new_area_block($r);
        }

        if ($areaBlock !== null) {
            $areaBlock['actions'][] = $r;
        }
    }

    $flushSub();

    $totalSummary = report_rpa_fc_03_economic_summary($rows);

    return ['blocks' => $blocks, 'total_summary' => $totalSummary];
}

/**
 * @param array<string,mixed> $r
 */
function report_rpa_fc_03_subprogram_key(array $r): string
{
    if (!isset($r['subprogram_id']) || $r['subprogram_id'] === null || $r['subprogram_id'] === '') {
        return 's_none';
    }

    return 's_' . (int) $r['subprogram_id'];
}

/**
 * @param array<string,mixed> $r
 */
function report_rpa_fc_03_area_key(array $r): string
{
    if (!isset($r['knowledge_area_id']) || $r['knowledge_area_id'] === null || $r['knowledge_area_id'] === '') {
        return 'a_none';
    }

    return 'a_' . (int) $r['knowledge_area_id'];
}

/**
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function report_rpa_fc_03_new_subprogram_block(array $r): array
{
    $code = $r['subprogram_code'] ?? null;
    $name = trim((string) ($r['subprogram_name'] ?? ''));
    if ($name === '') {
        $name = 'Sense subprograma';
    }
    $codeDisp = $code !== null && $code !== ''
        ? format_padded_code((int) $code, 3)
        : '—';

    return [
        'subprogram_id' => $r['subprogram_id'],
        'code_display' => $codeDisp,
        'name' => $name,
        'areas' => [],
    ];
}

/**
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function report_rpa_fc_03_new_area_block(array $r): array
{
    $code = $r['knowledge_area_code'] ?? null;
    $name = trim((string) ($r['knowledge_area_name'] ?? ''));
    if ($name === '') {
        $name = 'Sense àrea de coneixement';
    }
    $codeDisp = $code !== null && $code !== ''
        ? format_padded_code((int) $code, 3)
        : '—';

    return [
        'knowledge_area_id' => $r['knowledge_area_id'],
        'code_display' => $codeDisp,
        'name' => $name,
        'actions' => [],
    ];
}

/**
 * Accions agrupades sota un subprograma (tots els àmbits).
 *
 * @param array<string,mixed> $subBlock
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_03_subprogram_all_actions(array $subBlock): array
{
    $out = [];
    foreach ($subBlock['areas'] ?? [] as $area) {
        foreach ($area['actions'] ?? [] as $act) {
            $out[] = $act;
        }
    }

    return $out;
}

/**
 * Una fila de totals alineada amb les columnes de l’informe (sense llistes ni targetes).
 * Ús: totals globals inicial / final (dues columnes de text: codi + etiqueta).
 *
 * @param array<string,mixed> $s Resultat de report_rpa_fc_03_economic_summary
 */
function report_rpa_fc_03_render_economic_total_row(array $s, string $rowClass, string $codeText, string $labelText): void
{
    ?>
    <tr class="<?= e($rowClass) ?>">
        <td class="report-table__code"><?= e($codeText) ?></td>
        <td class="report-table__total-label"><?= e($labelText) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours((float) ($s['hours_sum'] ?? 0))) ?></td>
        <td class="report-table__num"><?= e((string) (int) ($s['places_sum'] ?? 0)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['total_cost_sum'] ?? null)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['municipal_cost_sum'] ?? null)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['third_party_sum'] ?? null)) ?></td>
        <td class="report-table__total-fund">—</td>
    </tr>
    <?php
}

/**
 * Capçalera de subprograma o àrea amb totals a la mateixa fila (abans del detall).
 * Primera cel·la: colspan 2 amb el text identificatiu; després columnes numèriques.
 *
 * @param array<string,mixed> $s Resultat de report_rpa_fc_03_economic_summary
 */
function report_rpa_fc_03_render_heading_total_row(array $s, string $rowClass, string $headingText): void
{
    ?>
    <tr class="<?= e($rowClass) ?>">
        <td colspan="2" class="report-table__heading-total-label"><?= e($headingText) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours((float) ($s['hours_sum'] ?? 0))) ?></td>
        <td class="report-table__num"><?= e((string) (int) ($s['places_sum'] ?? 0)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['total_cost_sum'] ?? null)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['municipal_cost_sum'] ?? null)) ?></td>
        <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($s['third_party_sum'] ?? null)) ?></td>
        <td class="report-table__total-fund">—</td>
    </tr>
    <?php
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_03_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';

    $rows = report_rpa_fc_03_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_03_build_blocks($rows);
    $blocks = $built['blocks'];
    $totalSummary = $built['total_summary'];

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
    require APP_ROOT . '/views/reports/rpa_fc_03_body.php';
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

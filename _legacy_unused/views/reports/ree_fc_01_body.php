<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $blocks */
/** @var array<string,mixed> $globalMetrics */
/** @var array{durada_promig:?float, percent_execucio:?float} $finalKpis */

?>
<div class="report-ree-fc-01">
<?php if ($blocks === []): ?>
    <p class="report-empty">No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.</p>
<?php else: ?>
    <div class="report-table-wrap report-ree-fc-01__table-wrap">
        <table class="report-table report-ree-fc-01__table">
            <colgroup>
                <col class="report-ree-fc-01__col-tree">
                <col class="report-ree-fc-01__col-stat">
                <col class="report-ree-fc-01__col-num">
                <col class="report-ree-fc-01__col-num">
                <col class="report-ree-fc-01__col-num">
                <col class="report-ree-fc-01__col-num">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" scope="col" class="report-ree-fc-01__head-tree">
                        Programa / Àrea de coneixement / Acció formativa
                    </th>
                    <th colspan="5" scope="colgroup" class="report-ree-fc-01__head-group">Execució</th>
                </tr>
                <tr>
                    <th scope="col" class="report-ree-fc-01__head-stat">Estat</th>
                    <th scope="col" class="report-ree-fc-01__head-num">Ins.</th>
                    <th scope="col" class="report-ree-fc-01__head-num">Durada</th>
                    <th scope="col" class="report-ree-fc-01__head-num">Ass.</th>
                    <th scope="col" class="report-ree-fc-01__head-num">Hores</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $subIdx = 0;
                foreach ($blocks as $sub):
                    $subBannerClass = 'report-ree-fc-01__banner report-ree-fc-01__banner--subprogram';
                    if ($subIdx > 0) {
                        $subBannerClass .= ' report-ree-fc-01__banner--pagebreak';
                    }
                    ?>
                <tr class="<?= e($subBannerClass) ?>">
                    <td colspan="6" class="report-ree-fc-01__banner-cell">
                        <span class="report-ree-fc-01__banner-label">Subprograma</span>
                        <span class="report-ree-fc-01__banner-text"><?= e(trim((string) $sub['code_display'] . ' ' . (string) $sub['name'])) ?></span>
                    </td>
                </tr>
                    <?php foreach ($sub['areas'] ?? [] as $area): ?>
                        <?php
                        $areaActions = $area['actions'] ?? [];
                        /** @var array<string,mixed> $areaRoll */
                        $areaRoll = $area['rollup'] ?? report_ree_fc_01_metrics_for_actions($areaActions);
                        ?>
                <tr class="report-ree-fc-01__banner report-ree-fc-01__banner--area">
                    <td colspan="6" class="report-ree-fc-01__banner-cell report-ree-fc-01__banner-cell--area">
                        <span class="report-ree-fc-01__banner-label">Àrea de coneixement</span>
                        <span class="report-ree-fc-01__banner-text"><?= e(trim((string) $area['code_display'] . ' ' . (string) $area['name'])) ?></span>
                    </td>
                </tr>
                        <?php foreach ($areaActions as $act): ?>
                            <?php
                            $py = (int) $act['program_year'];
                            $an = (int) $act['action_number'];
                            $displayCode = training_actions_format_display_code($py, $an);
                            $estat = training_actions_normalize_execution_status(
                                isset($act['execution_status']) ? (string) $act['execution_status'] : ''
                            );
                            $estatDisp = $estat ?? '—';
                            $durReal = $act['actual_duration_hours_f'] ?? null;
                            $hExec = $act['hours_exec'] ?? null;
                            ?>
                <tr class="report-ree-fc-01__row-action">
                    <td class="report-ree-fc-01__tree">
                        <span class="report-ree-fc-01__code"><?= e($displayCode) ?></span>
                        <span class="report-ree-fc-01__name"><?= e((string) $act['name']) ?></span>
                    </td>
                    <td class="report-ree-fc-01__cell-stat"><?= e($estatDisp) ?></td>
                    <td class="report-ree-fc-01__num"><?= e((string) (int) ($act['ins_count'] ?? 0)) ?></td>
                    <td class="report-ree-fc-01__num"><?= e(report_rpa_fc_01_format_hours($durReal)) ?></td>
                    <td class="report-ree-fc-01__num"><?= e((string) (int) ($act['ass_count'] ?? 0)) ?></td>
                    <td class="report-ree-fc-01__num"><?= $hExec === null ? '—' : e(report_rpa_fc_01_format_hours((float) $hExec)) ?></td>
                </tr>
                        <?php endforeach; ?>
                <tr class="report-ree-fc-01__subtotal report-ree-fc-01__subtotal--area">
                    <th scope="row" class="report-ree-fc-01__subtotal-label">Subtotal àrea</th>
                    <td class="report-ree-fc-01__subtotal-dash">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($areaRoll['ins_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($areaRoll['ass_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num"><?php
                        $hs = (float) ($areaRoll['hours_sum'] ?? 0.0);
                    echo e($hs > 0.0 ? report_rpa_fc_01_format_hours($hs) : '—');
                    ?></td>
                </tr>
                    <?php endforeach; ?>
                    <?php
                    /** @var array<string,mixed> $subRoll */
                    $subRoll = $sub['rollup'] ?? report_ree_fc_01_metrics_for_actions([]);
                    $hsSub = (float) ($subRoll['hours_sum'] ?? 0.0);
                    ?>
                <tr class="report-ree-fc-01__subtotal report-ree-fc-01__subtotal--subprogram">
                    <th scope="row" class="report-ree-fc-01__subtotal-label">Subtotal subprograma</th>
                    <td class="report-ree-fc-01__subtotal-dash">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($subRoll['ins_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($subRoll['ass_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num"><?= $hsSub > 0.0 ? e(report_rpa_fc_01_format_hours($hsSub)) : '—' ?></td>
                </tr>
                    <?php
                    $subIdx++;
                endforeach;
                ?>
                <?php
                $hsG = (float) ($globalMetrics['hours_sum'] ?? 0.0);
                $pct = $finalKpis['percent_execucio'] ?? null;
                $dp = $finalKpis['durada_promig'] ?? null;
                $pctStr = $pct === null ? '—' : e(number_format((float) $pct * 100.0, 2, ',', '.') . ' %');
                $dpStr = $dp === null ? '—' : e(report_rpa_fc_01_format_hours((float) $dp));
                ?>
                <tr class="report-ree-fc-01__subtotal report-ree-fc-01__subtotal--global">
                    <th scope="row" class="report-ree-fc-01__subtotal-label">Totals generals</th>
                    <td class="report-ree-fc-01__subtotal-dash">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($globalMetrics['ins_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num">—</td>
                    <td class="report-ree-fc-01__num"><?= (int) ($globalMetrics['ass_sum'] ?? 0) ?></td>
                    <td class="report-ree-fc-01__num"><?= $hsG > 0.0 ? e(report_rpa_fc_01_format_hours($hsG)) : '—' ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="report-summary report-ree-fc-01__kpi">
        <ul class="report-summary__list report-ree-fc-01__kpi-list">
            <li>
                <span class="report-summary__label">Durada promig de la formació (Σ durada real × assistents / Σ assistents)</span>
                <span class="report-summary__value report-ree-fc-01__kpi-value"><?= $dpStr ?></span>
            </li>
            <li>
                <span class="report-summary__label">Percentatge d’execució d’hores</span>
                <span class="report-summary__value report-ree-fc-01__kpi-value"><?= $pctStr ?></span>
            </li>
        </ul>
    </div>
<?php endif; ?>
</div>

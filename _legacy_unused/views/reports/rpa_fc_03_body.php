<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $blocks */
/** @var array<string,mixed> $totalSummary */
?>

<?php if ($rows === []): ?>
    <p class="report-empty">No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.</p>
<?php else: ?>
    <div class="report-table-wrap report-table-wrap--rpa-fc-03">
        <table class="report-table report-table--economic report-table--rpa-fc-03">
            <thead>
                <tr class="report-table__cost-group-row">
                    <th colspan="4" class="report-table__cost-group-spacer"></th>
                    <th colspan="3" class="report-table__cost-group-label">Cost</th>
                    <th class="report-table__cost-group-spacer"></th>
                </tr>
                <tr class="report-table__header-row-main">
                    <th scope="col">Codi</th>
                    <th scope="col">Acció formativa</th>
                    <th scope="col" class="report-table__th-num">Hores</th>
                    <th scope="col" class="report-table__th-num">Places</th>
                    <th scope="col" class="report-table__th-num">Total</th>
                    <th scope="col" class="report-table__th-num">Ajuntament</th>
                    <th scope="col" class="report-table__th-num">Tercer</th>
                    <th scope="col">Finançament</th>
                </tr>
            </thead>
            <tbody>
                <?php
                report_rpa_fc_03_render_economic_total_row(
                    $totalSummary,
                    'report-total-row report-total-row--global-init',
                    '—',
                    'Totals globals (informe)'
                );

                foreach ($blocks as $sub) {
                    $subAll = report_rpa_fc_03_subprogram_all_actions($sub);
                    $subSummary = report_rpa_fc_03_economic_summary($subAll);
                    $subLine = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
                    report_rpa_fc_03_render_heading_total_row(
                        $subSummary,
                        'report-heading-total-row report-heading-total-row--subprogram',
                        $subLine
                    );

                    foreach ($sub['areas'] as $area) {
                        $areaActions = $area['actions'];
                        $areaSummary = report_rpa_fc_03_economic_summary($areaActions);
                        $areaLine = 'ÀREA DE CONEIXEMENT: ' . trim((string) $area['code_display'] . ' ' . (string) $area['name']);
                        report_rpa_fc_03_render_heading_total_row(
                            $areaSummary,
                            'report-heading-total-row report-heading-total-row--area',
                            $areaLine
                        );

                        foreach ($areaActions as $act) {
                            $py = (int) $act['program_year'];
                            $an = (int) $act['action_number'];
                            $displayCode = training_actions_format_display_code($py, $an);
                            $dur = $act['planned_duration_hours'] ?? null;
                            $durF = $dur !== null && $dur !== '' ? (float) $dur : null;
                            $third = report_rpa_fc_03_row_third_party($act);
                            $pt = $act['planned_total_cost'] ?? null;
                            $ptF = ($pt !== null && $pt !== '') ? (float) $pt : null;
                            $pm = $act['planned_municipal_cost'] ?? null;
                            $pmF = ($pm !== null && $pm !== '') ? (float) $pm : null;
                            $fund = trim((string) ($act['funding_name'] ?? ''));
                            $fundDisp = $fund === '' ? '—' : $fund;
                            ?>
                <tr class="report-table__row-main">
                    <td class="report-table__code"><?= e($displayCode) ?></td>
                    <td class="report-table__action-name"><?= e((string) $act['name']) ?></td>
                    <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours($durF)) ?></td>
                    <td class="report-table__num"><?= e((string) (int) ($act['planned_places'] ?? 0)) ?></td>
                    <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($ptF)) ?></td>
                    <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($pmF)) ?></td>
                    <td class="report-table__num"><?= e(report_rpa_fc_03_format_money($third)) ?></td>
                    <td class="report-table__long"><?= e($fundDisp) ?></td>
                </tr>
                            <?php
                        }
                    }
                }

                report_rpa_fc_03_render_economic_total_row(
                    $totalSummary,
                    'report-total-row report-total-row--global-final',
                    '—',
                    'Total general (informe)'
                );
                ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

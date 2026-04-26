<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $blocks */
/** @var array{
 *   places_sum:int,
 *   pre_sum:int,
 *   hours_planned_sum:float,
 *   hours_previstes_sum:float
 * } $globalTotals */
/** @var float|null $avgHoursPerPlace */

?>
<div class="report-rpa-fc-04">
<?php if ($blocks === []): ?>
    <p class="report-empty">No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.</p>
<?php else: ?>
    <?php foreach ($blocks as $sub): ?>
        <?php
        $subActions = $sub['actions'] ?? [];
        $subTotals = report_rpa_fc_04_totals_for_actions($subActions);
        ?>
        <section class="report-block report-block--subprogram report-rpa-fc-04__subprogram">
            <h2 class="report-heading report-heading--subprogram">
                <span class="report-heading__label">Subprograma</span>
                <span class="report-heading__text"><?= e(trim((string) $sub['code_display'] . ' ' . (string) $sub['name'])) ?></span>
            </h2>
            <div class="report-table-wrap">
                <table class="report-table report-rpa-fc-04__table">
                    <thead>
                        <tr>
                            <th scope="col" class="report-rpa-fc-04__col-accio">Acció formativa</th>
                            <th scope="col" class="report-rpa-fc-04__col-persones">Persones preinscrites</th>
                            <th scope="col" class="report-rpa-fc-04__col-num">Places previstes</th>
                            <th scope="col" class="report-rpa-fc-04__col-num">Durada prevista</th>
                            <th scope="col" class="report-rpa-fc-04__col-num">Hores previstes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subActions as $act): ?>
                            <?php
                            $py = (int) $act['program_year'];
                            $an = (int) $act['action_number'];
                            $displayCode = training_actions_format_display_code($py, $an);
                            $durF = $act['duration_hours'] ?? null;
                            $hp = $act['hours_previstes'] ?? null;
                            $names = $act['pre_names'] ?? [];
                            ?>
                            <tr class="report-rpa-fc-04__row-action">
                                <td class="report-rpa-fc-04__accio">
                                    <span class="report-rpa-fc-04__code"><?= e($displayCode) ?></span>
                                    <span class="report-rpa-fc-04__name"><?= e((string) $act['name']) ?></span>
                                </td>
                                <td class="report-rpa-fc-04__persones">—</td>
                                <td class="report-table__num"><?= e((string) (int) ($act['planned_places'] ?? 0)) ?></td>
                                <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours($durF)) ?></td>
                                <td class="report-table__num"><?= $hp === null ? '—' : e(report_rpa_fc_01_format_hours((float) $hp)) ?></td>
                            </tr>
                            <?php foreach ($names as $pname): ?>
                                <tr class="report-rpa-fc-04__row-person">
                                    <td class="report-rpa-fc-04__accio report-rpa-fc-04__accio--indent"></td>
                                    <td class="report-rpa-fc-04__persones report-rpa-fc-04__persones--name"><?= e($pname) ?></td>
                                    <td class="report-table__num">—</td>
                                    <td class="report-table__num">—</td>
                                    <td class="report-table__num">—</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="report-rpa-fc-04__subtotal">
                            <th scope="row" colspan="2" class="report-rpa-fc-04__subtotal-label">Subtotal subprograma</th>
                            <td class="report-table__num"><?= (int) $subTotals['places_sum'] ?></td>
                            <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours((float) $subTotals['hours_planned_sum'] > 0 ? $subTotals['hours_planned_sum'] : null)) ?></td>
                            <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours((float) $subTotals['hours_previstes_sum'] > 0 ? $subTotals['hours_previstes_sum'] : null)) ?></td>
                        </tr>
                        <tr class="report-rpa-fc-04__subtotal-persones">
                            <td colspan="5" class="report-rpa-fc-04__subtotal-persones-cell">
                                Persones preinscrites (subtotal): <?= (int) $subTotals['pre_sum'] ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="report-summary report-rpa-fc-04__totals">
        <div class="report-summary__title">Totals generals</div>
        <ul class="report-summary__list">
            <li>
                <span class="report-summary__label">Places previstes (suma)</span>
                <span class="report-summary__value"><?= (int) $globalTotals['places_sum'] ?></span>
            </li>
            <li>
                <span class="report-summary__label">Persones preinscrites (suma)</span>
                <span class="report-summary__value"><?= (int) $globalTotals['pre_sum'] ?></span>
            </li>
            <li>
                <span class="report-summary__label">Suma durades previstes (per acció)</span>
                <span class="report-summary__value"><?= e(report_rpa_fc_01_format_hours((float) $globalTotals['hours_planned_sum'] > 0 ? $globalTotals['hours_planned_sum'] : null)) ?></span>
            </li>
            <li>
                <span class="report-summary__label">Suma hores previstes (places × durada)</span>
                <span class="report-summary__value"><?= e(report_rpa_fc_01_format_hours((float) $globalTotals['hours_previstes_sum'] > 0 ? $globalTotals['hours_previstes_sum'] : null)) ?></span>
            </li>
            <li>
                <span class="report-summary__label">Promig d’hores per plaça preinscrita</span>
                <span class="report-summary__value"><?= $avgHoursPerPlace === null ? '—' : e(report_rpa_fc_01_format_hours($avgHoursPerPlace)) ?></span>
            </li>
        </ul>
    </div>
<?php endif; ?>
</div>

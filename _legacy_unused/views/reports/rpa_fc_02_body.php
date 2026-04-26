<?php
declare(strict_types=1);
/** @var string $title — no usat directament; la capçalera ja ve del layout */
/** @var int $programYear */
/** @var list<array{
 *   id:int,
 *   display_code:string,
 *   name:string,
 *   training_type_label:string,
 *   dates_used:list<string>,
 *   months: array<int,bool>
 * }> $rows */
/** @var array<string,bool> $calendarDayMarks */

$monthTitles = [
    1 => 'Gener', 2 => 'Febrer', 3 => 'Març', 4 => 'Abril',
    5 => 'Maig', 6 => 'Juny', 7 => 'Juliol', 8 => 'Agost',
    9 => 'Setembre', 10 => 'Octubre', 11 => 'Novembre', 12 => 'Desembre',
];
$wdShort = ['Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds', 'Dg'];
?>
<div class="report-rpa-fc-02">
    <section class="report-rpa-fc-02-calendar-section" aria-label="Calendari anual">
        <h2 class="report-rpa-fc-02-part-title">Calendari anual <?= (int) $programYear ?></h2>
        <div class="report-rpa-fc-02-months-grid">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <?php
                $weeks = report_rpa_fc_02_month_weeks($programYear, $m);
                ?>
                <div class="report-rpa-fc-02-month">
                    <div class="report-rpa-fc-02-month-title"><?= e($monthTitles[$m] ?? (string) $m) ?></div>
                    <table class="report-rpa-fc-02-month-cal">
                        <thead>
                            <tr>
                                <?php foreach ($wdShort as $w): ?>
                                    <th scope="col" class="report-rpa-fc-02-month-cal__wd"><?= e($w) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weeks as $week): ?>
                                <tr>
                                    <?php foreach ($week as $dayNum): ?>
                                        <?php
                                        if ($dayNum === null) {
                                            echo '<td class="report-rpa-fc-02-day report-rpa-fc-02-day--empty"></td>';
                                            continue;
                                        }
                                        $ymd = sprintf('%04d-%02d-%02d', $programYear, $m, $dayNum);
                                        $has = !empty($calendarDayMarks[$ymd]);
                                        $cls = 'report-rpa-fc-02-day' . ($has ? ' report-rpa-fc-02-day--has' : '');
                                        ?>
                                        <td class="<?= e($cls) ?>">
                                            <span class="report-rpa-fc-02-day-num"><?= (int) $dayNum ?></span>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <section class="report-rpa-fc-02-list-section" aria-label="Llistat per acció">
        <h2 class="report-rpa-fc-02-part-title">Accions formatives i mesos amb dates previstes</h2>
        <div class="report-rpa-fc-02-table-wrap">
            <table class="report-table report-rpa-fc-02-matrix">
                <thead>
                    <tr class="report-rpa-fc-02-matrix__group-row">
                        <th colspan="2" class="report-rpa-fc-02-matrix__group-spacer"></th>
                        <th colspan="12" class="report-rpa-fc-02-matrix__mes-group" scope="colgroup">MES</th>
                    </tr>
                    <tr class="report-rpa-fc-02-matrix__header-row">
                        <th scope="col" class="report-rpa-fc-02-matrix__accio">Acció formativa</th>
                        <th scope="col" class="report-rpa-fc-02-matrix__prog">Tipus de formació</th>
                        <?php for ($c = 1; $c <= 12; $c++): ?>
                            <th scope="col" class="report-rpa-fc-02-matrix__mes"><?= sprintf('%02d', $c) ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="14" class="report-table__empty">No hi ha accions amb els criteris seleccionats.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="report-rpa-fc-02-matrix__accio">
                                    <span class="report-rpa-fc-02-matrix__code"><?= e($row['display_code']) ?></span>
                                    <span class="report-rpa-fc-02-matrix__name"> <?= e($row['name']) ?></span>
                                </td>
                                <td class="report-rpa-fc-02-matrix__prog"><?= e((string) ($row['training_type_label'] ?? '—')) ?></td>
                                <?php for ($c = 1; $c <= 12; $c++): ?>
                                    <?php $on = !empty($row['months'][$c]); ?>
                                    <td class="report-rpa-fc-02-matrix__cell<?= $on ? ' report-rpa-fc-02-matrix__cell--on' : '' ?>"><?= $on ? '●' : '' ?></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

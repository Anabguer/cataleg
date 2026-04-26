<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $blocks */
/** @var array<string,mixed> $totalSummary */
/** @var bool $includePersonalData */
/** @var array<int, list<string>> $preRegisteredByAction */
?>

<?php if ($rows === []): ?>
    <p class="report-empty">No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.</p>
<?php else: ?>
    <?php foreach ($blocks as $sub): ?>
        <section class="report-block report-block--subprogram">
            <h2 class="report-heading report-heading--subprogram">
                <span class="report-heading__label">Subprograma</span>
                <span class="report-heading__text"><?= e((string) $sub['code_display'] . ' ' . (string) $sub['name']) ?></span>
            </h2>
            <?php
            $subContextLabel = trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
            foreach ($sub['areas'] as $area) {
                $areaActions = $area['actions'];
                $areaSummary = report_rpa_fc_01_summary($areaActions);
                $areaContextLabel = trim((string) $area['code_display'] . ' ' . (string) $area['name']);
                ?>
                <section class="report-block report-block--area">
                    <h3 class="report-heading report-heading--area">
                        <span class="report-heading__label">Àrea de coneixement</span>
                        <span class="report-heading__text"><?= e((string) $area['code_display'] . ' ' . (string) $area['name']) ?></span>
                    </h3>
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th scope="col">Codi</th>
                                    <th scope="col">Acció formativa</th>
                                    <th scope="col">Dates previstes</th>
                                    <th scope="col">Durada (h)</th>
                                    <th scope="col">Places</th>
                                    <th scope="col">Organitzador</th>
                                </tr>
                                <tr class="report-table__context-row">
                                    <td colspan="6" class="report-table__context-cell">
                                        <div class="report-continue-context">
                                            <div class="report-continue-context__line">
                                                <span class="report-continue-context__tag">SUBPROGRAMA</span>
                                                <span class="report-continue-context__val"><?= e($subContextLabel) ?></span>
                                            </div>
                                            <div class="report-continue-context__line">
                                                <span class="report-continue-context__tag">ÀREA</span>
                                                <span class="report-continue-context__val"><?= e($areaContextLabel) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($areaActions as $act) {
                                    $aid = (int) $act['id'];
                                    $py = (int) $act['program_year'];
                                    $an = (int) $act['action_number'];
                                    $displayCode = training_actions_format_display_code($py, $an);
                                    $orgCode = $act['organizer_code'] ?? null;
                                    $orgName = trim((string) ($act['organizer_name'] ?? ''));
                                    if ($orgName === '') {
                                        $orgLabel = '—';
                                    } else {
                                        $orgLabel = ($orgCode !== null && $orgCode !== '')
                                            ? format_padded_code((int) $orgCode, 3) . ' ' . $orgName
                                            : $orgName;
                                    }
                                    $dur = $act['planned_duration_hours'] ?? null;
                                    $durF = $dur !== null && $dur !== '' ? (float) $dur : null;
                                    $objText = trim((string) ($act['training_objectives'] ?? ''));
                                    $objDisplay = $objText === '' ? '—' : $objText;
                                    $destDisplay = trim((string) ($act['target_audience'] ?? '')) === '' ? '—' : (string) $act['target_audience'];

                                    $names = $includePersonalData && isset($preRegisteredByAction[$aid])
                                        ? $preRegisteredByAction[$aid]
                                        : [];
                                    $personesFmt = $names === []
                                        ? ['line' => '—', 'more' => 0]
                                        : report_rpa_fc_01_truncate_person_names($names, 12);
                                    ?>
                                    <tr class="report-table__row-main">
                                        <td class="report-table__code"><?= e($displayCode) ?></td>
                                        <td class="report-table__action-name"><?= e((string) $act['name']) ?></td>
                                        <td><?= e(report_rpa_fc_01_format_dates_cell($act)) ?></td>
                                        <td class="report-table__num"><?= e(report_rpa_fc_01_format_hours($durF)) ?></td>
                                        <td class="report-table__num"><?= e((string) (int) ($act['planned_places'] ?? 0)) ?></td>
                                        <td><?= e($orgLabel) ?></td>
                                    </tr>
                                    <tr class="report-table__row-dest">
                                        <td colspan="6" class="report-table__cell-extra">
                                            <span class="report-table__field-label">Destinataris:</span>
                                            <span class="report-table__field-text"><?= e($destDisplay) ?></span>
                                        </td>
                                    </tr>
                                    <tr class="report-table__row-objectives<?= $includePersonalData ? '' : ' report-table__row-objectives--action-end' ?>">
                                        <td colspan="6" class="report-table__cell-extra">
                                            <span class="report-table__field-label">Objectius formatius:</span>
                                            <span class="report-table__field-text"><?= e($objDisplay) ?></span>
                                        </td>
                                    </tr>
                                    <?php if ($includePersonalData) { ?>
                                        <tr class="report-table__row-persones report-table__row-objectives--action-end">
                                            <td colspan="6" class="report-table__cell-extra">
                                                <span class="report-table__field-label">Persones preinscrites:</span>
                                                <span class="report-table__field-text">
                                                    <?= e($personesFmt['line']) ?>
                                                    <?php if ($personesFmt['more'] > 0): ?>
                                                        <span class="report-table__field-more"> + <?= (int) $personesFmt['more'] ?> més</span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php report_rpa_fc_01_render_summary_block($areaSummary); ?>
                </section>
                <?php
            } ?>
        </section>
    <?php endforeach; ?>

    <section class="report-block report-block--total" aria-label="Resum global">
        <h2 class="report-heading report-heading--total">Resum de l’informe</h2>
        <?php report_rpa_fc_01_render_summary_block($totalSummary); ?>
    </section>
<?php endif; ?>

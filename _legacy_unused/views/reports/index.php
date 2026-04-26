<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $selectorReports */
/** @var int $programYear */
/** @var int $selectedReportId */
/** @var bool $includePersonalData */
/** @var bool $selectedReportIsRpaFc01 */
/** @var bool $selectedReportIsRpaFc03 */
/** @var bool $selectedReportIsRpaFc02 */
/** @var bool $selectedReportIsRpaFc04 */
/** @var bool $selectedReportIsReeFc01 */
/** @var bool $selectedReportIsRpeFc02 */
/** @var bool $selectedReportIsRpeFc04 */
/** @var bool $ambAssistentsRpefc02 */
/** @var bool $dadesInscritsRpefc04 */
/** @var string $trainingTypeFilter Valors interns: all | programmed | non_programmed | personal */
/** @var bool $initialDateOnly */
/** @var string $runMessage */

$pageHeader = page_header_with_escut([
    'title' => 'Informes',
    'subtitle' => 'Selecció d’informe i paràmetres d’execució',
    'back_url' => app_url('dashboard.php'),
    'back_label' => 'Tauler',
]);
require APP_ROOT . '/views/partials/page_header.php';
?>

<?php if ($runMessage !== ''): ?>
<div class="alert alert--info" role="status"><?= e($runMessage) ?></div>
<?php endif; ?>

<section class="form-card reports-page">
    <div class="form-card__header">
        <h2 class="form-card__title">Selector general d’informes</h2>
    </div>
    <div class="form-card__body">
        <form method="get" action="<?= e(app_url('reports.php')) ?>" class="form-grid">
            <input type="hidden" name="run" value="1">

            <div class="form-group form-grid__full reports-page__top-row">
                <div class="reports-page__top-row-year">
                    <label class="form-label" for="reports_program_year">Any del programa</label>
                    <div class="reports-year-toolbar">
                        <button type="button" class="btn btn--secondary btn--sm reports-year-btn" id="reports-year-prev" title="Any anterior" aria-label="Any anterior">←</button>
                        <input
                            class="form-input reports-year-input"
                            type="number"
                            min="1990"
                            max="2100"
                            step="1"
                            id="reports_program_year"
                            name="program_year"
                            value="<?= (int) $programYear ?>"
                            required
                            inputmode="numeric"
                            autocomplete="off"
                        >
                        <button type="button" class="btn btn--secondary btn--sm reports-year-btn" id="reports-year-next" title="Any següent" aria-label="Any següent">→</button>
                    </div>
                </div>
                <div class="form-actions reports-page__top-row-actions">
                    <button type="submit" class="btn btn--primary">Acceptar</button>
                    <a class="btn btn--secondary" href="<?= e(app_url('dashboard.php')) ?>">Cancel·lar</a>
                </div>
            </div>

            <div class="form-group form-grid__full">
                <span class="form-label" id="reports_report_list_label">Informe</span>
                <div class="reports-picker" role="group" aria-labelledby="reports_report_list_label">
                    <div class="reports-picker__legend">Informes disponibles</div>
                    <?php if ($selectorReports === []): ?>
                        <p class="reports-picker__empty">No hi ha informes configurats al selector general. Afegeix-ne a manteniment o revisa <code>training_reports</code>.</p>
                    <?php else: ?>
                        <?php foreach ($selectorReports as $r): ?>
                            <?php
                            $rid = (int) $r['id'];
                            $code = (string) $r['report_code'];
                            $name = (string) $r['report_name'];
                            $desc = trim((string) ($r['report_description'] ?? ''));
                            $ariaLabel = $desc !== ''
                                ? $code . ' — ' . $desc . ' (' . $name . ')'
                                : $code . ' — ' . $name;
                            ?>
                            <label
                                class="reports-picker__row"
                                data-report-name="<?= e($name) ?>"
                                data-report-code="<?= e($code) ?>"
                                aria-label="<?= e($ariaLabel) ?>"
                            >
                                <input
                                    type="radio"
                                    name="report_id"
                                    value="<?= $rid ?>"
                                    data-report-code="<?= e($code) ?>"
                                    <?= $selectedReportId === $rid ? ' checked' : '' ?>
                                >
                                <span class="reports-picker__line">
                                    <span class="reports-picker__code"><?= e($code) ?></span>
                                    <?php if ($desc !== ''): ?>
                                        <span class="reports-picker__text"><?= e($desc) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group form-grid__full reports-rpa-personal-row" id="reports_rpa_personal_row"<?= !empty($selectedReportIsRpaFc01) ? '' : ' hidden' ?>>
                <label class="reports-switch" for="reports_include_personal_data">
                    <input
                        type="checkbox"
                        id="reports_include_personal_data"
                        name="include_personal_data"
                        value="1"
                        <?= !empty($includePersonalData) ? ' checked' : '' ?>
                    >
                    <span class="reports-switch__text">Persones preinscrites</span>
                </label>
                <p class="reports-rpa-personal-hint">Mostra els noms de les persones preinscrites a l’informe RPAFC-01.</p>
            </div>

            <div class="form-group form-grid__full reports-rpa-training-type-row" id="reports_rpa_training_type_row"<?= !empty($selectedReportIsRpaFc01) || !empty($selectedReportIsRpaFc03) || !empty($selectedReportIsRpaFc02) || !empty($selectedReportIsRpaFc04) || !empty($selectedReportIsReeFc01) ? '' : ' hidden' ?>>
                <label class="form-label" for="reports_training_type">Tipus de formació</label>
                <select class="form-input" id="reports_training_type" name="training_type">
                    <option value="all"<?= $trainingTypeFilter === 'all' ? ' selected' : '' ?>>Tots els tipus</option>
                    <option value="programmed"<?= $trainingTypeFilter === 'programmed' ? ' selected' : '' ?>>Programada</option>
                    <option value="non_programmed"<?= $trainingTypeFilter === 'non_programmed' ? ' selected' : '' ?>>No programada</option>
                    <option value="personal"<?= $trainingTypeFilter === 'personal' ? ' selected' : '' ?>>Personal</option>
                </select>
                <p class="reports-rpa-training-type-hint">Filtra les accions segons el tipus de formació assignat al subprograma. «Tots els tipus» mostra totes les accions de l’any sense aplicar aquest filtre.</p>
            </div>

            <div class="form-group form-grid__full reports-rpa-fc-02-initial-row" id="reports_rpa_fc_02_initial_row"<?= !empty($selectedReportIsRpaFc02) ? '' : ' hidden' ?>>
                <label class="reports-switch" for="reports_initial_date_only">
                    <input
                        type="checkbox"
                        id="reports_initial_date_only"
                        name="initial_date_only"
                        value="1"
                        <?= !empty($initialDateOnly) ? ' checked' : '' ?>
                    >
                    <span class="reports-switch__text">Només data inicial?</span>
                </label>
                <p class="reports-rpa-fc-02-initial-hint">Si està marcat, només es té en compte la primera data de cada acció (calendari i columnes de mesos).</p>
            </div>

            <div class="form-group form-grid__full reports-rpe-fc-02-assist-row" id="reports_rpe_fc_02_assist_row"<?= !empty($selectedReportIsRpeFc02) ? '' : ' hidden' ?>>
                <input type="hidden" name="amb_assistents" value="0" id="reports_amb_assistents_hidden"<?= empty($selectedReportIsRpeFc02) ? ' disabled' : '' ?>>
                <label class="reports-switch" for="reports_amb_assistents_rpefc02">
                    <input
                        type="checkbox"
                        id="reports_amb_assistents_rpefc02"
                        name="amb_assistents"
                        value="1"
                        <?= empty($selectedReportIsRpeFc02) ? ' disabled' : '' ?>
                        <?= !empty($ambAssistentsRpefc02) ? ' checked' : '' ?>
                    >
                    <span class="reports-switch__text">Amb assistents</span>
                </label>
            </div>

            <div class="form-group form-grid__full reports-rpe-fc-04-inscrits-row" id="reports_rpe_fc_04_inscrits_row"<?= !empty($selectedReportIsRpeFc04) ? '' : ' hidden' ?>>
                <input type="hidden" name="dades_inscrits" value="0" id="reports_dades_inscrits_hidden"<?= empty($selectedReportIsRpeFc04) ? ' disabled' : '' ?>>
                <label class="reports-switch" for="reports_dades_inscrits_rpefc04">
                    <input
                        type="checkbox"
                        id="reports_dades_inscrits_rpefc04"
                        name="dades_inscrits"
                        value="1"
                        <?= empty($selectedReportIsRpeFc04) ? ' disabled' : '' ?>
                        <?= !empty($dadesInscritsRpefc04) ? ' checked' : '' ?>
                    >
                    <span class="reports-switch__text">Dades inscrits?</span>
                </label>
            </div>

        </form>
    </div>
</section>

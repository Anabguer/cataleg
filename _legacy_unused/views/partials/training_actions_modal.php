<?php
declare(strict_types=1);
/** @var list<array{id:int,name:string,code_display:string}> $subprogramsModal */
/** @var list<array{id:int,name:string,code_display:string}> $organizersModal */
/** @var list<array{id:int,knowledge_area_code:int,name:string,image_url:?string}> $knowledgeAreasModal */
/** @var list<array{id:int,name:string,code_display:string}> $trainerTypesModal */
/** @var list<array{id:int,name:string,code_display:string}> $locationsModal */
/** @var list<array{id:int,name:string,code_display:string}> $fundingModal */
/** @var list<array{id:int,label:string}> $authorizersModal */
/** @var int $currentCalendarYear */
/** @var bool $canViewCatalog */

$subprogramsModal = $subprogramsModal ?? [];
$organizersModal = $organizersModal ?? [];
$knowledgeAreasModal = $knowledgeAreasModal ?? [];
$trainerTypesModal = $trainerTypesModal ?? [];
$locationsModal = $locationsModal ?? [];
$fundingModal = $fundingModal ?? [];
$authorizersModal = $authorizersModal ?? [];
$currentCalendarYear = $currentCalendarYear ?? (int) date('Y');
$canViewCatalog = $canViewCatalog ?? false;

$yearOpts = [];
for ($y = $currentCalendarYear - 3; $y <= $currentCalendarYear + 8; $y++) {
    $yearOpts[] = $y;
}
?>
<div id="training-actions-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-actions-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form modal__dialog--training-actions training-actions-ui-standard">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="training-actions-modal-title" data-ta-modal-heading>Acció formativa</h2>
                        <p class="users-modal-form__subtitle" data-ta-modal-subheading></p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-ta-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="training-actions-modal-form" data-ta-submit-btn>Desar</button>
                </div>
            </div>
            <div class="modal-tabs" role="tablist" aria-label="Seccions del formulari">
                <button type="button" class="modal-tabs__btn is-active" role="tab" aria-selected="true" data-ta-tab="prog">Programació</button>
                <button type="button" class="modal-tabs__btn" role="tab" aria-selected="false" data-ta-tab="details">Detalls de l’acció</button>
                <button type="button" class="modal-tabs__btn" role="tab" aria-selected="false" data-ta-tab="exec">Execució</button>
                <button type="button" class="modal-tabs__btn" role="tab" aria-selected="false" data-ta-tab="assist">Assistents</button>
                <button type="button" class="modal-tabs__btn" role="tab" aria-selected="false" data-ta-tab="eval">Avaluació</button>
                <button type="button" class="modal-tabs__btn" role="tab" aria-selected="false" data-ta-tab="docs">Documents</button>
            </div>
            <form id="training-actions-modal-form" class="users-modal-form__body training-actions-modal-form" novalidate lang="ca">
                <input type="hidden" name="id" value="" data-field="id">
                <input type="hidden" name="catalog_action_id" value="" data-field="catalog_action_id">
                <div class="training-actions-modal-form__alert js-ta-msg" hidden>
                    <div class="alert alert--error" role="alert" data-ta-form-error></div>
                </div>

                <div class="modal-tab-panel is-active" data-ta-panel="prog" role="tabpanel">
                    <div class="training-actions-grid training-actions-grid--prog">
                        <p class="training-actions-modal__section-title ta-span-full">Identificació</p>

                        <div class="form-group ta-span-2">
                            <label class="form-label" for="ta_program_year">Any de programa <span class="users-modal-form__req">*</span></label>
                            <select class="form-select" id="ta_program_year" name="program_year" required data-field="program_year">
                                <?php foreach ($yearOpts as $y): ?>
                                    <option value="<?= $y ?>"<?= $y === $currentCalendarYear ? ' selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="program_year" hidden></p>
                        </div>
                        <div class="form-group ta-span-2">
                            <label class="form-label" for="ta_action_number_ro">Número d’acció</label>
                            <input class="form-input" id="ta_action_number_ro" type="text" readonly tabindex="-1" data-ta-action-number-display value="">
                            <p class="muted training-actions-modal__hint">Auto. per any</p>
                        </div>
                        <div class="form-group ta-span-3">
                            <label class="form-label" for="ta_display_code_ro">Codi complet</label>
                            <input class="form-input" id="ta_display_code_ro" type="text" readonly tabindex="-1" data-ta-display-code value="">
                        </div>
                        <?php if ($canViewCatalog): ?>
                            <div class="form-group ta-span-5 ta-field--catalog">
                                <span class="form-label" id="ta-catalog-btn-label">Catàleg</span>
                                <button type="button" class="btn btn--outline btn--sm ta-catalog-btn" data-ta-catalog-btn aria-labelledby="ta-catalog-btn-label">Catàleg d’accions</button>
                            </div>
                        <?php else: ?>
                            <div class="form-group ta-span-5 ta-field--spacer" aria-hidden="true"></div>
                        <?php endif; ?>

                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_name">Acció formativa / nom <span class="users-modal-form__req">*</span></label>
                            <input class="form-input" id="ta_name" name="name" type="text" maxlength="255" required data-field="name" lang="ca" spellcheck="true">
                            <p class="form-error" data-error-for="name" hidden></p>
                        </div>

                        <p class="training-actions-modal__section-title ta-span-full">Organització</p>

                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_subprogram_id">Subprograma</label>
                            <select class="form-select" id="ta_subprogram_id" name="subprogram_id" data-field="subprogram_id">
                                <option value="">—</option>
                                <?php foreach ($subprogramsModal as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['code_display'] . ' — ' . $s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="subprogram_id" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_organizer_id">Organitzador</label>
                            <select class="form-select" id="ta_organizer_id" name="organizer_id" data-field="organizer_id">
                                <option value="">—</option>
                                <?php foreach ($organizersModal as $o): ?>
                                    <option value="<?= (int) $o['id'] ?>"><?= e($o['code_display'] . ' — ' . $o['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="organizer_id" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_trainer_type_id">Tipus de formador</label>
                            <select class="form-select" id="ta_trainer_type_id" name="trainer_type_id" data-field="trainer_type_id">
                                <option value="">—</option>
                                <?php foreach ($trainerTypesModal as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>"><?= e($t['code_display'] . ' — ' . $t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="trainer_type_id" hidden></p>
                        </div>

                        <div class="form-group ta-span-full ta-knowledge-area-field">
                            <div class="ta-knowledge-area-field__row">
                                <div class="ta-knowledge-area-field__select">
                                    <label class="form-label" for="ta_knowledge_area_id">Àrea de coneixement</label>
                                    <select class="form-select" id="ta_knowledge_area_id" name="knowledge_area_id" data-field="knowledge_area_id">
                                        <option value="">—</option>
                                        <?php foreach ($knowledgeAreasModal as $ka): ?>
                                            <option value="<?= (int) $ka['id'] ?>"><?= e(format_padded_code((int) $ka['knowledge_area_code'], 3) . ' — ' . $ka['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="form-error" data-error-for="knowledge_area_id" hidden></p>
                                </div>
                                <div class="knowledge-areas-modal-preview ta-knowledge-area-field__preview" data-ta-ka-preview hidden>
                                    <img class="knowledge-areas-modal-preview__img" data-ta-ka-preview-img alt="" loading="lazy">
                                </div>
                            </div>
                        </div>

                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_trainers_text">Formadors (text)</label>
                            <input class="form-input" id="ta_trainers_text" name="trainers_text" type="text" maxlength="500" data-field="trainers_text" lang="ca">
                            <p class="form-error" data-error-for="trainers_text" hidden></p>
                        </div>

                        <p class="training-actions-modal__section-title ta-span-full">Places, lloc i durada prevista</p>

                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_planned_places">Places previstes</label>
                            <input class="form-input" id="ta_planned_places" name="planned_places" type="number" min="0" step="1" data-field="planned_places">
                            <p class="form-error" data-error-for="planned_places" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_training_location_id">Lloc d’impartició</label>
                            <select class="form-select" id="ta_training_location_id" name="training_location_id" data-field="training_location_id">
                                <option value="">—</option>
                                <?php foreach ($locationsModal as $l): ?>
                                    <option value="<?= (int) $l['id'] ?>"><?= e($l['code_display'] . ' — ' . $l['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="training_location_id" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_planned_duration_hours">Durada prevista (h)</label>
                            <input class="form-input" id="ta_planned_duration_hours" name="planned_duration_hours" type="number" step="0.01" min="0" max="9999.99" data-field="planned_duration_hours">
                            <p class="form-error" data-error-for="planned_duration_hours" hidden></p>
                        </div>

                        <p class="training-actions-modal__section-title ta-span-full">Costos i finançament previst</p>

                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_planned_total_cost">Cost total previst</label>
                            <div class="input-addon-group" role="group" aria-label="Cost total previst">
                                <input class="form-input input-addon-group__input" id="ta_planned_total_cost" name="planned_total_cost" type="number" step="0.01" min="0" data-field="planned_total_cost">
                                <span class="input-addon-group__suffix" aria-hidden="true">€</span>
                            </div>
                            <p class="form-error" data-error-for="planned_total_cost" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_municipal_funding_percent">Finançament municipal (%)</label>
                            <div class="input-addon-group" role="group" aria-label="Finançament municipal">
                                <input class="form-input input-addon-group__input" id="ta_municipal_funding_percent" name="municipal_funding_percent" type="number" step="0.01" min="0" max="100" data-field="municipal_funding_percent">
                                <span class="input-addon-group__suffix" aria-hidden="true">%</span>
                            </div>
                            <p class="form-error" data-error-for="municipal_funding_percent" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_planned_municipal_cost">Cost previst ajuntament</label>
                            <div class="input-addon-group input-addon-group--calculated" role="group" aria-label="Cost previst ajuntament (calculat)">
                                <input class="form-input input-addon-group__input form-input--calculated" id="ta_planned_municipal_cost" name="planned_municipal_cost" type="number" step="0.01" min="0" data-field="planned_municipal_cost" readonly tabindex="-1" title="Camp calculat automàticament">
                                <span class="input-addon-group__suffix" aria-hidden="true">€</span>
                            </div>
                            <p class="muted training-actions-modal__hint">Calculat a partir del cost total i el percentatge municipal.</p>
                            <p class="form-error" data-error-for="planned_municipal_cost" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_funding_id">Altre finançament</label>
                            <select class="form-select" id="ta_funding_id" name="funding_id" data-field="funding_id">
                                <option value="">—</option>
                                <?php foreach ($fundingModal as $f): ?>
                                    <option value="<?= (int) $f['id'] ?>"><?= e($f['code_display'] . ' — ' . $f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="funding_id" hidden></p>
                        </div>

                        <p class="training-actions-modal__section-title ta-span-full">Autorització i pla</p>

                        <div class="form-group ta-span-6">
                            <label class="form-label" for="ta_training_authorizer_id">Autoritzador</label>
                            <select class="form-select" id="ta_training_authorizer_id" name="training_authorizer_id" data-field="training_authorizer_id">
                                <option value="">—</option>
                                <?php foreach ($authorizersModal as $a): ?>
                                    <option value="<?= (int) $a['id'] ?>"><?= e($a['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="training_authorizer_id" hidden></p>
                        </div>
                        <div class="form-group ta-span-6">
                            <label class="form-label" for="ta_grouped_plan_code">Codi pla agrupat</label>
                            <input class="form-input" id="ta_grouped_plan_code" name="grouped_plan_code" type="text" maxlength="50" data-field="grouped_plan_code" lang="ca">
                            <p class="form-error" data-error-for="grouped_plan_code" hidden></p>
                        </div>

                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_planned_schedule">Horari previst</label>
                            <input class="form-input" id="ta_planned_schedule" name="planned_schedule" type="text" maxlength="255" data-field="planned_schedule" lang="ca">
                            <p class="form-error" data-error-for="planned_schedule" hidden></p>
                        </div>

                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_notes">Observacions</label>
                            <textarea class="form-input" id="ta_notes" name="notes" rows="2" data-field="notes" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="notes" hidden></p>
                        </div>

                        <p class="training-actions-modal__section-title ta-span-full">Dates previstes</p>

                        <div class="form-group ta-span-full ta-dates-range-block">
                            <p class="form-label">Generació ràpida (dies laborables)</p>
                            <p class="muted training-actions-modal__hint">Dilluns a divendres entre dues dates; s’afegeixen a la llista sense esborrar les existents (es salten duplicats).</p>
                            <div class="ta-dates-range-block__row">
                                <div class="ta-dates-range-block__field">
                                    <label class="form-label form-label--inline-sm" for="ta_dates_range_start">Data inici</label>
                                    <input class="form-input" type="date" id="ta_dates_range_start" data-ta-range-start autocomplete="off">
                                </div>
                                <div class="ta-dates-range-block__field">
                                    <label class="form-label form-label--inline-sm" for="ta_dates_range_end">Data fi</label>
                                    <input class="form-input" type="date" id="ta_dates_range_end" data-ta-range-end autocomplete="off">
                                </div>
                                <div class="ta-dates-range-block__action">
                                    <span class="form-label form-label--inline-sm ta-dates-range-block__action-label" aria-hidden="true">&nbsp;</span>
                                    <button type="button" class="btn btn--outline btn--sm" data-ta-generate-weekdays>Afegir dates laborables</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group ta-span-full">
                            <div class="ta-dates-block">
                                <div class="ta-dates-table-wrap">
                                    <table class="data-table data-table--compact ta-dates-table">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Hora inici</th>
                                                <th>Hora fi</th>
                                                <th class="data-table__actions"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="ta-dates-tbody"></tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn--outline btn--sm" data-ta-add-date>Afegir data</button>
                            </div>
                            <p class="form-error" data-error-for="dates_0" hidden></p>
                        </div>

                        <div class="form-group ta-span-full">
                            <label class="users-switch">
                                <input type="checkbox" id="ta_is_active" name="is_active" value="1" checked data-field="is_active">
                                <span>Actiu</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-tab-panel" data-ta-panel="details" role="tabpanel" hidden>
                    <div class="training-actions-grid training-actions-grid--details">
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_target_audience">Destinatàries / destinataris</label>
                            <textarea class="form-input form-input--tall" id="ta_target_audience" name="target_audience" rows="5" data-field="target_audience" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="target_audience" hidden></p>
                        </div>
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_training_objectives">Objectius formatius</label>
                            <textarea class="form-input form-input--tall" id="ta_training_objectives" name="training_objectives" rows="5" data-field="training_objectives" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="training_objectives" hidden></p>
                        </div>
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_conceptual_contents">Continguts conceptuals</label>
                            <textarea class="form-input form-input--tall" id="ta_conceptual_contents" name="conceptual_contents" rows="5" data-field="conceptual_contents" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="conceptual_contents" hidden></p>
                        </div>
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_procedural_contents">Continguts procedimentals</label>
                            <textarea class="form-input form-input--tall" id="ta_procedural_contents" name="procedural_contents" rows="5" data-field="procedural_contents" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="procedural_contents" hidden></p>
                        </div>
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_attitudinal_contents">Continguts actitudinals</label>
                            <textarea class="form-input form-input--tall" id="ta_attitudinal_contents" name="attitudinal_contents" rows="5" data-field="attitudinal_contents" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="attitudinal_contents" hidden></p>
                        </div>
                    </div>
                </div>

                <div class="modal-tab-panel" data-ta-panel="exec" role="tabpanel" hidden>
                    <div class="training-actions-grid training-actions-grid--exec">
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_execution_status">Estat d’execució <span class="users-modal-form__req">*</span></label>
                            <select class="form-select" id="ta_execution_status" name="execution_status" required data-field="execution_status">
                                <option value="">—</option>
                                <?php foreach (training_actions_execution_status_allowed_values() as $esOpt): ?>
                                    <option value="<?= e($esOpt) ?>"><?= e($esOpt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="form-error" data-error-for="execution_status" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_actual_cost">Cost real</label>
                            <div class="input-addon-group" role="group" aria-label="Cost real">
                                <input class="form-input input-addon-group__input" id="ta_actual_cost" name="actual_cost" type="number" step="0.01" min="0" data-field="actual_cost">
                                <span class="input-addon-group__suffix" aria-hidden="true">€</span>
                            </div>
                            <p class="form-error" data-error-for="actual_cost" hidden></p>
                        </div>
                        <div class="form-group ta-span-4">
                            <label class="form-label" for="ta_actual_duration_hours">Durada real (h)</label>
                            <input class="form-input" id="ta_actual_duration_hours" name="actual_duration_hours" type="number" step="0.01" min="0" max="9999.99" data-field="actual_duration_hours">
                            <p class="form-error" data-error-for="actual_duration_hours" hidden></p>
                        </div>
                        <div class="form-group ta-span-full">
                            <label class="form-label" for="ta_execution_notes">Observacions d’execució</label>
                            <textarea class="form-input form-input--tall" id="ta_execution_notes" name="execution_notes" rows="6" data-field="execution_notes" lang="ca" spellcheck="true"></textarea>
                            <p class="form-error" data-error-for="execution_notes" hidden></p>
                        </div>
                    </div>
                </div>

                <div class="modal-tab-panel" data-ta-panel="assist" role="tabpanel" hidden>
                    <div class="training-actions-assist">
                        <div class="ta-attendees-locked" data-ta-attendees-locked hidden>
                            <p class="muted">Cal desar l’acció formativa abans de gestionar els assistents.</p>
                        </div>
                        <div class="ta-attendees-wrap" data-ta-attendees-wrap hidden>
                            <div class="ta-assist-toolbar">
                                <button type="button" class="btn btn--outline btn--module-accent-outline btn--sm" data-ta-attendee-add><?= ui_icon('plus') ?> Afegir assistent</button>
                                <button type="button" class="btn btn--outline btn--module-accent-outline btn--sm" data-ta-send-q-all hidden><?= ui_icon('download') ?> Enviar qüestionari (tots amb assistència)</button>
                            </div>
                            <div class="ta-attendees-table-wrap">
                                <table class="data-table data-table--compact ta-attendees-table">
                                    <thead>
                                        <tr>
                                            <th>Persona</th>
                                            <th>Lloc de treball</th>
                                            <th>Correu</th>
                                            <th class="ta-attendees-ic">Sol.</th>
                                            <th class="ta-attendees-ic">Pre.</th>
                                            <th class="ta-attendees-ic">Ins.</th>
                                            <th class="ta-attendees-ic">Ass.</th>
                                            <th>Certificat</th>
                                            <th title="Estat de l’Excel enviat / enviament">Qüest.</th>
                                            <th class="data-table__actions"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ta-attendees-tbody"></tbody>
                                </table>
                            </div>
                            <p class="muted ta-attendees-empty" data-ta-attendees-empty hidden>Encara no hi ha assistents registrats.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-tab-panel" data-ta-panel="eval" role="tabpanel" hidden>
                    <div class="training-actions-eval">
                        <div class="ta-eval-locked" data-ta-eval-locked hidden>
                            <p class="muted">Cal desar l’acció formativa abans de gestionar avaluacions.</p>
                        </div>
                        <div class="ta-eval-wrap" data-ta-eval-wrap hidden>
                            <div class="ta-eval-toolbar">
                                <label class="btn btn--outline btn--module-accent-outline btn--sm" style="cursor:pointer;margin:0;">
                                    <?= ui_icon('folder') ?> Importar qüestionaris rebuts
                                    <input type="file" class="ta-eval-import-input" name="eval_files[]" accept=".xlsx,.xlsm,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" multiple hidden data-ta-eval-import-input>
                                </label>
                                <p class="muted ta-eval-import-hint">Seleccioneu un o diversos fitxers .xlsx (o una carpeta al Chrome: trieu diversos fitxers d’una carpeta de respostes).</p>
                            </div>
                            <div class="ta-eval-legend muted" data-ta-eval-legend></div>
                            <div class="ta-eval-table-wrap">
                                <table class="data-table data-table--compact ta-eval-table">
                                    <thead>
                                        <tr data-ta-eval-thead-tr>
                                            <th scope="col">
                                                <button type="button" class="data-table__sort-link ta-eval-sort-btn" data-ta-eval-sort="person" title="Ordenar per assistent">
                                                    Assistent <span class="data-table__sort" data-ta-eval-sort-ind="person" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <?php for ($qi = 1; $qi <= 20; $qi++) : ?>
                                                <th scope="col">
                                                    <button type="button" class="data-table__sort-link ta-eval-sort-btn" data-ta-eval-sort="q<?= $qi ?>" title="Ordenar per Q<?= str_pad((string) $qi, 2, '0', STR_PAD_LEFT) ?>">
                                                        Q<?= str_pad((string) $qi, 2, '0', STR_PAD_LEFT) ?> <span class="data-table__sort" data-ta-eval-sort-ind="q<?= $qi ?>" aria-hidden="true"></span>
                                                    </button>
                                                </th>
                                            <?php endfor; ?>
                                            <th scope="col">
                                                <button type="button" class="data-table__sort-link ta-eval-sort-btn" data-ta-eval-sort="global" title="Ordenar per nota global">
                                                    Global <span class="data-table__sort" data-ta-eval-sort-ind="global" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th class="data-table__actions" scope="col"><span class="visually-hidden">Accions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ta-eval-tbody"></tbody>
                                </table>
                            </div>
                            <p class="muted ta-eval-empty" data-ta-eval-empty hidden>Encara no hi ha avaluacions importades per a aquesta acció.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-tab-panel" data-ta-panel="docs" role="tabpanel" hidden>
                    <div class="training-actions-docs">
                        <div class="ta-docs-locked" data-ta-docs-locked hidden>
                            <p class="muted">Cal desar l’acció formativa abans de gestionar els documents.</p>
                        </div>
                        <div class="ta-docs-wrap" data-ta-docs-wrap hidden>
                            <div class="ta-docs-toolbar">
                                <button type="button" class="btn btn--outline btn--module-accent-outline btn--sm" data-ta-doc-add><?= ui_icon('plus') ?> Nou document</button>
                            </div>
                            <div class="ta-docs-table-wrap">
                                <table class="data-table data-table--compact ta-docs-table">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Observacions</th>
                                            <th>Visible consulta externa</th>
                                            <th>Tipus / origen</th>
                                            <th class="data-table__actions"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ta-docs-tbody"></tbody>
                                </table>
                            </div>
                            <p class="muted ta-docs-empty" data-ta-docs-empty hidden>Encara no hi ha documents vinculats a aquesta acció.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($canViewCatalog): ?>
<div id="training-actions-catalog-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="ta-catalog-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form training-actions-ui-standard">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('folder') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="ta-catalog-title">Catàleg d’accions</h2>
                        <p class="users-modal-form__subtitle">Selecciona una acció per omplir el formulari</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-ta-catalog-close>Tancar</button>
                </div>
            </div>
            <div class="users-modal-form__body">
                <div class="form-group">
                    <label class="form-label" for="ta_catalog_search">Cercar</label>
                    <input class="form-input" id="ta_catalog_search" type="search" autocomplete="off" lang="ca">
                </div>
                <ul class="ta-catalog-list" id="ta-catalog-list" role="listbox"></ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require APP_ROOT . '/views/partials/training_actions_attendee_modal.php'; ?>
<?php require APP_ROOT . '/views/partials/training_actions_document_modal.php'; ?>
<?php require APP_ROOT . '/views/partials/training_actions_evaluation_detail_modal.php'; ?>

<template id="ta-date-row-template">
    <tr class="ta-date-row">
        <td><input class="form-input" type="date" data-ta-date-field="session_date" required></td>
        <td><input class="form-input" type="time" data-ta-date-field="start_time"></td>
        <td><input class="form-input" type="time" data-ta-date-field="end_time"></td>
        <td class="data-table__actions"><button type="button" class="btn btn--sm btn--icon-del" data-ta-remove-date><?= ui_icon('trash') ?></button></td>
    </tr>
</template>

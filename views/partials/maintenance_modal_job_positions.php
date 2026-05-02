<?php
declare(strict_types=1);
/** @var string $module */
/** @var PDO $db */
/** @var int $year */
/** @var array<string,mixed> $maintenancePageInlineConfig */
$jpOrganic3 = $db->query('SELECT org_unit_level_3_id AS id, org_unit_level_3_name AS name FROM org_units_level_3 WHERE catalog_year = ' . (int) $year . ' ORDER BY org_unit_level_3_id ASC')->fetchAll() ?: [];
$jpSpecS = $db->query('SELECT special_specific_compensation_id AS id, special_specific_compensation_name AS name FROM specific_compensation_special WHERE catalog_year = ' . (int) $year . ' ORDER BY special_specific_compensation_id ASC')->fetchAll() ?: [];
$jpSpecG = $db->query('SELECT general_specific_compensation_id AS id, general_specific_compensation_name AS name FROM specific_compensation_general WHERE catalog_year = ' . (int) $year . ' ORDER BY general_specific_compensation_id ASC')->fetchAll() ?: [];
$jpWorkCenters = $db->query('SELECT work_center_id AS id, work_center_name AS name FROM work_centers WHERE catalog_year = ' . (int) $year . ' ORDER BY work_center_id ASC')->fetchAll() ?: [];
$jpAvailability = $db->query('SELECT availability_id AS id, availability_name AS name FROM availability_options WHERE catalog_year = ' . (int) $year . ' ORDER BY sort_order ASC, availability_id ASC')->fetchAll() ?: [];
$jpProvision = $db->query('SELECT provision_method_id AS id, provision_method_name AS name FROM provision_methods WHERE catalog_year = ' . (int) $year . ' ORDER BY sort_order ASC, provision_method_id ASC')->fetchAll() ?: [];
$jpSsEpigraph = $db->query('SELECT contribution_epigraph_id AS id, contribution_epigraph_id AS name FROM social_security_coefficients WHERE catalog_year = ' . (int) $year . ' ORDER BY contribution_epigraph_id ASC')->fetchAll() ?: [];
$jpSsGroup = $db->query('SELECT contribution_group_id AS id, contribution_group_description AS name FROM social_security_base_limits WHERE catalog_year = ' . (int) $year . ' ORDER BY contribution_group_id ASC')->fetchAll() ?: [];
$jpScales = $maintenancePageInlineConfig['scales'] ?? [];
$jpSubscales = $maintenancePageInlineConfig['subscales'] ?? [];
$jpClasses = $maintenancePageInlineConfig['classes'] ?? [];
$jpCategories = $maintenancePageInlineConfig['categories'] ?? [];
$jpLegal = $maintenancePageInlineConfig['jobPositionLegalOptions'] ?? [];
$jpSalaryBaseRows = $db->query('SELECT classification_group, base_salary FROM salary_base_by_group WHERE catalog_year = ' . (int) $year . ' ORDER BY classification_group ASC')->fetchAll() ?: [];
$jpDestRows = $db->query('SELECT organic_level, destination_allowance, destination_allowance_new FROM destination_allowances WHERE catalog_year = ' . (int) $year . ' ORDER BY organic_level ASC')->fetchAll() ?: [];
$jpCmRespRows = maintenance_job_positions_responsible_cm_options($db, (int) $year);
$jpJobTypes = maintenance_job_position_type_options($db, (int) $year);
?>
<div class="form-grid__full job-positions-modal-shell" data-maintenance-field="job_positions_modal" data-job-positions-modal="1">
    <div class="job-positions-tabs" role="tablist" aria-label="Seccions del lloc de treball">
        <button type="button" class="job-positions-tabs__btn is-active" role="tab" aria-selected="true" data-job-positions-tab="ident">Identificació</button>
        <button type="button" class="job-positions-tabs__btn" role="tab" aria-selected="false" data-job-positions-tab="dedicacio">Dedicació</button>
        <button type="button" class="job-positions-tabs__btn" role="tab" aria-selected="false" data-job-positions-tab="funcions">Funcions</button>
        <button type="button" class="job-positions-tabs__btn" role="tab" aria-selected="false" data-job-positions-tab="provisio">Provisió</button>
        <button type="button" class="job-positions-tabs__btn" role="tab" aria-selected="false" data-job-positions-tab="condicions">Condicions</button>
        <button type="button" class="job-positions-tabs__btn" role="tab" aria-selected="false" data-job-positions-tab="observacions">Observacions</button>
    </div>

    <div class="job-positions-tab-panels">
        <div class="job-positions-tab-panel is-active" data-job-positions-panel="ident" role="tabpanel">
            <div class="form-grid form-grid--modal form-grid__full">
                <input type="hidden" name="job_position_id" id="jp_job_position_id" value="" data-field="job_position_id" autocomplete="off">
                <p class="form-error" data-error-for="job_position_id" hidden></p>
                <p class="form-error" data-error-for="catalog_code" hidden></p>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="jp_job_title">Denominació <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="jp_job_title" name="job_title" type="text" required data-field="job_title" data-job-positions-title-source autocomplete="off">
                    <p class="form-error" data-error-for="job_title" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_org_unit_level_3_id">Departament <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="jp_org_unit_level_3_id" name="org_unit_level_3_id" data-field="org_unit_level_3_id" data-job-positions-dept required>
                        <option value="">—</option>
                        <?php foreach ($jpOrganic3 as $it): ?>
                            <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-error" data-error-for="org_unit_level_3_id" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_job_number">Número <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="jp_job_number" name="job_number" type="text" data-field="job_number" data-job-positions-num maxlength="20" required autocomplete="off" inputmode="numeric">
                    <p class="form-error" data-error-for="job_number" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_job_position_code_display">Codi complet del lloc <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="jp_job_position_code_display" name="job_position_code_display" type="text" readonly required tabindex="-1" data-job-positions-full-code data-job-position-code-display maxlength="30" autocomplete="off">
                    <p class="form-error" data-error-for="job_position_code_display" hidden></p>
                </div>
                <div class="form-grid form-grid--two-cols">
                    <div class="form-group">
                        <label class="form-label" for="jp_org_dependency_id">Responsable</label>
                        <select class="form-select" id="jp_org_dependency_id" name="org_dependency_id" data-field="org_dependency_id">
                            <option value="">—</option>
                            <?php foreach ($jpCmRespRows as $it): ?>
                                <?php
                                $rid = trim((string) ($it['job_position_id'] ?? ''));
                                $codeDisp = maintenance_format_job_position_code_display($rid);
                                $rname = trim((string) ($it['job_title'] ?? ''));
                                $rlab = $codeDisp !== '' ? ($codeDisp . ' — ' . $rname) : $rname;
                                ?>
                                <option value="<?= e($rid) ?>"><?= e($rlab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jp_legal_relation_id">Relació jurídica</label>
                        <select class="form-select" id="jp_legal_relation_id" name="legal_relation_id" data-field="legal_relation_id">
                            <option value="">—</option>
                            <?php foreach ($jpLegal as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid form-grid--two-cols">
                    <div class="form-group">
                        <label class="form-label" for="jp_civil_service_scale_id">Escala</label>
                        <select class="form-select" id="jp_civil_service_scale_id" name="civil_service_scale_id" data-field="civil_service_scale_id">
                            <option value="">—</option>
                            <?php foreach ($jpScales as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jp_civil_service_subscale_id">Subescala</label>
                        <select class="form-select" id="jp_civil_service_subscale_id" name="civil_service_subscale_id" data-field="civil_service_subscale_id">
                            <option value="">—</option>
                            <?php foreach ($jpSubscales as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>" data-scale-id="<?= e((string) ($it['scale_id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid form-grid--two-cols">
                    <div class="form-group">
                        <label class="form-label" for="jp_civil_service_class_id">Classe</label>
                        <select class="form-select" id="jp_civil_service_class_id" name="civil_service_class_id" data-field="civil_service_class_id">
                            <option value="">—</option>
                            <?php foreach ($jpClasses as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>" data-scale-id="<?= e((string) ($it['scale_id'] ?? '')) ?>" data-subscale-id="<?= e((string) ($it['subscale_id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jp_civil_service_category_id">Categoria</label>
                        <select class="form-select" id="jp_civil_service_category_id" name="civil_service_category_id" data-field="civil_service_category_id">
                            <option value="">—</option>
                            <?php foreach ($jpCategories as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>" data-scale-id="<?= e((string) ($it['scale_id'] ?? '')) ?>" data-subscale-id="<?= e((string) ($it['subscale_id'] ?? '')) ?>" data-class-id="<?= e((string) ($it['class_id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="jp_labor_category">Categoria laboral</label>
                    <input class="form-input" id="jp_labor_category" name="labor_category" type="text" data-field="labor_category">
                </div>
                <div class="jp-comp-section">
                    <div class="jp-comp-row">
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group">Grup classificació</label>
                            <select class="form-select" id="jp_classification_group" name="classification_group" data-field="classification_group">
                                <option value="">—</option>
                                <?php foreach ($jpSalaryBaseRows as $sb): ?>
                                    <?php $cg = trim((string) ($sb['classification_group'] ?? '')); ?>
                                    <option value="<?= e($cg) ?>"><?= e($cg) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group_amount">Import grup classificació</label>
                            <input class="form-input form-input--money" id="jp_classification_group_amount" type="text" readonly tabindex="-1" data-job-positions-classification-group-amount>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group_slash">Grup / Agts-Caporals</label>
                            <select class="form-select" id="jp_classification_group_slash" name="classification_group_slash" data-field="classification_group_slash">
                                <option value="">—</option>
                                <?php foreach ($jpSalaryBaseRows as $sb): ?>
                                    <?php $cg = trim((string) ($sb['classification_group'] ?? '')); ?>
                                    <option value="<?= e($cg) ?>"><?= e($cg) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group_slash_amount">Import grup Agts-Caporals</label>
                            <input class="form-input form-input--money" id="jp_classification_group_slash_amount" type="text" readonly tabindex="-1" data-job-positions-classification-slash-amount>
                        </div>
                    </div>
                    <div class="jp-comp-row">
                        <div class="form-group">
                            <label class="form-label" for="jp_organic_level">Nivell orgànic</label>
                            <select class="form-select" id="jp_organic_level" name="organic_level" data-field="organic_level">
                                <option value="">—</option>
                                <?php foreach ($jpDestRows as $dr): ?>
                                    <?php $olv = trim((string) ($dr['organic_level'] ?? '')); ?>
                                    <option value="<?= e($olv) ?>"><?= e($olv) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_organic_level_amount">Import nivell orgànic</label>
                            <input class="form-input form-input--money" id="jp_organic_level_amount" type="text" readonly tabindex="-1" data-job-positions-organic-amount>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group_new">Grup / Classif. Barra</label>
                            <select class="form-select" id="jp_classification_group_new" name="classification_group_new" data-field="classification_group_new">
                                <option value="">—</option>
                                <?php foreach ($jpSalaryBaseRows as $sb): ?>
                                    <?php $cg = trim((string) ($sb['classification_group'] ?? '')); ?>
                                    <option value="<?= e($cg) ?>"><?= e($cg) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_classification_group_new_amount">Import grup Classif. Barra</label>
                            <input class="form-input form-input--money" id="jp_classification_group_new_amount" type="text" readonly tabindex="-1" data-job-positions-classification-new-amount>
                        </div>
                    </div>
                    <div class="jp-comp-row">
                        <div class="form-group">
                            <label class="form-label" for="jp_general_specific_compensation_id">Complement específic general</label>
                            <select class="form-select" id="jp_general_specific_compensation_id" name="general_specific_compensation_id" data-field="general_specific_compensation_id">
                                <option value="">—</option>
                                <?php foreach ($jpSpecG as $it): ?>
                                    <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_general_specific_compensation_amount">Import complement específic general</label>
                            <input class="form-input form-input--money" id="jp_general_specific_compensation_amount" name="general_specific_compensation_amount" type="text" inputmode="decimal" data-field="general_specific_compensation_amount" readonly tabindex="-1" data-job-positions-general-amount>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_special_specific_compensation_id">Complement específic especial</label>
                            <select class="form-select" id="jp_special_specific_compensation_id" name="special_specific_compensation_id" data-field="special_specific_compensation_id" data-job-positions-special-select>
                                <option value="">—</option>
                                <?php foreach ($jpSpecS as $it): ?>
                                    <?php $sid = (string) ($it['id'] ?? ''); ?>
                                    <option value="<?= e($sid) ?>"><?= e($sid . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="jp_special_specific_compensation_amount">Import complement específic especial</label>
                            <input class="form-input form-input--money" id="jp_special_specific_compensation_amount" name="special_specific_compensation_amount" type="text" inputmode="decimal" data-field="special_specific_compensation_amount" readonly tabindex="-1" data-job-positions-special-amount>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_job_type_id">Tipus de lloc</label>
                    <select class="form-select" id="jp_job_type_id" name="job_type_id" data-field="job_type_id">
                        <option value="">—</option>
                        <?php foreach ($jpJobTypes as $jt): ?>
                            <option value="<?= e((string) ($jt['id'] ?? '')) ?>"><?= e((string) ($jt['label'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_catalog_code">Tipus catàleg</label>
                    <select class="form-select" id="jp_catalog_code" name="catalog_code" data-field="catalog_code">
                        <option value="">—</option>
                        <option value="01">01 - Ordinari</option>
                        <option value="02">02 - No permanents</option>
                        <option value="03">03 - Proposta</option>
                        <option value="04">04 - Funcional</option>
                        <option value="05">05 - Directiu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_contribution_epigraph_id">Epígraf</label>
                    <select class="form-select" id="jp_contribution_epigraph_id" name="contribution_epigraph_id" data-field="contribution_epigraph_id">
                        <option value="">—</option>
                        <?php foreach ($jpSsEpigraph as $it): ?>
                            <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grid form-grid--three-cols">
                    <div class="form-group">
                        <label class="form-label" for="jp_contribution_group_id">Grup de cotització</label>
                        <select class="form-select" id="jp_contribution_group_id" name="contribution_group_id" data-field="contribution_group_id">
                            <option value="">—</option>
                            <?php foreach ($jpSsGroup as $it): ?>
                                <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jp_deleted_at">Data baixa</label>
                        <input class="form-input" id="jp_deleted_at" name="deleted_at" type="text" placeholder="dd/mm/aaaa" data-field="deleted_at" data-job-positions-deleted-at>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jp_deletion_file_reference">Expedient baixa</label>
                        <input class="form-input" id="jp_deletion_file_reference" name="deletion_file_reference" type="text" data-field="deletion_file_reference">
                    </div>
                </div>
                <input id="jp_created_at" name="created_at" type="hidden" data-field="created_at">
                <textarea id="jp_creation_reason" name="creation_reason" hidden data-field="creation_reason"></textarea>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="jp_deletion_reason">Motiu baixa</label>
                    <textarea class="form-input" id="jp_deletion_reason" name="deletion_reason" rows="2" data-field="deletion_reason"></textarea>
                </div>
                <input id="jp_creation_file_reference" name="creation_file_reference" type="hidden" data-field="creation_file_reference">
                <input id="jp_job_evaluation" name="job_evaluation" type="hidden" data-field="job_evaluation">
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_is_to_be_amortized" name="is_to_be_amortized" value="1" data-field="is_to_be_amortized">
                        <span class="form-check__label">A amortitzar</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_is_job_active" value="1" data-field="jp_is_active_derived" disabled>
                        <span class="form-check__label">Actiu</span>
                    </label>
                </div>
            </div>
            <div class="form-group form-grid__full job-positions-ocupants">
                <label class="form-label">Ocupants del lloc</label>
                <table class="data-table data-table--compact job-positions-ocupants__table job-positions-occupants-table">
                    <thead><tr>
                        <th>Codi</th>
                        <th>Nom</th>
                        <th>% Dedicació</th>
                        <th>% Pressupostat</th>
                        <th>Situació</th>
                        <th>Coef. Cotització</th>
                        <th class="job-positions-ocupants__actions"></th>
                    </tr></thead>
                    <tbody data-job-positions-assigned-rows></tbody>
                </table>
                <button type="button" class="btn btn--ghost btn--sm" data-job-positions-assigned-add>Afegir ocupant</button>
                <p class="form-error" data-error-for="assigned_people" hidden></p>
            </div>
        </div>

        <div class="job-positions-tab-panel" data-job-positions-panel="dedicacio" role="tabpanel" hidden>
            <div class="form-group form-grid__full">
                <label class="form-label">Lloc de treball</label>
                <input class="form-input" type="text" readonly tabindex="-1" data-job-positions-title-mirror>
            </div>
            <div class="form-grid form-grid--modal form-grid__full">
                <div class="form-group">
                    <label class="form-label" for="jp_workday_type">Tipus de jornada</label>
                    <input class="form-input" id="jp_workday_type" name="workday_type" type="text" data-field="workday_type">
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_working_time_dedication">Dedicació horària</label>
                    <input class="form-input" id="jp_working_time_dedication" name="working_time_dedication" type="text" data-field="working_time_dedication">
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="jp_schedule_text">Horari</label>
                    <input class="form-input" id="jp_schedule_text" name="schedule_text" type="text" data-field="schedule_text">
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_has_night_schedule" name="has_night_schedule" value="1" data-field="has_night_schedule">
                        <span class="form-check__label">Nocturnitat</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_has_holiday_schedule" name="has_holiday_schedule" value="1" data-field="has_holiday_schedule">
                        <span class="form-check__label">Festivitat</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_has_shift_schedule" name="has_shift_schedule" value="1" data-field="has_shift_schedule">
                        <span class="form-check__label">Torns</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="jp_has_special_dedication" name="has_special_dedication" value="1" data-field="has_special_dedication">
                        <span class="form-check__label">Dedicació especial</span>
                    </label>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="jp_special_dedication_type">Tipus de dedicació especial</label>
                    <textarea class="form-input" id="jp_special_dedication_type" name="special_dedication_type" rows="2" data-field="special_dedication_type"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="jp_availability_id">Disponibilitat</label>
                    <select class="form-select" id="jp_availability_id" name="availability_id" data-field="availability_id">
                        <option value="">—</option>
                        <?php foreach ($jpAvailability as $it): ?>
                            <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="job-positions-tab-panel" data-job-positions-panel="funcions" role="tabpanel" hidden>
            <div class="form-group form-grid__full">
                <label class="form-label">Lloc de treball</label>
                <input class="form-input" type="text" readonly tabindex="-1" data-job-positions-title-mirror>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_mission">Missió</label>
                <textarea class="form-input" id="jp_mission" name="mission" rows="5" data-field="mission"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_generic_functions">Funcions genèriques</label>
                <textarea class="form-input" id="jp_generic_functions" name="generic_functions" rows="6" data-field="generic_functions"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_specific_functions">Funcions específiques</label>
                <textarea class="form-input" id="jp_specific_functions" name="specific_functions" rows="6" data-field="specific_functions"></textarea>
            </div>
        </div>

        <div class="job-positions-tab-panel" data-job-positions-panel="provisio" role="tabpanel" hidden>
            <div class="form-group form-grid__full">
                <label class="form-label">Lloc de treball</label>
                <input class="form-input" type="text" readonly tabindex="-1" data-job-positions-title-mirror>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_qualification_requirements">Titulació requerida</label>
                <textarea class="form-input" id="jp_qualification_requirements" name="qualification_requirements" rows="4" data-field="qualification_requirements"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_other_requirements">Altres requisits</label>
                <textarea class="form-input" id="jp_other_requirements" name="other_requirements" rows="4" data-field="other_requirements"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_training_requirements">Formació</label>
                <textarea class="form-input" id="jp_training_requirements" name="training_requirements" rows="4" data-field="training_requirements"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_experience_requirements">Experiència</label>
                <textarea class="form-input" id="jp_experience_requirements" name="experience_requirements" rows="4" data-field="experience_requirements"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_other_merits">Altres mèrits</label>
                <textarea class="form-input" id="jp_other_merits" name="other_merits" rows="4" data-field="other_merits"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="jp_provision_method_id">Forma de provisió</label>
                <select class="form-select" id="jp_provision_method_id" name="provision_method_id" data-field="provision_method_id">
                    <option value="">—</option>
                    <?php foreach ($jpProvision as $it): ?>
                        <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="job-positions-tab-panel" data-job-positions-panel="condicions" role="tabpanel" hidden>
            <div class="form-group form-grid__full">
                <label class="form-label">Lloc de treball</label>
                <input class="form-input" type="text" readonly tabindex="-1" data-job-positions-title-mirror>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_effort">Esforços físics</label>
                <textarea class="form-input" id="jp_effort" name="effort" rows="4" data-field="effort"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_hardship">Penositat</label>
                <textarea class="form-input" id="jp_hardship" name="hardship" rows="4" data-field="hardship"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_danger">Perillositat</label>
                <textarea class="form-input" id="jp_danger" name="danger" rows="4" data-field="danger"></textarea>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_incompatibilities">Incompatibilitats</label>
                <textarea class="form-input" id="jp_incompatibilities" name="incompatibilities" rows="4" data-field="incompatibilities"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="jp_work_center_id">Centre de treball</label>
                <select class="form-select" id="jp_work_center_id" name="work_center_id" data-field="work_center_id">
                    <option value="">—</option>
                    <?php foreach ($jpWorkCenters as $it): ?>
                        <option value="<?= e((string) ($it['id'] ?? '')) ?>"><?= e((string) ($it['id'] ?? '') . ' — ' . (string) ($it['name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_provincial_notes">Notes addicionals</label>
                <textarea class="form-input" id="jp_provincial_notes" name="provincial_notes" rows="3" data-field="provincial_notes"></textarea>
            </div>
        </div>

        <div class="job-positions-tab-panel" data-job-positions-panel="observacions" role="tabpanel" hidden>
            <div class="form-group form-grid__full">
                <label class="form-label">Lloc de treball</label>
                <input class="form-input" type="text" readonly tabindex="-1" data-job-positions-title-mirror>
            </div>
            <div class="form-group form-grid__full">
                <label class="form-label" for="jp_notes">Observacions</label>
                <textarea class="form-input" id="jp_notes" name="notes" rows="10" data-field="notes"></textarea>
            </div>
        </div>
    </div>
</div>

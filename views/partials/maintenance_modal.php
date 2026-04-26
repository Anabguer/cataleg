<?php
declare(strict_types=1);
/** @var string $module */
/** @var array<string,mixed> $config */
?>
<div id="maintenance-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="maintenance-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="maintenance-modal-title" data-maintenance-modal-heading>Nou registre</h2>
                        <p class="users-modal-form__subtitle" data-maintenance-modal-subheading>Introdueix les dades del registre</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-maintenance-modal-close data-maintenance-modal-cancel>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="maintenance-modal-form" data-maintenance-modal-submit>Desar</button>
                </div>
            </div>
            <form id="maintenance-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate>
                <input type="hidden" name="original_id" data-field="original_id">
                <div class="form-group form-grid__full js-maintenance-msg" hidden>
                    <div class="alert alert--error" role="alert" data-maintenance-form-error></div>
                </div>
                <div class="form-group" data-maintenance-field="id">
                    <label class="form-label" for="maintenance_id" data-maintenance-label-id>Codi <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_id" name="id" type="number" min="1" required data-field="id">
                    <p class="form-error" data-error-for="id" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="maintenance_name" data-maintenance-label-name>Nom <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_name" name="name" type="text" required data-field="name">
                    <p class="form-error" data-error-for="name" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="short_name" hidden>
                    <label class="form-label" for="maintenance_short_name">Nom curt</label>
                    <input class="form-input" id="maintenance_short_name" name="short_name" type="text" data-field="short_name">
                    <p class="form-error" data-error-for="short_name" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="full_name" hidden>
                    <label class="form-label" for="maintenance_full_name">Nom complet</label>
                    <input class="form-input" id="maintenance_full_name" name="full_name" type="text" data-field="full_name">
                </div>
                <div class="form-group" data-maintenance-field="scale_id" hidden>
                    <label class="form-label" for="maintenance_scale_id">Escala <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_scale_id" name="scale_id" data-field="scale_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="scale_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="subscale_id" hidden>
                    <label class="form-label" for="maintenance_subscale_id">Subescala <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_subscale_id" name="subscale_id" data-field="subscale_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="subscale_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="class_id" hidden>
                    <label class="form-label" for="maintenance_class_id">Classe <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_class_id" name="class_id" data-field="class_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="class_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="org_unit_level_1_id" hidden>
                    <label class="form-label" for="maintenance_org_unit_level_1_id">Orgànic 1 dígit <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_org_unit_level_1_id" name="org_unit_level_1_id" data-field="org_unit_level_1_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="org_unit_level_1_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="org_unit_level_2_id" hidden>
                    <label class="form-label" for="maintenance_org_unit_level_2_id">Orgànic 2 <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_org_unit_level_2_id" name="org_unit_level_2_id" data-field="org_unit_level_2_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="org_unit_level_2_id" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="address" hidden>
                    <label class="form-label" for="maintenance_address">Domicili</label>
                    <input class="form-input" id="maintenance_address" name="address" type="text" data-field="address">
                </div>
                <div class="form-group" data-maintenance-field="postal_code" hidden>
                    <label class="form-label" for="maintenance_postal_code">C.P.</label>
                    <input class="form-input" id="maintenance_postal_code" name="postal_code" type="text" data-field="postal_code">
                </div>
                <div class="form-group" data-maintenance-field="city" hidden>
                    <label class="form-label" for="maintenance_city">Població</label>
                    <input class="form-input" id="maintenance_city" name="city" type="text" data-field="city">
                </div>
                <div class="form-group" data-maintenance-field="phone" hidden>
                    <label class="form-label" for="maintenance_phone">Telèfon</label>
                    <input class="form-input" id="maintenance_phone" name="phone" type="text" data-field="phone">
                </div>
                <div class="form-group" data-maintenance-field="fax" hidden>
                    <label class="form-label" for="maintenance_fax">Fax</label>
                    <input class="form-input" id="maintenance_fax" name="fax" type="text" data-field="fax">
                </div>
                <div class="form-group" data-maintenance-field="sort_order" hidden>
                    <label class="form-label" for="maintenance_sort_order">Ordre</label>
                    <input class="form-input" id="maintenance_sort_order" name="sort_order" type="number" step="1" data-field="sort_order">
                    <p class="form-error" data-error-for="sort_order" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="contribution_account_code" hidden>
                    <label class="form-label" for="maintenance_contribution_account_code">CCC núm.</label>
                    <input class="form-input" id="maintenance_contribution_account_code" name="contribution_account_code" type="text" maxlength="14" inputmode="numeric" autocomplete="off" placeholder="00 0000000 00" data-field="contribution_account_code">
                    <p class="form-error" data-error-for="contribution_account_code" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_1" hidden>
                    <label class="form-label" for="maintenance_company_1">Emp. 1</label>
                    <input class="form-input" id="maintenance_company_1" name="company_1" type="text" inputmode="decimal" autocomplete="off" data-field="company_1">
                    <p class="form-error" data-error-for="company_1" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_2" hidden>
                    <label class="form-label" for="maintenance_company_2">Emp. 2</label>
                    <input class="form-input" id="maintenance_company_2" name="company_2" type="text" inputmode="decimal" autocomplete="off" data-field="company_2">
                    <p class="form-error" data-error-for="company_2" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_3" hidden>
                    <label class="form-label" for="maintenance_company_3">Emp. 3</label>
                    <input class="form-input" id="maintenance_company_3" name="company_3" type="text" inputmode="decimal" autocomplete="off" data-field="company_3">
                    <p class="form-error" data-error-for="company_3" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_4" hidden>
                    <label class="form-label" for="maintenance_company_4">Emp. 4</label>
                    <input class="form-input" id="maintenance_company_4" name="company_4" type="text" inputmode="decimal" autocomplete="off" data-field="company_4">
                    <p class="form-error" data-error-for="company_4" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_5a" hidden>
                    <label class="form-label" for="maintenance_company_5a">Emp. 5A</label>
                    <input class="form-input" id="maintenance_company_5a" name="company_5a" type="text" inputmode="decimal" autocomplete="off" data-field="company_5a">
                    <p class="form-error" data-error-for="company_5a" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_5b" hidden>
                    <label class="form-label" for="maintenance_company_5b">Emp. 5B</label>
                    <input class="form-input" id="maintenance_company_5b" name="company_5b" type="text" inputmode="decimal" autocomplete="off" data-field="company_5b">
                    <p class="form-error" data-error-for="company_5b" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_5c" hidden>
                    <label class="form-label" for="maintenance_company_5c">Emp. 5C</label>
                    <input class="form-input" id="maintenance_company_5c" name="company_5c" type="text" inputmode="decimal" autocomplete="off" data-field="company_5c">
                    <p class="form-error" data-error-for="company_5c" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_5d" hidden>
                    <label class="form-label" for="maintenance_company_5d">Emp. 5D</label>
                    <input class="form-input" id="maintenance_company_5d" name="company_5d" type="text" inputmode="decimal" autocomplete="off" data-field="company_5d">
                    <p class="form-error" data-error-for="company_5d" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="company_5e" hidden>
                    <label class="form-label" for="maintenance_company_5e">Emp. 5E</label>
                    <input class="form-input" id="maintenance_company_5e" name="company_5e" type="text" inputmode="decimal" autocomplete="off" data-field="company_5e">
                    <p class="form-error" data-error-for="company_5e" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="temporary_employment_company" hidden>
                    <label class="form-label" for="maintenance_temporary_employment_company">Emp. E.T.</label>
                    <input class="form-input" id="maintenance_temporary_employment_company" name="temporary_employment_company" type="text" inputmode="decimal" autocomplete="off" data-field="temporary_employment_company">
                    <p class="form-error" data-error-for="temporary_employment_company" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="minimum_base" hidden>
                    <label class="form-label" for="maintenance_minimum_base">Base mínima</label>
                    <input class="form-input" id="maintenance_minimum_base" name="minimum_base" type="text" inputmode="decimal" autocomplete="off" data-field="minimum_base">
                    <p class="form-error" data-error-for="minimum_base" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="maximum_base" hidden>
                    <label class="form-label" for="maintenance_maximum_base">Base màxima</label>
                    <input class="form-input" id="maintenance_maximum_base" name="maximum_base" type="text" inputmode="decimal" autocomplete="off" data-field="maximum_base">
                    <p class="form-error" data-error-for="maximum_base" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="period_label" hidden>
                    <label class="form-label" for="maintenance_period_label">Període</label>
                    <input class="form-input" id="maintenance_period_label" name="period_label" type="text" autocomplete="off" data-field="period_label">
                    <p class="form-error" data-error-for="period_label" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="subfunction_id" hidden>
                    <label class="form-label" for="maintenance_subfunction_id">Subfunció <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_subfunction_id" name="subfunction_id" type="text" maxlength="3" inputmode="numeric" autocomplete="off" pattern="[0-9]{1,3}" data-field="subfunction_id">
                    <p class="form-error" data-error-for="subfunction_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="program_number" hidden>
                    <label class="form-label" for="maintenance_program_number">Número <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_program_number" name="program_number" type="text" maxlength="1" inputmode="numeric" autocomplete="off" pattern="[0-9]{1}" data-field="program_number">
                    <p class="form-error" data-error-for="program_number" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="program_computed_code" hidden>
                    <label class="form-label" for="maintenance_program_computed_code">Codi</label>
                    <input class="form-input" id="maintenance_program_computed_code" name="program_computed_code" type="text" readonly data-field="program_computed_code" autocomplete="off">
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="description" hidden>
                    <label class="form-label" for="maintenance_description">Descripció</label>
                    <textarea class="form-input" id="maintenance_description" name="description" rows="4" data-field="description"></textarea>
                    <p class="form-error" data-error-for="description" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="responsible_person_code" hidden>
                    <label class="form-label" for="maintenance_responsible_person_code">Responsable</label>
                    <select class="form-select" id="maintenance_responsible_person_code" name="responsible_person_code" data-field="responsible_person_code">
                        <option value="">— Sense assignar —</option>
                    </select>
                    <p class="form-error" data-error-for="responsible_person_code" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="subprogram_parent_program" hidden>
                    <label class="form-label" for="maintenance_subprogram_program_id">Programa <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_subprogram_program_id" name="program_id" data-field="subprogram_parent_program">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="program_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="subprogram_number" hidden>
                    <label class="form-label" for="maintenance_subprogram_number">Número subprograma <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_subprogram_number" name="subprogram_number" type="text" maxlength="2" inputmode="numeric" autocomplete="off" pattern="[0-9]{1,2}" data-field="subprogram_number">
                    <p class="form-error" data-error-for="subprogram_number" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="subprogram_computed_code" hidden>
                    <label class="form-label" for="maintenance_subprogram_computed_code">Codi</label>
                    <input class="form-input" id="maintenance_subprogram_computed_code" name="subprogram_computed_code" type="text" readonly data-field="subprogram_computed_code" autocomplete="off">
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="technical_manager_code" hidden>
                    <label class="form-label" for="maintenance_technical_manager_code">Responsable tècnic</label>
                    <select class="form-select" id="maintenance_technical_manager_code" name="technical_manager_code" data-field="technical_manager_code">
                        <option value="">— Sense assignar —</option>
                    </select>
                    <p class="form-error" data-error-for="technical_manager_code" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="elected_manager_code" hidden>
                    <label class="form-label" for="maintenance_elected_manager_code">Responsable electe</label>
                    <select class="form-select" id="maintenance_elected_manager_code" name="elected_manager_code" data-field="elected_manager_code">
                        <option value="">— Sense assignar —</option>
                    </select>
                    <p class="form-error" data-error-for="elected_manager_code" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="nature" hidden>
                    <label class="form-label" for="maintenance_nature">Naturalesa</label>
                    <select class="form-select" id="maintenance_nature" name="nature" data-field="nature">
                        <option value="">—</option>
                        <option value="Continuació de serveis">Continuació de serveis</option>
                        <option value="Nou servei">Nou servei</option>
                    </select>
                    <p class="form-error" data-error-for="nature" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="is_mandatory_service" hidden>
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="maintenance_is_mandatory_service" name="is_mandatory_service" value="1" data-field="is_mandatory_service">
                        <span class="form-check__label">Servei obligatori</span>
                    </label>
                </div>
                <div class="form-group" data-maintenance-field="has_corporate_agreements" hidden>
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="maintenance_has_corporate_agreements" name="has_corporate_agreements" value="1" data-field="has_corporate_agreements">
                        <span class="form-check__label">Acords corporatius</span>
                    </label>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="objectives" hidden>
                    <label class="form-label" for="maintenance_objectives">Objectius</label>
                    <textarea class="form-input" id="maintenance_objectives" name="objectives" rows="4" data-field="objectives"></textarea>
                    <p class="form-error" data-error-for="objectives" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="activities" hidden>
                    <label class="form-label" for="maintenance_activities">Activitats</label>
                    <textarea class="form-input" id="maintenance_activities" name="activities" rows="4" data-field="activities"></textarea>
                    <p class="form-error" data-error-for="activities" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="notes" hidden>
                    <label class="form-label" for="maintenance_notes">Observacions</label>
                    <textarea class="form-input" id="maintenance_notes" name="notes" rows="3" data-field="notes"></textarea>
                    <p class="form-error" data-error-for="notes" hidden></p>
                </div>
            </form>
        </div>
    </div>
</div>

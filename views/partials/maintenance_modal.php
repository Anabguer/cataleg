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
                    <?php if (($module ?? '') === 'management_positions'): ?>
                    <button type="button" id="btn-copy-management-position" class="btn btn--secondary btn--sm d-none">Copiar plaça</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn--primary btn--sm" form="maintenance-modal-form" data-maintenance-modal-submit>Desar</button>
                </div>
            </div>
            <form id="maintenance-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate>
                <input type="hidden" name="original_id" data-field="original_id">
                <div class="form-group form-grid__full js-maintenance-msg" hidden>
                    <div class="alert alert--error" role="alert" data-maintenance-form-error></div>
                </div>
                <?php if (($module ?? '') !== 'people'): ?>
                    <div class="form-group" data-maintenance-field="id">
                        <label class="form-label" for="maintenance_id" data-maintenance-label-id>Codi <span class="users-modal-form__req">*</span></label>
                        <input class="form-input" id="maintenance_id" name="id" type="number" min="1" required data-field="id">
                        <p class="form-error" data-error-for="id" hidden></p>
                    </div>
                <?php endif; ?>
                <div class="form-group" data-maintenance-field="name">
                    <label class="form-label" for="maintenance_name" data-maintenance-label-name>Nom <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_name" name="name" type="text" required data-field="name">
                    <p class="form-error" data-error-for="name" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="position_name" hidden>
                    <label class="form-label" for="maintenance_position_name">Denominació <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_position_name" name="position_name" type="text" data-field="position_name">
                    <p class="form-error" data-error-for="position_name" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="position_class_id" hidden>
                    <label class="form-label" for="maintenance_position_class_id">Classe de plaça <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="maintenance_position_class_id" name="position_class_id" data-field="position_class_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="position_class_id" hidden></p>
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
                <div class="form-group" data-maintenance-field="category_id" hidden>
                    <label class="form-label" for="maintenance_category_id">Categoria</label>
                    <select class="form-select" id="maintenance_category_id" name="category_id" data-field="category_id">
                        <option value="">Selecciona…</option>
                    </select>
                    <p class="form-error" data-error-for="category_id" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="labor_category" hidden>
                    <label class="form-label" for="maintenance_labor_category">Categoria laboral</label>
                    <input class="form-input" id="maintenance_labor_category" name="labor_category" type="text" data-field="labor_category">
                    <p class="form-error" data-error-for="labor_category" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="classification_group" hidden>
                    <label class="form-label" for="maintenance_classification_group">Grup classificació</label>
                    <select class="form-select" id="maintenance_classification_group" name="classification_group" data-field="classification_group">
                        <option value="">Selecciona…</option>
                    </select>
                </div>
                <div class="form-group" data-maintenance-field="access_type_id" hidden>
                    <label class="form-label" for="maintenance_access_type_id">Tipus accés</label>
                    <select class="form-select" id="maintenance_access_type_id" name="access_type_id" data-field="access_type_id">
                        <option value="">Selecciona…</option>
                    </select>
                </div>
                <div class="form-group" data-maintenance-field="access_system_id" hidden>
                    <label class="form-label" for="maintenance_access_system_id">Sistema accés</label>
                    <select class="form-select" id="maintenance_access_system_id" name="access_system_id" data-field="access_system_id">
                        <option value="">Selecciona…</option>
                    </select>
                </div>
                <div class="form-group" data-maintenance-field="budgeted_amount" hidden>
                    <label class="form-label" for="maintenance_budgeted_amount">Pressupostat</label>
                    <input class="form-input" id="maintenance_budgeted_amount" name="budgeted_amount" type="text" inputmode="decimal" placeholder="100,00" data-field="budgeted_amount">
                </div>
                <div class="form-group" data-maintenance-field="opo_year" hidden>
                    <label class="form-label" for="maintenance_opo_year">Any OPO</label>
                    <input class="form-input" id="maintenance_opo_year" name="opo_year" type="number" min="1900" max="2999" step="1" data-field="opo_year">
                </div>
                <div class="form-group" data-maintenance-field="created_at" hidden>
                    <label class="form-label" for="maintenance_created_at">Data creació</label>
                    <input class="form-input" id="maintenance_created_at" name="created_at" type="text" placeholder="dd/mm/yyyy" data-field="created_at">
                </div>
                <div class="form-group" data-maintenance-field="call_for_applications_date" hidden>
                    <label class="form-label" for="maintenance_call_for_applications_date">Data convocatòria</label>
                    <input class="form-input" id="maintenance_call_for_applications_date" name="call_for_applications_date" type="text" placeholder="dd/mm/yyyy" data-field="call_for_applications_date">
                </div>
                <div class="form-group" data-maintenance-field="deleted_at" hidden>
                    <label class="form-label" for="maintenance_deleted_at">Data baixa</label>
                    <input class="form-input" id="maintenance_deleted_at" name="deleted_at" type="text" placeholder="dd/mm/yyyy" data-field="deleted_at">
                    <p class="form-error" data-error-for="deleted_at" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="creation_file_reference" hidden>
                    <label class="form-label" for="maintenance_creation_file_reference">Expedient creació</label>
                    <input class="form-input" id="maintenance_creation_file_reference" name="creation_file_reference" type="text" data-field="creation_file_reference">
                </div>
                <div class="form-group" data-maintenance-field="deletion_file_reference" hidden>
                    <label class="form-label" for="maintenance_deletion_file_reference">Expedient baixa</label>
                    <input class="form-input" id="maintenance_deletion_file_reference" name="deletion_file_reference" type="text" data-field="deletion_file_reference">
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
                <div class="form-group" data-maintenance-field="base_salary" hidden>
                    <label class="form-label" for="maintenance_base_salary">Sou base <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_base_salary" name="base_salary" type="text" inputmode="decimal" autocomplete="off" data-field="base_salary">
                    <p class="form-error" data-error-for="base_salary" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="base_salary_extra_pay" hidden>
                    <label class="form-label" for="maintenance_base_salary_extra_pay">Sou base afectació pagues <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_base_salary_extra_pay" name="base_salary_extra_pay" type="text" inputmode="decimal" autocomplete="off" data-field="base_salary_extra_pay">
                    <p class="form-error" data-error-for="base_salary_extra_pay" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="base_salary_new" hidden>
                    <label class="form-label" for="maintenance_base_salary_new">Sou base incrementat</label>
                    <input class="form-input" id="maintenance_base_salary_new" name="base_salary_new" type="text" inputmode="decimal" autocomplete="off" data-field="base_salary_new">
                    <p class="form-error" data-error-for="base_salary_new" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="base_salary_extra_pay_new" hidden>
                    <label class="form-label" for="maintenance_base_salary_extra_pay_new">Sou base afectació pagues incrementat</label>
                    <input class="form-input" id="maintenance_base_salary_extra_pay_new" name="base_salary_extra_pay_new" type="text" inputmode="decimal" autocomplete="off" data-field="base_salary_extra_pay_new">
                    <p class="form-error" data-error-for="base_salary_extra_pay_new" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="destination_allowance" hidden>
                    <label class="form-label" for="maintenance_destination_allowance">Complement destinació <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_destination_allowance" name="destination_allowance" type="text" inputmode="decimal" autocomplete="off" data-field="destination_allowance">
                    <p class="form-error" data-error-for="destination_allowance" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="destination_allowance_new" hidden>
                    <label class="form-label" for="maintenance_destination_allowance_new">Complement destinació incrementat</label>
                    <input class="form-input" id="maintenance_destination_allowance_new" name="destination_allowance_new" type="text" inputmode="decimal" autocomplete="off" data-field="destination_allowance_new">
                    <p class="form-error" data-error-for="destination_allowance_new" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="seniority_amount" hidden>
                    <label class="form-label" for="maintenance_seniority_amount">Trienni <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_seniority_amount" name="seniority_amount" type="text" inputmode="decimal" autocomplete="off" data-field="seniority_amount">
                    <p class="form-error" data-error-for="seniority_amount" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="seniority_extra_pay_amount" hidden>
                    <label class="form-label" for="maintenance_seniority_extra_pay_amount">Trienni afectació pagues <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_seniority_extra_pay_amount" name="seniority_extra_pay_amount" type="text" inputmode="decimal" autocomplete="off" data-field="seniority_extra_pay_amount">
                    <p class="form-error" data-error-for="seniority_extra_pay_amount" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="seniority_amount_new" hidden>
                    <label class="form-label" for="maintenance_seniority_amount_new">Trienni incrementat</label>
                    <input class="form-input" id="maintenance_seniority_amount_new" name="seniority_amount_new" type="text" inputmode="decimal" autocomplete="off" data-field="seniority_amount_new">
                    <p class="form-error" data-error-for="seniority_amount_new" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="seniority_extra_pay_amount_new" hidden>
                    <label class="form-label" for="maintenance_seniority_extra_pay_amount_new">Trienni afectació pagues incrementat</label>
                    <input class="form-input" id="maintenance_seniority_extra_pay_amount_new" name="seniority_extra_pay_amount_new" type="text" inputmode="decimal" autocomplete="off" data-field="seniority_extra_pay_amount_new">
                    <p class="form-error" data-error-for="seniority_extra_pay_amount_new" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="special_specific_compensation_name" hidden>
                    <label class="form-label" for="maintenance_special_specific_compensation_name">Denominació <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_special_specific_compensation_name" name="special_specific_compensation_name" type="text" autocomplete="off" data-field="special_specific_compensation_name">
                    <p class="form-error" data-error-for="special_specific_compensation_name" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="amount" hidden>
                    <label class="form-label" for="maintenance_amount">Complement específic especial <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_amount" name="amount" type="text" inputmode="decimal" autocomplete="off" data-field="amount">
                    <p class="form-error" data-error-for="amount" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="amount_new" hidden>
                    <label class="form-label" for="maintenance_amount_new">Complement específic especial incrementat</label>
                    <input class="form-input" id="maintenance_amount_new" name="amount_new" type="text" inputmode="decimal" autocomplete="off" data-field="amount_new">
                    <p class="form-error" data-error-for="amount_new" hidden></p>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="general_specific_compensation_name" hidden>
                    <label class="form-label" for="maintenance_general_specific_compensation_name">Descripció Complement <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_general_specific_compensation_name" name="general_specific_compensation_name" type="text" autocomplete="off" data-field="general_specific_compensation_name">
                    <p class="form-error" data-error-for="general_specific_compensation_name" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="decrease_amount" hidden>
                    <label class="form-label" for="maintenance_decrease_amount">Import de la disminució Complement Específic de Agents i Caporals (C2-C1) <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="maintenance_decrease_amount" name="decrease_amount" type="text" inputmode="decimal" autocomplete="off" data-field="decrease_amount">
                    <p class="form-error" data-error-for="decrease_amount" hidden></p>
                </div>
                <div class="form-group" data-maintenance-field="decrease_amount_new" hidden>
                    <label class="form-label" for="maintenance_decrease_amount_new">Import de la disminució Complement Específic de Agents i Caporals (C2-C1) incrementat</label>
                    <input class="form-input" id="maintenance_decrease_amount_new" name="decrease_amount_new" type="text" inputmode="decimal" autocomplete="off" data-field="decrease_amount_new">
                    <p class="form-error" data-error-for="decrease_amount_new" hidden></p>
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
                <div class="form-group" data-maintenance-field="is_offerable" hidden>
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="maintenance_is_offerable" name="is_offerable" value="1" data-field="is_offerable">
                        <span class="form-check__label">Ofertable</span>
                    </label>
                </div>
                <div class="form-group" data-maintenance-field="is_to_be_amortized" hidden>
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="maintenance_is_to_be_amortized" name="is_to_be_amortized" value="1" data-field="is_to_be_amortized">
                        <span class="form-check__label">Amortitzar</span>
                    </label>
                </div>
                <div class="form-group" data-maintenance-field="is_internal_promotion" hidden>
                    <label class="form-check">
                        <input type="checkbox" class="form-check__input" id="maintenance_is_internal_promotion" name="is_internal_promotion" value="1" data-field="is_internal_promotion">
                        <span class="form-check__label">Promoció interna</span>
                    </label>
                </div>
                <div class="form-grid--three-cols">
                    <?php if (($module ?? '') === 'people'): ?>
                        <div class="form-group" data-maintenance-field="id" hidden>
                            <label class="form-label" for="maintenance_id" data-maintenance-label-id>Codi <span class="users-modal-form__req">*</span></label>
                            <input class="form-input" id="maintenance_id" name="id" type="number" min="1" required data-field="id">
                            <p class="form-error" data-error-for="id" hidden></p>
                        </div>
                    <?php endif; ?>
                    <div class="form-group" data-maintenance-field="legacy_person_id" hidden>
                        <label class="form-label" for="maintenance_legacy_person_id">Codi antic</label>
                        <input class="form-input" id="maintenance_legacy_person_id" name="legacy_person_id" type="text" inputmode="numeric" data-field="legacy_person_id">
                    </div>
                    <div class="form-group" data-maintenance-field="is_active" hidden>
                        <label class="form-check">
                            <input type="checkbox" class="form-check__input" id="maintenance_is_active" name="is_active" value="1" data-field="is_active" disabled>
                            <span class="form-check__label">Activa</span>
                        </label>
                    </div>
                </div>
                <div class="form-grid--three-cols">
                    <div class="form-group" data-maintenance-field="last_name_1" hidden>
                        <label class="form-label" for="maintenance_last_name_1">1r Cognom <span class="users-modal-form__req">*</span></label>
                        <input class="form-input" id="maintenance_last_name_1" name="last_name_1" type="text" data-field="last_name_1">
                        <p class="form-error" data-error-for="last_name_1" hidden></p>
                    </div>
                    <div class="form-group" data-maintenance-field="last_name_2" hidden>
                        <label class="form-label" for="maintenance_last_name_2">2n Cognom</label>
                        <input class="form-input" id="maintenance_last_name_2" name="last_name_2" type="text" data-field="last_name_2">
                    </div>
                    <div class="form-group" data-maintenance-field="first_name" hidden>
                        <label class="form-label" for="maintenance_first_name">Nom <span class="users-modal-form__req">*</span></label>
                        <input class="form-input" id="maintenance_first_name" name="first_name" type="text" data-field="first_name">
                        <p class="form-error" data-error-for="first_name" hidden></p>
                    </div>
                </div>
                <div class="form-grid--three-cols">
                    <div class="form-group" data-maintenance-field="national_id_number" hidden>
                        <label class="form-label" for="maintenance_national_id_number">DNI</label>
                        <input class="form-input" id="maintenance_national_id_number" name="national_id_number" type="text" data-field="national_id_number">
                    </div>
                    <div class="form-group" data-maintenance-field="birth_date" hidden>
                        <label class="form-label" for="maintenance_birth_date">Data naixement</label>
                        <input class="form-input" id="maintenance_birth_date" name="birth_date" type="text" placeholder="dd/mm/yyyy" data-field="birth_date">
                    </div>
                    <div class="form-group" data-maintenance-field="email" hidden>
                        <label class="form-label" for="maintenance_email">Email</label>
                        <input class="form-input" id="maintenance_email" name="email" type="email" data-field="email">
                    </div>
                </div>
                <div class="form-grid--three-cols">
                    <div class="form-group" data-maintenance-field="social_security_number" hidden>
                        <label class="form-label" for="maintenance_social_security_number">Núm. S.S.</label>
                        <input class="form-input" id="maintenance_social_security_number" name="social_security_number" type="text" data-field="social_security_number">
                    </div>
                    <div class="form-group" data-maintenance-field="status_text" hidden>
                        <label class="form-label" for="maintenance_status_text">Situació</label>
                        <input class="form-input" id="maintenance_status_text" name="status_text" type="text" data-field="status_text">
                    </div>
                    <div class="form-group" data-maintenance-field="social_security_contribution_coefficient" hidden>
                        <label class="form-label" for="maintenance_social_security_contribution_coefficient">Coeficient cotització</label>
                        <input class="form-input" id="maintenance_social_security_contribution_coefficient" name="social_security_contribution_coefficient" type="text" inputmode="decimal" data-field="social_security_contribution_coefficient">
                    </div>
                </div>
                <div class="form-grid--three-cols">
                    <div class="form-group" data-maintenance-field="job_position_id" hidden>
                        <label class="form-label" for="maintenance_job_position_id">Lloc de treball</label>
                        <select class="form-select" id="maintenance_job_position_id" name="job_position_id" data-field="job_position_id">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                    <div class="form-group" data-maintenance-field="people_position_id" hidden>
                        <label class="form-label" for="maintenance_people_position_id">Plaça</label>
                        <select class="form-select" id="maintenance_people_position_id" name="position_id" data-field="people_position_id">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                    <div class="form-group" data-maintenance-field="productivity_bonus" hidden>
                        <label class="form-label" for="maintenance_productivity_bonus">Complement productivitat</label>
                        <input class="form-input form-input--money" id="maintenance_productivity_bonus" name="productivity_bonus" type="text" inputmode="decimal" data-field="productivity_bonus">
                    </div>
                </div>
                <div class="form-grid--three-cols">
                    <div class="form-group" data-maintenance-field="dedication" hidden>
                        <label class="form-label" for="maintenance_dedication">Dedicació</label>
                        <input class="form-input" id="maintenance_dedication" name="dedication" type="text" inputmode="decimal" data-field="dedication">
                    </div>
                    <div class="form-group" data-maintenance-field="people_budgeted_amount" hidden>
                        <label class="form-label" for="maintenance_people_budgeted_amount">Pressupostat</label>
                        <input class="form-input" id="maintenance_people_budgeted_amount" name="budgeted_amount" type="text" inputmode="decimal" data-field="people_budgeted_amount">
                    </div>
                    <div class="form-group" data-maintenance-field="legacy_social_security" hidden>
                        <label class="form-label" for="maintenance_legacy_social_security">Complement personal</label>
                        <input class="form-input form-input--money" id="maintenance_legacy_social_security" name="legacy_social_security" type="text" inputmode="decimal" data-field="legacy_social_security">
                    </div>
                </div>
                <div class="form-grid--two-cols">
                    <div class="form-group" data-maintenance-field="legal_relation_id" hidden>
                        <label class="form-label" for="maintenance_legal_relation_id">Relació jurídica</label>
                        <select class="form-select" id="maintenance_legal_relation_id" name="legal_relation_id" data-field="legal_relation_id">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                    <div class="form-group" data-maintenance-field="administrative_status_id" hidden>
                        <label class="form-label" for="maintenance_administrative_status_id">Situació administrativa</label>
                        <select class="form-select" id="maintenance_administrative_status_id" name="administrative_status_id" data-field="administrative_status_id">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid--two-cols">
                    <div class="form-group" data-maintenance-field="company_id" hidden>
                        <label class="form-label" for="maintenance_company_id">Empresa</label>
                        <select class="form-select" id="maintenance_company_id" name="company_id" data-field="company_id">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                    <div class="form-group" data-maintenance-field="personal_grade" hidden>
                        <label class="form-label" for="maintenance_personal_grade">Grau personal</label>
                        <select class="form-select" id="maintenance_personal_grade" name="personal_grade" data-field="personal_grade">
                            <option value="">Selecciona…</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid--two-cols">
                    <div class="form-group" data-maintenance-field="hired_at" hidden>
                        <label class="form-label" for="maintenance_hired_at">Data alta</label>
                        <input class="form-input" id="maintenance_hired_at" name="hired_at" type="text" placeholder="dd/mm/yyyy" data-field="hired_at">
                    </div>
                    <div class="form-group" data-maintenance-field="terminated_at" hidden>
                        <label class="form-label" for="maintenance_terminated_at">Data baixa</label>
                        <input class="form-input" id="maintenance_terminated_at" name="terminated_at" type="text" placeholder="dd/mm/yyyy" data-field="terminated_at">
                    </div>
                </div>
                <div class="form-grid--one-col" data-maintenance-field="people_seniority_block" hidden>
                    <div class="people-seniority-block">
                        <h3 class="people-seniority-block__title">Antiguitat (Triennis)</h3>

                        <div class="people-seniority-group" data-people-seniority-group="A1">
                            <div class="people-seniority-group__label">Grup A1</div>
                            <div class="people-seniority-grid">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_previous_triennia">Triennis anteriors</label>
                                    <input class="form-input" id="maintenance_group_a1_previous_triennia" name="group_a1_previous_triennia" type="number" min="0" step="1" data-field="group_a1_previous_triennia" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_mes">Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a1_mes" type="text" data-people-seniority-monthly="A1" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_paga">Paga</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a1_paga" type="text" data-people-seniority-extra="A1" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_current_year_percentage">% Trienni any</label>
                                    <input class="form-input" id="maintenance_group_a1_current_year_percentage" name="group_a1_current_year_percentage" type="text" inputmode="decimal" data-field="group_a1_current_year_percentage" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_current_year_triennia">Triennis any</label>
                                    <input class="form-input" id="maintenance_group_a1_current_year_triennia" name="group_a1_current_year_triennia" type="number" min="0" step="1" data-field="group_a1_current_year_triennia" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a1_total">Total</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a1_total" type="text" data-people-seniority-total="A1" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="people-seniority-group" data-people-seniority-group="A2">
                            <div class="people-seniority-group__label">Grup A2</div>
                            <div class="people-seniority-grid">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_previous_triennia">Triennis anteriors</label>
                                    <input class="form-input" id="maintenance_group_a2_previous_triennia" name="group_a2_previous_triennia" type="number" min="0" step="1" data-field="group_a2_previous_triennia" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_mes">Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a2_mes" type="text" data-people-seniority-monthly="A2" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_paga">Paga</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a2_paga" type="text" data-people-seniority-extra="A2" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_current_year_percentage">% Trienni any</label>
                                    <input class="form-input" id="maintenance_group_a2_current_year_percentage" name="group_a2_current_year_percentage" type="text" inputmode="decimal" data-field="group_a2_current_year_percentage" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_current_year_triennia">Triennis any</label>
                                    <input class="form-input" id="maintenance_group_a2_current_year_triennia" name="group_a2_current_year_triennia" type="number" min="0" step="1" data-field="group_a2_current_year_triennia" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_a2_total">Total</label>
                                    <input class="form-input form-input--money" id="maintenance_group_a2_total" type="text" data-people-seniority-total="A2" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="people-seniority-group" data-people-seniority-group="C1">
                            <div class="people-seniority-group__label">Grup C1</div>
                            <div class="people-seniority-grid">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_previous_triennia">Triennis anteriors</label>
                                    <input class="form-input" id="maintenance_group_c1_previous_triennia" name="group_c1_previous_triennia" type="number" min="0" step="1" data-field="group_c1_previous_triennia" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_mes">Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c1_mes" type="text" data-people-seniority-monthly="C1" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_paga">Paga</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c1_paga" type="text" data-people-seniority-extra="C1" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_current_year_percentage">% Trienni any</label>
                                    <input class="form-input" id="maintenance_group_c1_current_year_percentage" name="group_c1_current_year_percentage" type="text" inputmode="decimal" data-field="group_c1_current_year_percentage" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_current_year_triennia">Triennis any</label>
                                    <input class="form-input" id="maintenance_group_c1_current_year_triennia" name="group_c1_current_year_triennia" type="number" min="0" step="1" data-field="group_c1_current_year_triennia" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c1_total">Total</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c1_total" type="text" data-people-seniority-total="C1" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="people-seniority-group" data-people-seniority-group="C2">
                            <div class="people-seniority-group__label">Grup C2</div>
                            <div class="people-seniority-grid">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_previous_triennia">Triennis anteriors</label>
                                    <input class="form-input" id="maintenance_group_c2_previous_triennia" name="group_c2_previous_triennia" type="number" min="0" step="1" data-field="group_c2_previous_triennia" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_mes">Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c2_mes" type="text" data-people-seniority-monthly="C2" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_paga">Paga</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c2_paga" type="text" data-people-seniority-extra="C2" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_current_year_percentage">% Trienni any</label>
                                    <input class="form-input" id="maintenance_group_c2_current_year_percentage" name="group_c2_current_year_percentage" type="text" inputmode="decimal" data-field="group_c2_current_year_percentage" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_current_year_triennia">Triennis any</label>
                                    <input class="form-input" id="maintenance_group_c2_current_year_triennia" name="group_c2_current_year_triennia" type="number" min="0" step="1" data-field="group_c2_current_year_triennia" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_c2_total">Total</label>
                                    <input class="form-input form-input--money" id="maintenance_group_c2_total" type="text" data-people-seniority-total="C2" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="people-seniority-group" data-people-seniority-group="E">
                            <div class="people-seniority-group__label">Grup E</div>
                            <div class="people-seniority-grid">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_previous_triennia">Triennis anteriors</label>
                                    <input class="form-input" id="maintenance_group_e_previous_triennia" name="group_e_previous_triennia" type="number" min="0" step="1" data-field="group_e_previous_triennia" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_mes">Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_group_e_mes" type="text" data-people-seniority-monthly="E" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_paga">Paga</label>
                                    <input class="form-input form-input--money" id="maintenance_group_e_paga" type="text" data-people-seniority-extra="E" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_current_year_percentage">% Trienni any</label>
                                    <input class="form-input" id="maintenance_group_e_current_year_percentage" name="group_e_current_year_percentage" type="text" inputmode="decimal" data-field="group_e_current_year_percentage" data-people-seniority-edit="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_current_year_triennia">Triennis any</label>
                                    <input class="form-input" id="maintenance_group_e_current_year_triennia" name="group_e_current_year_triennia" type="number" min="0" step="1" data-field="group_e_current_year_triennia" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_group_e_total">Total</label>
                                    <input class="form-input form-input--money" id="maintenance_group_e_total" type="text" data-people-seniority-total="E" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="people-seniority-accum">
                            <div class="people-seniority-accum__row">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_seniority_amount_current">Antiguitat pressupost (BBDD)</label>
                                    <input class="form-input form-input--money" id="maintenance_people_seniority_amount_current" type="text" data-people-seniority-db="seniority_amount" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_seniority_budget_calc">Antiguitat pressupost calculada</label>
                                    <input class="form-input form-input--money" id="maintenance_people_seniority_budget_calc" type="text" data-people-seniority-accum="budget" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_seniority_extra_calc">Antiguitat pagues</label>
                                    <input class="form-input form-input--money" id="maintenance_people_seniority_extra_calc" type="text" data-people-seniority-accum="extra" readonly>
                                </div>
                            </div>
                            <div class="people-seniority-accum__row">
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_seniority_monthly_calc">Antiguitat Mes</label>
                                    <input class="form-input form-input--money" id="maintenance_people_seniority_monthly_calc" type="text" data-people-seniority-accum="monthly" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_annual_budgeted_seniority_current">Antiguitat anyal pressupost (BBDD)</label>
                                    <input class="form-input form-input--money" id="maintenance_people_annual_budgeted_seniority_current" type="text" data-people-seniority-db="annual_budgeted_seniority" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="maintenance_people_annual_budgeted_seniority_calc">Antiguitat anyal pressupost calculada</label>
                                    <input class="form-input form-input--money" id="maintenance_people_annual_budgeted_seniority_calc" type="text" data-people-seniority-accum="annual" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-grid--one-col">
                    <div class="form-group" data-maintenance-field="notes" hidden>
                        <label class="form-label" for="maintenance_notes">Observacions</label>
                        <textarea class="form-input" id="maintenance_notes" name="notes" rows="3" data-field="notes"></textarea>
                        <p class="form-error" data-error-for="notes" hidden></p>
                    </div>
                </div>
                <div class="form-group form-grid__full" data-maintenance-field="subprogram_people" hidden>
                    <label class="form-label">Subprogrames</label>
                    <table class="data-table data-table--compact">
                        <thead>
                        <tr><th>Subprograma</th><th>Dedicació %</th><th></th></tr>
                        </thead>
                        <tbody data-people-subprogram-rows></tbody>
                    </table>
                    <button type="button" class="btn btn--ghost btn--sm" data-people-subprogram-add>Afegir fila</button>
                    <p class="form-error" data-error-for="subprogram_people" hidden></p>
                </div>
            </form>
        </div>
    </div>
</div>

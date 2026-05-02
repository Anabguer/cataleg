<?php
declare(strict_types=1);
/** @var string $module */
?>
<div class="reports-modal" data-maintenance-field="reports_fields" hidden>
    <input type="hidden" name="id" value="" data-field="id" id="maintenance_reports_id">
    <div class="form-grid form-grid--two-cols reports-row">
        <div class="form-group">
            <label class="form-label" for="maintenance_reports_group">Grup <span class="users-modal-form__req">*</span></label>
            <input class="form-input" id="maintenance_reports_group" name="report_group" type="text" maxlength="120" required data-field="report_group" autocomplete="off">
            <p class="form-error" data-error-for="report_group" hidden></p>
        </div>
        <div class="form-group">
            <label class="form-label" for="maintenance_reports_group_order">Ordre grup</label>
            <input class="form-input" id="maintenance_reports_group_order" name="report_group_order" type="number" step="1" value="0" data-field="report_group_order" autocomplete="off">
            <p class="form-error" data-error-for="report_group_order" hidden></p>
        </div>
    </div>
    <div class="form-grid form-grid--two-cols reports-row">
        <div class="form-group">
            <label class="form-label" for="maintenance_reports_code">Codi informe <span class="users-modal-form__req">*</span></label>
            <input class="form-input" id="maintenance_reports_code" name="report_code" type="text" maxlength="64" required data-field="report_code" autocomplete="off">
            <p class="form-error" data-error-for="report_code" hidden></p>
        </div>
        <div class="form-group">
            <label class="form-label" for="maintenance_reports_name">Nom informe <span class="users-modal-form__req">*</span></label>
            <input class="form-input" id="maintenance_reports_name" name="report_name" type="text" maxlength="200" required data-field="report_name" autocomplete="off">
            <p class="form-error" data-error-for="report_name" hidden></p>
        </div>
    </div>
    <div class="form-grid form-grid--three-cols reports-row">
        <div class="form-group">
            <label class="form-label" for="maintenance_reports_version">Versió</label>
            <input class="form-input" id="maintenance_reports_version" name="report_version" type="text" maxlength="32" data-field="report_version" autocomplete="off">
            <p class="form-error" data-error-for="report_version" hidden></p>
        </div>
        <div class="form-group">
            <label class="form-check" for="maintenance_reports_show_gen">
                <input type="checkbox" class="form-check__input" id="maintenance_reports_show_gen" name="show_in_general_selector" value="1" data-field="show_in_general_selector" checked>
                <span class="form-check__label">Selector general</span>
            </label>
            <p class="form-error" data-error-for="show_in_general_selector" hidden></p>
        </div>
        <div class="form-group">
            <label class="form-check" for="maintenance_reports_active">
                <input type="checkbox" class="form-check__input" id="maintenance_reports_active" name="is_active" value="1" data-field="is_active" checked>
                <span class="form-check__label">Actiu</span>
            </label>
            <p class="form-error" data-error-for="is_active" hidden></p>
        </div>
    </div>
    <div class="form-grid form-grid--one-col reports-row">
        <div class="form-group form-grid__full">
            <label class="form-label" for="maintenance_reports_description">Descripció</label>
            <textarea class="form-input reports-textarea" id="maintenance_reports_description" name="report_description" rows="5" data-field="report_description"></textarea>
            <p class="form-error" data-error-for="report_description" hidden></p>
        </div>
    </div>
    <div class="form-grid form-grid--one-col reports-row">
        <div class="form-group form-grid__full">
            <label class="form-label" for="maintenance_reports_explanation">Explicació funcionament</label>
            <textarea class="form-input reports-textarea reports-explanation" id="maintenance_reports_explanation" name="report_explanation" rows="6" data-field="report_explanation"></textarea>
            <p class="form-error" data-error-for="report_explanation" hidden></p>
        </div>
    </div>
</div>

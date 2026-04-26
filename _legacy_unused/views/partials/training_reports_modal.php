<?php declare(strict_types=1); ?>
<div id="training-reports-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-reports-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="training-reports-modal-title" data-training-reports-modal-heading>Nou informe</h2>
                        <p class="users-modal-form__subtitle" data-training-reports-modal-subheading>Introdueix les dades de l’informe</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-training-reports-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="training-reports-modal-form">Desar</button>
                </div>
            </div>
            <form id="training-reports-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-reports-msg" hidden><div class="alert alert--error" role="alert" data-training-reports-form-error></div></div>

                <div class="form-group">
                    <label class="form-label" for="training_reports_report_code">Codi <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="training_reports_report_code" name="report_code" type="text" maxlength="64" required data-field="report_code" autocomplete="off">
                    <p class="form-error" data-error-for="report_code" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="training_reports_report_version">Versió</label>
                    <input class="form-input" id="training_reports_report_version" name="report_version" type="text" maxlength="32" data-field="report_version" placeholder="p. ex. 1.0">
                    <p class="form-error" data-error-for="report_version" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="training_reports_report_name">Nom <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="training_reports_report_name" name="report_name" type="text" maxlength="200" required data-field="report_name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="report_name" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="training_reports_report_description">Descripció</label>
                    <textarea class="form-input" id="training_reports_report_description" name="report_description" rows="4" maxlength="2000" data-field="report_description" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="report_description" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="training_reports_report_explanation">Explicació del funcionament</label>
                    <textarea class="form-input" id="training_reports_report_explanation" name="report_explanation" rows="8" data-field="report_explanation" lang="ca" spellcheck="true" placeholder="Text opcional que es mostra al final de l’informe (HTML/PDF)."></textarea>
                    <p class="form-error" data-error-for="report_explanation" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="training_reports_display_order">Ordre</label>
                    <input class="form-input" id="training_reports_display_order" name="display_order" type="number" min="0" max="9999" step="1" data-field="display_order" value="0">
                    <p class="form-error" data-error-for="display_order" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch"><input type="checkbox" id="training_reports_show_in_general_selector" name="show_in_general_selector" value="1" checked><span>Mostrar al selector general</span></label>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch"><input type="checkbox" id="training_reports_is_active" name="is_active" value="1" checked><span>Actiu</span></label>
                </div>
            </form>
        </div>
    </div>
</div>

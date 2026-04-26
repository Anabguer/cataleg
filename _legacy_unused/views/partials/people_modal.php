<?php
declare(strict_types=1);
/** @var list<array{id:int,unit_code:int,position_number:int,name:string}> $jobPositionsFormModal */
$jobPositionsFormModal = $jobPositionsFormModal ?? [];
?>
<div id="people-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="people-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="people-modal-title" data-people-modal-heading>Nova persona</h2>
                        <p class="users-modal-form__subtitle" data-people-modal-subheading>Introdueix les dades de la persona</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-people-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="people-modal-form" data-people-submit-btn>Desar</button>
                </div>
            </div>
            <form id="people-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-people-msg" hidden>
                    <div class="alert alert--error" role="alert" data-people-form-error></div>
                </div>
                <div class="form-group form-grid__full js-people-catalog-banner" hidden>
                    <div class="alert alert--warning" role="status"><?= e('No es pot modificar un registre de catàleg des d’aquest formulari.') ?></div>
                </div>
                <div class="form-group form-grid__full js-people-create-code-block">
                    <p class="form-label muted"><?= e('Codi de persona') ?></p>
                    <p class="page-header__title" data-people-code-preview aria-live="polite">—</p>
                    <p class="muted"><?= e('Es generarà automàticament (sèrie ≥ 80000). Només visualització en l’alta.') ?></p>
                </div>
                <div class="form-group form-grid__full js-people-edit-code-row" hidden>
                    <label class="form-label" for="people_person_code_ro"><?= e('Codi de persona') ?></label>
                    <input class="form-input" id="people_person_code_ro" type="text" readonly tabindex="-1" data-field="person_code_display" data-people-person-code-readonly>
                </div>
                <div class="form-group">
                    <label class="form-label" for="people_last_name_1"><?= e('Primer cognom') ?> <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="people_last_name_1" name="last_name_1" type="text" maxlength="150" required data-field="last_name_1" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="last_name_1" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="people_last_name_2"><?= e('Segon cognom') ?></label>
                    <input class="form-input" id="people_last_name_2" name="last_name_2" type="text" maxlength="150" data-field="last_name_2" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="last_name_2" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="people_first_name"><?= e('Nom') ?> <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="people_first_name" name="first_name" type="text" maxlength="150" required data-field="first_name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="first_name" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="people_dni"><?= e('DNI') ?></label>
                    <input class="form-input" id="people_dni" name="dni" type="text" maxlength="20" data-field="dni" autocomplete="off">
                    <p class="form-error" data-error-for="dni" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="people_email"><?= e('Correu electrònic') ?></label>
                    <input class="form-input" id="people_email" name="email" type="email" maxlength="150" data-field="email" lang="ca" autocomplete="email">
                    <p class="form-error" data-error-for="email" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="people_job_position_id"><?= e('Lloc de treball') ?></label>
                    <select class="form-select" id="people_job_position_id" name="job_position_id" data-field="job_position_id">
                        <option value=""><?= e('— Cap —') ?></option>
                        <?php foreach ($jobPositionsFormModal as $jp): ?>
                            <option value="<?= (int) $jp['id'] ?>"><?= e(format_job_position_code((int) $jp['unit_code'], (int) $jp['position_number']) . ' — ' . (string) $jp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-error" data-error-for="job_position_id" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch">
                        <input type="checkbox" id="people_is_active" name="is_active" value="1" checked data-field="is_active">
                        <span><?= e('Actiu') ?></span>
                    </label>
                </div>
            </form>
        </div>
    </div>
</div>

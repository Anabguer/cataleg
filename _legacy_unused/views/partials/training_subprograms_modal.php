<?php declare(strict_types=1); ?>
<div id="training-subprograms-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-subprograms-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="training-subprograms-modal-title" data-training-subprograms-modal-heading>Nou subprograma</h2><p class="users-modal-form__subtitle" data-training-subprograms-modal-subheading>Introdueix les dades del subprograma</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-training-subprograms-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="training-subprograms-modal-form">Desar</button></div>
            </div>
            <form id="training-subprograms-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-subprograms-msg" hidden><div class="alert alert--error" role="alert" data-training-subprograms-form-error></div></div>
                <div class="form-group"><label class="form-label" for="training_subprograms_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_subprograms_code" name="subprogram_code" type="number" min="1" required data-field="subprogram_code"><p class="form-error" data-error-for="subprogram_code" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="training_subprograms_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_subprograms_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="training_subprograms_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="training_subprograms_training_type">Tipus de formació</label>
                    <select class="form-input" id="training_subprograms_training_type" name="training_type">
                        <option value="programmed">Programada</option>
                        <option value="non_programmed">No programada</option>
                        <option value="personal">Personal</option>
                    </select>
                    <p class="form-error" data-error-for="training_type" hidden></p>
                </div>
            </form>
        </div>
    </div>
</div>

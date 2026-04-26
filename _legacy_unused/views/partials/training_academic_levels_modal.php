<?php declare(strict_types=1); ?>
<div id="training-academic-levels-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-academic-levels-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="training-academic-levels-modal-title" data-training-academic-levels-modal-heading>Nou nivell acadèmic</h2><p class="users-modal-form__subtitle" data-training-academic-levels-modal-subheading>Introdueix les dades del nivell acadèmic</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-training-academic-levels-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="training-academic-levels-modal-form">Desar</button></div>
            </div>
            <form id="training-academic-levels-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-academic-levels-msg" hidden><div class="alert alert--error" role="alert" data-training-academic-levels-form-error></div></div>
                <div class="form-group"><label class="form-label" for="training_academic_levels_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_academic_levels_code" name="academic_level_code" type="number" min="0" required data-field="academic_level_code"><p class="form-error" data-error-for="academic_level_code" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="training_academic_levels_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_academic_levels_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="training_academic_levels_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

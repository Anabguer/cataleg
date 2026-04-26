<?php declare(strict_types=1); ?>
<div id="training-organizers-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-organizers-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="training-organizers-modal-title" data-training-organizers-modal-heading>Nou organitzador</h2><p class="users-modal-form__subtitle" data-training-organizers-modal-subheading>Introdueix les dades de l'organitzador</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-training-organizers-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="training-organizers-modal-form">Desar</button></div>
            </div>
            <form id="training-organizers-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-organizers-msg" hidden><div class="alert alert--error" role="alert" data-training-organizers-form-error></div></div>
                <div class="form-group"><label class="form-label" for="training_organizers_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_organizers_code" name="organizer_code" type="number" min="1" required data-field="organizer_code"><p class="form-error" data-error-for="organizer_code" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="training_organizers_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_organizers_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="training_organizers_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

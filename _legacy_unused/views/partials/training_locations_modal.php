<?php declare(strict_types=1); ?>
<div id="training-locations-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-locations-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="training-locations-modal-title" data-training-locations-modal-heading>Nou lloc d'impartició</h2><p class="users-modal-form__subtitle" data-training-locations-modal-subheading>Introdueix les dades del lloc d'impartició</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-training-locations-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="training-locations-modal-form">Desar</button></div>
            </div>
            <form id="training-locations-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-locations-msg" hidden><div class="alert alert--error" role="alert" data-training-locations-form-error></div></div>
                <div class="form-group"><label class="form-label" for="training_locations_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_locations_code" name="location_code" type="number" min="1" required data-field="location_code"><p class="form-error" data-error-for="location_code" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="training_locations_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="training_locations_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="training_locations_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

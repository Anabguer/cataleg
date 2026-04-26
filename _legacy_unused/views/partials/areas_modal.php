<?php
declare(strict_types=1);
?>
<div id="areas-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="areas-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div><h2 class="users-modal-form__title" id="areas-modal-title" data-areas-modal-heading>Nova àrea</h2><p class="users-modal-form__subtitle" data-areas-modal-subheading>Introdueix les dades de l’àrea</p></div>
                </div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-areas-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="areas-modal-form">Desar</button></div>
            </div>
            <form id="areas-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-areas-msg" hidden><div class="alert alert--error" role="alert" data-areas-form-error></div></div>
                <div class="form-group"><label class="form-label" for="areas_area_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="areas_area_code" name="area_code" type="number" min="0" max="9" required data-field="area_code"><p class="form-error" data-error-for="area_code" hidden></p></div>
                <div class="form-group"><label class="form-label" for="areas_alias">Àlies <span class="users-modal-form__req">*</span></label><input class="form-input" id="areas_alias" name="alias" type="text" maxlength="150" required data-field="alias" lang="ca" spellcheck="true"><p class="form-error" data-error-for="alias" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="areas_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="areas_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="areas_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

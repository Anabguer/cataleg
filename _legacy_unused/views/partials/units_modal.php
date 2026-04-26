<?php declare(strict_types=1); ?>
<div id="units-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="units-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="units-modal-title" data-units-modal-heading>Nova unitat</h2><p class="users-modal-form__subtitle" data-units-modal-subheading>Introdueix les dades de la unitat</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-units-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="units-modal-form">Desar</button></div>
            </div>
            <form id="units-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-units-msg" hidden><div class="alert alert--error" role="alert" data-units-form-error></div></div>
                <div class="form-group"><label class="form-label" for="units_unit_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="units_unit_code" name="unit_code" type="number" min="1" required data-field="unit_code"><p class="form-error" data-error-for="unit_code" hidden></p></div>
                <div class="form-group"><label class="form-label" for="units_section_id">Secció <span class="users-modal-form__req">*</span></label><select class="form-select" id="units_section_id" name="section_id" required data-field="section_id"><option value="">Selecciona</option><?php foreach($sectionsSelect as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e(format_padded_code((int)$s['area_code'],1) . '.' . format_padded_code((int)$s['section_code'],2) . ' - ' . (string)$s['name']) ?></option><?php endforeach; ?></select><p class="form-error" data-error-for="section_id" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="units_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="units_name" name="name" type="text" maxlength="180" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="units_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

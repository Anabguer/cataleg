<?php declare(strict_types=1); ?>
<div id="sections-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="sections-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="sections-modal-title" data-sections-modal-heading>Nova secció</h2><p class="users-modal-form__subtitle" data-sections-modal-subheading>Introdueix les dades de la secció</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-sections-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="sections-modal-form">Desar</button></div>
            </div>
            <form id="sections-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-sections-msg" hidden><div class="alert alert--error" role="alert" data-sections-form-error></div></div>
                <div class="form-group"><label class="form-label" for="sections_section_code">Codi <span class="users-modal-form__req">*</span></label><input class="form-input" id="sections_section_code" name="section_code" type="number" min="1" required data-field="section_code"><p class="form-error" data-error-for="section_code" hidden></p></div>
                <div class="form-group"><label class="form-label" for="sections_area_id">Àrea <span class="users-modal-form__req">*</span></label><select class="form-select" id="sections_area_id" name="area_id" required data-field="area_id"><option value="">Selecciona</option><?php foreach($areasSelect as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e(format_padded_code((int)$a['area_code'],1).' - '.(string)$a['name']) ?></option><?php endforeach; ?></select><p class="form-error" data-error-for="area_id" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="sections_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="sections_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="sections_is_active" name="is_active" value="1" checked><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

<?php declare(strict_types=1); ?>
<div id="knowledge-areas-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="knowledge-areas-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start"><span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span><div><h2 class="users-modal-form__title" id="knowledge-areas-modal-title" data-knowledge-areas-modal-heading>Nova àrea</h2><p class="users-modal-form__subtitle" data-knowledge-areas-modal-subheading>Introdueix les dades de l’àrea</p></div></div>
                <div class="users-modal-form__header-actions"><button type="button" class="btn btn--ghost btn--sm" data-knowledge-areas-modal-close>Cancel·lar</button><button type="submit" class="btn btn--primary btn--sm" form="knowledge-areas-modal-form">Desar</button></div>
            </div>
            <form id="knowledge-areas-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca" enctype="multipart/form-data" method="post">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-knowledge-areas-msg" hidden><div class="alert alert--error" role="alert" data-knowledge-areas-form-error></div></div>
                <div class="form-group"><label class="form-label" for="ka_code_display">Codi</label><input class="form-input" id="ka_code_display" name="code_display" type="text" readonly autocomplete="off" data-field="code_display" value=""><p class="form-hint muted">S’assigna automàticament en crear el registre.</p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="ka_name">Nom <span class="users-modal-form__req">*</span></label><input class="form-input" id="ka_name" name="name" type="text" maxlength="150" required data-field="name" lang="ca" spellcheck="true"><p class="form-error" data-error-for="name" hidden></p></div>
                <div class="form-group form-grid__full"><label class="form-label" for="ka_image">Imatge</label><input class="visually-hidden" id="ka_image" name="image" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" data-field="image" tabindex="-1"><label for="ka_image" class="users-modal-form__file-btn">Seleccionar fitxer</label><p class="form-error" data-error-for="image" hidden></p><p class="form-hint muted">Només fitxers JPG, PNG o WEBP vàlids (no SVG ni altres formats). Opcional en crear.</p>
                    <div class="knowledge-areas-modal-preview" data-ka-preview-wrap hidden><p class="form-hint" data-ka-preview-caption>Vista prèvia</p><img src="" alt="" class="knowledge-areas-modal-preview__img" data-ka-preview-img width="160" height="160" decoding="async"><p class="muted form-hint" data-ka-image-label hidden></p></div>
                </div>
                <div class="form-group form-grid__full" data-ka-remove-wrap hidden><label class="users-switch"><input type="checkbox" id="ka_remove_image" name="remove_image" value="1" data-field="remove_image"><span>Eliminar imatge actual</span></label></div>
                <div class="form-group form-grid__full"><label class="users-switch"><input type="checkbox" id="ka_is_active" name="is_active" value="1" checked data-field="is_active_flag"><span>Actiu</span></label></div>
            </form>
        </div>
    </div>
</div>

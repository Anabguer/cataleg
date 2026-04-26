<?php
declare(strict_types=1);
?>
<div id="roles-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="roles-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud" data-roles-modal-panel>
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="roles-modal-title" data-roles-modal-heading>Nou rol</h2>
                        <p class="users-modal-form__subtitle" data-roles-modal-subheading>Introdueix les dades del rol</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-roles-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="roles-modal-form" data-roles-modal-submit>Desar</button>
                </div>
            </div>
            <form id="roles-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" value="" data-field="id">
                <div class="form-group form-grid__full js-roles-msg" hidden>
                    <div class="alert alert--error" role="alert" data-roles-form-error></div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="roles_name">Nom <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="roles_name" name="name" type="text" required maxlength="100" autocomplete="off" data-field="name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="name" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="roles_slug">Codi (slug) <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="roles_slug" name="slug" type="text" required maxlength="100" autocomplete="off" data-field="slug" placeholder="Ex: training_manager" lang="ca" spellcheck="false">
                    <p class="form-error" data-error-for="slug" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="roles_description">Descripció</label>
                    <textarea class="form-input" id="roles_description" name="description" rows="3" data-field="description" placeholder="Opcional" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="description" hidden></p>
                </div>
            </form>
        </div>
    </div>
</div>


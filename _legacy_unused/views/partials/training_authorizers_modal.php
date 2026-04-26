<?php declare(strict_types=1); ?>
<div id="training-authorizers-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-authorizers-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="training-authorizers-modal-title" data-training-authorizers-modal-heading>Nou autoritzador</h2>
                        <p class="users-modal-form__subtitle" data-training-authorizers-modal-subheading>Introdueix les dades de l’autoritzador</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-training-authorizers-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="training-authorizers-modal-form">Desar</button>
                </div>
            </div>
            <form id="training-authorizers-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-authorizers-msg" hidden><div class="alert alert--error" role="alert" data-training-authorizers-form-error></div></div>
                <div class="form-group">
                    <label class="form-label" for="training_authorizers_area_id">Àrea <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="training_authorizers_area_id" name="area_id" required data-field="area_id">
                        <option value="">Selecciona</option>
                        <?php foreach($areasSelect as $a): ?>
                            <option value="<?= (int)$a['id'] ?>"><?= e(format_padded_code((int)$a['area_code'],1).' - '.(string)$a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-error" data-error-for="area_id" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="training_authorizers_full_name">Nom complet <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="training_authorizers_full_name" name="full_name" type="text" maxlength="150" required data-field="full_name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="full_name" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch"><input type="checkbox" id="training_authorizers_is_active" name="is_active" value="1" checked><span>Actiu</span></label>
                </div>
            </form>
        </div>
    </div>
</div>

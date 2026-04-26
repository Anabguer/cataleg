<?php
declare(strict_types=1);
/** @var list<array{id:int,name:string,slug:string}> $roles */
?>
<div id="users-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="users-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud" data-users-modal-panel>
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="users-modal-title" data-users-modal-heading>Nou usuari</h2>
                        <p class="users-modal-form__subtitle" data-users-modal-subheading>Introdueix les dades de l’usuari</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-users-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="users-modal-form" data-users-modal-submit>Desar</button>
                </div>
            </div>
            <form id="users-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" value="" data-field="id">
                <div class="form-group form-grid__full js-users-msg" hidden>
                    <div class="alert alert--error" role="alert" data-users-form-error></div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="users_username">Nom d’usuari <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="users_username" name="username" type="text" required maxlength="64" autocomplete="username" data-field="username" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="username" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="users_full_name">Nom complet <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <input class="form-input" id="users_full_name" name="full_name" type="text" required maxlength="150" autocomplete="name" data-field="full_name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="full_name" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="users_email">Correu</label>
                    <input class="form-input" id="users_email" name="email" type="email" maxlength="150" autocomplete="email" data-field="email" placeholder="Opcional">
                    <p class="form-error" data-error-for="email" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="users_role_id">Rol <span class="users-modal-form__req" aria-hidden="true">*</span></label>
                    <select class="form-select" id="users_role_id" name="role_id" required data-field="role_id">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id'] ?>"><?= e((string) $r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-error" data-error-for="role_id" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="users_password"><span data-users-password-label>Contrasenya</span> <span class="users-modal-form__req" aria-hidden="true" data-users-password-req>*</span></label>
                    <input class="form-input" id="users_password" name="password" type="password" autocomplete="new-password" data-field="password" placeholder="Mínim 8 caràcters">
                    <p class="form-error" data-error-for="password" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="users_password_confirm">Confirmació de contrasenya <span class="users-modal-form__req" aria-hidden="true" data-users-password2-req>*</span></label>
                    <input class="form-input" id="users_password_confirm" name="password_confirm" type="password" autocomplete="new-password" data-field="password_confirm">
                    <p class="form-error" data-error-for="password_confirm" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <div class="form-check">
                        <input class="form-check__input" type="checkbox" id="users_is_active" name="is_active" value="1" checked data-field="is_active">
                        <label class="form-check__label" for="users_is_active">Usuari actiu</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

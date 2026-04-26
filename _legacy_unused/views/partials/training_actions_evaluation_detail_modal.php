<?php
declare(strict_types=1);
?>
<div id="training-actions-evaluation-detail-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="ta-eval-detail-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form modal__dialog--training-actions training-actions-ui-standard">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('chart') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="ta-eval-detail-title">Detall de l’avaluació</h2>
                        <p class="users-modal-form__subtitle" data-ta-eval-detail-sub></p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-ta-eval-detail-close>Tancar</button>
                </div>
            </div>
            <div class="users-modal-form__body ta-eval-detail-body" data-ta-eval-detail-body>
                <p class="muted">Carregant…</p>
            </div>
        </div>
    </div>
</div>

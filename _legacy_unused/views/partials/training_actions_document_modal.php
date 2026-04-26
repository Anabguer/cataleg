<?php
declare(strict_types=1);
?>
<div id="training-actions-document-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="ta-doc-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form modal__dialog--training-actions-document training-actions-ui-standard">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('document') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="ta-doc-modal-title" data-ta-doc-modal-title>Document</h2>
                        <p class="users-modal-form__subtitle" data-ta-doc-modal-sub></p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-ta-doc-close>Cancel·lar</button>
                    <button type="button" class="btn btn--primary btn--sm" data-ta-doc-save>Desar</button>
                </div>
            </div>
            <div class="users-modal-form__body" id="ta-doc-form-body">
                <input type="hidden" id="ta_doc_id" value="" data-ta-doc-field="id">
                <input type="hidden" id="ta_doc_training_action_id" value="" data-ta-doc-field="training_action_id">

                <div class="form-group ta-doc-file-row" data-ta-doc-file-row>
                    <label class="form-label" for="ta_doc_file">Fitxer <span class="users-modal-form__req" data-ta-doc-file-req>*</span></label>
                    <input type="file" id="ta_doc_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.webp" autocomplete="off">
                    <p class="muted training-actions-modal__hint">PDF o imatge (màx. 10 MB).</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ta_doc_file_name">Nom del document <span class="users-modal-form__req">*</span></label>
                    <input type="text" id="ta_doc_file_name" class="form-input" maxlength="255" data-ta-doc-field="file_name" lang="ca" autocomplete="off">
                    <p class="form-error" data-ta-doc-error="file_name" hidden></p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ta_doc_notes">Observacions</label>
                    <textarea id="ta_doc_notes" class="form-input" rows="3" data-ta-doc-field="document_notes" lang="ca"></textarea>
                </div>

                <div class="form-group">
                    <label class="users-switch">
                        <input type="checkbox" id="ta_doc_visible" value="1" data-ta-doc-field="is_visible">
                        <span>Visible per a consulta externa <span class="muted">(altres aplicacions web, no amaga el document dins Formació)</span></span>
                    </label>
                </div>

                <div class="form-group ta-doc-view-row" data-ta-doc-view-row hidden>
                    <a class="btn btn--outline btn--sm" href="#" data-ta-doc-view-link target="_blank" rel="noopener"><?= ui_icon('document') ?> Veure document</a>
                </div>
            </div>
        </div>
    </div>
</div>

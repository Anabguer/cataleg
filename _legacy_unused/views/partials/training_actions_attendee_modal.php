<?php
declare(strict_types=1);
?>
<div id="training-actions-attendee-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="ta-attendee-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form modal__dialog--training-actions-attendee training-actions-ui-standard">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('users') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="ta-attendee-modal-title" data-ta-attendee-modal-title>Assistent</h2>
                        <p class="users-modal-form__subtitle" data-ta-attendee-modal-sub></p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-ta-attendee-close>Cancel·lar</button>
                    <button type="button" class="btn btn--primary btn--sm" data-ta-attendee-save>Desar</button>
                </div>
            </div>
            <div class="users-modal-form__body" id="ta-attendee-form-body">
                <input type="hidden" id="ta_attendee_id" value="" data-ta-attendee-field="id">
                <input type="hidden" id="ta_attendee_training_action_id" value="" data-ta-attendee-field="training_action_id">
                <input type="hidden" id="ta_attendee_certificate_doc_id" value="" data-ta-attendee-field="attendance_certificate_document_id">

                <div class="form-group" data-ta-attendee-person-block>
                    <label class="form-label" for="ta_attendee_person_select">Persona <span class="users-modal-form__req">*</span></label>
                    <select
                        class="form-select ta-attendee-person-select"
                        id="ta_attendee_person_select"
                        autocomplete="off"
                        aria-describedby="ta_attendee_person_hint"
                        data-ta-attendee-field="person_id"
                    >
                        <option value="">— Seleccioneu una persona —</option>
                    </select>
                    <p class="muted training-actions-modal__hint" id="ta_attendee_person_hint" data-ta-attendee-person-hint>
                        Només persones actives. Cognoms i nom (codi). Ordre: cognoms, nom. El teclat permet saltar entre opcions (desplegable natiu).
                    </p>
                    <p class="form-error" data-ta-attendee-error="person_id" hidden></p>
                </div>

                <div class="training-actions-grid training-actions-grid--attendee-flags">
                    <div class="form-group ta-span-full">
                        <label class="users-switch">
                            <input type="checkbox" id="ta_attendee_request" value="1" data-ta-attendee-field="request_flag">
                            <span>Sol·licitud</span>
                        </label>
                    </div>
                    <div class="form-group ta-span-full">
                        <label class="users-switch">
                            <input type="checkbox" id="ta_attendee_pre_registration" value="1" data-ta-attendee-field="pre_registration_flag">
                            <span>Preinscripció</span>
                        </label>
                    </div>
                    <div class="form-group ta-span-full">
                        <label class="users-switch">
                            <input type="checkbox" id="ta_attendee_registration" value="1" data-ta-attendee-field="registration_flag">
                            <span>Inscripció</span>
                        </label>
                    </div>
                    <div class="form-group ta-span-full">
                        <label class="users-switch">
                            <input type="checkbox" id="ta_attendee_attendance" value="1" data-ta-attendee-field="attendance_flag">
                            <span>Assistència</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ta_attendee_non_attendance">Motiu de no assistència</label>
                    <textarea class="form-input" id="ta_attendee_non_attendance" rows="3" data-ta-attendee-field="non_attendance_reason" lang="ca"></textarea>
                </div>

                <div class="form-group ta-attendee-cert-block">
                    <span class="form-label">Certificat / justificant</span>
                    <div class="ta-attendee-cert-linked-row" data-ta-attendee-cert-linked hidden>
                        <a class="btn btn--outline btn--sm" href="#" data-ta-attendee-cert-link target="_blank" rel="noopener"><?= ui_icon('document') ?> Veure document</a>
                        <span class="ta-attendee-cert-name" data-ta-attendee-cert-name></span>
                    </div>
                    <div class="ta-attendee-cert-upload" data-ta-attendee-cert-upload hidden>
                        <label class="form-label" for="ta_attendee_cert_file">Pujar o substituir certificat</label>
                        <input type="file" id="ta_attendee_cert_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.webp" autocomplete="off">
                        <button type="button" class="btn btn--ghost btn--sm" data-ta-attendee-cert-clear>Treure document vinculat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

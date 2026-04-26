<?php
declare(strict_types=1);
/** @var list<array{id:int,knowledge_area_code:int,name:string}> $knowledgeAreasFormModal */
$knowledgeAreasFormModal = $knowledgeAreasFormModal ?? [];
?>
<div id="training-catalog-actions-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="training-catalog-actions-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="training-catalog-actions-modal-title" data-training-catalog-actions-modal-heading>Nova acció al catàleg</h2>
                        <p class="users-modal-form__subtitle" data-training-catalog-actions-modal-subheading>Introdueix les dades de l’acció formativa</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions">
                    <button type="button" class="btn btn--ghost btn--sm" data-training-catalog-actions-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="training-catalog-actions-modal-form">Desar</button>
                </div>
            </div>
            <form id="training-catalog-actions-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-training-catalog-actions-msg" hidden>
                    <div class="alert alert--error" role="alert" data-training-catalog-actions-form-error></div>
                </div>
                <div class="form-group form-grid__full js-tca-code-create">
                    <label class="form-label" for="tca_action_code_preview">Codi d’acció</label>
                    <input class="form-input" id="tca_action_code_preview" type="text" readonly tabindex="-1" data-field="action_code_display" aria-describedby="tca_action_code_help">
                    <p id="tca_action_code_help" class="muted">Es genera automàticament en desar (visualització prèvia).</p>
                </div>
                <div class="form-group form-grid__full js-tca-code-edit" hidden>
                    <label class="form-label" for="tca_action_code_ro">Codi d’acció</label>
                    <input class="form-input" id="tca_action_code_ro" type="text" readonly tabindex="-1" data-field="action_code_display_edit">
                    <p class="muted">El codi no es pot modificar.</p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_name">Nom de l’acció formativa <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="tca_name" name="name" type="text" maxlength="255" required data-field="name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="name" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_knowledge_area_id">Àrea de coneixement <span class="users-modal-form__req">*</span></label>
                    <select class="form-select" id="tca_knowledge_area_id" name="knowledge_area_id" required data-field="knowledge_area_id">
                        <option value="">— Seleccioneu —</option>
                        <?php foreach ($knowledgeAreasFormModal as $ka): ?>
                            <option value="<?= (int) $ka['id'] ?>"><?= e(format_padded_code((int) $ka['knowledge_area_code'], 3) . ' — ' . (string) $ka['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-error" data-error-for="knowledge_area_id" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_target_audience">Persones destinatàries</label>
                    <textarea class="form-input" id="tca_target_audience" name="target_audience" rows="5" data-field="target_audience" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="target_audience" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_training_objectives">Objectius formatius</label>
                    <textarea class="form-input" id="tca_training_objectives" name="training_objectives" rows="5" data-field="training_objectives" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="training_objectives" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_conceptual_contents">Continguts conceptuals</label>
                    <textarea class="form-input" id="tca_conceptual_contents" name="conceptual_contents" rows="5" data-field="conceptual_contents" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="conceptual_contents" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_procedural_contents">Continguts procedimentals</label>
                    <textarea class="form-input" id="tca_procedural_contents" name="procedural_contents" rows="5" data-field="procedural_contents" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="procedural_contents" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="tca_attitudinal_contents">Continguts actitudinals</label>
                    <textarea class="form-input" id="tca_attitudinal_contents" name="attitudinal_contents" rows="5" data-field="attitudinal_contents" lang="ca" spellcheck="true"></textarea>
                    <p class="form-error" data-error-for="attitudinal_contents" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tca_expected_duration_hours">Durada prevista (hores)</label>
                    <input class="form-input" id="tca_expected_duration_hours" name="expected_duration_hours" type="number" step="0.01" min="0" max="9999.99" data-field="expected_duration_hours">
                    <p class="form-error" data-error-for="expected_duration_hours" hidden></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tca_status">Estat</label>
                    <input class="form-input" id="tca_status" name="status" type="text" maxlength="50" data-field="status" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="status" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch">
                        <input type="checkbox" id="tca_is_active" name="is_active" value="1" checked data-field="is_active">
                        <span>Actiu</span>
                    </label>
                </div>
            </form>
        </div>
    </div>
</div>

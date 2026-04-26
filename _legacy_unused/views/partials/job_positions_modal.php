<?php
declare(strict_types=1);
/** @var list<array{id:int,unit_code:int,name:string}> $jobPositionsAutoUnits */
$jobPositionsAutoUnits = $jobPositionsAutoUnits ?? [];
$jobPositionsHasAutoUnits = $jobPositionsAutoUnits !== [];
?>
<div id="job-positions-modal-overlay" class="modal modal--form-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="job-positions-modal-title">
    <div class="modal__backdrop" aria-hidden="true"></div>
    <div class="modal__dialog modal__dialog--users-form">
        <div class="users-modal-form users-modal-form--crud">
            <div class="users-modal-form__header">
                <div class="users-modal-form__header-start">
                    <span class="users-modal-form__icon-tile" aria-hidden="true"><?= ui_icon('pencil-square') ?></span>
                    <div>
                        <h2 class="users-modal-form__title" id="job-positions-modal-title" data-job-positions-modal-heading>Nou lloc de treball</h2>
                        <p class="users-modal-form__subtitle" data-job-positions-modal-subheading>Introdueix les dades del lloc</p>
                    </div>
                </div>
                <div class="users-modal-form__header-actions" data-job-positions-modal-header-actions>
                    <button type="button" class="btn btn--ghost btn--sm" data-job-positions-modal-close>Cancel·lar</button>
                    <button type="submit" class="btn btn--primary btn--sm" form="job-positions-modal-form" data-job-positions-submit-btn>Desar</button>
                </div>
            </div>
            <form id="job-positions-modal-form" class="users-modal-form__body form-grid form-grid--modal" novalidate lang="ca">
                <input type="hidden" name="id" data-field="id">
                <div class="form-group form-grid__full js-job-positions-msg" hidden>
                    <div class="alert alert--error" role="alert" data-job-positions-form-error></div>
                </div>
                <div class="form-group form-grid__full js-job-positions-catalog-banner" hidden>
                    <div class="alert alert--warning" role="status"><?= e('No es pot modificar un lloc de catàleg des d’aquest formulari.') ?></div>
                </div>
                <div class="form-group form-grid__full js-job-positions-assignment-block">
                    <p class="form-label"><?= e('Assignació d’unitat') ?></p>
                    <div class="form-group" role="radiogroup" aria-label="<?= e('Mode d’assignació') ?>">
                        <label class="users-switch" style="display:flex;align-items:flex-start;gap:var(--space-2);margin-bottom:var(--space-2);">
                            <input type="radio" name="assignment_mode" value="existing" data-job-positions-mode-existing<?= $jobPositionsHasAutoUnits ? ' checked' : '' ?><?= $jobPositionsHasAutoUnits ? '' : ' disabled' ?>>
                            <span><?= e('Utilitzar una unitat automàtica existent (codi ≥ 8000)') ?></span>
                        </label>
                        <label class="users-switch" style="display:flex;align-items:flex-start;gap:var(--space-2);">
                            <input type="radio" name="assignment_mode" value="new" data-job-positions-mode-new<?= $jobPositionsHasAutoUnits ? '' : ' checked' ?>>
                            <span><?= e('Crear una nova unitat automàtica (següent codi disponible ≥ 8000)') ?></span>
                        </label>
                    </div>
                    <p class="form-error" data-error-for="assignment_mode" hidden></p>
                    <div class="form-group form-grid__full" data-job-positions-existing-unit-wrap>
                        <label class="form-label" for="job_positions_existing_unit_id"><?= e('Unitat automàtica') ?> <span class="users-modal-form__req">*</span></label>
                        <select class="form-select" id="job_positions_existing_unit_id" name="existing_unit_id" data-job-positions-existing-unit-select>
                            <option value=""><?= e('— Selecciona —') ?></option>
                            <?php foreach ($jobPositionsAutoUnits as $u): ?>
                                <option value="<?= (int) $u['id'] ?>" data-unit-code="<?= (int) $u['unit_code'] ?>"><?= e(format_padded_code((int) $u['unit_code'], 4) . ' — ' . (string) $u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-error" data-error-for="existing_unit_id" hidden></p>
                    </div>
                    <p class="muted js-job-positions-new-unit-hint" hidden data-job-positions-new-hint><?= e('Es generarà la unitat amb el següent codi lliure (≥ 8000).') ?></p>
                    <p class="muted js-job-positions-no-auto-units-hint"<?= $jobPositionsHasAutoUnits ? ' hidden' : '' ?>><?= e('Encara no hi ha unitats automàtiques: es crearà una unitat nova.') ?></p>
                </div>
                <div class="form-group form-grid__full">
                    <p class="form-label muted"><?= e('Codi del lloc (previsualització)') ?></p>
                    <p class="page-header__title" data-job-positions-code-preview aria-live="polite">—</p>
                    <p class="muted"><?= e('Es deriva del codi d’unitat i el número (no es desa com a text concatenat).') ?></p>
                </div>
                <div class="form-group form-grid__full js-job-positions-edit-unit-readonly" hidden>
                    <p class="form-label"><?= e('Unitat') ?></p>
                    <p class="muted" data-job-positions-unit-readonly-text></p>
                </div>
                <div class="form-group">
                    <label class="form-label" for="job_positions_position_number"><?= e('Número dins la unitat') ?> <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="job_positions_position_number" name="position_number" type="number" min="1" max="99" required data-field="position_number" data-job-positions-position-input inputmode="numeric">
                    <p class="form-error" data-error-for="position_number" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="form-label" for="job_positions_name"><?= e('Denominació') ?> <span class="users-modal-form__req">*</span></label>
                    <input class="form-input" id="job_positions_name" name="name" type="text" maxlength="200" required data-field="name" lang="ca" spellcheck="true">
                    <p class="form-error" data-error-for="name" hidden></p>
                </div>
                <div class="form-group form-grid__full">
                    <label class="users-switch">
                        <input type="checkbox" id="job_positions_is_active" name="is_active" value="1" checked data-field="is_active">
                        <span><?= e('Actiu') ?></span>
                    </label>
                </div>
            </form>
        </div>
    </div>
</div>

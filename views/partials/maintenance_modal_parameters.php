<?php
declare(strict_types=1);
/** @var string $module */
?>
<div class="form-grid form-grid--two-cols form-grid__full" data-maintenance-field="parameters_fields" hidden>
    <div class="form-group" data-maintenance-field="catalog_year">
        <label class="form-label" for="maintenance_id" data-maintenance-label-id>Any catàleg <span class="users-modal-form__req">*</span></label>
        <input class="form-input" id="maintenance_id" name="id" type="number" min="2000" max="2100" step="1" required data-field="id" autocomplete="off">
        <p class="form-error" data-error-for="id" hidden></p>
    </div>
    <div class="form-group" data-maintenance-field="mei_percentage">
        <label class="form-label" for="maintenance_parameters_mei">% MEI</label>
        <input class="form-input form-input--percent" id="maintenance_parameters_mei" name="mei_percentage" type="text" inputmode="decimal" placeholder="0,75" data-field="mei_percentage" autocomplete="off">
        <p class="form-error" data-error-for="mei_percentage" hidden></p>
    </div>
</div>

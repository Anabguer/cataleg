<?php
declare(strict_types=1);
/** @var string $module */
?>
<div class="form-grid form-grid--two-cols" data-maintenance-field="catalogs_fields" hidden>
    <div class="form-group">
        <label class="form-label" for="maintenance_catalog_code">Codi <span class="users-modal-form__req">*</span></label>
        <input class="form-input" id="maintenance_catalog_code" name="catalog_code" type="text" maxlength="20" required data-field="catalog_code" autocomplete="off">
        <p class="form-error" data-error-for="catalog_code" hidden></p>
    </div>
    <div class="form-group">
        <label class="form-label" for="maintenance_catalog_description">Descripció <span class="users-modal-form__req">*</span></label>
        <input class="form-input" id="maintenance_catalog_description" name="catalog_description" type="text" maxlength="255" required data-field="catalog_description" autocomplete="off">
        <p class="form-error" data-error-for="catalog_description" hidden></p>
    </div>
</div>

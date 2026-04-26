<?php
declare(strict_types=1);
/** @var string $screenTitle */
/** @var string $formCode */
$canCreate = can_create_form($formCode);
$canEdit = can_edit_form($formCode);
$canDelete = can_delete_form($formCode);

ob_start();
?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--primary btn--sm">Nou registre</button>
    <?php endif; ?>
    <button type="button" class="btn btn--secondary btn--sm js-demo-confirm">Demo confirmació</button>
    <button type="button" class="btn btn--secondary btn--sm js-demo-alert">Demo alerta</button>
    <span class="action-bar__spacer"></span>
    <div class="action-bar__group">
        <button type="button" class="btn btn--secondary btn--sm" disabled title="Pendent d’implementar">Importar</button>
        <button type="button" class="btn btn--secondary btn--sm" disabled title="Pendent d’implementar">Exportar</button>
    </div>
</div>
<?php
$actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres';
$filterShowClear = false;
$filterCardInner = '
    <form method="get" action="#" class="users-filter-form">
        <div class="filter-bar__field">
            <label class="form-label" for="ph_filter_q">Cerca</label>
            <input class="form-input" type="search" id="ph_filter_q" name="ph_q" placeholder="Codi o nom…" autocomplete="off">
        </div>
        <div class="filter-bar__field">
            <label class="form-label" for="ph_filter_act">Estat</label>
            <select class="form-select" id="ph_filter_act" name="ph_act">
                <option value=\"\">Tots</option>
                <option value=\"1\">Actiu</option>
                <option value=\"0\">Inactiu</option>
            </select>
        </div>
        <div class="users-filter-actions" role="group" aria-label="Accions de filtre">
            <button type="submit" class="btn btn--filter-icon btn--filter-apply" title="Aplicar filtres" aria-label="Aplicar filtres">
                <img src="<?= e(asset_url(\'img/icon_filter_apply.svg\')) ?>" alt="" width="48" height="48" decoding="async">
            </button>
            <button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear" title="Netejar filtres" aria-label="Netejar filtres">
                <img src="<?= e(asset_url(\'img/icon_filter_clear.svg\')) ?>" alt="" width="48" height="48" decoding="async">
            </button>
        </div>
    </form>';

$dataTableCaption = 'Llistat';
$dataTableToolbar = '0 registres (plantilla)';
ob_start();
?>
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Descripció</th>
            <th>Estat</th>
            <th class="data-table__actions">Accions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>—</td>
            <td class="muted">Encara no hi ha dades; aquesta és la graella estàndard.</td>
            <td><span class="badge badge--neutral">—</span></td>
            <td class="data-table__actions">
                <?php if ($canEdit): ?>
                    <button type="button" class="btn btn--secondary btn--sm js-demo-save">Editar</button>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                    <button type="button" class="btn btn--danger btn--sm js-demo-delete">Eliminar</button>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>
<?php
$dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => $screenTitle,
    'subtitle' => 'Mòdul en construcció — mateix patró visual que la resta de gestió',
    'back_url' => app_url('dashboard.php'),
    'back_label' => 'Tauler',
]);

ob_start();
?>
<section class="form-card">
    <div class="form-card__header">
        <h2 class="form-card__title">Permisos en aquesta pantalla</h2>
        <p class="form-card__desc">El backend ha de validar igualment abans de guardar o esborrar.</p>
    </div>
    <div class="form-card__body">
        <ul class="perm-list">
            <li>Veure: <strong><?= can_view_form($formCode) ? 'Sí' : 'No' ?></strong></li>
            <li>Crear: <strong><?= $canCreate ? 'Sí' : 'No' ?></strong></li>
            <li>Editar: <strong><?= $canEdit ? 'Sí' : 'No' ?></strong></li>
            <li>Esborrar: <strong><?= $canDelete ? 'Sí' : 'No' ?></strong></li>
        </ul>
    </div>
</section>
<?php
$pageContentExtra = ob_get_clean();

require APP_ROOT . '/views/layouts/admin_page.php';

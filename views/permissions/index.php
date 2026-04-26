<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rolesOverview */
/** @var list<array<string,mixed>> $permissionGroups */
/** @var list<array<string,mixed>> $roleUsers */
/** @var list<array<string,mixed>> $usersPool */
/** @var int $currentRoleId */
/** @var array<string,mixed>|null $currentRole */
/** @var bool $canEditPermissions */
/** @var bool $canEditPermissionsUi */

ob_start();
?>
<div class="action-bar__group permissions-screen__top-actions">
    <a href="<?= e(app_url('roles.php')) ?>" class="btn btn--ghost">Gestionar rols</a>
    <?php if ($canEditPermissions): ?>
        <a href="<?= e(app_url('roles.php')) ?>" class="btn btn--primary">Nou rol</a>
    <?php endif; ?>
</div>
<?php
$actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = false;
$filterShowClear = false;
ob_start();
?>
<p class="muted">Selecciona un rol a l'esquerra, ajusta permisos al centre i gestiona usuaris a la dreta.</p>
<?php
$filterCardInner = ob_get_clean();

$dataTableCaption = 'Configuració de rols i permisos';
$dataTableToolbar = 'Rol actiu: ' . e((string) ($currentRole['name'] ?? '—'));

ob_start();
?>
<div class="permissions-screen" id="permissions-screen" data-current-role-id="<?= (int) $currentRoleId ?>">
    <aside class="permissions-panel permissions-panel--roles">
        <div class="permissions-panel__header">
            <h3>Rols</h3>
            <span class="muted"><?= count($rolesOverview) ?> registres</span>
        </div>
        <div class="permissions-role-list" id="permissions-role-list">
            <?php foreach ($rolesOverview as $role): ?>
                <article class="permissions-role-card<?= (int) $role['id'] === $currentRoleId ? ' is-active' : '' ?>" data-role-id="<?= (int) $role['id'] ?>">
                    <div class="permissions-role-card__head">
                        <h4><?= e((string) $role['name']) ?></h4>
                        <span class="badge badge--info"><?= (int) $role['users_count'] ?> usuaris</span>
                    </div>
                    <?php if (!empty($role['description'])): ?>
                        <p class="muted"><?= e((string) $role['description']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="permissions-panel permissions-panel--matrix">
        <header class="permissions-panel__header permissions-role-header">
            <div>
                <h3 id="permissions-role-title"><?= e((string) ($currentRole['name'] ?? 'Rol')) ?></h3>
                <p class="muted" id="permissions-role-description"><?= e((string) ($currentRole['description'] ?? 'Selecciona un rol per començar.')) ?></p>
            </div>
            <div class="permissions-footer">
                <button type="button" class="btn btn--primary" id="permissions-save-btn"<?= $canEditPermissionsUi ? '' : ' disabled' ?>>Guardar canvis</button>
            </div>
        </header>

        <form method="post" action="<?= e(app_url('permissions.php')) ?>" id="permissions-matrix-form">
            <input type="hidden" name="role_id" value="<?= (int) $currentRoleId ?>" id="permissions-role-id">
            <div id="permissions-groups-root">
                <?php foreach ($permissionGroups as $group): ?>
                    <div class="permissions-group">
                        <h4 class="permissions-group__title"><?= e((string) $group['label']) ?></h4>
                        <div class="permissions-table-scroll">
                            <table class="data-table permissions-table">
                                <colgroup>
                                    <col class="permissions-col permissions-col--screen">
                                    <col class="permissions-col permissions-col--group">
                                    <col class="permissions-col permissions-col--order">
                                    <col class="permissions-col permissions-col--perm">
                                    <col class="permissions-col permissions-col--perm">
                                    <col class="permissions-col permissions-col--perm">
                                    <col class="permissions-col permissions-col--perm">
                                    <col class="permissions-col permissions-col--quick">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Pantalla</th>
                                        <th scope="col">Grup</th>
                                        <th scope="col">Ordre</th>
                                        <th scope="col">Veure</th>
                                        <th scope="col">Crear</th>
                                        <th scope="col">Editar</th>
                                        <th scope="col">Esborrar</th>
                                        <th scope="col">Acció ràpida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['items'] as $f): ?>
                                        <?php $p = $f['permissions']; ?>
                                        <tr data-form-id="<?= (int) $f['id'] ?>" data-form-code="<?= e((string) $f['code']) ?>">
                                            <th scope="row">
                                                <div class="permissions-form-name">
                                                    <span><?= e((string) $f['name']) ?></span>
                                                    <span class="permissions-form-code"><?= e((string) $f['code']) ?></span>
                                                </div>
                                            </th>
                                            <td class="permissions-cell">
                                                <select class="form-select form-select--sm permissions-group-select" data-field="form_group"<?= $canEditPermissionsUi ? '' : ' disabled' ?>>
                                                    <?php foreach (permissions_form_group_options() as $groupOpt): ?>
                                                        <option value="<?= e((string) $groupOpt['value']) ?>"<?= (string) $f['form_group'] === (string) $groupOpt['value'] ? ' selected' : '' ?>>
                                                            <?= e((string) $groupOpt['label']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="permissions-cell permissions-cell--order">
                                                <input type="number" min="0" step="1" class="form-input form-input--sm permissions-order-input" data-field="group_sort_order" value="<?= (int) $f['group_sort_order'] ?>"<?= $canEditPermissionsUi ? '' : ' disabled' ?>>
                                            </td>
                                            <td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="view"<?= !empty($p['can_view']) ? ' checked' : '' ?><?= $canEditPermissionsUi ? '' : ' disabled' ?>><span class="permissions-switch__slider"></span></label></td>
                                            <td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="create"<?= !empty($p['can_create']) ? ' checked' : '' ?><?= $canEditPermissionsUi ? '' : ' disabled' ?>><span class="permissions-switch__slider"></span></label></td>
                                            <td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="edit"<?= !empty($p['can_edit']) ? ' checked' : '' ?><?= $canEditPermissionsUi ? '' : ' disabled' ?>><span class="permissions-switch__slider"></span></label></td>
                                            <td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="delete"<?= !empty($p['can_delete']) ? ' checked' : '' ?><?= $canEditPermissionsUi ? '' : ' disabled' ?>><span class="permissions-switch__slider"></span></label></td>
                                            <td class="permissions-cell permissions-cell--quick">
                                                <button type="button" class="btn btn--ghost btn--sm permissions-row-toggle"<?= $canEditPermissionsUi ? '' : ' disabled' ?>>Tot/Nada</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </section>

    <aside class="permissions-panel permissions-panel--users">
        <div class="permissions-panel__header"><h3>Gestió d'usuaris del rol</h3></div>
        <?php if ($canEditPermissionsUi): ?>
            <div class="permissions-assign-box" id="permissions-assign-box">
                <h4 class="permissions-subtitle">Assignar usuari a aquest rol</h4>
                <p class="muted">Si l'usuari ja té un altre rol, es demanarà confirmació per substituir-lo.</p>
                <label class="form-label" for="permissions-user-select">Selecciona usuari</label>
                <select class="form-select" id="permissions-user-select">
                    <?php foreach ($usersPool as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" data-current-role-id="<?= (int) $u['current_role_id'] ?>" data-current-role-name="<?= e((string) $u['current_role_name']) ?>">
                            <?= e((string) $u['full_name']) ?> (<?= e((string) $u['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn--primary" id="permissions-assign-btn">Assignar</button>
            </div>
        <?php endif; ?>
        <div class="permissions-panel__header"><h4 class="permissions-subtitle">Usuaris amb aquest rol</h4></div>
        <div class="permissions-user-list" id="permissions-user-list">
            <?php foreach ($roleUsers as $u): ?>
                <article class="permissions-user-card" data-user-id="<?= (int) $u['id'] ?>">
                    <div>
                        <h4><?= e((string) $u['full_name']) ?></h4>
                        <p class="muted"><?= e((string) ($u['email'] ?: $u['username'])) ?></p>
                        <span class="badge <?= (int) $u['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                            <?= (int) $u['is_active'] ? 'Actiu' : 'Inactiu' ?>
                        </span>
                    </div>
                    <?php if ($canEditPermissionsUi): ?>
                        <div class="permissions-user-actions">
                            <button type="button" class="btn btn--ghost btn--sm permissions-change-user-role" data-user-id="<?= (int) $u['id'] ?>" data-user-name="<?= e((string) $u['full_name']) ?>">Canviar rol</button>
                            <button type="button" class="btn btn--ghost btn--sm permissions-remove-user" data-user-id="<?= (int) $u['id'] ?>" data-user-name="<?= e((string) $u['full_name']) ?>">Quitar</button>
                        </div>
                        <div class="permissions-user-change-box" data-user-change-box="<?= (int) $u['id'] ?>" hidden>
                            <label class="form-label" for="permissions-change-role-<?= (int) $u['id'] ?>">Nou rol</label>
                            <select class="form-select permissions-change-role-select" id="permissions-change-role-<?= (int) $u['id'] ?>" data-user-id="<?= (int) $u['id'] ?>">
                                <?php foreach ($rolesOverview as $role): ?>
                                    <?php if ((int) $role['id'] === $currentRoleId) { continue; } ?>
                                    <option value="<?= (int) $role['id'] ?>"><?= e((string) $role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn--primary btn--sm permissions-apply-role-change" data-user-id="<?= (int) $u['id'] ?>" data-user-name="<?= e((string) $u['full_name']) ?>">Aplicar canvi</button>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </aside>
</div>
<?php if (!$canEditPermissionsUi): ?>
    <div class="permissions-footer">
        <span class="muted"><?= !$canEditPermissions
            ? 'Tens accés de consulta. No pots desar ni reassignar usuaris.'
            : 'Només un usuari amb rol administrador del sistema pot modificar aquest rol.' ?></span>
    </div>
<?php endif; ?>
<?php
$dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Permisos per rol',
    'subtitle' => 'Matriz de permisos per formulari i rol',
]);

$pageContentExtra = '';

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';


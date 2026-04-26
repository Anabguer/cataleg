<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $roleRows */
/** @var array{q:string} $filters */
/** @var bool $canCreate */
/** @var bool $canEdit */
/** @var bool $canDelete */
/** @var string $rolesProtectedSlug */
/** @var bool $rolesActorIsSystemAdmin */
/** @var string $sortBy */
/** @var string $sortDir */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalRoles */
/** @var int $totalPages */
/** @var int $offset */

$filterQueryBase = roles_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);

$shownFrom = $totalRoles === 0 ? 0 : $offset + 1;
$shownTo = $totalRoles === 0 ? 0 : min($offset + count($roleRows), $totalRoles);

ob_start();
?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-roles-open-create>
            <?= ui_icon('plus') ?>
            Nou rol
        </button>
    <?php endif; ?>
</div>
<?php
$actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = true;
$filterShowClear = false;
ob_start();
?>
<form method="get" action="<?= e(app_url('roles.php')) ?>" class="users-filter-form" id="roles-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="rf_q">Cercar</label>
        <input class="form-input" type="search" id="rf_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom o codi de rol…" autocomplete="off">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="rf_pp">Per pàgina</label>
        <select class="form-select" id="rf_pp" name="per_page" data-preserve-on-filter-clear>
            <?php foreach ([10, 20, 50, 100] as $pp): ?>
                <option value="<?= $pp ?>"<?= $perPage === $pp ? ' selected' : '' ?>><?= $pp ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="users-filter-actions" role="group" aria-label="Accions de filtre">
        <button type="submit" class="btn btn--filter-icon btn--filter-apply" title="Aplicar filtres" aria-label="Aplicar filtres">
            <img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48" decoding="async">
        </button>
        <button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear" title="Netejar filtres" aria-label="Netejar filtres">
            <img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48" decoding="async">
        </button>
    </div>
</form>
<?php
$filterCardInner = ob_get_clean();

$dataTableCaption = 'Llistat de rols';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalRoles;

ob_start();
?>
<table class="data-table">
    <thead>
        <tr>
            <th scope="col">
                <?php
                [$sym, $title] = roles_view_sort_indicator('name', $sortBy, $sortDir);
                ?>
                <a href="<?= e(roles_view_sort_href('name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'name' ? ' is-active' : '' ?>">
                    Nom <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = roles_view_sort_indicator('slug', $sortBy, $sortDir);
                ?>
                <a href="<?= e(roles_view_sort_href('slug', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'slug' ? ' is-active' : '' ?>">
                    Codi <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">Descripció</th>
            <th scope="col">
                <?php
                [$sym, $title] = roles_view_sort_indicator('users_count', $sortBy, $sortDir);
                ?>
                <a href="<?= e(roles_view_sort_href('users_count', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'users_count' ? ' is-active' : '' ?>">
                    Usuaris <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = roles_view_sort_indicator('created_at', $sortBy, $sortDir);
                ?>
                <a href="<?= e(roles_view_sort_href('created_at', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'created_at' ? ' is-active' : '' ?>">
                    Creat el <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th class="data-table__actions" scope="col">Accions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($roleRows === []): ?>
            <tr>
                <td colspan="6" class="muted">No hi ha rols amb aquests filtres.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($roleRows as $r): ?>
                <tr data-role-row data-role-id="<?= (int) $r['id'] ?>">
                    <td><strong><?= e((string) $r['name']) ?></strong></td>
                    <td><code><?= e((string) $r['slug']) ?></code></td>
                    <td><?= $r['description'] !== null && $r['description'] !== '' ? e((string) $r['description']) : '—' ?></td>
                    <td><?= (int) $r['users_count'] ?></td>
                    <td><?= e((string) $r['created_at']) ?></td>
                    <td class="data-table__actions">
                        <?php if ($canEdit && ((string) $r['slug'] !== $rolesProtectedSlug || $rolesActorIsSystemAdmin)): ?>
                            <button type="button" class="btn btn--sm btn--icon-edit" title="Editar" data-roles-edit="<?= (int) $r['id'] ?>"><?= ui_icon('pencil-square') ?></button>
                        <?php endif; ?>
                        <?php if ($canDelete && (string) $r['slug'] !== $rolesProtectedSlug): ?>
                            <button type="button"
                                    class="btn btn--sm btn--icon-del"
                                    title="Eliminar"
                                    data-roles-delete="<?= (int) $r['id'] ?>"><?= ui_icon('trash') ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?php
$dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Rols',
    'subtitle' => 'Gestió de rols del sistema',
]);

ob_start();
if ($totalPages > 1 || $totalRoles > 0) {
    $paginationBase = $filterQueryBase;
    $paginationBase['sort_by'] = $sortBy;
    $paginationBase['sort_dir'] = $sortDir;
    $paginationPage = $page;
    $paginationTotalPages = $totalPages;
    $paginationAriaLabel = 'Paginació del llistat de rols';
    $paginationBuildUrl = static function (int $p) use ($paginationBase): string {
        return roles_view_query_url($paginationBase, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';

require APP_ROOT . '/views/partials/roles_modal.php';


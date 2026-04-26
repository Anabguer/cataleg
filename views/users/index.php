<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $userRows */
/** @var list<array{id:int,name:string,slug:string}> $roles */
/** @var array{q:string,role_id:string,active:string} $filters */
/** @var bool $canCreate */
/** @var bool $canEdit */
/** @var bool $canDelete */
/** @var int|null $currentUserId */
/** @var string $sortBy */
/** @var string $sortDir */
/** @var int $page */
/** @var int $perPage */
/** @var int $totalUsers */
/** @var int $totalPages */
/** @var int $offset */

$filterQueryBase = users_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);

$shownFrom = $totalUsers === 0 ? 0 : $offset + 1;
$shownTo = $totalUsers === 0 ? 0 : min($offset + count($userRows), $totalUsers);

ob_start();
?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-users-open-create>
            <?= ui_icon('plus') ?>
            Nou usuari
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
<form method="get" action="<?= e(app_url('users.php')) ?>" class="users-filter-form" id="users-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="uf_q">Cercar</label>
        <input class="form-input" type="search" id="uf_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Usuari, nom o correu…" autocomplete="off">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="uf_role">Rol</label>
        <select class="form-select" id="uf_role" name="role_id">
            <option value="">Tots</option>
            <option value="none"<?= $filters['role_id'] === 'none' ? ' selected' : '' ?>>Sense rol</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>"<?= (string) $filters['role_id'] === (string) $r['id'] ? ' selected' : '' ?>><?= e((string) $r['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="uf_act">Estat</label>
        <select class="form-select" id="uf_act" name="active">
            <option value=""<?= $filters['active'] === '' ? ' selected' : '' ?>>Tots</option>
            <option value="1"<?= $filters['active'] === '1' ? ' selected' : '' ?>>Actiu</option>
            <option value="0"<?= $filters['active'] === '0' ? ' selected' : '' ?>>Inactiu</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="uf_pp">Per pàgina</label>
        <select class="form-select" id="uf_pp" name="per_page" data-preserve-on-filter-clear>
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

$dataTableCaption = 'Llistat d’usuaris';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalUsers;
if ($filters['role_id'] === 'none') {
    $dataTableToolbar .= ' · Filtre rol: Sense rol';
}

ob_start();
?>
<table class="data-table">
    <thead>
        <tr>
            <th scope="col">
                <?php
                [$sym, $title] = users_view_sort_indicator('username', $sortBy, $sortDir);
                ?>
                <a href="<?= e(users_view_sort_href('username', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'username' ? ' is-active' : '' ?>">
                    Usuari <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = users_view_sort_indicator('full_name', $sortBy, $sortDir);
                ?>
                <a href="<?= e(users_view_sort_href('full_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'full_name' ? ' is-active' : '' ?>">
                    Nom <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = users_view_sort_indicator('email', $sortBy, $sortDir);
                ?>
                <a href="<?= e(users_view_sort_href('email', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'email' ? ' is-active' : '' ?>">
                    Correu <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = users_view_sort_indicator('role_name', $sortBy, $sortDir);
                ?>
                <a href="<?= e(users_view_sort_href('role_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'role_name' ? ' is-active' : '' ?>">
                    Rol <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th scope="col">
                <?php
                [$sym, $title] = users_view_sort_indicator('is_active', $sortBy, $sortDir);
                ?>
                <a href="<?= e(users_view_sort_href('is_active', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_active' ? ' is-active' : '' ?>">
                    Estat <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span>
                </a>
            </th>
            <th class="data-table__actions" scope="col">Accions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($userRows === []): ?>
            <tr>
                <td colspan="6" class="muted">No hi ha resultats amb aquests filtres.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($userRows as $u): ?>
                <tr data-user-row data-user-id="<?= (int) $u['id'] ?>">
                    <td><strong><?= e((string) $u['username']) ?></strong></td>
                    <td><?= e((string) $u['full_name']) ?></td>
                    <td><?= $u['email'] !== null && $u['email'] !== '' ? e((string) $u['email']) : '—' ?></td>
                    <td><?= !empty($u['role_name']) ? e((string) $u['role_name']) : '<span class="muted">Sense rol</span>' ?></td>
                    <td>
                        <?php if (!empty($u['is_active'])): ?>
                            <span class="badge badge--success badge--dot-success">Actiu</span>
                        <?php else: ?>
                            <span class="badge badge--neutral">Inactiu</span>
                        <?php endif; ?>
                    </td>
                    <td class="data-table__actions">
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn--sm btn--icon-edit" title="Editar" data-users-edit="<?= (int) $u['id'] ?>"><?= ui_icon('pencil-square') ?></button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <?php
                            $isSelf = $currentUserId !== null && (int) $u['id'] === (int) $currentUserId;
                            ?>
                            <button type="button"
                                    class="btn btn--sm btn--icon-del"
                                    title="<?= $isSelf ? 'No pots eliminar el teu usuari' : 'Eliminar' ?>"
                                    data-users-delete="<?= (int) $u['id'] ?>"
                                    <?= $isSelf ? 'disabled' : '' ?>><?= ui_icon('trash') ?></button>
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
    'title' => 'Usuaris',
    'subtitle' => 'Gestió de comptes d’accés i rols',
]);

ob_start();
if ($totalPages > 1 || $totalUsers > 0) {
    $paginationBase = $filterQueryBase;
    $paginationBase['sort_by'] = $sortBy;
    $paginationBase['sort_dir'] = $sortDir;
    $paginationPage = $page;
    $paginationTotalPages = $totalPages;
    $paginationAriaLabel = 'Paginació del llistat';
    $paginationBuildUrl = static function (int $p) use ($paginationBase): string {
        return users_view_query_url($paginationBase, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';

require APP_ROOT . '/views/partials/users_modal.php';

<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $areasSelect */
/** @var list<array<string,mixed>> $sectionsSelect */
/** @var list<array<string,mixed>> $unitsSelect */
/** @var array{q:string,area_id:string,section_id:string,unit_id:string,is_catalog:string,active:string} $filters */
/** @var bool $canCreate */ /** @var bool $canEdit */ /** @var bool $canDelete */
/** @var string $sortBy */ /** @var string $sortDir */
/** @var int $page */ /** @var int $perPage */ /** @var int $totalRows */ /** @var int $totalPages */ /** @var int $offset */

$filterQueryBase = job_positions_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);
$shownFrom = $totalRows === 0 ? 0 : $offset + 1;
$shownTo = $totalRows === 0 ? 0 : min($offset + count($rows), $totalRows);

ob_start(); ?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-job-positions-open-create><?= ui_icon('plus') ?> Nou lloc de treball</button>
    <?php endif; ?>
</div>
<?php $actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = true;
$filterShowClear = false;
ob_start(); ?>
<form method="get" action="<?= e(app_url('job_positions.php')) ?>" class="users-filter-form" id="job-positions-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_q">Cercar</label>
        <input class="form-input" type="search" id="jp_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom, codi d’unitat o número…" lang="ca" spellcheck="true">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_area">Àrea</label>
        <select class="form-select" id="jp_area" name="area_id">
            <option value="">Totes</option>
            <?php foreach ($areasSelect as $a): ?>
                <option value="<?= (int) $a['id'] ?>"<?= (string) $filters['area_id'] === (string) $a['id'] ? ' selected' : '' ?>><?= e(format_padded_code((int) $a['area_code'], 1) . ' - ' . (string) $a['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_section">Secció</label>
        <select class="form-select" id="jp_section" name="section_id">
            <option value="">Totes</option>
            <?php foreach ($sectionsSelect as $s): ?>
                <option value="<?= (int) $s['id'] ?>"<?= (string) $filters['section_id'] === (string) $s['id'] ? ' selected' : '' ?>><?= e(format_padded_code((int) $s['section_code'], 2) . ' - ' . (string) $s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_unit">Unitat</label>
        <select class="form-select" id="jp_unit" name="unit_id">
            <option value="">Totes</option>
            <?php foreach ($unitsSelect as $u): ?>
                <option value="<?= (int) $u['id'] ?>"<?= (string) $filters['unit_id'] === (string) $u['id'] ? ' selected' : '' ?>><?= e(format_padded_code((int) $u['unit_code'], 4) . ' — ' . (string) $u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_catalog">Catàleg</label>
        <select class="form-select" id="jp_catalog" name="is_catalog">
            <option value="">Tots</option>
            <option value="1"<?= $filters['is_catalog'] === '1' ? ' selected' : '' ?>>Sí</option>
            <option value="0"<?= $filters['is_catalog'] === '0' ? ' selected' : '' ?>>No</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_active">Estat</label>
        <select class="form-select" id="jp_active" name="active">
            <option value="">Tots</option>
            <option value="1"<?= $filters['active'] === '1' ? ' selected' : '' ?>>Actiu</option>
            <option value="0"<?= $filters['active'] === '0' ? ' selected' : '' ?>>Inactiu</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="jp_pp">Per pàgina</label>
        <select class="form-select" id="jp_pp" name="per_page" data-preserve-on-filter-clear><?php foreach ([10, 20, 50, 100] as $pp): ?><option value="<?= $pp ?>"<?= $perPage === $pp ? ' selected' : '' ?>><?= $pp ?></option><?php endforeach; ?></select>
    </div>
    <div class="users-filter-actions">
        <button type="submit" class="btn btn--filter-icon btn--filter-apply"><img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48"></button>
        <button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear"><img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48"></button>
    </div>
</form>
<?php $filterCardInner = ob_get_clean();

$dataTableCaption = 'Llistat de llocs de treball';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalRows;
ob_start(); ?>
<table class="data-table">
    <thead>
        <tr>
            <th><?php [$sym] = job_positions_view_sort_indicator('code', $sortBy, $sortDir); ?>
                <a href="<?= e(job_positions_view_sort_href('code', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'code' ? ' is-active' : '' ?>">Codi <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = job_positions_view_sort_indicator('unit_name', $sortBy, $sortDir); ?>
                <a href="<?= e(job_positions_view_sort_href('unit_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'unit_name' ? ' is-active' : '' ?>">Unitat <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = job_positions_view_sort_indicator('name', $sortBy, $sortDir); ?>
                <a href="<?= e(job_positions_view_sort_href('name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'name' ? ' is-active' : '' ?>">Denominació <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = job_positions_view_sort_indicator('is_catalog', $sortBy, $sortDir); ?>
                <a href="<?= e(job_positions_view_sort_href('is_catalog', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_catalog' ? ' is-active' : '' ?>">Catàleg <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = job_positions_view_sort_indicator('is_active', $sortBy, $sortDir); ?>
                <a href="<?= e(job_positions_view_sort_href('is_active', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_active' ? ' is-active' : '' ?>">Estat <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th class="data-table__actions">Accions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="6" class="muted">No hi ha llocs de treball.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr data-job-positions-id="<?= (int) $r['id'] ?>">
                <td><strong><?= e(format_job_position_code((int) $r['unit_code'], (int) $r['position_number'])) ?></strong></td>
                <td><?= e(format_padded_code((int) $r['unit_code'], 4) . ' — ' . (string) $r['unit_name']) ?></td>
                <td><?= e((string) $r['name']) ?></td>
                <td><?= !empty($r['is_catalog']) ? '<span class="badge badge--success">Sí</span>' : '<span class="badge badge--neutral">No</span>' ?></td>
                <td><?= !empty($r['is_active']) ? '<span class="badge badge--success">Actiu</span>' : '<span class="badge badge--neutral">Inactiu</span>' ?></td>
                <td class="data-table__actions">
                    <?php if ($canEdit && empty($r['is_catalog'])): ?><button type="button" class="btn btn--sm btn--icon-edit" data-job-positions-edit="<?= (int) $r['id'] ?>"><?= ui_icon('pencil-square') ?></button><?php endif; ?>
                    <?php if ($canDelete && empty($r['is_catalog'])): ?><button type="button" class="btn btn--sm btn--icon-del" data-job-positions-delete="<?= (int) $r['id'] ?>"><?= ui_icon('trash') ?></button><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php $dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Llocs de treball',
    'subtitle' => 'Manteniment de llocs de treball vinculats a unitats organitzatives',
    'back_url' => app_url('dashboard.php'),
    'back_label' => 'Tauler',
]);
ob_start();
if ($totalPages > 1 || $totalRows > 0) {
    $base = $filterQueryBase;
    $base['sort_by'] = $sortBy;
    $base['sort_dir'] = $sortDir;
    $paginationPage = $page;
    $paginationTotalPages = $totalPages;
    $paginationBuildUrl = static function (int $p) use ($base): string {
        return job_positions_view_query_url($base, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';
require APP_ROOT . '/views/partials/job_positions_modal.php';

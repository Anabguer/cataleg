<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array{id:int,unit_code:int,position_number:int,name:string}> $jobPositionsSelect */
/** @var array{q:string,active:string,job_position_id:string,has_job_position:string,is_catalog:string} $filters */
/** @var bool $canCreate */ /** @var bool $canEdit */ /** @var bool $canDelete */
/** @var string $sortBy */ /** @var string $sortDir */
/** @var int $page */ /** @var int $perPage */ /** @var int $totalRows */ /** @var int $totalPages */ /** @var int $offset */

$filterQueryBase = people_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);
$shownFrom = $totalRows === 0 ? 0 : $offset + 1;
$shownTo = $totalRows === 0 ? 0 : min($offset + count($rows), $totalRows);

ob_start(); ?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-people-open-create><?= ui_icon('plus') ?> Nova persona</button>
    <?php endif; ?>
</div>
<?php $actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = true;
$filterShowClear = false;
ob_start(); ?>
<form method="get" action="<?= e(app_url('people.php')) ?>" class="users-filter-form" id="people-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="people_q">Cercar</label>
        <input class="form-input" type="search" id="people_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Cognoms, nom, DNI, correu o codi…" lang="ca" spellcheck="true">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="people_active">Estat</label>
        <select class="form-select" id="people_active" name="active">
            <option value="">Tots</option>
            <option value="1"<?= $filters['active'] === '1' ? ' selected' : '' ?>>Actiu</option>
            <option value="0"<?= $filters['active'] === '0' ? ' selected' : '' ?>>Inactiu</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="people_jp">Lloc de treball</label>
        <select class="form-select" id="people_jp" name="job_position_id">
            <option value="">Tots</option>
            <?php foreach ($jobPositionsSelect as $jp): ?>
                <option value="<?= (int) $jp['id'] ?>"<?= (string) $filters['job_position_id'] === (string) $jp['id'] ? ' selected' : '' ?>><?= e(format_job_position_code((int) $jp['unit_code'], (int) $jp['position_number']) . ' — ' . (string) $jp['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="people_has_job">Assignació de lloc</label>
        <select class="form-select" id="people_has_job" name="has_job_position">
            <option value="">Tots</option>
            <option value="1"<?= $filters['has_job_position'] === '1' ? ' selected' : '' ?>>Amb lloc assignat</option>
            <option value="0"<?= $filters['has_job_position'] === '0' ? ' selected' : '' ?>>Sense lloc</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="people_catalog">Catàleg</label>
        <select class="form-select" id="people_catalog" name="is_catalog">
            <option value="">Tots</option>
            <option value="1"<?= $filters['is_catalog'] === '1' ? ' selected' : '' ?>>Sí</option>
            <option value="0"<?= $filters['is_catalog'] === '0' ? ' selected' : '' ?>>No</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="people_pp">Per pàgina</label>
        <select class="form-select" id="people_pp" name="per_page" data-preserve-on-filter-clear><?php foreach ([10, 20, 50, 100] as $pp): ?><option value="<?= $pp ?>"<?= $perPage === $pp ? ' selected' : '' ?>><?= $pp ?></option><?php endforeach; ?></select>
    </div>
    <div class="users-filter-actions">
        <button type="submit" class="btn btn--filter-icon btn--filter-apply"><img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48"></button>
        <button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear"><img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48"></button>
    </div>
</form>
<?php $filterCardInner = ob_get_clean();

$dataTableCaption = 'Llistat de persones';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalRows;
ob_start(); ?>
<table class="data-table">
    <thead>
        <tr>
            <th><?php [$sym] = people_view_sort_indicator('person_code', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('person_code', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'person_code' ? ' is-active' : '' ?>">Codi <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('person_name', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('person_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'person_name' ? ' is-active' : '' ?>">Cognoms i nom <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('dni', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('dni', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'dni' ? ' is-active' : '' ?>">DNI <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('email', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('email', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'email' ? ' is-active' : '' ?>">Correu <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('job_position', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('job_position', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'job_position' ? ' is-active' : '' ?>">Lloc de treball <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('is_catalog', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('is_catalog', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_catalog' ? ' is-active' : '' ?>">Catàleg <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th><?php [$sym] = people_view_sort_indicator('is_active', $sortBy, $sortDir); ?>
                <a href="<?= e(people_view_sort_href('is_active', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_active' ? ' is-active' : '' ?>">Estat <span class="data-table__sort"><?= e($sym) ?></span></a>
            </th>
            <th class="data-table__actions">Accions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="muted">No hi ha persones.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr data-people-id="<?= (int) $r['id'] ?>">
                <td><strong><?= e(format_padded_code((int) $r['person_code'], 5)) ?></strong></td>
                <td><?= e(people_view_format_full_name($r)) ?></td>
                <td><?= e((string) ($r['dni'] ?? '') ?: '—') ?></td>
                <td><?= e((string) ($r['email'] ?? '') ?: '—') ?></td>
                <td><?= e(people_view_job_position_label($r)) ?></td>
                <td><?= !empty($r['is_catalog']) ? '<span class="badge badge--success">Sí</span>' : '<span class="badge badge--neutral">No</span>' ?></td>
                <td><?= !empty($r['is_active']) ? '<span class="badge badge--success">Actiu</span>' : '<span class="badge badge--neutral">Inactiu</span>' ?></td>
                <td class="data-table__actions">
                    <?php if ($canEdit && empty($r['is_catalog'])): ?><button type="button" class="btn btn--sm btn--icon-edit" data-people-edit="<?= (int) $r['id'] ?>"><?= ui_icon('pencil-square') ?></button><?php endif; ?>
                    <?php if ($canDelete && empty($r['is_catalog'])): ?><button type="button" class="btn btn--sm btn--icon-del" data-people-delete="<?= (int) $r['id'] ?>"><?= ui_icon('trash') ?></button><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php $dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Persones',
    'subtitle' => 'Manteniment de persones per a la formació',
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
        return people_view_query_url($base, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';
require APP_ROOT . '/views/partials/people_modal.php';

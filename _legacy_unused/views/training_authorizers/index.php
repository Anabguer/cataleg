<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $areasSelect */
/** @var array{q:string,area_id:string,active:string} $filters */
/** @var bool $canCreate */ /** @var bool $canEdit */ /** @var bool $canDelete */
/** @var string $sortBy */ /** @var string $sortDir */
/** @var int $page */ /** @var int $perPage */ /** @var int $totalRows */ /** @var int $totalPages */ /** @var int $offset */

$filterQueryBase = training_authorizers_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);
$shownFrom = $totalRows === 0 ? 0 : $offset + 1;
$shownTo = $totalRows === 0 ? 0 : min($offset + count($rows), $totalRows);

ob_start(); ?>
<div class="action-bar__group">
    <?php if ($canCreate): ?><button type="button" class="btn btn--outline btn--module-accent-outline" data-training-authorizers-open-create><?= ui_icon('plus') ?> Nou autoritzador</button><?php endif; ?>
</div>
<?php $actionBarInner = ob_get_clean();

$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = true;
$filterShowClear = false;
ob_start(); ?>
<form method="get" action="<?= e(app_url('training_authorizers.php')) ?>" class="users-filter-form" id="training-authorizers-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field"><label class="form-label" for="taf_q">Cercar</label><input class="form-input" type="search" id="taf_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Nom o àrea…" lang="ca" spellcheck="true"></div>
    <div class="filter-bar__field"><label class="form-label" for="taf_area">Àrea</label><select class="form-select" id="taf_area" name="area_id"><option value="">Totes</option><?php foreach($areasSelect as $a): ?><option value="<?= (int)$a['id'] ?>"<?= (string)$filters['area_id']===(string)$a['id']?' selected':'' ?>><?= e(format_padded_code((int)$a['area_code'],1).' - '.(string)$a['name']) ?></option><?php endforeach; ?></select></div>
    <div class="filter-bar__field"><label class="form-label" for="taf_active">Estat</label><select class="form-select" id="taf_active" name="active"><option value="">Tots</option><option value="1"<?= $filters['active']==='1'?' selected':'' ?>>Actiu</option><option value="0"<?= $filters['active']==='0'?' selected':'' ?>>Inactiu</option></select></div>
    <div class="filter-bar__field"><label class="form-label" for="taf_pp">Per pàgina</label><select class="form-select" id="taf_pp" name="per_page" data-preserve-on-filter-clear><?php foreach([10,20,50,100] as $pp): ?><option value="<?= $pp ?>"<?= $perPage===$pp?' selected':'' ?>><?= $pp ?></option><?php endforeach; ?></select></div>
    <div class="users-filter-actions"><button type="submit" class="btn btn--filter-icon btn--filter-apply"><img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48"></button><button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear"><img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48"></button></div>
</form>
<?php $filterCardInner = ob_get_clean();

$dataTableCaption = 'Llistat d’autoritzadors de formació';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalRows;
ob_start(); ?>
<table class="data-table"><thead><tr>
<th><?php [$sym,$title]=training_authorizers_view_sort_indicator('area_name',$sortBy,$sortDir); ?><a href="<?= e(training_authorizers_view_sort_href('area_name',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='area_name'?' is-active':'' ?>">Àrea <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=training_authorizers_view_sort_indicator('full_name',$sortBy,$sortDir); ?><a href="<?= e(training_authorizers_view_sort_href('full_name',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='full_name'?' is-active':'' ?>">Autoritzador <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=training_authorizers_view_sort_indicator('is_active',$sortBy,$sortDir); ?><a href="<?= e(training_authorizers_view_sort_href('is_active',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='is_active'?' is-active':'' ?>">Estat <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th class="data-table__actions">Accions</th>
</tr></thead><tbody>
<?php if($rows===[]): ?><tr><td colspan="4" class="muted">No hi ha autoritzadors.</td></tr><?php else: foreach($rows as $r): ?>
<tr data-training-authorizer-id="<?= (int)$r['id'] ?>">
    <td><?= e(format_padded_code((int)$r['area_code'],1) . ' - ' . (string)$r['area_name']) ?><?= !empty($r['area_alias']) ? ' (' . e((string)$r['area_alias']) . ')' : '' ?></td>
    <td><strong><?= e((string)$r['full_name']) ?></strong></td>
    <td><?= !empty($r['is_active'])?'<span class="badge badge--success">Actiu</span>':'<span class="badge badge--neutral">Inactiu</span>' ?></td>
    <td class="data-table__actions"><?php if($canEdit): ?><button type="button" class="btn btn--sm btn--icon-edit" data-training-authorizers-edit="<?= (int)$r['id'] ?>"><?= ui_icon('pencil-square') ?></button><?php endif; ?><?php if($canDelete): ?><button type="button" class="btn btn--sm btn--icon-del" data-training-authorizers-delete="<?= (int)$r['id'] ?>"><?= ui_icon('trash') ?></button><?php endif; ?></td>
</tr>
<?php endforeach; endif; ?></tbody></table>
<?php $dataTableInner = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Autoritzadors de formació',
    'subtitle' => 'Manteniment auxiliar d’autoritzadors',
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
        return training_authorizers_view_query_url($base, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';
require APP_ROOT . '/views/partials/training_authorizers_modal.php';

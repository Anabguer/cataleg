<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $unitRows */
/** @var list<array<string,mixed>> $areasSelect */
/** @var list<array<string,mixed>> $sectionsSelect */
/** @var array{q:string,area_id:string,section_id:string,active:string} $filters */
/** @var bool $canCreate */ /** @var bool $canEdit */ /** @var bool $canDelete */
/** @var string $sortBy */ /** @var string $sortDir */
/** @var int $page */ /** @var int $perPage */ /** @var int $totalUnits */ /** @var int $totalPages */ /** @var int $offset */

$filterQueryBase = units_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);
$shownFrom = $totalUnits === 0 ? 0 : $offset + 1;
$shownTo = $totalUnits === 0 ? 0 : min($offset + count($unitRows), $totalUnits);
ob_start(); ?>
<div class="action-bar__group"><?php if($canCreate): ?><button type="button" class="btn btn--outline btn--module-accent-outline" data-units-open-create><?= ui_icon('plus') ?> Nova unitat</button><?php endif; ?></div>
<?php $actionBarInner = ob_get_clean();
$filterSummaryLabel='Filtres de cerca'; $filterExpanded=true; $filterShowClear=false; ob_start(); ?>
<form method="get" action="<?= e(app_url('units.php')) ?>" class="users-filter-form" id="units-filter-form">
<input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear><input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
<div class="filter-bar__field"><label class="form-label" for="uf_q2">Cercar</label><input class="form-input" type="search" id="uf_q2" name="q" value="<?= e($filters['q']) ?>" placeholder="Codi, nom, secció o àrea…"></div>
<div class="filter-bar__field"><label class="form-label" for="uf_area2">Àrea</label><select class="form-select" id="uf_area2" name="area_id"><option value="">Totes</option><?php foreach($areasSelect as $a): ?><option value="<?= (int)$a['id'] ?>"<?= (string)$filters['area_id']===(string)$a['id']?' selected':'' ?>><?= e(format_padded_code((int)$a['area_code'],1).' - '.(string)$a['name']) ?></option><?php endforeach; ?></select></div>
<div class="filter-bar__field"><label class="form-label" for="uf_section2">Secció</label><select class="form-select" id="uf_section2" name="section_id"><option value="">Totes</option><?php foreach($sectionsSelect as $s): ?><option value="<?= (int)$s['id'] ?>"<?= (string)$filters['section_id']===(string)$s['id']?' selected':'' ?>><?= e(format_padded_code((int)$s['section_code'],2).' - '.(string)$s['name']) ?></option><?php endforeach; ?></select></div>
<div class="filter-bar__field"><label class="form-label" for="uf_active2">Estat</label><select class="form-select" id="uf_active2" name="active"><option value="">Tots</option><option value="1"<?= $filters['active']==='1'?' selected':'' ?>>Actiu</option><option value="0"<?= $filters['active']==='0'?' selected':'' ?>>Inactiu</option></select></div>
<div class="filter-bar__field"><label class="form-label" for="uf_pp2">Per pàgina</label><select class="form-select" id="uf_pp2" name="per_page" data-preserve-on-filter-clear><?php foreach([10,20,50,100] as $pp): ?><option value="<?= $pp ?>"<?= $perPage===$pp?' selected':'' ?>><?= $pp ?></option><?php endforeach; ?></select></div>
<div class="users-filter-actions"><button type="submit" class="btn btn--filter-icon btn--filter-apply"><img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48"></button><button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear"><img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48"></button></div>
</form>
<?php $filterCardInner = ob_get_clean();
$dataTableCaption='Llistat d’unitats'; $dataTableToolbar='Mostrant '.$shownFrom.'–'.$shownTo.' de '.$totalUnits; ob_start(); ?>
<table class="data-table"><thead><tr>
<th><?php [$sym,$title]=units_view_sort_indicator('unit_code',$sortBy,$sortDir); ?><a href="<?= e(units_view_sort_href('unit_code',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='unit_code'?' is-active':'' ?>">Codi <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=units_view_sort_indicator('name',$sortBy,$sortDir); ?><a href="<?= e(units_view_sort_href('name',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='name'?' is-active':'' ?>">Nom <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=units_view_sort_indicator('section_name',$sortBy,$sortDir); ?><a href="<?= e(units_view_sort_href('section_name',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='section_name'?' is-active':'' ?>">Secció <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=units_view_sort_indicator('area_name',$sortBy,$sortDir); ?><a href="<?= e(units_view_sort_href('area_name',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='area_name'?' is-active':'' ?>">Àrea <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th><?php [$sym,$title]=units_view_sort_indicator('is_active',$sortBy,$sortDir); ?><a href="<?= e(units_view_sort_href('is_active',$sortBy,$sortDir,$filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy==='is_active'?' is-active':'' ?>">Estat <span class="data-table__sort"><?= e($sym) ?></span></a></th>
<th class="data-table__actions">Accions</th>
</tr></thead><tbody>
<?php if($unitRows===[]): ?><tr><td colspan="6" class="muted">No hi ha unitats.</td></tr><?php else: foreach($unitRows as $u): ?>
<tr data-unit-id="<?= (int)$u['id'] ?>"><td><strong><?= e(format_padded_code((int)$u['unit_code'],4)) ?></strong></td><td><?= e((string)$u['name']) ?></td><td><?= e(format_padded_code((int)$u['section_code'],2).' - '.(string)$u['section_name']) ?></td><td><?= e(format_padded_code((int)$u['area_code'],1).' - '.(string)$u['area_name']) ?></td><td><?= !empty($u['is_active'])?'<span class="badge badge--success">Actiu</span>':'<span class="badge badge--neutral">Inactiu</span>' ?></td><td class="data-table__actions"><?php if($canEdit): ?><button type="button" class="btn btn--sm btn--icon-edit" data-units-edit="<?= (int)$u['id'] ?>"><?= ui_icon('pencil-square') ?></button><?php endif; ?><?php if($canDelete): ?><button type="button" class="btn btn--sm btn--icon-del" data-units-delete="<?= (int)$u['id'] ?>"><?= ui_icon('trash') ?></button><?php endif; ?></td></tr>
<?php endforeach; endif; ?></tbody></table>
<?php $dataTableInner = ob_get_clean();
$pageHeader = page_header_with_escut(['title'=>'Unitats','subtitle'=>'Manteniment d’unitats organitzatives','back_url'=>app_url('dashboard.php'),'back_label'=>'Tauler']);
ob_start();
if ($totalPages > 1 || $totalUnits > 0) {
    $base = $filterQueryBase;
    $base['sort_by'] = $sortBy;
    $base['sort_dir'] = $sortDir;
    $paginationPage = $page;
    $paginationTotalPages = $totalPages;
    $paginationBuildUrl = static function (int $p) use ($base): string {
        return units_view_query_url($base, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();
echo '<div class="module-users">'; require APP_ROOT . '/views/layouts/admin_page.php'; echo '</div>'; require APP_ROOT . '/views/partials/units_modal.php';

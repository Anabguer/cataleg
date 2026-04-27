<?php
declare(strict_types=1);
/** @var array<string,mixed> $config */
/** @var string $module */
/** @var int $year */
/** @var list<array<string,mixed>> $rows */
/** @var int $total */
/** @var int $offset */
/** @var int $totalPages */
/** @var int $page */
/** @var int $perPage */
/** @var array{by:string,dir:string} $sort */
/** @var string $q */

$implemented = (bool) ($config['implemented'] ?? false);
$listCols = maintenance_table_columns($module, $implemented);
$tableColspan = count($listCols) + ($module === 'maintenance_personal_transitory_bonus' ? 0 : 1);

$filterBase = maintenance_view_filter_query_base($module, $q, $perPage, $sort['by'], $sort['dir']);

$pageHeader = page_header_with_escut([
    'title' => (string) $config['title'],
    'subtitle' => 'Manteniment auxiliar · Any actiu ' . $year,
]);

ob_start();
?>
<div class="action-bar__group">
    <?php if ($implemented && can_create_form($module)): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-maintenance-open-create><?= ui_icon('plus') ?> Nou registre</button>
    <?php endif; ?>
    <?php if ($implemented && in_array($module, ['maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus'], true) && can_edit_form($module)): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-salary-increment>Increment Imports</button>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-salary-apply>Actualitzar Imports</button>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-salary-cancel>Anul·lar Increment</button>
        <?php if ($module === 'maintenance_seniority_pay_by_group'): ?>
            <button type="button" class="btn btn--outline btn--module-accent-outline" data-seniority-people-update>Actualitzar triennis persona</button>
        <?php endif; ?>
        <?php if ($module === 'maintenance_specific_compensation_special_prices'): ?>
            <button type="button" class="btn btn--outline btn--module-accent-outline" data-special-prices-update>Actualitzar preus Lloc</button>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php if ($implemented && in_array($module, ['maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus'], true)): ?>
    <div class="maintenance-legend-note">
        Per actualitzar els imports, prémer el botó "Increment Imports", demanarà % increment i visualitzarà els valors nous per verificar si és correcte. Si es dóna per bona la modificació, i es vol actualitzar els valors, prémer el botó "Actualitzar Imports" i els imports es modificaran pels nous. Si vols anul·lar el increment prémer el botó "Anul·lar Increment"
    </div>
<?php endif; ?>
<?php if ($implemented && $module === 'maintenance_seniority_pay_by_group'): ?>
    <div class="maintenance-legend-note maintenance-legend-note--strong">
        Un cop actualitzats els preus del Trienni, s'ha de prémer el botó d'Actualitzar Triennis Persona perquè els canvis efectuats es vegin reflectits en el Catáleg de Persones.
    </div>
<?php endif; ?>
<?php if ($implemented && $module === 'maintenance_specific_compensation_special_prices'): ?>
    <div class="maintenance-legend-note maintenance-legend-note--strong">
        Un cop actualitzats els preus del Complement Específic Especial, s'ha de prémer el botó d'Actualitzar preus Lloc perquè els canvis efectuats es vegin reflectits en el Lloc de Treball.
    </div>
<?php endif; ?>
<?php
$actionBarInner = ob_get_clean();

$filterExpanded = true;
$filterSummaryLabel = 'Filtres de cerca';
$filterShowClear = false;
ob_start();
?>
<form method="get" action="<?= e(app_url('maintenance.php')) ?>" class="users-filter-form" id="maintenance-filter-form">
    <input type="hidden" name="module" value="<?= e($module) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_by" value="<?= e($sort['by']) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sort['dir']) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="m_q">Cercar</label>
        <input class="form-input" id="m_q" name="q" value="<?= e($q) ?>" placeholder="Codi o nom">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="m_per_page">Per pàgina</label>
        <select class="form-select" id="m_per_page" name="per_page">
            <?php foreach ([10, 20, 50, 100] as $pp): ?>
                <option value="<?= $pp ?>"<?= $perPage === $pp ? ' selected' : '' ?>><?= $pp ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="users-filter-actions">
        <button type="submit" class="btn btn--filter-icon btn--filter-apply" title="Aplicar filtres" aria-label="Aplicar filtres">
            <img src="<?= e(asset_url('img/icon_filter_apply.svg')) ?>" alt="" width="48" height="48">
        </button>
        <button type="button" class="btn btn--filter-icon btn--filter-clear js-filter-clear" title="Netejar filtres" aria-label="Netejar filtres">
            <img src="<?= e(asset_url('img/icon_filter_clear.svg')) ?>" alt="" width="48" height="48">
        </button>
    </div>
</form>
<?php
$filterCardInner = ob_get_clean();

ob_start();
?>
<table class="data-table<?= $module === 'maintenance_subprograms' ? ' data-table--subprograms' : '' ?><?= $module === 'maintenance_personal_transitory_bonus' ? ' data-table--personal-transitory-cpt' : '' ?>">
    <thead>
    <tr>
        <?php foreach ($listCols as $col): ?>
            <?php $headAlign = (string) ($col['cell']['align'] ?? ''); ?>
            <?php $headExtraClass = trim((string) ($col['cell']['header_class'] ?? '')); ?>
            <?php $colHeaderTitle = trim((string) ($col['header_title'] ?? '')); ?>
            <?php
            $thClasses = [];
            if ($headAlign !== '') {
                $thClasses[] = 'table-cell--' . $headAlign;
            }
            if ($headExtraClass !== '') {
                $thClasses[] = $headExtraClass;
            }
            ?>
            <?php if (!empty($col['sortable'])): ?>
                <?php [$sym, $title] = maintenance_view_sort_indicator($col['sort_key'], $sort['by'], $sort['dir']); ?>
                <th<?= $thClasses !== [] ? ' class="' . e(implode(' ', $thClasses)) . '"' : '' ?><?= $colHeaderTitle !== '' ? ' title="' . e($colHeaderTitle) . '"' : '' ?>><a href="<?= e(maintenance_view_sort_href($col['sort_key'], $sort['by'], $sort['dir'], $filterBase)) ?>" class="data-table__sort-link<?= $sort['by'] === $col['sort_key'] ? ' is-active' : '' ?>"><?= e($col['label']) ?> <span class="data-table__sort" title="<?= e($title) ?>"><?= e($sym) ?></span></a></th>
            <?php else: ?>
                <th<?= $thClasses !== [] ? ' class="' . e(implode(' ', $thClasses)) . '"' : '' ?><?= $colHeaderTitle !== '' ? ' title="' . e($colHeaderTitle) . '"' : '' ?>><?= e($col['label']) ?></th>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($module !== 'maintenance_personal_transitory_bonus'): ?>
        <th class="data-table__actions table__actions-header<?= $module === 'maintenance_subprograms' ? ' table__actions-header--maint-sub' : '' ?>">Accions</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php if (!$implemented): ?>
        <tr><td colspan="<?= (int) $tableColspan ?>" class="muted">Mòdul preparat per al menú. Implementació pendent en fase següent.</td></tr>
    <?php elseif ($rows === []): ?>
        <tr><td colspan="<?= (int) $tableColspan ?>" class="muted">No hi ha registres.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <?php foreach ($listCols as $colDef): ?>
                <?php $cellAlign = (string) ($colDef['cell']['align'] ?? ''); ?>
                <?php $cellClass = trim((string) ($colDef['cell']['class'] ?? '')); ?>
                <?php
                $tdClasses = [];
                if ($cellAlign !== '') {
                    $tdClasses[] = 'table-cell--' . $cellAlign;
                }
                if ($cellClass !== '') {
                    $tdClasses[] = $cellClass;
                }
                $tdClassAttr = $tdClasses !== [] ? ' class="' . e(implode(' ', $tdClasses)) . '"' : '';
                ?>
                <?php if ($module === 'maintenance_personal_transitory_bonus' && ($colDef['cell']['field'] ?? '') === 'personal_transitory_bonus_new'): ?>
                    <?php
                    $pid = (int) ($r['person_id'] ?? 0);
                    $rawNew = $r['personal_transitory_bonus_new'] ?? null;
                    $inputVal = '';
                    if ($rawNew !== null && $rawNew !== '' && is_numeric(trim((string) $rawNew))) {
                        $inputVal = number_format((float) $rawNew, 2, ',', '.');
                    }
                    ?>
                    <td<?= $tdClassAttr ?>>
                        <?php if (can_edit_form($module)): ?>
                            <span class="maintenance-ptb-new__cell">
                                <input type="text" class="form-input form-input--sm maintenance-ptb-new__input" data-ptb-new-input data-person-id="<?= $pid ?>" value="<?= e($inputVal) ?>" inputmode="decimal" autocomplete="off" aria-label="CPT incrementat">
                                <span class="maintenance-ptb-new__hint" aria-live="polite" hidden></span>
                            </span>
                        <?php else: ?>
                            <?= e(maintenance_format_currency_eur_2_display($rawNew)) ?>
                        <?php endif; ?>
                    </td>
                <?php else: ?>
                <td<?= $tdClassAttr ?>><?= maintenance_column_cell_html($colDef, $r) ?></td>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($module !== 'maintenance_personal_transitory_bonus'): ?>
            <?php
            if ($module === 'maintenance_scales') {
                $rid = (string) (int) ($r['scale_id'] ?? 0);
            } elseif ($module === 'maintenance_subscales') {
                $rid = (string) (int) ($r['subscale_id'] ?? 0);
            } elseif ($module === 'maintenance_classes') {
                $rid = (string) (int) ($r['class_id'] ?? 0);
            } elseif ($module === 'maintenance_categories') {
                $rid = (string) (int) ($r['category_id'] ?? 0);
            } elseif ($module === 'maintenance_administrative_statuses') {
                $rid = (string) (int) ($r['administrative_status_id'] ?? 0);
            } elseif ($module === 'maintenance_position_classes') {
                $rid = (string) (int) ($r['position_class_id'] ?? 0);
            } elseif ($module === 'maintenance_legal_relationships') {
                $rid = (string) (int) ($r['legal_relation_id'] ?? 0);
            } elseif ($module === 'maintenance_access_types') {
                $rid = (string) (int) ($r['access_type_id'] ?? 0);
            } elseif ($module === 'maintenance_access_systems') {
                $rid = (string) (int) ($r['access_system_id'] ?? 0);
            } elseif ($module === 'maintenance_work_centers') {
                $rid = (string) (int) ($r['work_center_id'] ?? 0);
            } elseif ($module === 'maintenance_availability_types') {
                $rid = trim((string) ($r['availability_id'] ?? ''));
            } elseif ($module === 'maintenance_provision_forms') {
                $rid = trim((string) ($r['provision_method_id'] ?? ''));
            } elseif ($module === 'maintenance_organic_level_1') {
                $rid = (string) (int) ($r['org_unit_level_1_id'] ?? 0);
            } elseif ($module === 'maintenance_organic_level_2') {
                $rid = trim((string) ($r['org_unit_level_2_id'] ?? ''));
            } elseif ($module === 'maintenance_organic_level_3') {
                $rid = trim((string) ($r['org_unit_level_3_id'] ?? ''));
            } elseif ($module === 'maintenance_programs') {
                $rid = trim((string) ($r['program_id'] ?? ''));
            } elseif ($module === 'maintenance_social_security_companies') {
                $rid = trim((string) ($r['company_id'] ?? ''));
            } elseif ($module === 'maintenance_social_security_coefficients') {
                $rid = trim((string) ($r['contribution_epigraph_id'] ?? ''));
            } elseif ($module === 'maintenance_social_security_base_limits') {
                $rid = trim((string) ($r['contribution_group_id'] ?? ''));
            } elseif ($module === 'maintenance_salary_base_by_group') {
                $rid = trim((string) ($r['classification_group'] ?? ''));
            } elseif ($module === 'maintenance_destination_allowances') {
                $rid = trim((string) ($r['organic_level'] ?? ''));
            } elseif ($module === 'maintenance_seniority_pay_by_group') {
                $rid = trim((string) ($r['classification_group'] ?? ''));
            } elseif ($module === 'maintenance_specific_compensation_special_prices') {
                $rid = trim((string) ($r['special_specific_compensation_id'] ?? ''));
            } elseif ($module === 'maintenance_specific_compensation_general') {
                $rid = trim((string) ($r['general_specific_compensation_id'] ?? ''));
            } elseif ($module === 'maintenance_subprograms') {
                $rid = trim((string) ($r['subprogram_id'] ?? ''));
            } else {
                $rid = trim((string) ($r['scale_id'] ?? $r['subscale_id'] ?? $r['category_id'] ?? $r['class_id'] ?? $r['administrative_status_id'] ?? $r['position_class_id'] ?? $r['legal_relation_id'] ?? $r['access_type_id'] ?? $r['access_system_id'] ?? $r['work_center_id'] ?? $r['availability_id'] ?? $r['provision_method_id'] ?? $r['org_unit_level_1_id'] ?? $r['org_unit_level_2_id'] ?? $r['org_unit_level_3_id'] ?? $r['program_id'] ?? $r['subprogram_id'] ?? ''));
            }
            $specPriceReservedZero = ($module === 'maintenance_specific_compensation_special_prices' && $rid === '0');
            ?>
            <td class="data-table__actions table__actions-cell<?= $module === 'maintenance_subprograms' ? ' table__actions-cell--maint-sub' : '' ?>">
                <div class="row-actions">
                    <?php if ($implemented && can_view_form($module)): ?>
                        <button type="button" class="btn btn--sm btn--icon-edit js-maintenance-view" data-action="view" data-maintenance-view="<?= $rid ?>" title="Visualitzar" aria-label="Visualitzar registre"><?= ui_icon('eye') ?></button>
                    <?php endif; ?>
                    <?php if ($implemented && can_edit_form($module) && !$specPriceReservedZero): ?>
                        <button type="button" class="btn btn--sm btn--icon-edit" data-maintenance-edit="<?= $rid ?>" title="Editar" aria-label="Editar registre"><?= ui_icon('pencil-square') ?></button>
                    <?php endif; ?>
                    <?php if ($implemented && can_delete_form($module) && !$specPriceReservedZero): ?>
                        <button type="button" class="btn btn--sm btn--icon-del" data-maintenance-delete="<?= $rid ?>" title="Eliminar" aria-label="Eliminar registre"><?= ui_icon('trash') ?></button>
                    <?php endif; ?>
                </div>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<?php
$dataTableInner = ob_get_clean();
$dataTableToolbar = 'Mostrant ' . ($total === 0 ? 0 : $offset + 1) . '–' . ($total === 0 ? 0 : min($offset + count($rows), $total)) . ' de ' . $total;

ob_start();
if ($totalPages > 1) {
    $paginationPage = $page;
    $paginationTotalPages = $totalPages;
    $paginationAriaLabel = 'Paginació';
    $paginationBuildUrl = static function (int $p) use ($filterBase): string {
        return maintenance_view_query_url($filterBase, ['page' => (string) $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';
if ($module !== 'maintenance_personal_transitory_bonus') {
    require APP_ROOT . '/views/partials/maintenance_modal.php';
}

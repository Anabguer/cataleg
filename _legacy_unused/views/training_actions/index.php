<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var array{q:string,program_year:string,subprogram_id:string,organizer_id:string,date_from:string,training_location_id:string,knowledge_area_id:string,trainer_type_id:string,execution_status:string,active:string} $filters */
/** @var int $currentCalendarYear */
/** @var list<array{id:int,name:string,code_display:string}> $subprogramsFilter */
/** @var list<array{id:int,name:string,code_display:string}> $organizersFilter */
/** @var list<array{id:int,knowledge_area_code:int,name:string}> $knowledgeAreasFilter */
/** @var list<array{id:int,name:string,code_display:string}> $trainerTypesFilter */
/** @var list<array{id:int,name:string,code_display:string}> $locationsFilter */
/** @var bool $canCreate */ /** @var bool $canEdit */ /** @var bool $canDelete */
/** @var string $sortBy */ /** @var string $sortDir */
/** @var int $page */ /** @var int $perPage */ /** @var int $totalRows */ /** @var int $totalPages */ /** @var int $offset */

$filterQueryBase = training_actions_view_filter_query_base($filters, $sortBy, $sortDir, $perPage);
$shownFrom = $totalRows === 0 ? 0 : $offset + 1;
$shownTo = $totalRows === 0 ? 0 : min($offset + count($rows), $totalRows);

$yearOptions = [];
for ($y = $currentCalendarYear - 5; $y <= $currentCalendarYear + 6; $y++) {
    $yearOptions[] = $y;
}

ob_start(); ?>
<div class="action-bar__group">
    <?php if ($canCreate): ?>
        <button type="button" class="btn btn--outline btn--module-accent-outline" data-ta-open-create>
            <?= ui_icon('plus') ?> Nova acció formativa
        </button>
    <?php endif; ?>
</div>
<?php
$actionBarInner = ob_get_clean();
$filterSummaryLabel = 'Filtres de cerca';
$filterExpanded = true;
$filterShowClear = false;
ob_start(); ?>
<form method="get" action="<?= e(app_url('training_actions.php')) ?>" class="users-filter-form" id="training-actions-filter-form">
    <input type="hidden" name="sort_by" value="<?= e($sortBy) ?>" data-preserve-on-filter-clear>
    <input type="hidden" name="sort_dir" value="<?= e($sortDir) ?>" data-preserve-on-filter-clear>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_q">Cercar</label>
        <input class="form-input" type="search" id="ta_q" name="q" value="<?= e($filters['q']) ?>" placeholder="Codi, nom, estat…" lang="ca" spellcheck="true">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_py">Any programa</label>
        <select class="form-select" id="ta_py" name="program_year">
            <option value="">Tots</option>
            <?php foreach ($yearOptions as $y): ?>
                <option value="<?= $y ?>"<?= (string) $filters['program_year'] === (string) $y ? ' selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_sp">Subprograma</label>
        <select class="form-select" id="ta_sp" name="subprogram_id">
            <option value="">Tots</option>
            <?php foreach ($subprogramsFilter as $s): ?>
                <option value="<?= (int) $s['id'] ?>"<?= (string) $filters['subprogram_id'] === (string) $s['id'] ? ' selected' : '' ?>><?= e($s['code_display'] . ' — ' . $s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_org">Organitzador</label>
        <select class="form-select" id="ta_org" name="organizer_id">
            <option value="">Tots</option>
            <?php foreach ($organizersFilter as $o): ?>
                <option value="<?= (int) $o['id'] ?>"<?= (string) $filters['organizer_id'] === (string) $o['id'] ? ' selected' : '' ?>><?= e($o['code_display'] . ' — ' . $o['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_df">Data inici (des de)</label>
        <input class="form-input" type="date" id="ta_df" name="date_from" value="<?= e($filters['date_from']) ?>">
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_loc">Lloc d’impartició</label>
        <select class="form-select" id="ta_loc" name="training_location_id">
            <option value="">Tots</option>
            <?php foreach ($locationsFilter as $l): ?>
                <option value="<?= (int) $l['id'] ?>"<?= (string) $filters['training_location_id'] === (string) $l['id'] ? ' selected' : '' ?>><?= e($l['code_display'] . ' — ' . $l['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_ka">Àrea de coneixement</label>
        <select class="form-select" id="ta_ka" name="knowledge_area_id">
            <option value="">Totes</option>
            <?php foreach ($knowledgeAreasFilter as $ka): ?>
                <option value="<?= (int) $ka['id'] ?>"<?= (string) $filters['knowledge_area_id'] === (string) $ka['id'] ? ' selected' : '' ?>><?= e(format_padded_code((int) $ka['knowledge_area_code'], 3) . ' — ' . $ka['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_tt">Tipus de formador</label>
        <select class="form-select" id="ta_tt" name="trainer_type_id">
            <option value="">Tots</option>
            <?php foreach ($trainerTypesFilter as $t): ?>
                <option value="<?= (int) $t['id'] ?>"<?= (string) $filters['trainer_type_id'] === (string) $t['id'] ? ' selected' : '' ?>><?= e($t['code_display'] . ' — ' . $t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_es">Estat d’execució</label>
        <select class="form-select" id="ta_es" name="execution_status">
            <option value="">Tots</option>
            <?php foreach (training_actions_execution_status_allowed_values() as $esOpt): ?>
                <option value="<?= e($esOpt) ?>"<?= (string) $filters['execution_status'] === $esOpt ? ' selected' : '' ?>><?= e($esOpt) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_act">Actiu</label>
        <select class="form-select" id="ta_act" name="active">
            <option value="">Tots</option>
            <option value="1"<?= $filters['active'] === '1' ? ' selected' : '' ?>>Actiu</option>
            <option value="0"<?= $filters['active'] === '0' ? ' selected' : '' ?>>Inactiu</option>
        </select>
    </div>
    <div class="filter-bar__field">
        <label class="form-label" for="ta_pp">Per pàgina</label>
        <select class="form-select" id="ta_pp" name="per_page" data-preserve-on-filter-clear>
            <?php foreach ([10, 20, 50, 100] as $pp): ?>
                <option value="<?= $pp ?>"<?= $perPage === $pp ? ' selected' : '' ?>><?= $pp ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="users-filter-actions">
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
$dataTableCaption = 'Llistat d’accions formatives';
$dataTableToolbar = 'Mostrant ' . $shownFrom . '–' . $shownTo . ' de ' . $totalRows;
ob_start(); ?>
<table class="data-table">
    <thead>
        <tr>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('display_code', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('display_code', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'display_code' ? ' is-active' : '' ?>">Codi <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('name', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'name' ? ' is-active' : '' ?>">Nom <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('subprogram_name', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('subprogram_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'subprogram_name' ? ' is-active' : '' ?>">Subprograma <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('organizer_name', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('organizer_name', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'organizer_name' ? ' is-active' : '' ?>">Organitzador <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('knowledge_area', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('knowledge_area', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'knowledge_area' ? ' is-active' : '' ?>">Àrea <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('nearest_session_date', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('nearest_session_date', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'nearest_session_date' ? ' is-active' : '' ?>">Data pròxima <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('execution_status', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('execution_status', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'execution_status' ? ' is-active' : '' ?>">Estat exec. <?= e($sym) ?></a>
            </th>
            <th scope="col"><?php [$sym] = training_actions_view_sort_indicator('is_active', $sortBy, $sortDir); ?>
                <a href="<?= e(training_actions_view_sort_href('is_active', $sortBy, $sortDir, $filterQueryBase)) ?>" class="data-table__sort-link<?= $sortBy === 'is_active' ? ' is-active' : '' ?>">Actiu <?= e($sym) ?></a>
            </th>
            <th class="data-table__actions" scope="col">Accions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="9" class="muted">No hi ha accions amb aquests filtres.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r):
                $py = (int) ($r['program_year'] ?? 0);
                $an = (int) ($r['action_number'] ?? 0);
                $dc = training_actions_format_display_code($py, $an);
                $kaL = isset($r['ka_code']) ? format_padded_code((int) $r['ka_code'], 3) . ' — ' . (string) ($r['ka_name'] ?? '') : '—';
                ?>
                <tr data-ta-row data-ta-id="<?= (int) $r['id'] ?>">
                    <td><strong><?= e($dc) ?></strong></td>
                    <td><?= e((string) ($r['name'] ?? '')) ?></td>
                    <td><?= !empty($r['subprogram_name']) ? e((string) $r['subprogram_name']) : '<span class="muted">—</span>' ?></td>
                    <td><?= !empty($r['organizer_name']) ? e((string) $r['organizer_name']) : '<span class="muted">—</span>' ?></td>
                    <td><?= !empty($r['ka_code']) ? e($kaL) : '<span class="muted">—</span>' ?></td>
                    <td><?= training_actions_list_nearest_date_cell(isset($r['nearest_session_date']) ? (string) $r['nearest_session_date'] : null) ?></td>
                    <td><?= training_actions_view_execution_status_cell(isset($r['execution_status']) ? (string) $r['execution_status'] : null) ?></td>
                    <td><?= !empty($r['is_active']) ? '<span class="badge badge--success">Actiu</span>' : '<span class="badge badge--neutral">Inactiu</span>' ?></td>
                    <td class="data-table__actions">
                        <button type="button" class="btn btn--sm btn--icon-edit" title="Visualitzar" data-ta-view="<?= (int) $r['id'] ?>"><?= ui_icon('document') ?></button>
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn--sm btn--icon-edit" title="Editar" data-ta-edit="<?= (int) $r['id'] ?>"><?= ui_icon('pencil-square') ?></button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <button type="button" class="btn btn--sm btn--icon-del" title="Eliminar" data-ta-delete="<?= (int) $r['id'] ?>"><?= ui_icon('trash') ?></button>
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
    'title' => 'Accions formatives',
    'subtitle' => 'Gestió de Formació — programació i execució',
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
    $paginationAriaLabel = 'Paginació del llistat';
    $paginationBuildUrl = static function (int $p) use ($base): string {
        return training_actions_view_query_url($base, ['page' => $p]);
    };
    require APP_ROOT . '/views/partials/pagination_nav.php';
}
$pageContentExtra = ob_get_clean();
echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
require APP_ROOT . '/views/partials/training_actions_modal.php';
echo '</div>';

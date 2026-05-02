<?php
declare(strict_types=1);
/** @var array<string, list<array<string,mixed>>> $groupedReports */
/** @var int $catalogYear Any de catàleg actiu (catalog_year_current des de report_selector.php) */

$pageHeader = page_header_with_escut([
    'title' => 'Selector general d’informes',
    'subtitle' => 'Gestió · Any actiu ' . $catalogYear,
]);

$actionBarInner = '';
if ($groupedReports !== []) {
    ob_start();
    ?>
<div class="action-bar__group">
    <button type="button" class="btn btn--primary btn--sm js-report-selector-accept">Acceptar</button>
    <button type="button" class="btn btn--outline btn--module-accent-outline btn--sm js-report-selector-cancel">Cancel·lar</button>
</div>
    <?php
    $actionBarInner = ob_get_clean();
}

$filterCardInner = '';
$dataTableInner = '';

ob_start();
?>
<section class="form-card reports-page report-selector-page" aria-label="Selector general d’informes">
    <div class="form-card__body">
        <?php if ($groupedReports === []): ?>
            <p class="report-selector-page__empty">No hi ha informes al selector. Activa «Selector general» i «Actiu» al manteniment <a href="<?= e(app_url('maintenance.php?module=reports')) ?>">Informes</a>.</p>
        <?php else: ?>
            <form id="report-selector-form" class="report-selector-form" method="get" action="#" novalidate>
                <div class="form-group form-grid__full">
                    <span class="form-label" id="report-selector-list-label">Informe</span>
                    <div class="report-selector-picker" role="radiogroup" aria-labelledby="report-selector-list-label">
                        <?php $groupIndex = 0; foreach ($groupedReports as $groupTitle => $items): ?>
                            <?php
                            $groupIndex++;
                            $groupDomId = 'report-selector-group-' . $groupIndex;
                            ?>
                            <div class="report-selector-group">
                                <div class="report-selector-group__bar" id="<?= e($groupDomId) ?>"><?= e((string) $groupTitle) ?></div>
                                <div class="report-selector-group__rows" role="group" aria-labelledby="<?= e($groupDomId) ?>">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $code = (string) ($item['report_code'] ?? '');
                                        $name = (string) ($item['report_name'] ?? '');
                                        $ver = $item['report_version'] ?? null;
                                        $verShow = $ver !== null && trim((string) $ver) !== '';
                                        ?>
                                        <label class="report-selector-row">
                                            <input class="report-selector-row__radio" type="radio" name="report_code" value="<?= e($code) ?>">
                                            <span class="report-selector-row__main">
                                                <span class="report-selector-row__code"><?= e($code) ?></span>
                                                <span class="report-selector-row__name"><?= e($name) ?></span>
                                            </span>
                                            <?php if ($verShow): ?>
                                                <span class="report-selector-row__ver"><?= e((string) $ver) ?></span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php
$pageContentExtra = ob_get_clean();

echo '<div class="module-users">';
require APP_ROOT . '/views/layouts/admin_page.php';
echo '</div>';

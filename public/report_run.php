<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

require_can_view('report_selector');

$year = catalog_year_current();
$code = trim((string) get_string('code'));
$yearParam = (int) get_string('catalog_year');

if ($year === null || $year < 1) {
    redirect(app_url('dashboard.php'));
}

if ($code === '') {
    redirect(app_url('report_selector.php'));
}

if ($yearParam !== (int) $year) {
    redirect(app_url('report_run.php?' . http_build_query(['code' => $code, 'catalog_year' => $year])));
}

$pageTitle = 'Execució informe';
$activeNav = 'report_selector';

require APP_ROOT . '/includes/header.php';
?>
<section class="form-card reports-page">
    <div class="form-card__header">
        <h1 class="form-card__title">Execució d’informe</h1>
    </div>
    <div class="form-card__body">
        <p class="report-run-placeholder">Execució pendent per a l’informe <strong><?= e($code) ?></strong> (any de catàleg <strong><?= e((string) $year) ?></strong>).</p>
        <p class="report-run-note">Quan l’informe estigui implementat, aquesta pàgina generarà o obrirà el resultat.</p>
        <p><a class="btn btn--outline" href="<?= e(app_url('report_selector.php')) ?>">Tornar al selector</a></p>
    </div>
</section>
<?php
require APP_ROOT . '/includes/footer.php';

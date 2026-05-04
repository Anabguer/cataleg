<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once APP_ROOT . '/includes/reports/report_helpers.php';

require_can_view('report_selector');

$yearSession = catalog_year_current();
$code = trim((string) get_string('code'));
$yearParam = (int) get_string('catalog_year');

if ($yearSession === null || $yearSession < 1) {
    redirect(app_url('dashboard.php'));
}

if ($code === '') {
    redirect(app_url('report_selector.php'));
}

if ($yearParam !== (int) $yearSession) {
    $q = $_GET;
    $q['code'] = $code;
    $q['catalog_year'] = (string) $yearSession;
    redirect(app_url('report_run.php?' . http_build_query($q)));
}

$db = db();
$reportRow = report_get_for_selector_run($db, $code);
if ($reportRow === null) {
    http_response_code(404);
    $pageTitle = 'Informe no disponible';
    $activeNav = 'report_selector';
    require APP_ROOT . '/includes/header.php';
    echo '<section class="form-card reports-page"><div class="form-card__body"><p>L’informe sol·licitat no existeix o no està actiu al selector.</p>';
    echo '<p><a class="btn btn--outline" href="' . e(app_url('report_selector.php')) . '">Tornar al selector</a></p></div></section>';
    require APP_ROOT . '/includes/footer.php';
    exit;
}

$veureTreballador = get_string('veure_treballador') !== '0';
$comentariRaw = get_string('comentari');
$comentari = $comentariRaw !== '' ? $comentariRaw : null;

switch ($code) {
    case 'RGE-01':
        require_once APP_ROOT . '/includes/reports/rge_01_catalog_llocs.php';
        $reportTitle = report_rge01_display_title($veureTreballador);
        $format = strtolower(get_string('format'));
        if ($format === 'pdf') {
            $autoPrint = get_string('autoprint') === '1';
            report_rge01_run_print_view($db, (int) $yearSession, (string) $reportRow['report_code'], $reportTitle, $comentari, $veureTreballador, $autoPrint);
            exit;
        }
        $pageTitle = $reportTitle;
        $activeNav = 'report_selector';
        $extraCss = ['css/report_print.css'];
        $bodyExtraClasses = 'report-body report-body--rge-01';
        $rge01PdfQuery = http_build_query([
            'code' => 'RGE-01',
            'catalog_year' => (int) $yearSession,
            'format' => 'pdf',
            'veure_treballador' => $veureTreballador ? '1' : '0',
            'comentari' => $comentariRaw,
            'autoprint' => '1',
        ]);
        $rge01PdfUrl = app_url('report_run.php?' . $rge01PdfQuery);
        require APP_ROOT . '/includes/header.php';
        echo '<div class="report-toolbar no-print">';
        echo '<a class="btn btn--outline btn--sm" href="' . e(app_url('report_selector.php')) . '">Tornar al selector</a> ';
        echo '<a class="btn btn--primary btn--sm" href="' . e($rge01PdfUrl) . '" target="_blank" rel="noopener" title="Obre la vista d’impressió (al diàleg, «Desar com a PDF»)">Generar PDF</a>';
        echo '</div>';
        echo '<div class="report-sheet">';
        report_rge01_run($db, (int) $yearSession, (string) $reportRow['report_code'], $reportTitle, $comentari, $veureTreballador);
        echo '</div>';
        require APP_ROOT . '/includes/footer.php';
        exit;
    default:
        $pageTitle = 'Execució informe';
        $activeNav = 'report_selector';
        require APP_ROOT . '/includes/header.php';
        ?>
        <section class="form-card reports-page">
            <div class="form-card__header">
                <h1 class="form-card__title">Execució d’informe</h1>
            </div>
            <div class="form-card__body">
                <p class="report-run-placeholder">Aquest informe (<strong><?= e($code) ?></strong>) encara no està implementat.</p>
                <p><a class="btn btn--outline" href="<?= e(app_url('report_selector.php')) ?>">Tornar al selector</a></p>
            </div>
        </section>
        <?php
        require APP_ROOT . '/includes/footer.php';
        exit;
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once APP_ROOT . '/includes/maintenance/aux_catalog.php';

require_can_view('report_selector');

$year = catalog_year_current();
if ($year === null || $year < 1) {
    redirect(app_url('dashboard.php'));
}
$catalogYear = $year;

$db = db();
$groupedReports = get_grouped_reports($db);

$pageTitle = 'Selector general d’informes';
$activeNav = 'report_selector';
$extraCss = ['css/module-users.css', 'css/reports.css'];
$extraScripts = ['report_selector.js'];
$reportSelectorPageInlineConfig = [
    'runUrl' => app_url('report_run.php'),
    'catalogYear' => $year,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/report_selector/index.php';
require APP_ROOT . '/includes/footer.php';

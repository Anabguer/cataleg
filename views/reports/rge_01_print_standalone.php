<?php
declare(strict_types=1);
/** @var int $year */
/** @var string $reportCode */
/** @var string $reportTitle */
/** @var string|null $comentari */
/** @var bool $veureTreballador */
/** @var array<string, mixed> $grouped */
/** @var array<string, string> $catalogDescriptions */
/** @var string $logoPath */
/** @var string $generatedAt */
/** @var bool $reportAutoPrint obrir diàleg d’impressió en carregar */

$pageTitle = $reportTitle;
$screenBackQuery = http_build_query([
    'code' => 'RGE-01',
    'catalog_year' => (int) $year,
    'veure_treballador' => $veureTreballador ? '1' : '0',
    'comentari' => (string) ($comentari ?? ''),
]);
$backScreenUrl = app_url('report_run.php?' . $screenBackQuery);
$bodyClass = 'report-body report-body--rge-01 report-code--rge-01';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/report_print.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
    <div class="report-toolbar no-print">
        <a class="btn btn--secondary btn--sm" href="<?= e(app_url('report_selector.php')) ?>">Tornar al selector</a>
        <a class="btn btn--outline btn--sm" href="<?= e($backScreenUrl) ?>">Tornar a la vista</a>
        <button type="button" class="btn btn--primary btn--sm" id="rge01-print-pdf-btn" title="Al diàleg, trieu «Desar com a PDF» o la impressora">Generar PDF</button>
    </div>
    <div class="report-sheet">
        <?php /* Mateix patró que formacio_molins_rei/views/reports/layout_report.php: capçalera repetida per pàgina. */ ?>
        <table class="report-print-shell">
            <thead class="report-print-shell__head">
                <tr>
                    <td class="report-print-shell__head-cell">
                        <?php
                        $catalogYear = $year;
                        require APP_ROOT . '/views/partials/report_print_header.php';
                        ?>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="report-print-shell__body">
                        <?php
                        $rge01SkipHeader = true;
                        require APP_ROOT . '/views/reports/rge_01_print.php';
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
    (function () {
        var btn = document.getElementById('rge01-print-pdf-btn');
        function doPrint() { window.print(); }
        if (btn) btn.addEventListener('click', doPrint);
        <?php if (!empty($reportAutoPrint)): ?>
        window.addEventListener('load', function () {
            setTimeout(doPrint, 300);
        });
        <?php endif; ?>
    })();
    </script>
</body>
</html>

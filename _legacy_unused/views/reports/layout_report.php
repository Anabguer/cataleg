<?php
declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $headerHtml */
/** @var string $bodyHtml */
/** @var string $backUrl */
/** @var string|null $reportExcelUrl */
/** @var string|null $reportLayoutBodyExtraClass Classes addicionals al &lt;body&gt; (ex.: report-body--rpe-fc-05) */
/** @var array<string,mixed>|null $reportRow Fila training_reports disponible a tots els render actuals */

$reportExcelUrl = $reportExcelUrl ?? null;
$reportLayoutBodyExtraClass = $reportLayoutBodyExtraClass ?? null;
$reportCodeRaw = isset($reportRow) && is_array($reportRow)
    ? strtoupper(trim((string) ($reportRow['report_code'] ?? '')))
    : '';
$reportCodeBodyClass = '';
if ($reportCodeRaw !== '') {
    $reportCodeBodyClass = ' report-code--' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $reportCodeRaw) ?? '');
}
$bodyClass = 'report-body'
    . ($reportLayoutBodyExtraClass !== null && $reportLayoutBodyExtraClass !== '' ? ' ' . $reportLayoutBodyExtraClass : '')
    . $reportCodeBodyClass;

$reportExplanationRaw = '';
if (isset($reportRow) && is_array($reportRow)) {
    $reportExplanationRaw = trim((string) ($reportRow['report_explanation'] ?? ''));
}
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
        <a class="btn btn--secondary btn--sm" href="<?= e($backUrl) ?>">Tornar a informes</a>
        <?php if (!empty($reportExcelUrl)): ?>
            <a class="btn btn--secondary btn--sm" href="<?= e($reportExcelUrl) ?>">Exportar a Excel</a>
        <?php endif; ?>
        <button type="button" class="btn btn--primary btn--sm" id="report-print-btn">Imprimir</button>
    </div>
    <div class="report-sheet">
        <?php /* Taula: thead repetit per pàgina. L’explicació va dins la mateixa cel·la del cos (després del body) per heretar el mateix context @page que el contingut de l’informe. */ ?>
        <table class="report-print-shell">
            <thead class="report-print-shell__head">
                <tr>
                    <td class="report-print-shell__head-cell">
                        <?= $headerHtml ?>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="report-print-shell__body">
                        <?= $bodyHtml ?>
                        <?php if ($reportExplanationRaw !== ''): ?>
                            <div class="report-explanation-final">
                                <aside class="report-explanation-box" role="note">
                                    <div class="report-explanation-box__label">Llegenda:</div>
                                    <div class="report-explanation-box__body"><?= nl2br(e($reportExplanationRaw), false) ?></div>
                                </aside>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <script>
    (function () {
        var btn = document.getElementById('report-print-btn');
        if (btn) btn.addEventListener('click', function () { window.print(); });
    })();
    </script>
</body>
</html>

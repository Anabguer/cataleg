<?php
declare(strict_types=1);
/** @var string $logoUrl */
/** @var string $report_code */
/** @var string $report_title */
/** @var int $programYear */
/** @var DateTimeImmutable $generatedAt */

$logoPx = 80;
?>
<header class="report-header" role="banner">
    <?php /* Taula interna: estable en impressió i «Guardar com a PDF» (Chromium fa servir layout de taula de forma fiable). */ ?>
    <table class="report-header__layout" role="presentation">
        <tbody>
            <tr>
                <td class="report-header__cell report-header__cell--logo">
                    <img
                        class="report-header__logo"
                        src="<?= e($logoUrl) ?>"
                        alt=""
                        width="<?= (int) $logoPx ?>"
                        height="<?= (int) $logoPx ?>"
                    >
                </td>
                <td class="report-header__cell report-header__cell--center">
                    <div class="report-header__exercici">
                        <span class="report-header__exercici-label">Exercici</span>
                        <span class="report-header__exercici-value"><?= (int) $programYear ?></span>
                    </div>
                    <h1 class="report-header__title"><?= e($report_title) ?></h1>
                </td>
                <td class="report-header__cell report-header__cell--meta">
                    <?php /* Bloc apilat: mateix ordre pantalla / impressió / PDF (evita que la línia de pàgina es confongui amb el marge @page). */ ?>
                    <div class="report-header__meta-stack">
                        <div class="report-header__code"><?= e($report_code) ?></div>
                        <div class="report-header__date"><?= e($generatedAt->format('d/m/Y H:i')) ?></div>
                        <div class="report-header__pages report-header__pages--screen">
                            Pàgina <span class="report-header__page-num" aria-label="Número de pàgina"></span>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</header>

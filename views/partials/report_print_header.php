<?php
declare(strict_types=1);
/** @var int $catalogYear */
/** @var string $reportCode */
/** @var string $reportTitle */
/** @var string|null $comentari */
/** @var string $logoPath relatiu a asset_url */
/** @var string $generatedAt data/hora text */
?>
<header class="report-header">
    <table class="report-header__layout" role="presentation">
        <tr>
            <td class="report-header__cell report-header__cell--logo" rowspan="2">
                <img class="report-header__logo" src="<?= e(asset_url($logoPath)) ?>" width="80" height="80" alt="<?= e('Escut') ?>">
            </td>
            <td class="report-header__cell report-header__cell--center">
                <div class="report-header__exercici">
                    <span class="report-header__exercici-label">Exercici</span>
                    <span class="report-header__exercici-value"><?= e((string) $catalogYear) ?></span>
                </div>
            </td>
            <td class="report-header__cell report-header__cell--meta" rowspan="2">
                <div class="report-header__meta-stack">
                    <span class="report-header__code"><?= e($reportCode) ?></span>
                    <span class="report-header__date"><?= e($generatedAt) ?></span>
                    <span class="report-header__pages">Pàgina <span class="report-header__page-num"></span></span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="report-header__cell report-header__cell--center">
                <h1 class="report-header__title"><?= e($reportTitle) ?></h1>
                <?php
                $com = trim((string) ($comentari ?? ''));
                if ($com !== ''): ?>
                    <p class="report-header__comment"><?= e($com) ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</header>

<?php
declare(strict_types=1);
$filterCardInner = $filterCardInner ?? '';
$filterExpanded = (bool) ($filterExpanded ?? true);
$filterSummaryLabel = (string) ($filterSummaryLabel ?? 'Filtres');
$filterShowClear = (bool) ($filterShowClear ?? true);
?>
<details class="filter-card card"<?= $filterExpanded ? ' open' : '' ?>>
    <summary class="filter-card__summary"><?= e($filterSummaryLabel) ?></summary>
    <div class="filter-card__body">
        <div class="filter-bar">
            <div class="filter-bar__fields">
                <?= $filterCardInner ?>
            </div>
            <?php if ($filterShowClear): ?>
                <div class="filter-bar__actions">
                    <button type="button" class="btn btn--secondary btn--sm js-filter-clear">Netejar filtres</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</details>

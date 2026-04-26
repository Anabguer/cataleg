<?php
declare(strict_types=1);

/**
 * Paginació compacta: primera / anterior / números (amb …) / següent / última.
 *
 * @var int $paginationPage
 * @var int $paginationTotalPages
 * @var callable(int):string $paginationBuildUrl
 * @var string|null $paginationAriaLabel
 */

require_once APP_ROOT . '/includes/pagination_helpers.php';

$paginationAriaLabel = $paginationAriaLabel ?? 'Paginació del llistat';
$cur = max(1, (int) $paginationPage);
$last = max(1, (int) $paginationTotalPages);
if ($cur > $last) {
    $cur = $last;
}

/** @var callable(int):string $build */
$build = $paginationBuildUrl;
$items = pagination_visible_items($cur, $last);

$firstHref = $build(1);
$lastHref = $build($last);
$prevHref = $build(max(1, $cur - 1));
$nextHref = $build(min($last, $cur + 1));

$isFirst = $cur <= 1;
$isLast = $cur >= $last;
?>
<nav class="users-pagination" aria-label="<?= e($paginationAriaLabel) ?>">
    <div class="users-pagination__summary muted">
        Pàgina <?= (int) $cur ?> de <?= (int) $last ?>
    </div>
    <div class="users-pagination__nav">
        <?php if ($isFirst): ?>
            <span class="btn btn--secondary btn--sm users-pagination__icon-btn is-disabled" aria-disabled="true" aria-label="Primera pàgina" title="Primera pàgina" tabindex="-1"><span aria-hidden="true">⏮</span></span>
        <?php else: ?>
            <a class="btn btn--secondary btn--sm users-pagination__icon-btn" href="<?= e($firstHref) ?>" aria-label="Primera pàgina" title="Primera pàgina"><span aria-hidden="true">⏮</span></a>
        <?php endif; ?>

        <?php if ($isFirst): ?>
            <span class="btn btn--secondary btn--sm users-pagination__icon-btn is-disabled" aria-disabled="true" aria-label="Pàgina anterior" title="Pàgina anterior" tabindex="-1"><span aria-hidden="true">◀</span></span>
        <?php else: ?>
            <a class="btn btn--secondary btn--sm users-pagination__icon-btn" href="<?= e($prevHref) ?>" aria-label="Pàgina anterior" title="Pàgina anterior"><span aria-hidden="true">◀</span></a>
        <?php endif; ?>

        <div class="users-pagination__pages">
            <?php foreach ($items as $entry): ?>
                <?php if ($entry === 'ellipsis'): ?>
                    <span class="users-pagination__ellipsis" aria-hidden="true">…</span>
                <?php else:
                    $p = (int) $entry;
                    if ($p === $cur): ?>
                        <span class="users-pagination__page is-current" aria-current="page"><?= (int) $p ?></span>
                    <?php else:
                        $u = $build($p); ?>
                        <a class="users-pagination__page" href="<?= e($u) ?>"><?= (int) $p ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($isLast): ?>
            <span class="btn btn--secondary btn--sm users-pagination__icon-btn is-disabled" aria-disabled="true" aria-label="Pàgina següent" title="Pàgina següent" tabindex="-1"><span aria-hidden="true">▶</span></span>
        <?php else: ?>
            <a class="btn btn--secondary btn--sm users-pagination__icon-btn" href="<?= e($nextHref) ?>" aria-label="Pàgina següent" title="Pàgina següent"><span aria-hidden="true">▶</span></a>
        <?php endif; ?>

        <?php if ($isLast): ?>
            <span class="btn btn--secondary btn--sm users-pagination__icon-btn is-disabled" aria-disabled="true" aria-label="Última pàgina" title="Última pàgina" tabindex="-1"><span aria-hidden="true">⏭</span></span>
        <?php else: ?>
            <a class="btn btn--secondary btn--sm users-pagination__icon-btn" href="<?= e($lastHref) ?>" aria-label="Última pàgina" title="Última pàgina"><span aria-hidden="true">⏭</span></a>
        <?php endif; ?>
    </div>
</nav>

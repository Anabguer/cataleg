<?php
declare(strict_types=1);
$dataTableCaption = $dataTableCaption ?? null;
$dataTableToolbar = $dataTableToolbar ?? null;
$dataTableInner = $dataTableInner ?? '';
$dataTableWrapperClass = trim((string) ($dataTableWrapperClass ?? ''));
?>
<div class="data-table-wrapper card<?= $dataTableWrapperClass !== '' ? ' ' . e($dataTableWrapperClass) : '' ?>">
    <?php if ($dataTableToolbar !== null && $dataTableToolbar !== ''): ?>
        <div class="data-table-toolbar"><?= $dataTableToolbar ?></div>
    <?php endif; ?>
    <div class="table-scroll" role="region"<?= $dataTableCaption ? ' aria-label="' . e((string) $dataTableCaption) . '"' : '' ?>>
        <?= $dataTableInner ?>
    </div>
</div>

<?php
declare(strict_types=1);
$actionBarInner = $actionBarInner ?? '';
if (trim($actionBarInner) === '') {
    return;
}
?>
<div class="action-bar card">
    <div class="action-bar__inner">
        <?= $actionBarInner ?>
    </div>
</div>

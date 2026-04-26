<?php
declare(strict_types=1);
$pageHeader = $pageHeader ?? [];
$phTitle = (string) ($pageHeader['title'] ?? '');
$phSubtitle = $pageHeader['subtitle'] ?? null;
$phIcon = $pageHeader['icon'] ?? null;
$phHelpUrl = $pageHeader['help_url'] ?? null;
$phHelpLabel = (string) ($pageHeader['help_label'] ?? 'Ajuda');
$phLogoPath = $pageHeader['logo_path'] ?? null;
$phLogoAlt = (string) ($pageHeader['logo_alt'] ?? '');
$phHelpClass = trim((string) ($pageHeader['help_class'] ?? ''));
$phGreeting = isset($pageHeader['greeting']) ? trim((string) $pageHeader['greeting']) : '';
?>
<header class="page-header">
    <div class="page-header__grid">
        <div class="page-header__start">
            <?php if ($phLogoPath): ?>
                <img class="page-header__escut" src="<?= e(asset_url((string) $phLogoPath)) ?>" alt="<?= e($phLogoAlt) ?>" width="52" height="52" loading="lazy">
            <?php endif; ?>
        </div>
        <div class="page-header__main">
            <?php if ($phIcon && !$phLogoPath): ?>
                <?= ui_icon((string) $phIcon, 'page-header__icon') ?>
            <?php endif; ?>
            <div class="page-header__titles">
                <?php if ($phGreeting !== ''): ?>
                    <div class="page-header__title-row">
                        <h1 class="page-header__title"><?= e($phTitle) ?></h1>
                        <p class="page-header__greeting">
                            Hola, <span class="page-header__greeting-name"><?= e($phGreeting) ?></span>
                        </p>
                    </div>
                <?php else: ?>
                    <h1 class="page-header__title"><?= e($phTitle) ?></h1>
                <?php endif; ?>
                <?php if ($phSubtitle !== null && $phSubtitle !== ''): ?>
                    <p class="page-header__subtitle"><?= e((string) $phSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="page-header__end">
            <?php if ($phHelpUrl): ?>
                <a href="<?= e((string) $phHelpUrl) ?>" class="btn btn--outline btn--sm page-header__help<?= $phHelpClass !== '' ? ' ' . e($phHelpClass) : '' ?>" title="<?= e($phHelpLabel) ?>">
                    <?= ui_icon('help', 'btn-icon') ?>
                    <span class="page-header__help-text"><?= e($phHelpLabel) ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

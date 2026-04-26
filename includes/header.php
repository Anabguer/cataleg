<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string|null $activeNav */

$pageTitle = $pageTitle ?? 'Aplicación';
$activeNav = $activeNav ?? '';
$menuItems = function_exists('menu_visible_forms') ? menu_visible_forms() : [];
$groupDefs = function_exists('permissions_form_group_definitions') ? permissions_form_group_definitions() : [
    'system' => ['label' => 'Sistema', 'nav' => 'Seguretat'],
    'organization' => ['label' => 'Organització', 'nav' => 'Manteniments'],
    'training_maintenance' => ['label' => 'Manteniment', 'nav' => 'Manteniments'],
    'training_management' => ['label' => 'Gestió', 'nav' => 'Gestió'],
];
$normalizeGroup = static function (string $group) use ($groupDefs): string {
    $key = trim(strtolower($group));
    return isset($groupDefs[$key]) ? $key : 'training_maintenance';
};

$securityCodes = ['users', 'roles', 'permissions', 'password_change'];
$securityItems = [];
$securityGroups = ['Seguretat' => []];
$maintenanceGroups = [];
$maintenanceItems = [];
$gestioItems = [];

foreach ($menuItems as $item) {
    $code = (string) ($item['code'] ?? '');
    if ($code === 'dashboard') {
        continue;
    }
    $groupKey = $normalizeGroup((string) ($item['form_group'] ?? ''));
    $groupLabel = (string) ($groupDefs[$groupKey]['label'] ?? 'Altres');
    if (in_array($code, $securityCodes, true)) {
        $securityItems[] = $item;
        $securityGroups['Seguretat'][] = $item;
        continue;
    }
    if ($groupKey === 'training_management') {
        $gestioItems[] = [
            'navKey' => $code,
            'name' => (string) ($item['name'] ?? $code),
            'route' => (string) ($item['route'] ?? ''),
        ];
        continue;
    }
    if ($groupKey === 'organization' || $groupKey === 'training_maintenance') {
        if (!isset($maintenanceGroups[$groupLabel])) {
            $maintenanceGroups[$groupLabel] = [];
        }
        $maintenanceGroups[$groupLabel][] = $item;
        $maintenanceItems[] = $item;
        continue;
    }
    $gestioItems[] = [
        'navKey' => $code,
        'name' => (string) ($item['name'] ?? $code),
        'route' => (string) ($item['route'] ?? ''),
    ];
}

$gestioActive = in_array($activeNav, array_column($gestioItems, 'navKey'), true);
$securityActive = in_array($activeNav, $securityCodes, true);
$maintenanceCodes = [];
foreach ($maintenanceGroups as $groupItems) {
    foreach ($groupItems as $groupItem) {
        $maintenanceCodes[] = (string) $groupItem['code'];
    }
}
$maintenanceActive = in_array($activeNav, $maintenanceCodes, true);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components.css')) ?>">
    <link rel="icon" href="<?= BASE_URL ?>favicon.ico">
    <?php
    if (!empty($extraCss) && is_array($extraCss)) {
        foreach ($extraCss as $cssPath) {
            echo '<link rel="stylesheet" href="' . e(asset_url((string) $cssPath)) . '">' . "\n    ";
        }
    }
    ?>
</head>
<body class="app-body">
<div class="app-shell">
    <header class="app-header">
        <div class="app-header__brand">
            <a href="<?= e(app_url('dashboard.php')) ?>" class="app-header__logo">Catàleg - Relació Llocs de Treball</a>
        </div>
        <?php if (auth_is_logged_in()): ?>
        <nav class="app-nav" aria-label="Principal">
            <?php if (can_view_form('dashboard')): ?>
                <a href="<?= e(app_url('dashboard.php')) ?>" class="app-nav__link<?= $activeNav === 'dashboard' ? ' is-active' : '' ?>">Tauler</a>
            <?php endif; ?>
            <div class="app-nav__dropdown">
                <button type="button" class="app-nav__link app-nav__link--button<?= $gestioActive ? ' is-active' : '' ?>" data-app-nav-dropdown-toggle>
                    Gestió
                </button>
                <div class="app-nav__dropdown-menu" data-app-nav-dropdown-menu>
                    <?php foreach ($gestioItems as $item): ?>
                        <?php
                        $href = app_url(ltrim((string) $item['route'], '/'));
                        $isActive = $activeNav === (string) $item['navKey'];
                        ?>
                        <a class="app-nav__dropdown-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e($href) ?>">
                            <?= e((string) $item['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($gestioItems === []): ?>
                        <div class="app-nav__dropdown-label">Sense opcions</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="app-nav__dropdown">
                <button type="button" class="app-nav__link app-nav__link--button<?= $securityActive ? ' is-active' : '' ?>" data-app-nav-dropdown-toggle>
                    Seguretat
                </button>
                <div class="app-nav__dropdown-menu" data-app-nav-dropdown-menu>
                    <?php foreach ($securityGroups as $groupLabel => $groupItems): ?>
                        <div class="app-nav__dropdown-label"><?= e((string) $groupLabel) ?></div>
                        <?php foreach ($groupItems as $item): ?>
                            <?php
                            $href = app_url(ltrim((string) $item['route'], '/'));
                            $isActive = $activeNav === (string) $item['code'];
                            ?>
                            <a class="app-nav__dropdown-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e($href) ?>">
                                <?= e((string) $item['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if ($securityItems === []): ?>
                        <div class="app-nav__dropdown-label">Sense opcions</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="app-nav__dropdown">
                <button type="button" class="app-nav__link app-nav__link--button<?= $maintenanceActive ? ' is-active' : '' ?>" data-app-nav-dropdown-toggle>
                    Manteniments
                </button>
                <div class="app-nav__dropdown-menu" data-app-nav-dropdown-menu>
                    <?php foreach ($maintenanceGroups as $groupLabel => $groupItems): ?>
                        <div class="app-nav__dropdown-label"><?= e((string) $groupLabel) ?></div>
                        <?php foreach ($groupItems as $item): ?>
                            <?php
                            $href = app_url(ltrim((string) $item['route'], '/'));
                            $isActive = $activeNav === (string) $item['code'];
                            ?>
                            <a class="app-nav__dropdown-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e($href) ?>">
                                <?= e((string) $item['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if ($maintenanceItems === []): ?>
                        <div class="app-nav__dropdown-label">Sense opcions</div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <div class="app-header__user">
            <span class="app-header__name"><?= e((string) ($_SESSION['full_name'] ?? '')) ?></span>
            <a class="btn btn--secondary btn--sm" href="<?= e(app_url('logout.php')) ?>">Sortir</a>
        </div>
        <?php endif; ?>
    </header>
    <main class="app-main">

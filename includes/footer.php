    </main>
    <?php
    $isDashboardFooter = ($activeNav ?? '') === 'dashboard';
    $dfLogin = (string) ($_SESSION['username'] ?? '');
    $dfFull = trim((string) ($_SESSION['full_name'] ?? ''));
    $dfUserLabel = $dfFull !== '' ? $dfFull : $dfLogin;
    $dfRole = isset($dashboardRoleLabel) ? trim((string) $dashboardRoleLabel) : '';
    if ($dfRole === '' && auth_is_logged_in()) {
        $roleId = auth_role_id();
        if ($roleId !== null && $roleId > 0) {
            $st = db()->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
            $st->execute(['id' => $roleId]);
            $row = $st->fetch();
            if ($row && isset($row['name'])) {
                $dfRole = (string) $row['name'];
            }
        }
    }
    ?>
    <footer class="app-footer<?= $isDashboardFooter ? ' app-footer--dashboard' : '' ?>">
        <span class="app-footer__brand">Catàleg municipal</span>
        <?php if (auth_is_logged_in()): ?>
            <span class="app-footer__sep" aria-hidden="true">·</span>
            <span class="app-footer__item">Usuari: <?= e($dfUserLabel) ?><?= $dfLogin !== '' && $dfLogin !== $dfUserLabel ? ' (' . e($dfLogin) . ')' : '' ?></span>
            <?php if ($dfRole !== ''): ?>
                <span class="app-footer__sep" aria-hidden="true">·</span>
                <span class="app-footer__item">Rol: <?= e($dfRole) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </footer>
</div>

<div id="modal-root" class="modal-root" aria-hidden="true"></div>
<div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="true"></div>

<?php
/** Config JS opcional per pàgina (p. ex. usuaris / rols) — ha d’executar-se abans dels scripts defer */
if (isset($usersPageInlineConfig) && is_array($usersPageInlineConfig)) {
    echo '<script>window.APP_USERS = ' . json_encode(
        $usersPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($rolesPageInlineConfig) && is_array($rolesPageInlineConfig)) {
    echo '<script>window.APP_ROLES = ' . json_encode(
        $rolesPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($permissionsPageInlineConfig) && is_array($permissionsPageInlineConfig)) {
    echo '<script>window.APP_PERMISSIONS = ' . json_encode(
        $permissionsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($areasPageInlineConfig) && is_array($areasPageInlineConfig)) {
    echo '<script>window.APP_AREAS = ' . json_encode(
        $areasPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($sectionsPageInlineConfig) && is_array($sectionsPageInlineConfig)) {
    echo '<script>window.APP_SECTIONS = ' . json_encode(
        $sectionsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($unitsPageInlineConfig) && is_array($unitsPageInlineConfig)) {
    echo '<script>window.APP_UNITS = ' . json_encode(
        $unitsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingAuthorizersPageInlineConfig) && is_array($trainingAuthorizersPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_AUTHORIZERS = ' . json_encode(
        $trainingAuthorizersPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingFundingPageInlineConfig) && is_array($trainingFundingPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_FUNDING = ' . json_encode(
        $trainingFundingPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingLocationsPageInlineConfig) && is_array($trainingLocationsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_LOCATIONS = ' . json_encode(
        $trainingLocationsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingAcademicLevelsPageInlineConfig) && is_array($trainingAcademicLevelsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_ACADEMIC_LEVELS = ' . json_encode(
        $trainingAcademicLevelsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingOrganizersPageInlineConfig) && is_array($trainingOrganizersPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_ORGANIZERS = ' . json_encode(
        $trainingOrganizersPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingSubprogramsPageInlineConfig) && is_array($trainingSubprogramsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_SUBPROGRAMS = ' . json_encode(
        $trainingSubprogramsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingTrainerTypesPageInlineConfig) && is_array($trainingTrainerTypesPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_TRAINER_TYPES = ' . json_encode(
        $trainingTrainerTypesPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($jobPositionsPageInlineConfig) && is_array($jobPositionsPageInlineConfig)) {
    echo '<script>window.APP_JOB_POSITIONS = ' . json_encode(
        $jobPositionsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($peoplePageInlineConfig) && is_array($peoplePageInlineConfig)) {
    echo '<script>window.APP_PEOPLE = ' . json_encode(
        $peoplePageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($knowledgeAreasPageInlineConfig) && is_array($knowledgeAreasPageInlineConfig)) {
    echo '<script>window.APP_KNOWLEDGE_AREAS = ' . json_encode(
        $knowledgeAreasPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingCatalogActionsPageInlineConfig) && is_array($trainingCatalogActionsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_CATALOG_ACTIONS = ' . json_encode(
        $trainingCatalogActionsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingActionsPageInlineConfig) && is_array($trainingActionsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_ACTIONS = ' . json_encode(
        $trainingActionsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($trainingReportsPageInlineConfig) && is_array($trainingReportsPageInlineConfig)) {
    echo '<script>window.APP_TRAINING_REPORTS = ' . json_encode(
        $trainingReportsPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($dashboardPageInlineConfig) && is_array($dashboardPageInlineConfig)) {
    echo '<script>window.APP_DASHBOARD = ' . json_encode(
        $dashboardPageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
if (isset($maintenancePageInlineConfig) && is_array($maintenancePageInlineConfig)) {
    echo '<script>window.APP_MAINTENANCE = ' . json_encode(
        $maintenancePageInlineConfig,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) . ';</script>' . "\n";
}
?>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= e(asset_url('js/' . ltrim((string) $src, '/'))) ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>

<?php
declare(strict_types=1);
/** @var array<string,string> $errors */
/** @var string|null $successMessage */
/** @var string $csrfToken */

ob_start();
?>
<div class="form-card form-card--narrow">
    <div class="form-card__header">
        <h2 class="form-card__title">Canvi contrasenya</h2>
        <p class="form-card__desc">Actualitza la teva contrasenya per reforçar la seguretat del compte.</p>
    </div>
    <form class="form-card__body" method="post" action="<?= e(app_url('change_password.php')) ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <?php if ($successMessage !== null): ?>
            <div class="alert alert--success" role="status"><?= e($successMessage) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['_general'])): ?>
            <div class="alert alert--error" role="alert"><?= e($errors['_general']) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="current_password">Contrasenya actual</label>
            <input class="form-input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
            <?php if (!empty($errors['current_password'])): ?>
                <p class="form-error" role="alert"><?= e($errors['current_password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password">Nova contrasenya</label>
            <input class="form-input" type="password" id="new_password" name="new_password" required autocomplete="new-password">
            <?php if (!empty($errors['new_password'])): ?>
                <p class="form-error" role="alert"><?= e($errors['new_password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirmació de contrasenya</label>
            <input class="form-input" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            <?php if (!empty($errors['confirm_password'])): ?>
                <p class="form-error" role="alert"><?= e($errors['confirm_password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-actions form-actions--end">
            <button type="submit" class="btn btn--primary">Desar contrasenya</button>
        </div>
    </form>
</div>
<?php
$pageContentExtra = ob_get_clean();

$pageHeader = page_header_with_escut([
    'title' => 'Canvi contrasenya',
    'subtitle' => 'Canvi de contrasenya del compte actual',
]);

$actionBarInner = '';
$filterCardInner = '';
$dataTableInner = '';

require APP_ROOT . '/views/layouts/admin_page.php';

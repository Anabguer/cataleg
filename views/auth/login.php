<?php
declare(strict_types=1);
/** @var string|null $error */
?>
<div class="auth-panel">
    <div class="form-card form-card--narrow auth-card">
        <div class="form-card__header auth-card__header">
            <div class="auth-card__institution-row">
                <img class="auth-card__logo" src="<?= e(asset_url('img/logos/Escut_alta.png')) ?>" alt="Escut de l'Ajuntament de Molins de Rei">
                <p class="auth-card__institution">Ajuntament de Molins de Rei</p>
            </div>
            <h1 class="form-card__title auth-card__title">Catàleg - Relació Llocs de Treball</h1>
            <p class="form-card__desc auth-card__desc">Plataforma de gestió del catàleg municipal de llocs de treball</p>
        </div>
        <form class="form-card__body auth-card__body" method="post" action="<?= e(app_url('login.php')) ?>" novalidate lang="ca">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken ?? '') ?>">
            <div class="form-group">
                <label class="form-label" for="username">Usuari</label>
                <input class="form-input" type="text" id="username" name="username" required autocomplete="username" lang="ca" spellcheck="true"
                       value="<?= e($username ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Contrasenya</label>
                <input class="form-input" type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <?php if (!empty($error)): ?>
                <p class="form-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <div class="form-actions form-actions--end auth-card__actions">
                <button type="submit" class="btn btn--primary auth-card__submit">Entrar</button>
            </div>
        </form>
        </div>
    </div>
</div>

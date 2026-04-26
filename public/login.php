<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (auth_is_logged_in()) {
    redirect(app_url('dashboard.php'));
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify('csrf_token')) {
        $error = 'Sessió no vàlida. Torna-ho a provar.';
    } else {
        $username = post_string('username');
        $password = $_POST['password'] ?? '';
        $password = is_string($password) ? $password : '';
        if (!auth_attempt_login($username, $password)) {
            $error = 'Credencials incorrectes o usuari inactiu.';
        } else {
            permissions_load_for_session();
            redirect(app_url('dashboard.php'));
        }
    }
}

$csrfToken = csrf_token();
$pageTitle = 'Inici de sessió';
require APP_ROOT . '/views/layouts/auth.php';
require APP_ROOT . '/views/auth/login.php';
require APP_ROOT . '/views/layouts/auth_footer.php';

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (auth_is_logged_in()) {
    redirect(app_url('dashboard.php'));
}
redirect(app_url('login.php'));

<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_url('dashboard.php'));
}

if (!csrf_verify('csrf_token')) {
    redirect(app_url('dashboard.php'));
}

$year = (int) post_string('catalog_year');
if ($year > 0) {
    catalog_year_set(db(), $year);
}

$redirectTo = post_string('redirect_to');
$fallback = app_url('dashboard.php');
if ($redirectTo === '') {
    redirect($fallback);
}

$path = parse_url($redirectTo, PHP_URL_PATH);
if (!is_string($path) || strpos($path, '/cataleg_molins/public/') === false) {
    redirect($fallback);
}

if (strpos($redirectTo, 'http://') === 0 || strpos($redirectTo, 'https://') === 0) {
    redirect($fallback);
}

redirect($redirectTo);

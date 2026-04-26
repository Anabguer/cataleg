<?php
declare(strict_types=1);

/**
 * Guia visual — accessible sense autenticació per a revisió de disseny.
 * En producció pots restringir accés (per IP, sessió, o entorn).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Guia visual — components';
$activeNav = '';
$extraCss = ['css/module-users.css'];
$extraScripts = ['styleguide.js'];
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/styleguide/index.php';
require APP_ROOT . '/includes/footer.php';

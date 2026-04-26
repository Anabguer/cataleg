<?php
declare(strict_types=1);

$envLoader = dirname(__DIR__) . '/config/env.php';
if (!is_readable($envLoader)) {
    throw new RuntimeException(
        'Falta config/env.php (carregador d’entorn). Restaura el fitxer des del repositori.'
    );
}
require_once $envLoader;

if (!defined('APP_ENV') || !defined('BASE_URL') || !defined('SITE_BASE_URL')) {
    throw new RuntimeException(
        'La configuració d’entorn ha de definir APP_ENV, SITE_BASE_URL i BASE_URL. '
        . 'Crea config/env.local.php (local) o config/env.production.php (servidor) a partir dels .example.'
    );
}

define('APP_IS_PRODUCTION', APP_ENV === 'production');

if (APP_IS_PRODUCTION && (!defined('APP_DEBUG') || !APP_DEBUG)) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} elseif (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
}

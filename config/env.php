<?php
declare(strict_types=1);

/**
 * Carrega la configuració d’entorn sense credencials al repositori.
 *
 * Prioritat:
 * 1) config/env.local.php — desenvolupament local (gitignored)
 * 2) config/env.production.php — servidor Hostalia / producció (gitignored, es crea al servidor)
 * 3) config/env.example.php — valors segurs per defecte (fallback si falten els anteriors)
 *
 * Migració des de l’antic env.php únic: copia el contingut a env.local.php i esborra l’antic env.php
 * si el tenies fora del control de versions.
 */
$dir = __DIR__;
$local = $dir . '/env.local.php';
$production = $dir . '/env.production.php';

if (is_readable($local)) {
    require_once $local;
} elseif (is_readable($production)) {
    require_once $production;
} else {
    require_once $dir . '/env.example.php';
}

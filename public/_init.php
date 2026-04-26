<?php
declare(strict_types=1);
/**
 * Patró comú per a pàgines sota /public (requereix bootstrap i permís de vista).
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

auth_require_login();
permissions_load_for_session();

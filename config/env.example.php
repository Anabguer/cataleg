<?php
/**
 * Valors per defecte si no existeixen env.local.php ni env.production.php.
 * Per treballar en local: millor còpia env.local.php.example → env.local.php.
 */
declare(strict_types=1);

define('APP_ENV', 'development');
/** En producció ha de ser false: amb true, errors 422 de knowledge_areas (pujada) poden incloure _upload_debug / _request_debug. */
define('APP_DEBUG', true);

define('DB_HOST', 'localhost');
define('DB_NAME', 'formacio_molins');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/** URL base del lloc (sense /public) — carpeta arrel del projecte servida per Apache */
define('SITE_BASE_URL', 'http://localhost/formacio_molins_rei/');
/** URL de l’aplicació (entrada sota /public) */
define('BASE_URL', rtrim(SITE_BASE_URL, '/') . '/public/');

/** Contrasenya de la fulla QUESTIONARI de la plantilla Excel (qüestionari d’avaluació). No la deixis buida en producció si la plantilla està protegida. */
define('EXCEL_QUESTIONARI_SHEET_PASSWORD', '');

/** Correu emissor per enviar qüestionaris (From). Ajusta al domini del teu servidor. */
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'smtp-user@example.com');
define('MAIL_PASS', 'smtp-password');
/** tls (587) o ssl (465). Si es deixa buit, es dedueix per port (465=ssl, resta=tls). */
define('MAIL_SECURE', 'tls');
define('MAIL_FROM_ADDRESS', 'noreply@localhost');
define('MAIL_FROM_NAME', 'Formació');

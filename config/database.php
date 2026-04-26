<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

/**
 * Conexión PDO única (singleton ligero por petición).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $charset = defined('DB_CHARSET') && is_string(DB_CHARSET) && DB_CHARSET !== ''
        ? DB_CHARSET
        : 'utf8mb4';
    // El charset històric "utf8" de MySQL és de 3 bytes; sempre usar utf8mb4.
    if (strcasecmp($charset, 'utf8') === 0) {
        $charset = 'utf8mb4';
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        $charset
    );

    $initCommand = strcasecmp($charset, 'utf8mb4') === 0
        ? 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
        : 'SET NAMES ' . $charset;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => $initCommand,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

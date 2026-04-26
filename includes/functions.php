<?php
declare(strict_types=1);

function app_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/') . '/';
    $path = ltrim($path, '/');
    return $base . $path;
}

function asset_url(string $path): string
{
    /** Sota /public/assets/ (mateix contingut que /assets/ al projecte via enllaç o còpia) perquè el servidor pugui servir fitxers estàtics. */
    $base = rtrim(BASE_URL, '/') . '/assets/';
    return $base . ltrim($path, '/');
}

/**
 * Fragment SQL LIMIT/OFFSET amb enters no negatius (sense placeholders).
 * Alguns hostings amb PDO MySQL/MariaDB i ATTR_EMULATE_PREPARES false fallen en LIMIT/OFFSET preparats;
 * això evita el patró problemàtic sense obrir injecció (només enters).
 */
function db_sql_limit_offset(int $limit, int $offset): string
{
    $limit = max(0, $limit);
    $offset = max(0, $offset);

    return 'LIMIT ' . $limit . ' OFFSET ' . $offset;
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key, string $default = ''): string
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    return is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get_string(string $key, string $default = ''): string
{
    if (!isset($_GET[$key])) {
        return $default;
    }
    return is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $fieldName = 'csrf_token'): bool
{
    $sent = $_POST[$fieldName] ?? '';
    $valid = isset($_SESSION['csrf_token']) && is_string($sent)
        && hash_equals($_SESSION['csrf_token'], $sent);
    return $valid;
}

/**
 * Converteix una cadena opcional buida (o només espais) en NULL per a persistència SQL.
 * Ús: emails, descripcions, notes i altres VARCHAR/TEXT opcionals.
 */
function null_if_empty(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $t = trim($value);
    return $t === '' ? null : $t;
}

/**
 * ID opcional des de formulari o valor buit: NULL si no hi ha enter positiu vàlid.
 *
 * @param mixed $value string trim, int, null
 */
/** @param mixed $value */
function null_if_empty_int($value): ?int
{
    if ($value === null) {
        return null;
    }
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (is_string($value)) {
        $t = trim($value);
        if ($t === '' || !ctype_digit($t)) {
            return null;
        }
        $n = (int) $t;
        return $n > 0 ? $n : null;
    }
    return null;
}

/**
 * Camp POST opcional de text: NULL si falta, buit o només espais.
 */
function post_optional_string(string $key): ?string
{
    if (!isset($_POST[$key])) {
        return null;
    }
    $v = $_POST[$key];
    if (!is_string($v)) {
        return null;
    }
    return null_if_empty($v);
}

/**
 * Camp POST opcional d’ID (select buit, hidden buit): NULL si no hi ha enter positiu.
 */
function post_optional_id(string $key): ?int
{
    if (!isset($_POST[$key])) {
        return null;
    }
    return null_if_empty_int($_POST[$key]);
}

/**
 * Fitxer de l’escut sota /assets (relatiu a asset_url).
 */
function page_header_escut_path(): string
{
    return 'img/logos/Escut_alta.png';
}

/**
 * Capçalera de gestió amb escut municipal: mostra el logo i no l’icona Heroicon decorativa.
 *
 * @param array<string, mixed> $header
 * @return array<string, mixed>
 */
function page_header_with_escut(array $header): array
{
    if (empty($header['logo_path'])) {
        $header['logo_path'] = page_header_escut_path();
    }
    if (!isset($header['logo_alt']) || trim((string) $header['logo_alt']) === '') {
        $header['logo_alt'] = 'Escut';
    }
    unset($header['icon']);

    return $header;
}

/**
 * Formata codis numèrics amb zeros a l'esquerra només per visualització.
 */
function format_padded_code(int $value, int $length): string
{
    if ($length < 1) {
        $length = 1;
    }
    return str_pad((string) max(0, $value), $length, '0', STR_PAD_LEFT);
}

/**
 * Detecta violacions d’integritat referencial (FK) amb PDO i MySQL/MariaDB.
 * Útil per traduir errors tècnics a missatges funcionals sense exposar el SQL.
 */
function db_is_integrity_constraint_violation(Throwable $e): bool
{
    if (!$e instanceof PDOException) {
        return false;
    }
    $sqlState = (string) ($e->errorInfo[0] ?? '');
    $driverCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
    // SQLSTATE 23000 = integrity constraint violation
    // MySQL/MariaDB 1451 = cannot delete/update parent: foreign key fails
    // 1217 = cannot delete row referenced (algunes versions)
    return $sqlState === '23000' || in_array($driverCode, [1451, 1217], true);
}

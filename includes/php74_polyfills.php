<?php
declare(strict_types=1);

/**
 * Funcions afegides a PHP 8.0+ que usa codi propi i dependències (p. ex. PhpSpreadsheet).
 * Es defineixen només si no existeixen (en PHP 8+ no fan res).
 */
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);

        return $len <= strlen($haystack) && substr_compare($haystack, $needle, -$len) === 0;
    }
}

<?php
declare(strict_types=1);

/**
 * Taules que participen en el catàleg per any.
 *
 * @return list<string>
 */
function catalog_year_tables(): array
{
    return [
        'civil_service_scales',
        'civil_service_subscales',
        'civil_service_classes',
        'civil_service_categories',
        'legal_relations',
        'administrative_statuses',
        'position_classes',
        'access_types',
        'access_systems',
        'work_centers',
        'availability_options',
        'provision_methods',
        'org_units_level_1',
        'org_units_level_2',
        'org_units_level_3',
    ];
}

/**
 * @return list<int>
 */
function catalog_year_available_years(PDO $db): array
{
    $tables = catalog_year_tables();
    $parts = [];
    foreach ($tables as $table) {
        $parts[] = 'SELECT DISTINCT catalog_year AS y FROM ' . $table;
    }
    if ($parts === []) {
        return [];
    }

    $sql = 'SELECT DISTINCT y FROM (' . implode(' UNION ', $parts) . ') years ORDER BY y ASC';
    $rows = $db->query($sql)->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $row) {
        $out[] = (int) $row['y'];
    }
    return $out;
}

function catalog_year_current(): ?int
{
    $v = $_SESSION['catalog_year'] ?? null;
    if ($v === null) {
        return null;
    }
    return (int) $v;
}

function catalog_year_init(PDO $db): void
{
    $years = catalog_year_available_years($db);
    if ($years === []) {
        return;
    }
    $cur = catalog_year_current();
    if ($cur !== null && in_array($cur, $years, true)) {
        return;
    }
    $_SESSION['catalog_year'] = max($years);
}

function catalog_year_set(PDO $db, int $year): bool
{
    $years = catalog_year_available_years($db);
    if (!in_array($year, $years, true)) {
        return false;
    }
    $_SESSION['catalog_year'] = $year;
    return true;
}

<?php
declare(strict_types=1);

/**
 * Paràmetres base per a filtres i enllaços d’ordenació (GET).
 *
 * @return array<string, string>
 */
function maintenance_view_filter_query_base(string $module, string $q, int $perPage, string $sortBy, string $sortDir, array $extra = []): array
{
    return array_merge([
        'module' => $module,
        'q' => $q,
        'per_page' => (string) $perPage,
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
    ], $extra);
}

function maintenance_view_query_url(array $base, array $overrides): string
{
    $merged = array_merge($base, $overrides);
    $q = http_build_query($merged);
    return app_url('maintenance.php' . ($q !== '' ? '?' . $q : ''));
}

function maintenance_view_sort_href(string $col, string $curCol, string $curDir, array $base): string
{
    $nextDir = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';
    return maintenance_view_query_url($base, ['sort_by' => $col, 'sort_dir' => $nextDir, 'page' => 1]);
}

/**
 * @return array{0:string,1:string} símbol i title
 */
function maintenance_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }
    return $curDir === 'asc'
        ? ['↑', 'Ordenació ascendent (clic per invertir)']
        : ['↓', 'Ordenació descendent (clic per invertir)'];
}

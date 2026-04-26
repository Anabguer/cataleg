<?php
declare(strict_types=1);

/**
 * Helpers només per a la vista del llistat de rols (URLs de filtre, ordenació i paginació).
 * No formen part del domini CRUD; es carreguen des de public/roles.php abans de la vista.
 */

/**
 * Paràmetres GET base per enllaços (filtres + ordenació + mida de pàgina).
 *
 * @param array{q:string} $filters
 * @return array<string, string>
 */
function roles_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}

/**
 * @param array<string, string> $base
 * @param array<string, string|int> $overrides
 */
function roles_view_query_url(array $base, array $overrides): string
{
    $m = array_merge($base, $overrides);
    $q = http_build_query($m);

    return app_url('roles.php' . ($q !== '' ? '?' . $q : ''));
}

/**
 * @param array<string, string> $filterQueryBase
 */
function roles_view_sort_href(string $col, string $curCol, string $curDir, array $filterQueryBase): string
{
    $nextDir = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';

    return roles_view_query_url($filterQueryBase, [
        'sort_by' => $col,
        'sort_dir' => $nextDir,
        'page' => 1,
    ]);
}

/**
 * @return array{0: string, 1: string} símbol i títol (accessibilitat) per a la capçalera ordenable
 */
function roles_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }
    if ($curDir === 'asc') {
        return ['↑', 'Ordenació ascendent (clic per invertir)'];
    }

    return ['↓', 'Ordenació descendent (clic per invertir)'];
}


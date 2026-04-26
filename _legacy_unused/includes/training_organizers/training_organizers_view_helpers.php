<?php
declare(strict_types=1);

function training_organizers_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'active' => $filters['active'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}
function training_organizers_view_query_url(array $base, array $overrides): string
{
    $q = http_build_query(array_merge($base, $overrides));
    return app_url('training_organizers.php' . ($q !== '' ? '?' . $q : ''));
}
function training_organizers_view_sort_href(string $col, string $curCol, string $curDir, array $base): string
{
    $next = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';
    return training_organizers_view_query_url($base, ['sort_by' => $col, 'sort_dir' => $next, 'page' => 1]);
}
function training_organizers_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) return ['↕', 'Ordenar per aquesta columna'];
    return $curDir === 'asc' ? ['↑', 'Ordenació ascendent (clic per invertir)'] : ['↓', 'Ordenació descendent (clic per invertir)'];
}

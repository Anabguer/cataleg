<?php
declare(strict_types=1);

/**
 * Codi visual del lloc (no emmagatzemat): unitat (4 dígits) + '.' + número (2 dígits).
 */
function format_job_position_code(int $unitCode, int $positionNumber): string
{
    return format_padded_code($unitCode, 4) . '.' . format_padded_code($positionNumber, 2);
}

function job_positions_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'area_id' => $filters['area_id'],
        'section_id' => $filters['section_id'],
        'unit_id' => $filters['unit_id'],
        'is_catalog' => $filters['is_catalog'],
        'active' => $filters['active'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}

function job_positions_view_query_url(array $base, array $overrides): string
{
    $q = http_build_query(array_merge($base, $overrides));
    return app_url('job_positions.php' . ($q !== '' ? '?' . $q : ''));
}

function job_positions_view_sort_href(string $col, string $curCol, string $curDir, array $base): string
{
    $next = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';
    return job_positions_view_query_url($base, ['sort_by' => $col, 'sort_dir' => $next, 'page' => 1]);
}

function job_positions_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }
    return $curDir === 'asc'
        ? ['↑', 'Ordenació ascendent (clic per invertir)']
        : ['↓', 'Ordenació descendent (clic per invertir)'];
}

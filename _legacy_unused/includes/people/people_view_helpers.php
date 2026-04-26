<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/job_positions/job_positions_view_helpers.php';

function people_view_format_full_name(array $row): string
{
    $s1 = trim((string) ($row['last_name_1'] ?? ''));
    $s2 = trim((string) ($row['last_name_2'] ?? ''));
    $fn = trim((string) ($row['first_name'] ?? ''));
    $surname = trim($s1 . ' ' . $s2);
    if ($surname !== '' && $fn !== '') {
        return $surname . ', ' . $fn;
    }
    return $surname !== '' ? $surname : $fn;
}

/**
 * Etiqueta de lloc de treball per al llistat (o em dash si no n’hi ha).
 */
function people_view_job_position_label(array $row): string
{
    if (empty($row['job_position_id'])) {
        return '—';
    }
    $code = format_job_position_code((int) ($row['job_unit_code'] ?? 0), (int) ($row['job_position_number'] ?? 0));
    $name = (string) ($row['job_position_name'] ?? '');
    return $code . ' — ' . $name;
}

function people_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'active' => $filters['active'],
        'job_position_id' => $filters['job_position_id'],
        'has_job_position' => $filters['has_job_position'],
        'is_catalog' => $filters['is_catalog'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}

function people_view_query_url(array $base, array $overrides): string
{
    $q = http_build_query(array_merge($base, $overrides));
    return app_url('people.php' . ($q !== '' ? '?' . $q : ''));
}

function people_view_sort_href(string $col, string $curCol, string $curDir, array $base): string
{
    $next = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';
    return people_view_query_url($base, ['sort_by' => $col, 'sort_dir' => $next, 'page' => 1]);
}

function people_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }
    return $curDir === 'asc'
        ? ['↑', 'Ordenació ascendent (clic per invertir)']
        : ['↓', 'Ordenació descendent (clic per invertir)'];
}

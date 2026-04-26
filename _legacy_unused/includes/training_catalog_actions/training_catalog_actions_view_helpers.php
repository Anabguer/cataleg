<?php
declare(strict_types=1);

function training_catalog_actions_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'active' => $filters['active'],
        'knowledge_area_id' => $filters['knowledge_area_id'],
        'status' => $filters['status'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}

function training_catalog_actions_view_query_url(array $base, array $overrides): string
{
    $q = http_build_query(array_merge($base, $overrides));
    return app_url('training_catalog_actions.php' . ($q !== '' ? '?' . $q : ''));
}

function training_catalog_actions_view_sort_href(string $col, string $curCol, string $curDir, array $base): string
{
    $next = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';
    return training_catalog_actions_view_query_url($base, ['sort_by' => $col, 'sort_dir' => $next, 'page' => 1]);
}

function training_catalog_actions_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }
    return $curDir === 'asc' ? ['↑', 'Ordenació ascendent (clic per invertir)'] : ['↓', 'Ordenació descendent (clic per invertir)'];
}

function training_catalog_actions_format_duration_hours(?string $v): string
{
    if ($v === null || $v === '') {
        return '—';
    }
    $f = (float) $v;
    return number_format($f, 2, ',', '') . ' h';
}

function training_catalog_actions_list_status_cell(?string $status): string
{
    if ($status === null || trim($status) === '') {
        return '<span class="muted">—</span>';
    }
    return e($status);
}

<?php
declare(strict_types=1);

/**
 * @param array{
 *   q:string,
 *   program_year:string,
 *   subprogram_id:string,
 *   organizer_id:string,
 *   date_from:string,
 *   training_location_id:string,
 *   knowledge_area_id:string,
 *   trainer_type_id:string,
 *   execution_status:string,
 *   active:string
 * } $filters
 * @return array<string, string>
 */
function training_actions_view_filter_query_base(array $filters, string $sortBy, string $sortDir, int $perPage): array
{
    return [
        'q' => $filters['q'],
        'program_year' => $filters['program_year'],
        'subprogram_id' => $filters['subprogram_id'],
        'organizer_id' => $filters['organizer_id'],
        'date_from' => $filters['date_from'],
        'training_location_id' => $filters['training_location_id'],
        'knowledge_area_id' => $filters['knowledge_area_id'],
        'trainer_type_id' => $filters['trainer_type_id'],
        'execution_status' => $filters['execution_status'],
        'active' => $filters['active'],
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'per_page' => (string) $perPage,
    ];
}

/**
 * @param array<string, string> $base
 * @param array<string, string|int> $overrides
 */
function training_actions_view_query_url(array $base, array $overrides): string
{
    $q = http_build_query(array_merge($base, $overrides));

    return app_url('training_actions.php' . ($q !== '' ? '?' . $q : ''));
}

/**
 * @param array<string, string> $filterQueryBase
 */
function training_actions_view_sort_href(string $col, string $curCol, string $curDir, array $filterQueryBase): string
{
    $next = ($curCol === $col) ? ($curDir === 'asc' ? 'desc' : 'asc') : 'asc';

    return training_actions_view_query_url($filterQueryBase, [
        'sort_by' => $col,
        'sort_dir' => $next,
        'page' => 1,
    ]);
}

function training_actions_view_sort_indicator(string $col, string $curCol, string $curDir): array
{
    if ($curCol !== $col) {
        return ['↕', 'Ordenar per aquesta columna'];
    }

    return $curDir === 'asc' ? ['↑', 'Ordenació ascendent (clic per invertir)'] : ['↓', 'Ordenació descendent (clic per invertir)'];
}

function training_actions_list_nearest_date_cell(?string $d): string
{
    if ($d === null || $d === '') {
        return '<span class="muted">—</span>';
    }

    return e($d);
}

/**
 * Cel·la del llistat: estat d’execució (valors tancats, badge coherent).
 */
function training_actions_view_execution_status_cell(?string $status): string
{
    $norm = training_actions_normalize_execution_status($status) ?? 'Pendent';
    switch ($norm) {
        case 'Pendent':
            $badgeClass = 'badge--neutral';
            break;
        case 'En curs':
            $badgeClass = 'badge--info';
            break;
        case 'Realitzada':
            $badgeClass = 'badge--success';
            break;
        case 'Cancel·lada':
            $badgeClass = 'badge--danger';
            break;
        default:
            $badgeClass = 'badge--neutral';
            break;
    }

    return '<span class="badge ' . $badgeClass . ' ta-badge-exec-status">' . e($norm) . '</span>';
}

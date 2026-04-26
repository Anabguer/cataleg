<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function dashboard_people_checks_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

try {
    if (!auth_is_logged_in()) {
        dashboard_people_checks_json(false, ['errors' => ['_general' => 'Cal iniciar sessió.']], 401);
        exit;
    }
    permissions_load_for_session();
    if (!can_view_form('dashboard')) {
        dashboard_people_checks_json(false, ['errors' => ['_general' => 'Sense permís de consulta.']], 403);
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        dashboard_people_checks_json(false, ['errors' => ['_general' => 'Mètode no permès.']], 405);
        exit;
    }

    $year = catalog_year_current();
    if ($year === null || $year < 1) {
        dashboard_people_checks_json(false, ['errors' => ['_general' => 'Any de catàleg no disponible.']], 400);
        exit;
    }

    $type = trim((string) get_string('type'));
    $title = '';
    $orderBy = '';
    $whereExtra = '';
    if ($type === 'inactive_without_terminated_at') {
        $title = 'No actiu sense data de baixa';
        $whereExtra = ' AND is_active = 0 AND terminated_at IS NULL';
        $orderBy = ' ORDER BY last_name_1, last_name_2, first_name';
    } elseif ($type === 'active_with_terminated_at') {
        $title = 'Actiu amb data de baixa';
        $whereExtra = ' AND is_active = 1 AND terminated_at IS NOT NULL';
        $orderBy = ' ORDER BY terminated_at DESC, last_name_1, last_name_2, first_name';
    } else {
        dashboard_people_checks_json(false, ['errors' => ['_general' => 'Tipus de revisió no vàlid.']], 400);
        exit;
    }

    $sql = 'SELECT
                person_id,
                last_name_1,
                last_name_2,
                first_name,
                national_id_number,
                email,
                job_position_id,
                position_id,
                is_active,
                terminated_at
            FROM people
            WHERE catalog_year = :year' . $whereExtra . $orderBy;
    $st = db()->prepare($sql);
    $st->execute(['year' => $year]);
    $rows = $st->fetchAll() ?: [];

    dashboard_people_checks_json(true, [
        'type' => $type,
        'title' => $title,
        'total' => count($rows),
        'rows' => $rows,
    ]);
} catch (Throwable $e) {
    dashboard_people_checks_json(false, ['errors' => ['_general' => 'Error intern.']], 500);
}


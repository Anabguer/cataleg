<?php
declare(strict_types=1);

/**
 * Calendari anual del tauler: JSON sense redirecció (adequat per a fetch).
 * Requereix sessió; dades completes només si l’usuari pot veure accions formatives.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/training_actions/training_actions.php';

auth_require_login();
permissions_load_for_session();

header('Content-Type: application/json; charset=utf-8');

function dashboard_calendar_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

try {
    if (!can_view_form('dashboard')) {
        dashboard_calendar_api_json(false, ['errors' => ['_general' => 'Sense accés al tauler.']], 403);
        exit;
    }

    $y = (int) ($_GET['year'] ?? $_GET['y'] ?? 0);
    if ($y < 1990 || $y > 2100) {
        dashboard_calendar_api_json(false, ['errors' => ['_general' => 'Any no vàlid']], 400);
        exit;
    }

    if (!can_view_form('training_actions')) {
        dashboard_calendar_api_json(true, [
            'calendar' => [
                'year' => $y,
                'dates' => [],
            ],
            'restricted' => true,
        ]);
        exit;
    }

    $payload = training_actions_calendar_year_data(db(), $y);
    dashboard_calendar_api_json(true, ['calendar' => $payload, 'restricted' => false]);
} catch (Throwable $e) {
    dashboard_calendar_api_json(false, ['errors' => ['_general' => 'Error intern.']], 500);
}

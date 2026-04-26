<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

auth_require_login();
permissions_load_for_session();

$deniedFromQuery = get_string('denied') === '1';
if (!can_view_form('dashboard')) {
    if (!$deniedFromQuery) {
        redirect(app_url('dashboard.php?denied=1'));
    }
    $denied = true;
} else {
    $denied = $deniedFromQuery;
}

$fn = trim((string) ($_SESSION['full_name'] ?? ''));
$dashboardGreetingName = $fn !== '' ? $fn : (string) ($_SESSION['username'] ?? '');

$dashboardRoleLabel = '';
$rid = auth_role_id();
if ($rid !== null && $rid > 0) {
    $st = db()->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $rid]);
    $rr = $st->fetch();
    if ($rr && isset($rr['name'])) {
        $dashboardRoleLabel = (string) $rr['name'];
    }
}

$year = catalog_year_current();
$kpis = [
    'people' => 0,
    'job_positions' => 0,
    'positions' => 0,
    'programs' => 0,
    'subprograms' => 0,
    'work_centers' => 0,
];
$peopleDataChecks = [
    'inactive_without_terminated_at' => 0,
    'active_with_terminated_at' => 0,
];
if ($year !== null && $year > 0) {
    $countByYear = static function (PDO $db, string $table, int $catalogYear): int {
        $sql = 'SELECT COUNT(*) AS c FROM ' . $table . ' WHERE catalog_year = :year';
        $st = $db->prepare($sql);
        $st->execute(['year' => $catalogYear]);
        $row = $st->fetch();
        return (int) (($row['c'] ?? 0));
    };
    $countPeopleByState = static function (PDO $db, int $catalogYear, bool $isActive, bool $terminatedIsNull): int {
        $sql = 'SELECT COUNT(*) AS c FROM people
                WHERE catalog_year = :year
                  AND is_active = :is_active
                  AND terminated_at IS ' . ($terminatedIsNull ? 'NULL' : 'NOT NULL');
        $st = $db->prepare($sql);
        $st->execute([
            'year' => $catalogYear,
            'is_active' => $isActive ? 1 : 0,
        ]);
        $row = $st->fetch();
        return (int) (($row['c'] ?? 0));
    };
    $db = db();
    $st = $db->prepare('SELECT COUNT(*) AS c FROM people WHERE catalog_year = :year AND terminated_at IS NULL');
    $st->execute(['year' => $year]);
    $row = $st->fetch();
    $kpis['people'] = (int) (($row['c'] ?? 0));
    $kpis['job_positions'] = $countByYear($db, 'job_positions', $year);
    $kpis['positions'] = $countByYear($db, 'positions', $year);
    $kpis['programs'] = $countByYear($db, 'programs', $year);
    $kpis['subprograms'] = $countByYear($db, 'subprograms', $year);
    $kpis['work_centers'] = $countByYear($db, 'work_centers', $year);
    $peopleDataChecks['inactive_without_terminated_at'] = $countPeopleByState($db, $year, false, true);
    $peopleDataChecks['active_with_terminated_at'] = $countPeopleByState($db, $year, true, false);
}

$pageTitle = 'Tauler';
$activeNav = 'dashboard';
$extraCss = [];
$extraScripts = ['dashboard_people_checks.js'];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/dashboard/index.php';
require APP_ROOT . '/includes/footer.php';

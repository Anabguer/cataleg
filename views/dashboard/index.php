<?php
declare(strict_types=1);
/** @var bool $denied */
/** @var string $dashboardGreetingName */
/** @var array<string,int> $kpis */
/** @var array<string,int> $peopleDataChecks */

$pageHeader = page_header_with_escut([
    'title' => 'Tauler',
    'subtitle' => 'Base comuna de Catàleg / RLT',
    'greeting' => $dashboardGreetingName,
]);
require APP_ROOT . '/views/partials/page_header.php';

$catalogYears = catalog_year_available_years(db());
$catalogYearCurrent = catalog_year_current();
$redirectTo = $_SERVER['REQUEST_URI'] ?? app_url('dashboard.php');
?>

<?php if (!empty($denied)): ?>
<div class="alert alert--warning dashboard-alert" role="status">
    No tens permís per accedir a aquesta pantalla.
</div>
<?php endif; ?>

<div class="dashboard">
    <?php if ($catalogYears !== [] && $catalogYearCurrent !== null): ?>
    <div class="card dashboard__catalog-year-block">
        <form method="post" action="<?= e(app_url('set_catalog_year.php')) ?>" class="dashboard__catalog-year-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="redirect_to" value="<?= e((string) $redirectTo) ?>">
            <div class="catalog-year-text">
                <div class="dashboard__catalog-year-label" id="catalog-year-label">Any Catàleg</div>
                <div class="dashboard__catalog-year-sub" id="catalog-year-hint"><?= e("Selecciona l'any de treball") ?></div>
            </div>
            <select id="catalog-year-select" name="catalog_year" class="form-select form-select--sm" aria-labelledby="catalog-year-label" aria-describedby="catalog-year-hint" onchange="this.form.submit()">
                <?php foreach ($catalogYears as $year): ?>
                    <option value="<?= (int) $year ?>"<?= (int) $year === (int) $catalogYearCurrent ? ' selected' : '' ?>>
                        <?= (int) $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>

    <?php
    $kpiCards = [
        'people' => 'Persones actives',
        'job_positions' => 'Llocs de treball',
        'positions' => 'Places',
        'programs' => 'Programes',
        'subprograms' => 'Subprogrames',
        'work_centers' => 'Centres de treball',
    ];
    ?>
    <div class="dashboard__kpis" aria-label="KPIs del catàleg">
        <?php foreach ($kpiCards as $kpiKey => $kpiLabel): ?>
            <div class="dashboard__kpi card">
                <div class="dashboard__kpi-value"><?= e((string) (int) ($kpis[$kpiKey] ?? 0)) ?></div>
                <div class="dashboard__kpi-label"><?= e($kpiLabel) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    $inactiveWithoutTerminatedAt = (int) ($peopleDataChecks['inactive_without_terminated_at'] ?? 0);
    $activeWithTerminatedAt = (int) ($peopleDataChecks['active_with_terminated_at'] ?? 0);
    $peopleChecksHasWarnings = ($inactiveWithoutTerminatedAt > 0 || $activeWithTerminatedAt > 0);
    ?>
    <div
        class="card dashboard__people-checks<?= $peopleChecksHasWarnings ? ' dashboard__people-checks--warning' : ' dashboard__people-checks--ok' ?>"
        data-people-checks-root
        data-api-url="<?= e(app_url('dashboard_people_checks_api.php')) ?>"
    >
        <h2 class="dashboard__people-checks-title">Revisions de persones</h2>
        <ul class="dashboard__people-checks-list">
            <li class="dashboard__people-checks-item">
                <button
                    type="button"
                    class="dashboard__people-checks-btn"
                    data-check-type="inactive_without_terminated_at"
                    data-check-title="No actiu sense data de baixa"
                >
                    <span>No actiu sense data de baixa</span>
                    <strong><?= e((string) $inactiveWithoutTerminatedAt) ?></strong>
                </button>
            </li>
            <li class="dashboard__people-checks-item">
                <button
                    type="button"
                    class="dashboard__people-checks-btn"
                    data-check-type="active_with_terminated_at"
                    data-check-title="Actiu amb data de baixa"
                >
                    <span>Actiu amb data de baixa</span>
                    <strong><?= e((string) $activeWithTerminatedAt) ?></strong>
                </button>
            </li>
        </ul>
    </div>

    <div class="dashboard__calendar-block">
        <div class="alert alert--info" role="status">
            Entorn base inicialitzat. Les opcions de Seguretat (Usuaris, Rols, Permisos i Canvi de contrasenya) ja estan disponibles segons permisos de rol.
        </div>
    </div>
</div>

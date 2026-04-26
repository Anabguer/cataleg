<?php
declare(strict_types=1);

require_once __DIR__ . '/report_training_type_filter.php';

/**
 * Despatxa la generació HTML d’un informe segons el codi del catàleg training_reports.
 * Per afegir un informe nou: implementar dades+vista i afegir un cas aquí.
 *
 * @return bool true si s’ha generat sortida (el cridant ha de fer exit)
 */
function report_dispatch_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $includePersonalData = false, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL, bool $initialDateOnly = false, bool $ambAssistentsRpefc02 = true, bool $dadesInscritsRpefc04 = true): bool
{
    $code = strtoupper(trim((string) ($reportRow['report_code'] ?? '')));
    if ($code === 'RPAFC-01') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';
        report_rpa_fc_01_render($db, $reportRow, $programYear, $includeDraft, $includePersonalData, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPAFC-03') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_03.php';
        report_rpa_fc_03_render($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPAFC-02') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_02.php';
        report_rpa_fc_02_render($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter, $initialDateOnly);

        return true;
    }
    if ($code === 'RPAFC-04') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_04.php';
        report_rpa_fc_04_render($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'REEFC-01') {
        require_once APP_ROOT . '/includes/reports/report_ree_fc_01.php';
        report_ree_fc_01_render($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPEFC-01') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_01.php';
        report_rpe_fc_01_render($db, $reportRow, $programYear, $includeDraft);

        return true;
    }
    if ($code === 'RPEFC-02') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_02.php';
        report_rpe_fc_02_render($db, $reportRow, $programYear, $includeDraft, $ambAssistentsRpefc02);

        return true;
    }
    if ($code === 'RPEFC-03') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_03.php';
        report_rpe_fc_03_render($db, $reportRow, $programYear, $includeDraft);

        return true;
    }
    if ($code === 'RPEFC-04') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_04.php';
        report_rpe_fc_04_render($db, $reportRow, $programYear, $includeDraft, $dadesInscritsRpefc04);

        return true;
    }
    if ($code === 'RPEFC-05') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_05.php';
        report_rpe_fc_05_render($db, $reportRow, $programYear, $includeDraft);

        return true;
    }

    return false;
}

/**
 * Exporta Excel per codi d’informe (mateixos paràmetres que la vista HTML).
 *
 * @return bool true si s’ha enviat la descàrrega (el cridant ha de fer exit)
 */
function report_dispatch_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $includePersonalData, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL, bool $initialDateOnly = false, bool $ambAssistentsRpefc02 = true, bool $dadesInscritsRpefc04 = true): bool
{
    $code = strtoupper(trim((string) ($reportRow['report_code'] ?? '')));
    if ($code === 'RPAFC-01') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_01_excel.php';
        report_rpa_fc_01_export_excel($db, $reportRow, $programYear, $includeDraft, $includePersonalData, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPAFC-03') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_03_excel.php';
        report_rpa_fc_03_export_excel($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPAFC-02') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_02_excel.php';
        report_rpa_fc_02_export_excel($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter, $initialDateOnly);

        return true;
    }
    if ($code === 'RPAFC-04') {
        require_once APP_ROOT . '/includes/reports/report_rpa_fc_04_excel.php';
        report_rpa_fc_04_export_excel($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'REEFC-01') {
        require_once APP_ROOT . '/includes/reports/report_ree_fc_01_excel.php';
        report_ree_fc_01_export_excel($db, $reportRow, $programYear, $includeDraft, $trainingTypeFilter);

        return true;
    }
    if ($code === 'RPEFC-01') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_01_excel.php';
        report_rpe_fc_01_export_excel($db, $reportRow, $programYear, $includeDraft);

        return true;
    }
    if ($code === 'RPEFC-02') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_02_excel.php';
        report_rpe_fc_02_export_excel($db, $reportRow, $programYear, $includeDraft, $ambAssistentsRpefc02);

        return true;
    }
    if ($code === 'RPEFC-03') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_03_excel.php';
        report_rpe_fc_03_export_excel($db, $reportRow, $programYear, $includeDraft);

        return true;
    }
    if ($code === 'RPEFC-04') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_04_excel.php';
        report_rpe_fc_04_export_excel($db, $reportRow, $programYear, $includeDraft, $dadesInscritsRpefc04);

        return true;
    }
    if ($code === 'RPEFC-05') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_05_excel.php';
        report_rpe_fc_05_export_excel($db, $reportRow, $programYear, $includeDraft);

        return true;
    }

    return false;
}

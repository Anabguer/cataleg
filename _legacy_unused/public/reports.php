<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

require_can_view('reports');
require_once APP_ROOT . '/includes/training_reports/training_reports.php';

$pageTitle = 'Informes';
$activeNav = 'reports';

$db = db();
$selectorReports = training_reports_for_general_selector($db);
$programYear = (int) get_string('program_year');
if ($programYear < 1990 || $programYear > 2100) {
    $programYear = (int) date('Y');
}
$selectedReportId = (int) get_string('report_id');
$includePersonalData = get_string('include_personal_data') === '1';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';
$trainingTypeFilter = report_training_type_normalize_from_get(get_string('training_type'), get_string('programmed_training_only'));
$initialDateOnly = get_string('initial_date_only') === '1';
$ambAssistentsRpefc02 = get_string('amb_assistents') !== '0';
$dadesInscritsRpefc04 = get_string('dades_inscrits') !== '0';
$runMessage = '';

$selectedReportIsRpaFc01 = false;
$selectedReportIsRpaFc03 = false;
$selectedReportIsRpaFc02 = false;
$selectedReportIsRpaFc04 = false;
$selectedReportIsReeFc01 = false;
$selectedReportIsRpeFc02 = false;
$selectedReportIsRpeFc04 = false;
if ($selectedReportId > 0) {
    foreach ($selectorReports as $r) {
        if ((int) $r['id'] === $selectedReportId) {
            $codeSel = strtoupper(trim((string) ($r['report_code'] ?? '')));
            $selectedReportIsRpaFc01 = $codeSel === 'RPAFC-01';
            $selectedReportIsRpaFc03 = $codeSel === 'RPAFC-03';
            $selectedReportIsRpaFc02 = $codeSel === 'RPAFC-02';
            $selectedReportIsRpaFc04 = $codeSel === 'RPAFC-04';
            $selectedReportIsReeFc01 = $codeSel === 'REEFC-01';
            $selectedReportIsRpeFc02 = $codeSel === 'RPEFC-02';
            $selectedReportIsRpeFc04 = $codeSel === 'RPEFC-04';
            break;
        }
    }
}

if (get_string('run') === '1') {
    if ($selectedReportId < 1) {
        $runMessage = 'Selecciona un informe per continuar.';
    } else {
        $selected = null;
        foreach ($selectorReports as $r) {
            if ((int) $r['id'] === $selectedReportId) {
                $selected = $r;
                break;
            }
        }
        if (!$selected) {
            $runMessage = 'L’informe seleccionat no està disponible al selector general.';
        } else {
            $codeNorm = strtoupper(trim((string) ($selected['report_code'] ?? '')));
            if ($codeNorm === 'RPAFC-01') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                if ($includePersonalData) {
                    $params['include_personal_data'] = '1';
                }
                if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
                    $params['training_type'] = $trainingTypeFilter;
                }
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPAFC-03') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
                    $params['training_type'] = $trainingTypeFilter;
                }
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPAFC-02') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
                    $params['training_type'] = $trainingTypeFilter;
                }
                if ($initialDateOnly) {
                    $params['initial_date_only'] = '1';
                }
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPAFC-04') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
                    $params['training_type'] = $trainingTypeFilter;
                }
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'REEFC-01') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
                    $params['training_type'] = $trainingTypeFilter;
                }
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPEFC-01') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPEFC-02') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                    'amb_assistents' => get_string('amb_assistents') === '1' ? '1' : '0',
                ];
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPEFC-03') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPEFC-04') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                    'dades_inscrits' => get_string('dades_inscrits') === '1' ? '1' : '0',
                ];
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            if ($codeNorm === 'RPEFC-05') {
                $params = [
                    'code' => (string) $selected['report_code'],
                    'program_year' => (string) $programYear,
                ];
                redirect(app_url('report_view.php?' . http_build_query($params)));
            }
            $runMessage = 'Execució pendent per a «' . (string) $selected['report_name'] . '». Estructura de selector preparada.';
        }
    }
}

$extraCss = ['css/reports.css'];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/reports/index.php';
$extraScripts = ['reports.js'];
require APP_ROOT . '/includes/footer.php';

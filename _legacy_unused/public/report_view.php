<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

require_can_view('reports');
require_once APP_ROOT . '/includes/training_reports/training_reports.php';

$db = db();
$code = trim(get_string('code'));
if ($code === '') {
    redirect(app_url('reports.php'));
}

$reportRow = training_reports_get_by_code($db, $code);
if (!$reportRow || !(int) ($reportRow['is_active'] ?? 0)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo 'Informe no trobat.';
    exit;
}

$programYear = (int) get_string('program_year');
if ($programYear < 1990 || $programYear > 2100) {
    $programYear = (int) date('Y');
}

$includeDraft = get_string('optional_include_draft') === '1';
$includePersonalData = get_string('include_personal_data') === '1';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';
$trainingTypeFilter = report_training_type_normalize_from_get(get_string('training_type'), get_string('programmed_training_only'));
$initialDateOnly = get_string('initial_date_only') === '1';
$ambAssistentsRpefc02 = get_string('amb_assistents') !== '0';
$dadesInscritsRpefc04 = get_string('dades_inscrits') !== '0';

require_once APP_ROOT . '/includes/reports/report_dispatch.php';
if (report_dispatch_render($db, $reportRow, $programYear, $includeDraft, $includePersonalData, $trainingTypeFilter, $initialDateOnly, $ambAssistentsRpefc02, $dadesInscritsRpefc04)) {
    exit;
}

http_response_code(501);
header('Content-Type: text/html; charset=UTF-8');
echo 'Informe no implementat.';

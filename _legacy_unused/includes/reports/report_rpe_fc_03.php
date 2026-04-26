<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/reports/report_rpe_fc_02.php';

/**
 * RPEFC-03 — Accions formatives sense cap assistent (sense `attendance_flag = 1`).
 *
 * Reutilitza dades, enriquiment i maquetació de RPEFC-02; filtre sense assistència flag 1; Durada = durada real de l’acció (sense producte).
 */

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_03_resolved_title(array $reportRow): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }

    return $base;
}

/**
 * @return list<array<string, mixed>>
 */
function report_rpe_fc_03_load_actions(PDO $db, int $programYear, bool $includeDraft): array
{
    return report_rpe_fc_02_load_actions($db, $programYear, $includeDraft, false, false, 'actual_hours_simple');
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_03_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $actions = report_rpe_fc_03_load_actions($db, $programYear, $includeDraft);
    $totalHours = report_rpe_fc_02_total_hours($actions);

    $title = report_rpe_fc_03_resolved_title($reportRow);

    $withAttendees = false;

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));

    $ctxHeader = [
        'report_code' => (string) $reportRow['report_code'],
        'report_title' => $title,
        'program_year' => $programYear,
        'generated_at' => $generatedAt,
    ];

    ob_start();
    report_header_render($ctxHeader);
    $headerHtml = ob_get_clean();

    ob_start();
    require APP_ROOT . '/views/reports/rpe_fc_03_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
    ];
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}

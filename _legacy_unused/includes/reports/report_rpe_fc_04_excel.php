<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/reports/report_rpe_fc_04.php';
require_once APP_ROOT . '/includes/reports/report_rpe_fc_02_excel.php';

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_04_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $dadesInscrits): void
{
    report_rpe_fc_02_excel_export_grid($db, $reportRow, $programYear, $includeDraft, [
        'grid_kind' => 'rpefc04',
        'with_attendees' => $dadesInscrits,
        'must_have_attendance_flag1' => false,
        'duration_mode' => 'actual_hours_simple',
        'resolved_title' => report_rpe_fc_04_resolved_title($reportRow, $dadesInscrits),
        'empty_message' => 'No hi ha accions amb inscrits sense assistència per a aquest exercici.',
        'include_meta_amb_assistents' => false,
        'include_meta_dades_inscrits' => true,
        'table_header_main' => 'Acció formativa / Dades inscrits',
        'table_header_dur' => 'Durada',
        'sheet_title' => 'RPEFC-04',
    ]);
}

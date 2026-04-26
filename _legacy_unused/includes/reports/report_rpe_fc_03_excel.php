<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/reports/report_rpe_fc_03.php';

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_03_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    require_once APP_ROOT . '/includes/reports/report_rpe_fc_02_excel.php';

    report_rpe_fc_02_excel_export_grid($db, $reportRow, $programYear, $includeDraft, [
        'with_attendees' => false,
        'must_have_attendance_flag1' => false,
        'duration_mode' => 'actual_hours_simple',
        'resolved_title' => report_rpe_fc_03_resolved_title($reportRow),
        'empty_message' => 'No hi ha accions formatives sense assistència registrada per a aquest exercici.',
        'include_meta_amb_assistents' => false,
        'sheet_title' => 'RPEFC-03',
    ]);
}

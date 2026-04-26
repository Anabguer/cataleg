<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @param array{expr: string, result: string}|null $parts
 */
function report_rpe_fc_02_excel_set_duration_cell(Worksheet $sheet, int $row, ?array $parts): void
{
    $cell = 'B' . $row;
    if ($parts === null) {
        $sheet->setCellValue($cell, '—');
        $sheet->getStyle($cell)->getAlignment()->setWrapText(false);

        return;
    }
    $rich = new RichText();
    $rich->createText($parts['expr']);
    $gap = $rich->createTextRun(str_repeat(' ', 8));
    $gap->getFont()->setSize(10);
    $res = $rich->createTextRun($parts['result']);
    $res->getFont()->setBold(true)->setSize(10);
    $sheet->setCellValue($cell, $rich);
    $sheet->getStyle($cell)->getAlignment()->setWrapText(false);
}

/**
 * Export Excel compartit RPEFC-02 / RPEFC-03 (mateixa graella i estils).
 *
 * @param array{
 *   with_attendees: bool,
 *   must_have_attendance_flag1: bool,
 *   duration_mode: string,
 *   resolved_title: string,
 *   empty_message: string,
 *   include_meta_amb_assistents?: bool,
 *   include_meta_dades_inscrits?: bool,
 *   grid_kind?: string,
 *   table_header_main?: string,
 *   table_header_dur?: string,
 *   sheet_title: string
 * } $cfg
 */
function report_rpe_fc_02_excel_export_grid(PDO $db, array $reportRow, int $programYear, bool $includeDraft, array $cfg): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpe_fc_02.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $withAttendees = (bool) $cfg['with_attendees'];
    if (($cfg['grid_kind'] ?? '') === 'rpefc04') {
        require_once APP_ROOT . '/includes/reports/report_rpe_fc_04.php';
        $actions = report_rpe_fc_04_load_actions($db, $programYear, $includeDraft, $withAttendees);
    } else {
        $actions = report_rpe_fc_02_load_actions(
            $db,
            $programYear,
            $includeDraft,
            $withAttendees,
            (bool) $cfg['must_have_attendance_flag1'],
            (string) ($cfg['duration_mode'] ?? 'performed_product')
        );
    }
    $totalHours = report_rpe_fc_02_total_hours($actions);

    $title = (string) $cfg['resolved_title'];

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, (string) $cfg['sheet_title'], 'B');

    $spreadsheet->getProperties()
        ->setCreator('Formació municipal')
        ->setTitle($reportCode . ' · ' . $programYear);

    $meta = [
        'code' => $reportCode,
        'title' => $title,
        'subtitle' => $title . ' · exportació Excel',
        'year' => $programYear,
        'generated_at' => $generatedAt,
        'include_personal_data' => false,
        'label_persones_preinscrites' => 'Dades personals',
    ];
    if (!empty($cfg['include_meta_amb_assistents'])) {
        $meta['amb_assistents'] = $withAttendees;
    }
    if (!empty($cfg['include_meta_dades_inscrits'])) {
        $meta['dades_inscrits'] = $withAttendees;
    }

    $headerLastRow = report_excel_layout_render_report_header($b, $meta, $theme);

    $sheet = $b->sheet;

    if ($actions === []) {
        report_excel_layout_render_empty_message(
            $b,
            (string) $cfg['empty_message'],
            $theme
        );
        $b->skip(1);
        report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpe_fc_02());
        report_rpe_fc_02_excel_finish_sheet($sheet, $headerLastRow);
        report_rpe_fc_02_excel_send_download($spreadsheet, $reportCode, $programYear);

        return;
    }

    $headers = [
        (string) ($cfg['table_header_main'] ?? 'Acció formativa / Assistents'),
        (string) ($cfg['table_header_dur'] ?? 'Durada'),
    ];
    report_excel_layout_render_table_header($b, $headers, $theme);
    $hdrRow = $b->row - 1;
    $sheet->getStyle('B' . $hdrRow)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);

    $dataStartRow = $b->row;

    $aIdx = 0;
    foreach ($actions as $act) {
        $durParts = $act['duration_parts'] ?? null;
        $durPartsArr = is_array($durParts) && isset($durParts['expr'], $durParts['result'])
            ? ['expr' => (string) $durParts['expr'], 'result' => (string) $durParts['result']]
            : null;
        $codeAct = (string) ($act['action_display_code'] ?? '');
        $aname = (string) ($act['action_name'] ?? '');
        $r = $b->row;
        $sheet->setCellValue('A' . $r, $codeAct . "\n" . $aname);
        if (array_key_exists('duration_simple', $act)) {
            $sheet->setCellValue('B' . $r, (string) $act['duration_simple']);
        } else {
            report_rpe_fc_02_excel_set_duration_cell($sheet, $r, $durPartsArr);
        }
        report_rpe_fc_02_excel_style_action_row($sheet, $r, $theme, $aIdx % 2 === 1);
        $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(10);
        if (array_key_exists('duration_simple', $act)) {
            $sheet->getStyle('B' . $r)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(false);
        }
        $b->row++;
        $aIdx++;

        if ($withAttendees) {
            $attendees = $act['attendees'] ?? [];
            if (is_array($attendees)) {
                foreach ($attendees as $att) {
                    $r2 = $b->row;
                    $pname = trim((string) ($att['display_name'] ?? ''));
                    $sheet->setCellValue('A' . $r2, $pname);
                    $sheet->setCellValue('B' . $r2, '');
                    report_rpe_fc_02_excel_style_attendee_row($sheet, $r2, $theme);
                    $b->row++;
                }
            }
        }

        report_rpe_fc_02_excel_style_separator_row($sheet, $b->row, $theme);
        $b->row++;
    }

    $rTot = $b->row;
    $sheet->setCellValue('A' . $rTot, 'Total hores realitzades');
    $totalCell = $totalHours > 0.0
        ? report_rpa_fc_01_format_hours($totalHours) . ' hores'
        : '0,00 hores';
    $sheet->setCellValue('B' . $rTot, $totalCell);
    $sheet->getStyle('A' . $rTot)->getFont()->setBold(true);
    $sheet->getStyle('B' . $rTot)->getFont()->setBold(true);
    $sheet->getStyle('B' . $rTot)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    report_excel_style_border_thin_all($sheet, 'A' . $rTot . ':B' . $rTot, $theme, 'border');
    $b->row++;

    if ($rTot > $dataStartRow) {
        $durMode = (string) ($cfg['duration_mode'] ?? 'performed_product');
        $hAlign = $durMode === 'actual_hours_simple'
            ? Alignment::HORIZONTAL_RIGHT
            : Alignment::HORIZONTAL_LEFT;
        $sheet->getStyle('B' . $dataStartRow . ':B' . ($rTot - 1))->getAlignment()
            ->setHorizontal($hAlign)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(false);
    }

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpe_fc_02());
    $sheet->setShowGridlines(false);

    report_rpe_fc_02_excel_finish_sheet($sheet, $headerLastRow);
    report_rpe_fc_02_excel_send_download($spreadsheet, $reportCode, $programYear);
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_02_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $withAttendees): void
{
    report_rpe_fc_02_excel_export_grid($db, $reportRow, $programYear, $includeDraft, [
        'with_attendees' => $withAttendees,
        'must_have_attendance_flag1' => true,
        'duration_mode' => 'performed_product',
        'resolved_title' => report_rpe_fc_02_resolved_title($reportRow, $withAttendees),
        'empty_message' => 'No hi ha accions formatives amb assistència registrada per a aquest exercici.',
        'include_meta_amb_assistents' => true,
        'sheet_title' => 'RPEFC-02',
    ]);
}

function report_rpe_fc_02_excel_style_action_row(Worksheet $sheet, int $r, array $theme, bool $alternateRow): void
{
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $r . ':B' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('B' . $r)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
        ->setVertical(Alignment::VERTICAL_TOP)
        ->setWrapText(false);
    $sheet->getStyle('A' . $r)->getFont()->setSize(10);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

function report_rpe_fc_02_excel_style_attendee_row(Worksheet $sheet, int $r, array $theme): void
{
    $range = 'A' . $r . ':B' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['row_white']);
    $sheet->getStyle('A' . $r)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
        ->setVertical(Alignment::VERTICAL_TOP)
        ->setWrapText(false)
        ->setIndent(8);
    $sheet->getStyle('B' . $r)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
        ->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('A' . $r)->getFont()->setSize(10)->setBold(false);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

function report_rpe_fc_02_excel_style_separator_row(Worksheet $sheet, int $r, array $theme): void
{
    $sheet->setCellValue('A' . $r, '');
    $sheet->mergeCells('A' . $r . ':B' . $r);
    $sheet->getStyle('A' . $r)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
    $sheet->getStyle('A' . $r)->getBorders()->getTop()->getColor()->setRGB($theme['border']);
    $sheet->getRowDimension($r)->setRowHeight(6);
}

function report_rpe_fc_02_excel_finish_sheet(Worksheet $sheet, int $headerLastRow): void
{
    $ps = $sheet->getPageSetup();
    $ps->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
    $ps->setPaperSize(PageSetup::PAPERSIZE_A4);
    $ps->setFitToPage(false);
    $sheet->setPrintGridlines(false);
    $margins = $sheet->getPageMargins();
    $margins->setLeft(0.55);
    $margins->setRight(0.55);
    $margins->setTop(0.45);
    $margins->setBottom(0.5);
    if ($headerLastRow >= 1) {
        $ps->setRowsToRepeatAtTopByStartAndEnd(1, $headerLastRow);
    }
    $sheet->freezePane('A' . ($headerLastRow + 1));
}

function report_rpe_fc_02_excel_send_download(Spreadsheet $spreadsheet, string $reportCode, int $programYear): void
{
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_AccionsFormatives_' . $programYear . '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

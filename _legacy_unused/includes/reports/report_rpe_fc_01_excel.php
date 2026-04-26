<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

function report_rpe_fc_01_excel_style_action_row(Worksheet $sheet, int $r, array $theme, bool $alternateRow): void
{
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $r . ':C' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('A' . $r)->getFont()->setSize(10);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_01_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpe_fc_01.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $peopleBlocks = report_rpe_fc_01_load_people_blocks($db, $programYear, $includeDraft);

    $title = report_rpe_fc_01_resolved_title($reportRow);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPEFC-01', 'C');

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

    $headerLastRow = report_excel_layout_render_report_header($b, $meta, $theme);

    $sheet = $b->sheet;

    if ($peopleBlocks === []) {
        report_excel_layout_render_empty_message(
            $b,
            'No hi ha persones amb formació realitzada (assistència registrada) per a aquest exercici.',
            $theme
        );
        $b->skip(1);
        report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpe_fc_01());
        report_rpe_fc_01_excel_finish_sheet($sheet, $headerLastRow);
        report_rpe_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear);

        return;
    }

    $headers = ['Codi i descripció de l’acció', 'Dates previstes', 'Durada real'];

    foreach ($peopleBlocks as $pIdx => $person) {
        if ($pIdx > 0) {
            $sheet->setBreak('A' . $b->row, Worksheet::BREAK_ROW);
        }

        $codeStr = format_padded_code((int) ($person['person_code'] ?? 0), 5);
        $pName = trim((string) ($person['display_name'] ?? ''));
        $personLabel = 'Persona: ' . $codeStr . '    ' . $pName;

        $r = $b->row;
        $sheet->setCellValue('A' . $r, $personLabel);
        $sheet->mergeCells('A' . $r . ':C' . $r);
        report_excel_style_apply_subprogram($sheet, 'A' . $r, $theme);
        $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A' . $r)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(false);
        $sheet->getRowDimension($r)->setRowHeight(26);
        $b->row++;

        foreach ($person['subprograms'] ?? [] as $sub) {
            $subLabel = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
            report_excel_layout_render_area_heading($b, $subLabel, $theme);

            report_excel_layout_render_table_header($b, $headers, $theme);
            $hdrRow = $b->row - 1;
            $sheet->getStyle('C' . $hdrRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            $acts = $sub['actions'] ?? [];
            $aIdx = 0;
            foreach ($acts as $act) {
                $r2 = $b->row;
                $durF = $act['actual_duration_hours_f'] ?? null;
                $durCell = $durF === null ? '—' : report_rpa_fc_01_format_hours((float) $durF) . ' hores';
                $codeAct = (string) ($act['action_display_code'] ?? '');
                $aname = (string) ($act['action_name'] ?? '');
                $planned = $act['planned_date_lines'] ?? [];
                $datesCell = report_rpe_fc_01_planned_dates_cell_text(is_array($planned) ? $planned : []);

                $sheet->setCellValue('A' . $r2, $codeAct . "\n" . $aname);
                $sheet->setCellValue('B' . $r2, $datesCell);
                $sheet->setCellValue('C' . $r2, $durCell);
                report_rpe_fc_01_excel_style_action_row($sheet, $r2, $theme, $aIdx % 2 === 1);
                $sheet->getStyle('A' . $r2)->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('C' . $r2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $b->row++;
                $aIdx++;
            }

            $b->skip(1);
        }

        $b->skip(1);
    }

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpe_fc_01());
    $sheet->setShowGridlines(false);

    report_rpe_fc_01_excel_finish_sheet($sheet, $headerLastRow);
    report_rpe_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear);
}

function report_rpe_fc_01_excel_finish_sheet(Worksheet $sheet, int $headerLastRow): void
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

function report_rpe_fc_01_excel_send_download(Spreadsheet $spreadsheet, string $reportCode, int $programYear): void
{
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_FormacioPersones_' . $programYear . '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

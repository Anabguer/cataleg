<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_05_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpe_fc_05.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();
    $rows = report_rpe_fc_05_fetch_people($db, $programYear, $includeDraft);
    $title = report_rpe_fc_05_resolved_title($reportRow);
    $reportCode = (string) ($reportRow['report_code'] ?? '');
    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPEFC-05', 'B');

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

    if ($rows === []) {
        report_excel_layout_render_empty_message(
            $b,
            'No hi ha persones sense correu vinculades a accions formatives per a aquest exercici.',
            $theme
        );
    } else {
        report_excel_layout_render_table_header($b, ['Codi', 'Nom'], $theme);
        $hdrRow = $b->row - 1;
        $sheet->getStyle('A' . $hdrRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B' . $hdrRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $idx = 0;
        foreach ($rows as $r) {
            $rowNum = $b->row;
            $codeStr = format_padded_code((int) ($r['person_code'] ?? 0), 5);
            $nameStr = people_format_surname_first($r);
            $sheet->setCellValue('A' . $rowNum, $codeStr);
            $sheet->setCellValue('B' . $rowNum, $nameStr);

            $alt = $idx % 2 === 1;
            $bg = $alt ? $theme['row_alt'] : $theme['row_white'];
            $range = 'A' . $rowNum . ':B' . $rowNum;
            $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
            $sheet->getStyle('A' . $rowNum)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('B' . $rowNum)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
            $sheet->getStyle('A' . $rowNum)->getFont()->setSize(10);
            $sheet->getStyle('B' . $rowNum)->getFont()->setSize(10);
            report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
            $b->row++;
            $idx++;
        }
    }

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpe_fc_05());
    $sheet->setShowGridlines(false);

    report_rpe_fc_05_excel_finish_sheet($sheet, $headerLastRow);
    report_rpe_fc_05_excel_send_download($spreadsheet, $reportCode, $programYear);
}

function report_rpe_fc_05_excel_finish_sheet(Worksheet $sheet, int $headerLastRow): void
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

function report_rpe_fc_05_excel_send_download(Spreadsheet $spreadsheet, string $reportCode, int $programYear): void
{
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_FormacioPersones_' . $programYear . '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

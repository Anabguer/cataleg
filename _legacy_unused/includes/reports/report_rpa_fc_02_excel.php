<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_02_export_excel(
    PDO $db,
    array $reportRow,
    int $programYear,
    bool $includeDraft,
    string $trainingTypeFilter,
    bool $initialDateOnly
): void {
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Exportació Excel no disponible (falta vendor).';

        return;
    }

    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_02.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $actions = report_rpa_fc_02_fetch_actions($db, $programYear, $includeDraft, $trainingTypeFilter);
    $ids = array_map(static fn (array $a): int => (int) ($a['id'] ?? 0), $actions);
    $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
    $datesByAction = report_rpa_fc_02_fetch_dates_by_action($db, $ids, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_02_build_rows($actions, $datesByAction, $initialDateOnly, $programYear);
    $rows = $built['rows'];
    $calendarDayMarks = $built['calendar_day_marks'];
    $monthCounts = report_rpa_fc_02_month_day_counts($programYear, $calendarDayMarks);

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPAFC-02', 'N');

    $spreadsheet->getProperties()
        ->setCreator('Formació municipal')
        ->setTitle($reportCode . ' · ' . $programYear);

    $iniLabel = $initialDateOnly ? 'Sí' : 'No';
    $meta = [
        'code' => $reportCode,
        'title' => $title,
        'subtitle' => $title . ' · exportació Excel · Només data inicial: ' . $iniLabel,
        'year' => $programYear,
        'generated_at' => $generatedAt,
        'include_personal_data' => false,
        'label_persones_preinscrites' => 'Persones preinscrites',
        'training_type_filter' => $trainingTypeFilter,
        'initial_date_only' => $initialDateOnly,
    ];

    report_excel_layout_render_report_header($b, $meta, $theme);
    $sheet = $b->sheet;

    $sheet->setCellValue('A' . $b->row, 'Resum: dies amb formació en què hi ha alguna acció (segons filtres).');
    $sheet->mergeCells('A' . $b->row . ':N' . $b->row);
    report_excel_style_apply_subtitle($sheet, 'A' . $b->row, $theme);
    $b->row++;

    $rSum = $b->row;
    $sheet->mergeCells('A' . $rSum . ':B' . $rSum);
    $sheet->setCellValue('A' . $rSum, 'Nombre de dies (per mes)');
    $sheet->getStyle('A' . $rSum)->getFont()->setBold(true)->setSize(9);
    $sheet->getStyle('A' . $rSum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    for ($m = 1; $m <= 12; $m++) {
        $col = Coordinate::stringFromColumnIndex($m + 2);
        $sheet->setCellValue($col . $rSum, sprintf('%02d', $m));
        $sheet->getStyle($col . $rSum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . $rSum)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle($col . $rSum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['table_head_bg'] ?? 'E0E0E0');
        $sheet->getStyle($col . $rSum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB($theme['border'] ?? 'CCCCCC');
    }
    report_excel_style_border_thin_all($sheet, 'A' . $rSum . ':N' . $rSum, $theme, 'border');
    $b->row++;

    $rCnt = $b->row;
    $sheet->setCellValue('A' . $rCnt, '');
    $sheet->mergeCells('A' . $rCnt . ':B' . $rCnt);
    for ($m = 1; $m <= 12; $m++) {
        $col = Coordinate::stringFromColumnIndex($m + 2);
        $sheet->setCellValue($col . $rCnt, (int) ($monthCounts[$m] ?? 0));
        $sheet->getStyle($col . $rCnt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col . $rCnt)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB($theme['border'] ?? 'CCCCCC');
    }
    $b->row += 2;

    $hdr = $b->row;
    $sheet->setCellValue('A' . $hdr, 'Acció formativa');
    $sheet->setCellValue('B' . $hdr, 'Tipus de formació');
    for ($m = 1; $m <= 12; $m++) {
        $col = Coordinate::stringFromColumnIndex($m + 2);
        $sheet->setCellValue($col . $hdr, sprintf('%02d', $m));
    }
    $rangeHdr = 'A' . $hdr . ':N' . $hdr;
    $sheet->getStyle($rangeHdr)->getFont()->setBold(true)->setSize(9);
    $sheet->getStyle($rangeHdr)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['table_head_bg'] ?? 'E0E0E0');
    report_excel_style_border_thin_all($sheet, $rangeHdr, $theme, 'border');
    $b->row++;

    $idx = 0;
    foreach ($rows as $row) {
        $r = $b->row;
        $sheet->setCellValue('A' . $r, $row['display_code'] . ' ' . $row['name']);
        $sheet->setCellValue('B' . $r, (string) ($row['training_type_label'] ?? '—'));
        for ($m = 1; $m <= 12; $m++) {
            $col = Coordinate::stringFromColumnIndex($m + 2);
            $on = !empty($row['months'][$m]);
            $sheet->setCellValue($col . $r, $on ? '•' : '');
            $sheet->getStyle($col . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $striped = $idx % 2 === 1;
        $bg = $striped ? ($theme['row_alt'] ?? 'FAFBFC') : ($theme['row_white'] ?? 'FFFFFF');
        $sheet->getStyle('A' . $r . ':N' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        report_excel_style_border_thin_all($sheet, 'A' . $r . ':N' . $r, $theme, 'border');
        $b->row++;
        $idx++;
    }

    if ($rows === []) {
        $sheet->setCellValue('A' . $b->row, 'No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.');
        $sheet->mergeCells('A' . $b->row . ':N' . $b->row);
        $b->row++;
    }

    $b->row++;
    $sheet->setCellValue('A' . $b->row, 'Nota: el calendari anual del HTML es resumeix aquí amb el recompte de dies per mes i la matriu d’accions per mes.');
    $sheet->mergeCells('A' . $b->row . ':N' . $b->row);
    $sheet->getStyle('A' . $b->row)->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB(report_excel_argb($theme['text_muted'] ?? '666666'));
    $b->row++;

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_02());

    $sheet->setShowGridlines(false);

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
    if ($hdr >= 1) {
        $ps->setRowsToRepeatAtTopByStartAndEnd(1, $hdr);
    }
    $sheet->freezePane('A' . ($hdr + 1));

    report_rpa_fc_02_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter, $initialDateOnly);
}

function report_rpa_fc_02_excel_send_download(
    Spreadsheet $spreadsheet,
    string $reportCode,
    int $programYear,
    string $trainingTypeFilter,
    bool $initialDateOnly
): void {
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_Calendari_previst_' . $programYear;
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $filename .= '_tipus_' . preg_replace('/[^a-z0-9_]+/i', '_', $trainingTypeFilter);
    }
    if ($initialDateOnly) {
        $filename .= '_nom_data_inicial';
    }
    $filename .= '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

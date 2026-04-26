<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Vora exterior una mica més visible en files de total (sense rejilla dura a l’interior).
 */
function report_rpa_fc_03_excel_apply_total_row_outline(Worksheet $sheet, int $r, array $theme): void
{
    $range = 'A' . $r . ':H' . $r;
    $rgb = $theme['rpa_fc_03_total_outline'] ?? $theme['border_strong'];
    $sheet->getStyle($range)->getBorders()->getOutline()
        ->setBorderStyle(Border::BORDER_MEDIUM)
        ->getColor()->setRGB($rgb);
}

/**
 * Dues files de capçalera: «Cost» sobre E–G + títols (refinat: dos tons, alçades, vores).
 */
function report_rpa_fc_03_excel_render_table_headers(ReportExcelBuilder $b, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $costBg = $theme['rpa_fc_03_header_cost_bg'] ?? $theme['table_head_bg'];
    $colBg = $theme['rpa_fc_03_header_columns_bg'] ?? $theme['table_head_bg'];
    $soft = isset($theme['rpa_fc_03_border_soft']) ? 'rpa_fc_03_border_soft' : 'border';

    $sheet->mergeCells('A' . $r . ':D' . $r);
    $sheet->mergeCells('E' . $r . ':G' . $r);
    $sheet->setCellValue('A' . $r, '');
    $sheet->setCellValue('E' . $r, 'Cost');
    $sheet->setCellValue('H' . $r, '');
    $range1 = 'A' . $r . ':H' . $r;
    $sheet->getStyle($range1)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($costBg);
    report_excel_style_border_thin_all($sheet, $range1, $theme, $soft);
    $sheet->getStyle('E' . $r)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('E' . $r)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($theme['border_strong']);
    $sheet->getRowDimension($r)->setRowHeight(20);

    $r2 = $r + 1;
    $headers = ['Codi', 'Acció formativa', 'Hores', 'Places', 'Total', 'Ajuntament', 'Tercer', 'Finançament'];
    for ($i = 0; $i < 8; $i++) {
        $colLetter = Coordinate::stringFromColumnIndex($i + 1);
        $cell = $colLetter . $r2;
        $sheet->setCellValue($cell, $headers[$i]);
        $st = $sheet->getStyle($cell);
        $st->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
        $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($colBg);
        $hAlign = Alignment::HORIZONTAL_CENTER;
        if ($i >= 2 && $i <= 6) {
            $hAlign = Alignment::HORIZONTAL_RIGHT;
        }
        $st->getAlignment()->setHorizontal($hAlign)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $st->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB($theme[$soft]);
    }

    $sheet->getRowDimension($r2)->setRowHeight(21);
    $b->row = $r2 + 1;
}

/**
 * @param 'global-init'|'global-final' $kind
 */
function report_rpa_fc_03_excel_write_economic_total_row(
    ReportExcelBuilder $b,
    array $theme,
    array $s,
    string $codeText,
    string $labelText,
    string $kind
): void {
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, $codeText);
    $sheet->setCellValue('B' . $r, $labelText);
    $sheet->setCellValue('C' . $r, report_rpa_fc_01_format_hours((float) ($s['hours_sum'] ?? 0)));
    $sheet->setCellValue('D' . $r, (int) ($s['places_sum'] ?? 0));
    $sheet->setCellValue('E' . $r, report_rpa_fc_03_format_money($s['total_cost_sum'] ?? null));
    $sheet->setCellValue('F' . $r, report_rpa_fc_03_format_money($s['municipal_cost_sum'] ?? null));
    $sheet->setCellValue('G' . $r, report_rpa_fc_03_format_money($s['third_party_sum'] ?? null));
    $sheet->setCellValue('H' . $r, '—');
    $range = 'A' . $r . ':H' . $r;
    $bgKey = $kind === 'global-final' ? 'rpa_fc_03_bg_global_final' : 'rpa_fc_03_bg_global_init';
    $bg = $theme[$bgKey] ?? $theme['summary_final_bg'];
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('B' . $r)->getFont()->setBold(true)->setSize(10);
    foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
        $sheet->getStyle($col . $r)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($col . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    }
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    report_excel_style_border_thin_all($sheet, $range, $theme, isset($theme['rpa_fc_03_border_soft']) ? 'rpa_fc_03_border_soft' : 'border');
    report_rpa_fc_03_excel_apply_total_row_outline($sheet, $r, $theme);
    $sheet->getRowDimension($r)->setRowHeight(20);
    $b->row++;
}

/**
 * Fila subprograma / àrea amb totals (merge A:B).
 */
function report_rpa_fc_03_excel_write_heading_total_row(
    ReportExcelBuilder $b,
    array $theme,
    array $s,
    string $headingText,
    string $variant
): void {
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->mergeCells('A' . $r . ':B' . $r);
    $sheet->setCellValue('A' . $r, $headingText);
    $sheet->setCellValue('C' . $r, report_rpa_fc_01_format_hours((float) ($s['hours_sum'] ?? 0)));
    $sheet->setCellValue('D' . $r, (int) ($s['places_sum'] ?? 0));
    $sheet->setCellValue('E' . $r, report_rpa_fc_03_format_money($s['total_cost_sum'] ?? null));
    $sheet->setCellValue('F' . $r, report_rpa_fc_03_format_money($s['municipal_cost_sum'] ?? null));
    $sheet->setCellValue('G' . $r, report_rpa_fc_03_format_money($s['third_party_sum'] ?? null));
    $sheet->setCellValue('H' . $r, '—');
    $range = 'A' . $r . ':H' . $r;
    $bgKey = $variant === 'subprogram' ? 'rpa_fc_03_bg_subprogram' : 'rpa_fc_03_bg_area';
    $bg = $theme[$bgKey] ?? ($variant === 'subprogram' ? $theme['subprogram_bg'] : $theme['area_bg']);
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $labelBold = $variant === 'subprogram';
    $sheet->getStyle('A' . $r)->getFont()->setBold($labelBold)->setSize(10);
    foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
        $sheet->getStyle($col . $r)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($col . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    }
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    report_excel_style_border_thin_all($sheet, $range, $theme, isset($theme['rpa_fc_03_border_soft']) ? 'rpa_fc_03_border_soft' : 'border');
    if ($variant === 'subprogram') {
        report_rpa_fc_03_excel_apply_total_row_outline($sheet, $r, $theme);
    }
    $sheet->getRowDimension($r)->setRowHeight($variant === 'subprogram' ? 22 : 21);
    $b->row++;
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_03_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_03.php';
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = array_merge(report_excel_theme(), report_excel_theme_rpa_fc_03_layer());

    $rows = report_rpa_fc_03_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_03_build_blocks($rows);
    $blocks = $built['blocks'];
    $totalSummary = $built['total_summary'];

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPAFC-03', 'H');

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
        'label_persones_preinscrites' => 'Persones preinscrites',
        'training_type_filter' => $trainingTypeFilter,
    ];

    $headerLastRow = report_excel_layout_render_report_header($b, $meta, $theme);

    report_rpa_fc_03_excel_render_table_headers($b, $theme);

    $lastHeaderRowForPrint = $headerLastRow + 2;

    $b->sheet->setShowGridlines(false);

    report_rpa_fc_03_excel_write_economic_total_row(
        $b,
        $theme,
        $totalSummary,
        '—',
        'Totals globals (informe)',
        'global-init'
    );

    if ($rows === []) {
        report_excel_layout_render_empty_message(
            $b,
            'No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.',
            $theme
        );
        report_rpa_fc_03_excel_write_economic_total_row(
            $b,
            $theme,
            $totalSummary,
            '—',
            'Total general (informe)',
            'global-final'
        );
    } else {
        $aIdx = 0;
        foreach ($blocks as $sub) {
            $subAll = report_rpa_fc_03_subprogram_all_actions($sub);
            $subSummary = report_rpa_fc_03_economic_summary($subAll);
            $subLine = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
            report_rpa_fc_03_excel_write_heading_total_row($b, $theme, $subSummary, $subLine, 'subprogram');

            foreach ($sub['areas'] as $area) {
                $areaActions = $area['actions'];
                $areaSummary = report_rpa_fc_03_economic_summary($areaActions);
                $areaLine = 'ÀREA DE CONEIXEMENT: ' . trim((string) $area['code_display'] . ' ' . (string) $area['name']);
                report_rpa_fc_03_excel_write_heading_total_row($b, $theme, $areaSummary, $areaLine, 'area');

                foreach ($areaActions as $act) {
                    $py = (int) $act['program_year'];
                    $an = (int) $act['action_number'];
                    $displayCode = training_actions_format_display_code($py, $an);
                    $dur = $act['planned_duration_hours'] ?? null;
                    $durF = $dur !== null && $dur !== '' ? (float) $dur : null;
                    $third = report_rpa_fc_03_row_third_party($act);
                    $pt = $act['planned_total_cost'] ?? null;
                    $ptF = ($pt !== null && $pt !== '') ? (float) $pt : null;
                    $pm = $act['planned_municipal_cost'] ?? null;
                    $pmF = ($pm !== null && $pm !== '') ? (float) $pm : null;
                    $fund = trim((string) ($act['funding_name'] ?? ''));
                    $fundDisp = $fund === '' ? '—' : $fund;

                    $sheet = $b->sheet;
                    $r = $b->row;
                    $sheet->setCellValue('A' . $r, $displayCode);
                    $sheet->setCellValue('B' . $r, (string) $act['name']);
                    $sheet->setCellValue('C' . $r, report_rpa_fc_01_format_hours($durF));
                    $sheet->setCellValue('D' . $r, (int) ($act['planned_places'] ?? 0));
                    $sheet->setCellValue('E' . $r, report_rpa_fc_03_format_money($ptF));
                    $sheet->setCellValue('F' . $r, report_rpa_fc_03_format_money($pmF));
                    $sheet->setCellValue('G' . $r, report_rpa_fc_03_format_money($third));
                    $sheet->setCellValue('H' . $r, $fundDisp);
                    report_excel_style_apply_rpa_fc_03_data_row($sheet, $r, $theme, $aIdx % 2 === 1, $b->lastCol);
                    $b->row++;
                    $aIdx++;
                }
            }
        }

        report_rpa_fc_03_excel_write_economic_total_row(
            $b,
            $theme,
            $totalSummary,
            '—',
            'Total general (informe)',
            'global-final'
        );
    }

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_03());

    $sheet = $b->sheet;
    $ps = $sheet->getPageSetup();
    $ps->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $ps->setPaperSize(PageSetup::PAPERSIZE_A4);
    $ps->setFitToPage(false);
    $sheet->setPrintGridlines(false);
    $margins = $sheet->getPageMargins();
    $margins->setLeft(0.55);
    $margins->setRight(0.55);
    $margins->setTop(0.45);
    $margins->setBottom(0.5);
    if ($lastHeaderRowForPrint >= 1) {
        $ps->setRowsToRepeatAtTopByStartAndEnd(1, $lastHeaderRowForPrint);
    }
    $sheet->freezePane('A' . ($lastHeaderRowForPrint + 1));
    $sheet->getPageSetup()->setHorizontalCentered(false);

    report_rpa_fc_03_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter);
}

function report_rpa_fc_03_excel_send_download(Spreadsheet $spreadsheet, string $reportCode, int $programYear, string $trainingTypeFilter): void
{
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_Resum_economic_' . $programYear;
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $filename .= '_tipus_' . preg_replace('/[^a-z0-9_]+/i', '_', $trainingTypeFilter);
    }
    $filename .= '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

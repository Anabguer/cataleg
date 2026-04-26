<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @param array{
 *   places_sum:int,
 *   pre_sum:int,
 *   hours_planned_sum:float,
 *   hours_previstes_sum:float
 * } $subTotals
 */
function report_rpa_fc_04_excel_render_subtotal_row(ReportExcelBuilder $b, array $theme, array $subTotals): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->mergeCells('A' . $r . ':B' . $r);
    $sheet->setCellValue('A' . $r, 'Subtotal subprograma');
    $sheet->setCellValue('C' . $r, (int) $subTotals['places_sum']);
    $hpSum = (float) $subTotals['hours_planned_sum'];
    $hvSum = (float) $subTotals['hours_previstes_sum'];
    $sheet->setCellValue('D' . $r, $hpSum > 0.0 ? report_rpa_fc_01_format_hours($hpSum) : '—');
    $sheet->setCellValue('E' . $r, $hvSum > 0.0 ? report_rpa_fc_01_format_hours($hvSum) : '—');
    $sheet->getStyle('A' . $r . ':E' . $r)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('A' . $r . ':E' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['summary_area_bg'] ?? 'FEF9E7');
    report_excel_style_border_thin_all($sheet, 'A' . $r . ':E' . $r, $theme, 'border');
    $b->row++;
}

function report_rpa_fc_04_excel_render_subtotal_persones_row(ReportExcelBuilder $b, array $theme, int $preSum): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, 'Persones preinscrites (subtotal): ' . $preSum);
    $sheet->mergeCells('A' . $r . ':E' . $r);
    report_excel_style_apply_detail_line($sheet, 'A' . $r, $theme);
    $sheet->getStyle('A' . $r)->getFont()->setSize(9)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $b->row++;
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_04_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_04.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $rows = report_rpa_fc_04_fetch_actions($db, $programYear, $includeDraft, $trainingTypeFilter);
    $ids = [];
    foreach ($rows as $r) {
        $ids[] = (int) ($r['id'] ?? 0);
    }
    $preByAction = $ids !== [] ? report_rpa_fc_01_fetch_pre_registered_by_action($db, $ids) : [];
    $blocks = report_rpa_fc_04_build_blocks($rows, $preByAction);
    $globalTotals = report_rpa_fc_04_totals_global($blocks);
    $avgHoursPerPlace = report_rpa_fc_04_avg_hours_per_place($globalTotals);

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPAFC-04', 'E');

    $spreadsheet->getProperties()
        ->setCreator('Formació municipal')
        ->setTitle($reportCode . ' · ' . $programYear);

    $meta = [
        'code' => $reportCode,
        'title' => $title,
        'subtitle' => $title . ' · exportació Excel',
        'year' => $programYear,
        'generated_at' => $generatedAt,
        'include_personal_data' => true,
        'label_persones_preinscrites' => 'Persones preinscrites',
        'training_type_filter' => $trainingTypeFilter,
    ];

    $headerLastRow = report_excel_layout_render_report_header($b, $meta, $theme);

    if ($rows === []) {
        report_excel_layout_render_empty_message(
            $b,
            'No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.',
            $theme
        );
        $b->skip(1);
        report_excel_layout_render_merged_detail_line(
            $b,
            report_rpa_fc_04_footnote_ca($trainingTypeFilter),
            $theme
        );
        report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_04());
        report_rpa_fc_04_excel_finish_sheet($b->sheet, $headerLastRow);
        report_rpa_fc_04_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter);

        return;
    }

    $headers = ['Acció formativa', 'Persones preinscrites', 'Places previstes', 'Durada prevista', 'Hores previstes'];

    foreach ($blocks as $sub) {
        $subLabel = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
        report_excel_layout_render_subprogram_heading($b, $subLabel, $theme);

        report_excel_layout_render_table_header($b, $headers, $theme);

        $subActions = $sub['actions'] ?? [];
        $aIdx = 0;
        foreach ($subActions as $act) {
            $py = (int) $act['program_year'];
            $an = (int) $act['action_number'];
            $displayCode = training_actions_format_display_code($py, $an);
            $durF = $act['duration_hours'] ?? null;
            $hp = $act['hours_previstes'] ?? null;
            $names = $act['pre_names'] ?? [];

            $sheet = $b->sheet;
            $r = $b->row;
            $sheet->setCellValue('A' . $r, $displayCode . ' ' . (string) $act['name']);
            $sheet->setCellValue('B' . $r, '—');
            $sheet->setCellValue('C' . $r, (int) ($act['planned_places'] ?? 0));
            $sheet->setCellValue('D' . $r, report_rpa_fc_01_format_hours($durF));
            $sheet->setCellValue('E' . $r, $hp === null ? '—' : report_rpa_fc_01_format_hours((float) $hp));
            report_rpa_fc_04_excel_style_action_row($sheet, $r, $theme, $aIdx % 2 === 1);
            $b->row++;
            $aIdx++;

            foreach ($names as $pname) {
                $r2 = $b->row;
                $sheet->setCellValue('A' . $r2, '');
                $sheet->setCellValue('B' . $r2, '  · ' . $pname);
                $sheet->setCellValue('C' . $r2, '—');
                $sheet->setCellValue('D' . $r2, '—');
                $sheet->setCellValue('E' . $r2, '—');
                report_rpa_fc_04_excel_style_person_row($sheet, $r2, $theme);
                $b->row++;
            }

            $b->skip(1);
        }

        $subTotals = report_rpa_fc_04_totals_for_actions($subActions);
        report_rpa_fc_04_excel_render_subtotal_row($b, $theme, $subTotals);
        report_rpa_fc_04_excel_render_subtotal_persones_row($b, $theme, (int) $subTotals['pre_sum']);
        $b->skip(1);
    }

    $hpG = (float) $globalTotals['hours_planned_sum'];
    $hvG = (float) $globalTotals['hours_previstes_sum'];
    report_excel_layout_render_summary_block(
        $b,
        $theme,
        'Totals generals',
        'final',
        [
            ['Places previstes (suma)', (int) $globalTotals['places_sum']],
            ['Persones preinscrites (suma)', (int) $globalTotals['pre_sum']],
            ['Suma durades previstes (per acció)', $hpG > 0.0 ? report_rpa_fc_01_format_hours($hpG) : '—'],
            ['Suma hores previstes (places × durada)', $hvG > 0.0 ? report_rpa_fc_01_format_hours($hvG) : '—'],
            ['Promig d’hores per plaça preinscrita', $avgHoursPerPlace === null ? '—' : report_rpa_fc_01_format_hours($avgHoursPerPlace)],
        ]
    );

    $b->skip(1);
    report_excel_layout_render_merged_detail_line(
        $b,
        report_rpa_fc_04_footnote_ca($trainingTypeFilter),
        $theme
    );

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_04());
    $sheet = $b->sheet;
    $sheet->setShowGridlines(false);

    report_rpa_fc_04_excel_finish_sheet($sheet, $headerLastRow);
    report_rpa_fc_04_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter);
}

function report_rpa_fc_04_excel_style_action_row(Worksheet $sheet, int $r, array $theme, bool $alternateRow): void
{
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $r . ':E' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

function report_rpa_fc_04_excel_style_person_row(Worksheet $sheet, int $r, array $theme): void
{
    $range = 'A' . $r . ':E' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['detail_line_bg']);
    $sheet->getStyle('B' . $r)->getFont()->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

function report_rpa_fc_04_excel_finish_sheet(Worksheet $sheet, int $headerLastRow): void
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

function report_rpa_fc_04_excel_send_download(
    Spreadsheet $spreadsheet,
    string $reportCode,
    int $programYear,
    string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL
): void {
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_Preinscripcions_' . $programYear;
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $filename .= '_tipus_' . preg_replace('/[^a-z0-9_]+/i', '_', $trainingTypeFilter);
    }
    $filename .= '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

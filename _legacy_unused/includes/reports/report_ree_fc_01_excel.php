<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Capçalera de taula en dues files: columna A (jerarquia) + grup «Execució» (B–F).
 */
function report_ree_fc_01_excel_render_table_header_two_rows(ReportExcelBuilder $b, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $r2 = $r + 1;

    $sheet->setCellValue('A' . $r, 'Programa / Àrea de coneixement / Acció formativa');
    $sheet->mergeCells('A' . $r . ':A' . $r2);
    $sheet->setCellValue('B' . $r, 'Execució');
    $sheet->mergeCells('B' . $r . ':F' . $r);

    $sheet->setCellValue('B' . $r2, 'Estat');
    $sheet->setCellValue('C' . $r2, 'Ins.');
    $sheet->setCellValue('D' . $r2, 'Durada');
    $sheet->setCellValue('E' . $r2, 'Ass.');
    $sheet->setCellValue('F' . $r2, 'Hores');

    foreach (['A' . $r, 'B' . $r, 'B' . $r2, 'C' . $r2, 'D' . $r2, 'E' . $r2, 'F' . $r2] as $cell) {
        report_excel_style_apply_table_header_cell($sheet, $cell, $theme);
    }

    $sheet->getStyle('A' . $r)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('B' . $r2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    foreach (['C', 'D', 'E', 'F'] as $col) {
        $sheet->getStyle($col . $r2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    }
    $sheet->getRowDimension($r)->setRowHeight(22);
    $sheet->getRowDimension($r2)->setRowHeight(20);

    report_excel_style_border_thin_all($sheet, 'A' . $r . ':F' . $r2, $theme, 'border');

    $b->row = $r2 + 1;
}

/**
 * @param array<string,mixed> $roll Mètriques amb ins_sum, ass_sum, hours_sum (mateix format que rollup d’àrea/subprograma).
 * @param 'area'|'subprogram'|'global' $level
 */
function report_ree_fc_01_excel_render_subtotal_row(
    ReportExcelBuilder $b,
    array $theme,
    string $label,
    array $roll,
    string $level
): void {
    $sheet = $b->sheet;
    $r = $b->row;
    $hs = (float) ($roll['hours_sum'] ?? 0.0);

    $sheet->setCellValue('A' . $r, $label);
    $sheet->setCellValue('B' . $r, '—');
    $sheet->setCellValue('C' . $r, (int) ($roll['ins_sum'] ?? 0));
    $sheet->setCellValue('D' . $r, '—');
    $sheet->setCellValue('E' . $r, (int) ($roll['ass_sum'] ?? 0));
    $sheet->setCellValue('F' . $r, $hs > 0.0 ? report_rpa_fc_01_format_hours($hs) : '—');

    $sheet->getStyle('A' . $r . ':F' . $r)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('F' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $bgKey = $level === 'area' ? 'summary_area_bg' : 'summary_final_bg';
    $rgb = $theme[$bgKey] ?? ($theme['summary_area_bg'] ?? 'FEF9E7');
    $sheet->getStyle('A' . $r . ':F' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    report_excel_style_border_thin_all($sheet, 'A' . $r . ':F' . $r, $theme, 'border');
    $b->row++;
}

function report_ree_fc_01_excel_style_action_row(Worksheet $sheet, int $r, array $theme, bool $alternateRow): void
{
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $r . ':F' . $r;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('F' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_ree_fc_01_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, string $trainingTypeFilter): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_ree_fc_01.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $rows = report_ree_fc_01_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_01_build_blocks($rows);
    $blocks = report_ree_fc_01_blocks_with_rollups($built['blocks']);
    $globalMetrics = report_ree_fc_01_metrics_global($blocks);
    $finalKpis = report_ree_fc_01_final_kpis($globalMetrics);

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'REEFC-01', 'F');

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
            report_ree_fc_01_footnote_ca($trainingTypeFilter),
            $theme
        );
        report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_ree_fc_01());
        report_ree_fc_01_excel_finish_sheet($b->sheet, $headerLastRow);
        report_ree_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter);

        return;
    }

    report_ree_fc_01_excel_render_table_header_two_rows($b, $theme);

    foreach ($blocks as $sub) {
        $subLabel = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
        report_excel_layout_render_subprogram_heading($b, $subLabel, $theme);

        foreach ($sub['areas'] ?? [] as $area) {
            $areaLabel = 'ÀREA: ' . trim((string) $area['code_display'] . ' ' . (string) $area['name']);
            report_excel_layout_render_area_heading($b, $areaLabel, $theme);

            $areaActions = $area['actions'] ?? [];
            $aIdx = 0;
            foreach ($areaActions as $act) {
                $py = (int) $act['program_year'];
                $an = (int) $act['action_number'];
                $displayCode = training_actions_format_display_code($py, $an);
                $estat = training_actions_normalize_execution_status(
                    isset($act['execution_status']) ? (string) $act['execution_status'] : ''
                );
                $durReal = $act['actual_duration_hours_f'] ?? null;
                $hExec = $act['hours_exec'] ?? null;

                $sheet = $b->sheet;
                $r = $b->row;
                $sheet->setCellValue('A' . $r, $displayCode . ' ' . (string) $act['name']);
                $sheet->setCellValue('B' . $r, $estat ?? '—');
                $sheet->setCellValue('C' . $r, (int) ($act['ins_count'] ?? 0));
                $sheet->setCellValue('D' . $r, report_rpa_fc_01_format_hours($durReal));
                $sheet->setCellValue('E' . $r, (int) ($act['ass_count'] ?? 0));
                $sheet->setCellValue('F' . $r, $hExec === null ? '—' : report_rpa_fc_01_format_hours((float) $hExec));
                report_ree_fc_01_excel_style_action_row($sheet, $r, $theme, $aIdx % 2 === 1);
                $b->row++;
                $aIdx++;
            }

            /** @var array<string,mixed> $areaRoll */
            $areaRoll = $area['rollup'] ?? report_ree_fc_01_metrics_for_actions($areaActions);
            report_ree_fc_01_excel_render_subtotal_row($b, $theme, 'Subtotal àrea', $areaRoll, 'area');
            $b->skip(1);
        }

        /** @var array<string,mixed> $subRoll */
        $subRoll = $sub['rollup'] ?? report_ree_fc_01_metrics_for_actions([]);
        report_ree_fc_01_excel_render_subtotal_row($b, $theme, 'Subtotal subprograma', $subRoll, 'subprogram');
        $b->skip(1);
    }

    $hsG = (float) ($globalMetrics['hours_sum'] ?? 0.0);
    $pct = $finalKpis['percent_execucio'] ?? null;
    $dp = $finalKpis['durada_promig'] ?? null;
    $pctStr = $pct === null ? '—' : number_format((float) $pct * 100.0, 2, ',', '.') . ' %';
    $dpStr = $dp === null ? '—' : report_rpa_fc_01_format_hours((float) $dp);

    report_ree_fc_01_excel_render_subtotal_row($b, $theme, 'Totals generals', $globalMetrics, 'global');
    $b->skip(1);

    report_excel_layout_render_summary_block(
        $b,
        $theme,
        'Durada promig i percentatge d’execució',
        'final',
        [
            ['Durada promig (Σ durada real × assistents / Σ assistents)', $dpStr],
            ['Percentatge d’execució d’hores', $pctStr],
        ]
    );

    $b->skip(1);
    report_excel_layout_render_merged_detail_line(
        $b,
        report_ree_fc_01_footnote_ca($trainingTypeFilter),
        $theme
    );

    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_ree_fc_01());
    $b->sheet->setShowGridlines(false);

    report_ree_fc_01_excel_finish_sheet($b->sheet, $headerLastRow);
    report_ree_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear, $trainingTypeFilter);
}

function report_ree_fc_01_excel_finish_sheet(Worksheet $sheet, int $headerLastRow): void
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

function report_ree_fc_01_excel_send_download(
    Spreadsheet $spreadsheet,
    string $reportCode,
    int $programYear,
    string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL
): void {
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_EstatExecucio_' . $programYear;
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $filename .= '_tipus_' . preg_replace('/[^a-z0-9_]+/i', '_', $trainingTypeFilter);
    }
    $filename .= '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}

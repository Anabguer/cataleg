<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Paleta corporativa compartida per a tots els informes Excel.
 *
 * @return array<string, string> clau => RGB sense # (6 hex)
 */
function report_excel_theme(): array
{
    return [
        'border' => 'CBD5E1',
        'border_strong' => '94A3B8',
        'text' => '0F172A',
        'text_muted' => '475467',
        'header_title_bg' => 'F1F5F9',
        'header_meta_bg' => 'F8FAFC',
        'subprogram_bg' => 'D8E4EF',
        'area_bg' => 'E8F0F7',
        'table_head_bg' => 'E2E8F0',
        'row_alt' => 'FAFBFC',
        'row_white' => 'FFFFFF',
        'detail_line_bg' => 'F8FAFC',
        'summary_area_bg' => 'FEF9E7',
        'summary_area_border' => 'E8DCC8',
        'summary_final_bg' => 'E2E8F0',
        'summary_final_border' => '94A3B8',
    ];
}

/**
 * Paleta i claus addicionals només per RPAFC-03 (export Excel): fons de totals, vores suaus, capçaleres.
 *
 * @return array<string, string>
 */
function report_excel_theme_rpa_fc_03_layer(): array
{
    return [
        'rpa_fc_03_border_soft' => 'E8EDF2',
        'rpa_fc_03_bg_global_init' => 'E8EEF4',
        'rpa_fc_03_bg_global_final' => 'C5D0E0',
        'rpa_fc_03_bg_subprogram' => 'D8E4EF',
        'rpa_fc_03_bg_area' => 'F1F5F9',
        'rpa_fc_03_header_cost_bg' => 'E2E8F0',
        'rpa_fc_03_header_columns_bg' => 'F1F5F9',
        'rpa_fc_03_total_outline' => '94A3B8',
    ];
}

function report_excel_argb(string $rgb6): string
{
    return 'FF' . ltrim($rgb6, '#');
}

function report_excel_style_border_thin_all(Worksheet $sheet, string $range, array $theme, string $colorKey = 'border'): void
{
    $rgb = $theme[$colorKey] ?? $theme['border'];
    $sheet->getStyle($range)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->getColor()->setRGB($rgb);
}

function report_excel_style_apply_title_main(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(15)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['header_title_bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(false);
    $st->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($theme['border_strong']);
}

function report_excel_style_apply_subtitle(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['header_title_bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
}

function report_excel_style_apply_meta_label(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['header_meta_bg']);
    $st->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
}

function report_excel_style_apply_meta_value(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['header_meta_bg']);
    $st->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
}

function report_excel_style_apply_empty_message(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setItalic(true)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $st->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
}

function report_excel_style_apply_subprogram(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['subprogram_bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $st->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($theme['border_strong']);
}

function report_excel_style_apply_area(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['area_bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $st->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($theme['border']);
}

function report_excel_style_apply_table_header_cell(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['table_head_bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $st->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($theme['border']);
}

/**
 * Fila de dades principal (accions): alternança i alineacions (A–F per a RPAFC-01; lastCol genèric).
 */
function report_excel_style_apply_action_data_row(
    Worksheet $sheet,
    int $row,
    array $theme,
    bool $alternateRow,
    string $lastCol = 'F'
): void {
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $row . ':' . $lastCol . $row;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('B' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    report_excel_style_border_thin_all($sheet, $range, $theme, 'border');
}

function report_excel_style_apply_detail_line(Worksheet $sheet, string $cell, array $theme): void
{
    $st = $sheet->getStyle($cell);
    $st->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $st->getFont()->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($theme['detail_line_bg']);
    $st->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($theme['border']);
}

/**
 * @param 'area'|'final' $level
 */
function report_excel_style_summary_block_colors(array $theme, string $level): array
{
    if ($level === 'final') {
        return ['bg' => $theme['summary_final_bg'], 'border' => $theme['summary_final_border']];
    }

    return ['bg' => $theme['summary_area_bg'], 'border' => $theme['summary_area_border']];
}

/**
 * @param 'area'|'final' $level
 */
function report_excel_style_apply_summary_title(Worksheet $sheet, string $cell, array $theme, string $level): void
{
    $c = report_excel_style_summary_block_colors($theme, $level);
    $st = $sheet->getStyle($cell);
    $st->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($c['bg']);
    $st->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
    $st->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($c['border']);
}

/**
 * @param 'area'|'final' $level
 */
function report_excel_style_apply_summary_label_cell(Worksheet $sheet, string $cell, array $theme, string $level): void
{
    $c = report_excel_style_summary_block_colors($theme, $level);
    $st = $sheet->getStyle($cell);
    $st->getFont()->setSize(10)->getColor()->setRGB(report_excel_argb($theme['text_muted']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($c['bg']);
    $st->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
}

/**
 * @param 'area'|'final' $level
 */
function report_excel_style_apply_summary_value_cell(Worksheet $sheet, string $cell, array $theme, string $level): void
{
    $c = report_excel_style_summary_block_colors($theme, $level);
    $st = $sheet->getStyle($cell);
    $st->getFont()->setSize(10)->setBold(true)->getColor()->setRGB(report_excel_argb($theme['text']));
    $st->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($c['bg']);
    $st->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setWrapText(true);
}

/**
 * @param 'area'|'final' $level
 */
function report_excel_style_apply_summary_block_outline(Worksheet $sheet, string $range, array $theme, string $level): void
{
    $c = report_excel_style_summary_block_colors($theme, $level);
    $borderRgb = $c['border'];
    $sheet->getStyle($range)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->getColor()->setRGB($borderRgb);
    $sheet->getStyle($range)->getBorders()->getOutline()
        ->setBorderStyle(Border::BORDER_MEDIUM)
        ->getColor()->setRGB($borderRgb);
}

/**
 * Fila de dades RPAFC-03 (8 columnes): C–G alineades a la dreta.
 */
function report_excel_style_apply_rpa_fc_03_data_row(
    Worksheet $sheet,
    int $row,
    array $theme,
    bool $alternateRow,
    string $lastCol = 'H'
): void {
    $bg = $alternateRow ? $theme['row_alt'] : $theme['row_white'];
    $range = 'A' . $row . ':' . $lastCol . $row;
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(10);
    $sheet->getStyle('B' . $row)->getFont()->setBold(false)->setSize(10);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    foreach (['C', 'D', 'E', 'F', 'G'] as $col) {
        $sheet->getStyle($col . $row)->getFont()->setSize(10);
        $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
    }
    $sheet->getStyle('H' . $row)->getFont()->setSize(10);
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $borderKey = isset($theme['rpa_fc_03_border_soft']) ? 'rpa_fc_03_border_soft' : 'border';
    report_excel_style_border_thin_all($sheet, $range, $theme, $borderKey);
    $sheet->getRowDimension($row)->setRowHeight(18);
}

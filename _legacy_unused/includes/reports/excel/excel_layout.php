<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once dirname(__DIR__) . '/report_training_type_filter.php';
require_once __DIR__ . '/excel_styles.php';
require_once __DIR__ . '/excel_builder.php';

/**
 * Metadades unificades per a la capçalera d’export Excel.
 *
 * @phpstan-type ReportExcelHeaderMeta array{
 *   code: string,
 *   title: string,
 *   subtitle?: string,
 *   year: int|string,
 *   generated_at: \DateTimeImmutable|\DateTime|\DateTimeInterface|string,
 *   include_personal_data: bool
 * }
 */

/**
 * Capçalera corporativa (títol, subtítol, quadre A:B de metadades, separador).
 * Avança $b->row i retorna el número de fila on acaba la zona de capçalera (per congelació i impressió).
 */
function report_excel_layout_render_report_header(ReportExcelBuilder $b, array $meta, array $theme): int
{
    $sheet = $b->sheet;
    $lastCol = $b->lastCol;

    $title = (string) ($meta['title'] ?? '');
    $subtitle = trim((string) ($meta['subtitle'] ?? ''));
    if ($subtitle === '') {
        $subtitle = 'Informe · exportació Excel';
    }

    $code = (string) ($meta['code'] ?? '');
    $year = $meta['year'] ?? '';
    $gen = $meta['generated_at'] ?? null;
    if ($gen instanceof \DateTimeImmutable || $gen instanceof \DateTimeInterface) {
        $genStr = $gen->format('d/m/Y H:i');
    } else {
        $genStr = (string) $gen;
    }
    $incPers = !empty($meta['include_personal_data']);
    $labelPersones = trim((string) ($meta['label_persones_preinscrites'] ?? ''));
    if ($labelPersones === '') {
        $labelPersones = 'Dades personals';
    }

    $r = $b->row;

    $sheet->setCellValue('A' . $r, $title);
    $sheet->mergeCells('A' . $r . ':' . $lastCol . $r);
    report_excel_style_apply_title_main($sheet, 'A' . $r, $theme);
    $sheet->getRowDimension($r)->setRowHeight(30);
    $r++;

    $sheet->setCellValue('A' . $r, $subtitle);
    $sheet->mergeCells('A' . $r . ':' . $lastCol . $r);
    report_excel_style_apply_subtitle($sheet, 'A' . $r, $theme);
    $r++;

    $sheet->setCellValue('A' . $r, '');
    $sheet->mergeCells('A' . $r . ':' . $lastCol . $r);
    $r++;

    $metaStart = $r;
    $metaRows = [
        ['Codi informe', $code],
        ['Descripció', $title],
        ['Exercici', (string) $year],
        ['Data de generació', $genStr],
        [$labelPersones, $incPers ? 'Sí' : 'No'],
    ];
    if (array_key_exists('training_type_filter', $meta)) {
        $tt = (string) ($meta['training_type_filter'] ?? REPORT_TRAINING_TYPE_ALL);
        $metaRows[] = ['Tipus de formació', report_training_type_label_ca($tt)];
    } elseif (array_key_exists('programmed_training_only', $meta)) {
        $metaRows[] = ['Formació programada', !empty($meta['programmed_training_only']) ? 'Sí' : 'No'];
    }
    if (array_key_exists('initial_date_only', $meta)) {
        $metaRows[] = ['Només data inicial', !empty($meta['initial_date_only']) ? 'Sí' : 'No'];
    }
    if (array_key_exists('amb_assistents', $meta)) {
        $metaRows[] = ['Amb assistents', !empty($meta['amb_assistents']) ? 'Sí' : 'No'];
    }
    if (array_key_exists('dades_inscrits', $meta)) {
        $metaRows[] = ['Dades inscrits', !empty($meta['dades_inscrits']) ? 'Sí' : 'No'];
    }
    foreach ($metaRows as $pair) {
        $sheet->setCellValue('A' . $r, $pair[0]);
        $sheet->setCellValue('B' . $r, $pair[1]);
        report_excel_style_apply_meta_label($sheet, 'A' . $r, $theme);
        report_excel_style_apply_meta_value($sheet, 'B' . $r, $theme);
        $r++;
    }
    $metaEnd = $r - 1;
    report_excel_style_border_thin_all($sheet, 'A' . $metaStart . ':B' . $metaEnd, $theme, 'border');

    $r++;
    $headerLastRow = $r;
    $r++;

    $b->row = $r;

    return $headerLastRow;
}

function report_excel_layout_render_subprogram_heading(ReportExcelBuilder $b, string $label, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, $label);
    $sheet->mergeCells('A' . $r . ':' . $b->lastCol . $r);
    report_excel_style_apply_subprogram($sheet, 'A' . $r, $theme);
    $sheet->getRowDimension($r)->setRowHeight(22);
    $b->row++;
}

function report_excel_layout_render_area_heading(ReportExcelBuilder $b, string $label, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, $label);
    $sheet->mergeCells('A' . $r . ':' . $b->lastCol . $r);
    report_excel_style_apply_area($sheet, 'A' . $r, $theme);
    $sheet->getRowDimension($r)->setRowHeight(20);
    $b->row++;
}

/**
 * @param list<string> $headers
 */
function report_excel_layout_render_table_header(ReportExcelBuilder $b, array $headers, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $col = 1;
    foreach ($headers as $h) {
        $cell = Coordinate::stringFromColumnIndex($col) . $r;
        $sheet->setCellValue($cell, $h);
        report_excel_style_apply_table_header_cell($sheet, $cell, $theme);
        $col++;
    }
    $b->row++;
}

function report_excel_layout_render_merged_detail_line(ReportExcelBuilder $b, string $text, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, $text);
    $sheet->mergeCells('A' . $r . ':' . $b->lastCol . $r);
    report_excel_style_apply_detail_line($sheet, 'A' . $r, $theme);
    $b->row++;
}

/**
 * @param list<array{0: string, 1: string|int|float}> $lines
 * @param 'area'|'final' $level
 */
function report_excel_layout_render_summary_block(
    ReportExcelBuilder $b,
    array $theme,
    string $blockTitle,
    string $level,
    array $lines
): void {
    $sheet = $b->sheet;
    $lastCol = $b->lastCol;
    $r = $b->row;
    $startRow = $r;

    $sheet->setCellValue('A' . $r, $blockTitle);
    $sheet->mergeCells('A' . $r . ':' . $lastCol . $r);
    report_excel_style_apply_summary_title($sheet, 'A' . $r, $theme, $level);
    $r++;

    foreach ($lines as $line) {
        $sheet->setCellValue('A' . $r, $line[0]);
        $sheet->setCellValue('B' . $r, $line[1]);
        report_excel_style_apply_summary_label_cell($sheet, 'A' . $r, $theme, $level);
        report_excel_style_apply_summary_value_cell($sheet, 'B' . $r, $theme, $level);
        $r++;
    }

    $endRow = $r - 1;
    report_excel_style_apply_summary_block_outline($sheet, 'A' . $startRow . ':' . $lastCol . $endRow, $theme, $level);
    $b->row = $r + 1;
}

function report_excel_layout_render_empty_message(ReportExcelBuilder $b, string $message, array $theme): void
{
    $sheet = $b->sheet;
    $r = $b->row;
    $sheet->setCellValue('A' . $r, $message);
    $sheet->mergeCells('A' . $r . ':' . $b->lastCol . $r);
    report_excel_style_apply_empty_message($sheet, 'A' . $r, $theme);
    $b->row++;
}

/**
 * Amplades per defecte RPAFC-01 (6 columnes). Altres informes poden passar un mapa propi.
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpa_fc_01(): array
{
    return [
        'A' => 18,
        'B' => 42,
        'C' => 22,
        'D' => 11,
        'E' => 9,
        'F' => 34,
    ];
}

/**
 * RPAFC-03 — 8 columnes (codi, nom, hores, places, imports, finançament).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpa_fc_03(): array
{
    return [
        'A' => 13,
        'B' => 42,
        'C' => 9,
        'D' => 8,
        'E' => 13,
        'F' => 13,
        'G' => 13,
        'H' => 32,
    ];
}

/**
 * RPAFC-02 — Acció, programada, 12 mesos (C–N).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpa_fc_02(): array
{
    $w = [
        'A' => 36,
        'B' => 12,
    ];
    for ($i = 3; $i <= 14; $i++) {
        $w[Coordinate::stringFromColumnIndex($i)] = 5;
    }

    return $w;
}

/**
 * RPAFC-04 — Acció, persones, places, durada, hores (A–E).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpa_fc_04(): array
{
    return [
        'A' => 40,
        'B' => 32,
        'C' => 12,
        'D' => 12,
        'E' => 14,
    ];
}

/**
 * REEFC-01 — jerarquia + Execució (Estat…Hores), columnes A–F.
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_ree_fc_01(): array
{
    return [
        'A' => 44,
        'B' => 14,
        'C' => 8,
        'D' => 11,
        'E' => 8,
        'F' => 11,
    ];
}

/**
 * RPEFC-01 — Codi acció, descripció, dates, durada (A–D).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpe_fc_01(): array
{
    return [
        'A' => 42,
        'B' => 22,
        'C' => 14,
    ];
}

/**
 * RPEFC-02 — Acció / assistents, durada (A–B).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpe_fc_02(): array
{
    return [
        'A' => 48,
        'B' => 26,
    ];
}

/**
 * RPEFC-05 — Codi persona, nom (A–B).
 *
 * @return array<string, float|int>
 */
function report_excel_layout_column_widths_rpe_fc_05(): array
{
    return [
        'A' => 14,
        'B' => 48,
    ];
}

/**
 * @param array<string, float|int> $widths
 */
function report_excel_layout_apply_column_widths(ReportExcelBuilder $b, array $widths): void
{
    foreach ($widths as $col => $w) {
        $b->sheet->getColumnDimension($col)->setWidth((float) $w);
    }
}

function report_excel_layout_configure_print(Worksheet $sheet, int $headerLastRow): void
{
    $ps = $sheet->getPageSetup();
    $ps->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $ps->setPaperSize(PageSetup::PAPERSIZE_A4);
    $ps->setFitToPage(false);

    $margins = $sheet->getPageMargins();
    $margins->setLeft(0.55);
    $margins->setRight(0.55);
    $margins->setTop(0.45);
    $margins->setBottom(0.5);

    if ($headerLastRow >= 1) {
        $ps->setRowsToRepeatAtTopByStartAndEnd(1, $headerLastRow);
    }
}

function report_excel_layout_freeze_below_header(Worksheet $sheet, int $headerLastRow): void
{
    $sheet->freezePane('A' . ($headerLastRow + 1));
}

<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Context de generació Excel: workbook, fulla activa i punter de fila.
 * Reutilitzable per a qualsevol informe.
 */
final class ReportExcelBuilder
{
    public Spreadsheet $spreadsheet;

    public Worksheet $sheet;

    /** Fila actual (1-based) */
    public int $row = 1;

    /** Última columna de dades (lletra), ex. "F" */
    public string $lastCol;

    public int $lastColIndex;

    public function __construct(
        Spreadsheet $spreadsheet,
        string $sheetTitle,
        string $lastCol = 'F'
    ) {
        $this->spreadsheet = $spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle(mb_substr($sheetTitle, 0, 31));
        $this->lastCol = $lastCol;
        $this->lastColIndex = Coordinate::columnIndexFromString($lastCol);
    }

    public function line(): int
    {
        return $this->row;
    }

    public function setRow(int $row): void
    {
        $this->row = max(1, $row);
    }

    public function skip(int $n = 1): int
    {
        $this->row += $n;

        return $this->row;
    }

    public function cell(string $colLetter): string
    {
        return $colLetter . $this->row;
    }

    public function mergeFullWidth(): void
    {
        $this->sheet->mergeCells('A' . $this->row . ':' . $this->lastCol . $this->row);
    }

    public function mergeFromTo(string $colFrom, string $colTo): void
    {
        $this->sheet->mergeCells($colFrom . $this->row . ':' . $colTo . $this->row);
    }

    /** @param mixed $value */
    public function setValue(string $colLetter, $value): void
    {
        $this->sheet->setCellValue($colLetter . $this->row, $value);
    }

    public function setRowHeight(?float $height): void
    {
        if ($height !== null) {
            $this->sheet->getRowDimension($this->row)->setRowHeight($height);
        }
    }
}

/**
 * Envia el workbook per descàrrega (php://output). Sense desar al disc del projecte.
 */
function report_excel_send_download(Spreadsheet $spreadsheet, string $filename): void
{
    $writer = new Xlsx($spreadsheet);
    $encoded = rawurlencode($filename);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"; filename*=UTF-8\'\'' . $encoded);
    header('Cache-Control: max-age=0, must-revalidate');
    header('Pragma: public');

    $writer->save('php://output');
}

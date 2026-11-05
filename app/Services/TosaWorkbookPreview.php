<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;
use ZipArchive;

final class TosaWorkbookPreview
{
    private const ROWS_PER_PAGE = 100;
    private const COLUMNS_PER_PAGE = 26;
    private const MAX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;

    /** Return one bounded worksheet window without calculating formulas. */
    public function read(string $path, int $sheetIndex, int $rowPage, int $columnPage): array
    {
        $this->assertSafeWorkbook($path);
        $reader = new Xlsx();
        $reader->setReadEmptyCells(false);
        $reader->setIncludeCharts(false);
        try {
            $sheets = array_values(array_filter($reader->listWorksheetInfo($path),
                fn (array $sheet): bool => ($sheet['sheetState'] ?? Worksheet::SHEETSTATE_VISIBLE) === Worksheet::SHEETSTATE_VISIBLE));
            if (! isset($sheets[$sheetIndex])) {
                throw new InvalidArgumentException('This worksheet is unavailable.');
            }
            $info = $sheets[$sheetIndex];
            $totalRows = max(1, (int) $info['totalRows']);
            $totalColumns = max(1, (int) $info['totalColumns']);
            $rowPages = (int) ceil($totalRows / self::ROWS_PER_PAGE);
            $columnPages = (int) ceil($totalColumns / self::COLUMNS_PER_PAGE);
            $rowPage = min($rowPage, $rowPages - 1);
            $columnPage = min($columnPage, $columnPages - 1);
            $firstRow = $rowPage * self::ROWS_PER_PAGE + 1;
            $lastRow = min($totalRows, $firstRow + self::ROWS_PER_PAGE - 1);
            $firstColumn = $columnPage * self::COLUMNS_PER_PAGE + 1;
            $lastColumn = min($totalColumns, $firstColumn + self::COLUMNS_PER_PAGE - 1);
            $reader->setLoadSheetsOnly([$info['worksheetName']]);
            $reader->setReadFilter(new class($firstRow, $lastRow, $firstColumn, $lastColumn) implements IReadFilter {
                public function __construct(private int $firstRow, private int $lastRow, private int $firstColumn, private int $lastColumn) {}
                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    $column = Coordinate::columnIndexFromString($columnAddress);
                    return $row >= $this->firstRow && $row <= $this->lastRow
                        && $column >= $this->firstColumn && $column <= $this->lastColumn;
                }
            });
            $spreadsheet = $reader->load($path);
            try {
                $sheet = $spreadsheet->getSheet(0);
                $rows = [];
                for ($row = $firstRow; $row <= $lastRow; $row++) {
                    $cells = [];
                    for ($column = $firstColumn; $column <= $lastColumn; $column++) {
                        $coordinate = Coordinate::stringFromColumnIndex($column).$row;
                        $cell = $sheet->cellExists($coordinate) ? $sheet->getCell($coordinate) : null;
                        $value = $cell ? ($cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue()) : null;
                        if ($value instanceof RichText) $value = $value->getPlainText();
                        if (is_bool($value)) $value = $value ? 'TRUE' : 'FALSE';
                        if ($cell && (is_int($value) || is_float($value))) {
                            $value = NumberFormat::toFormattedString($value, $cell->getStyle()->getNumberFormat()->getFormatCode() ?? NumberFormat::FORMAT_GENERAL);
                        }
                        $cells[] = ['text' => (string) ($value ?? '')];
                    }
                    $rows[] = $cells;
                }
                foreach ($sheet->getMergeCells() as $range) {
                    [[$left, $top], [$right, $bottom]] = Coordinate::rangeBoundaries($range);
                    $startRow = max($top, $firstRow);
                    $endRow = min($bottom, $lastRow);
                    $startColumn = max($left, $firstColumn);
                    $endColumn = min($right, $lastColumn);
                    if ($startRow > $endRow || $startColumn > $endColumn) continue;
                    for ($row = $startRow; $row <= $endRow; $row++) {
                        for ($column = $startColumn; $column <= $endColumn; $column++) {
                            if ($row !== $startRow || $column !== $startColumn) $rows[$row - $firstRow][$column - $firstColumn] = ['skip' => true];
                        }
                    }
                    $anchor = &$rows[$startRow - $firstRow][$startColumn - $firstColumn];
                    if ($top < $firstRow || $left < $firstColumn) $anchor['text'] = 'Merged from '.Coordinate::stringFromColumnIndex($left).$top.' (see previous page)';
                    $anchor['rowspan'] = $endRow - $startRow + 1;
                    $anchor['colspan'] = $endColumn - $startColumn + 1;
                    unset($anchor);
                }
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
            return [
                'sheets' => array_map(fn (array $sheet): string => $sheet['worksheetName'], $sheets),
                'sheet' => $sheetIndex, 'row_page' => $rowPage, 'column_page' => $columnPage,
                'row_pages' => $rowPages, 'column_pages' => $columnPages,
                'first_row' => $firstRow, 'last_row' => $lastRow, 'total_rows' => $totalRows,
                'first_column' => $firstColumn, 'last_column' => $lastColumn, 'total_columns' => $totalColumns,
                'rows' => $rows,
            ];
        } catch (InvalidArgumentException $error) {
            throw $error;
        } catch (Throwable) {
            throw new InvalidArgumentException('This workbook could not be read. Download the original to view it.');
        }
    }

    private function assertSafeWorkbook(string $path): void
    {
        $invalid = new InvalidArgumentException('This workbook could not be read. Download the original to view it.');
        if (! is_file($path) || ! is_readable($path) || filesize($path) <= 0 || filesize($path) > 20 * 1024 * 1024) throw $invalid;
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) throw $invalid;
        try {
            if ($zip->numFiles > 10000 || $zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false) throw $invalid;
            $bytes = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (! is_array($stat)) throw $invalid;
                $bytes += (int) $stat['size'];
                if ($bytes > self::MAX_UNCOMPRESSED_BYTES) throw $invalid;
            }
        } finally {
            $zip->close();
        }
    }
}

<?php

namespace App\Services;

use Carbon\Carbon;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Reads, previews, and exports the OSO "Financial Report_New Format" workbook.
 *
 * Uploaded workbooks are untrusted: formulas are never evaluated, only their
 * cached results are displayed, and every cash total is recalculated from the
 * Form 4 / Form 5 rows (or the recorded Form 3 statement lines).
 */
class FinancialWorkbookService
{
    public const PREVIEW_ROWS = 80;

    public const PREVIEW_COLUMNS = 20;

    public const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const MAX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;

    private const CASH_HEADER_ROW = 9;

    private const STATEMENT_ROWS = [
        'inflow_header' => 11,
        'inflow_total' => 17,
        'outflow_header' => 19,
        'outflow_total' => 42,
        'net' => 44,
        'beginning' => 46,
        'ending' => 48,
    ];

    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const NS_RELATIONSHIPS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const NS_PACKAGE_RELATIONSHIPS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const NS_CONTENT_TYPES = 'http://schemas.openxmlformats.org/package/2006/content-types';

    public function templatePath(): string
    {
        return storage_path('Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Financial Report_New Format.xlsx');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException when the file is not a completed Financial Report workbook
     */
    public function summarize(string $path): array
    {
        $spreadsheet = $this->load($path);

        try {
            return $this->parse($spreadsheet)['summary'];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param  array<string, mixed>|null  $storedSummary
     * @return array{sheets: list<array<string, mixed>>, summary: array<string, mixed>}
     *
     * @throws InvalidArgumentException
     */
    public function preview(string $path, ?array $storedSummary = null): array
    {
        $spreadsheet = $this->load($path);

        try {
            $parsed = $this->parse($spreadsheet);

            return [
                'sheets' => $this->sheets($spreadsheet, $parsed['overrides']),
                'summary' => $storedSummary ?? $parsed['summary'],
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Combine activity totals by event name (case- and spacing-insensitive).
     *
     * @param  iterable<iterable<array{name?: string, inflow?: float|int, outflow?: float|int}>>  $activityLists
     * @return list<array{name: string, inflow: float, outflow: float}>
     */
    public function mergeActivities(iterable $activityLists): array
    {
        $merged = [];
        foreach ($activityLists as $activities) {
            foreach ($activities ?? [] as $activity) {
                $name = trim((string) ($activity['name'] ?? '')) ?: 'Unspecified event';
                $key = mb_strtolower((string) preg_replace('/\s+/u', ' ', $name));
                $merged[$key] ??= ['name' => $name, 'inflow' => 0.0, 'outflow' => 0.0];
                $merged[$key]['inflow'] = round($merged[$key]['inflow'] + (float) ($activity['inflow'] ?? 0), 2);
                $merged[$key]['outflow'] = round($merged[$key]['outflow'] + (float) ($activity['outflow'] ?? 0), 2);
            }
        }

        return array_values($merged);
    }

    /**
     * Fill a copy of the supplied template with the given reports' actual
     * cash rows and return the path of the generated temporary workbook.
     *
     * @param  list<array{summary: array<string, mixed>, period_label: string}>  $reports  chronological
     */
    public function export(array $reports, float $beginningBalance, string $organization): string
    {
        if ($reports === []) {
            throw new InvalidArgumentException('There is no financial report data to export.');
        }

        $copy = $this->temporaryPath();
        $output = $this->temporaryPath();
        if (! copy($this->templatePath(), $copy)) {
            throw new RuntimeException('The Financial Report template could not be copied.');
        }

        try {
            $spreadsheet = (new XlsxReader())->load($copy);
            $receipts = [];
            $disbursements = [];
            $inflowLines = [];
            $outflowLines = [];
            foreach ($reports as $report) {
                $summary = $report['summary'];
                array_push($receipts, ...($summary['receipts'] ?? []));
                array_push($disbursements, ...($summary['disbursements'] ?? []));
                if (($summary['inflow_source'] ?? null) === 'statement' && (float) ($summary['cash_inflow'] ?? 0) != 0.0) {
                    $inflowLines[] = ['name' => 'Recorded cash inflows · '.$report['period_label'], 'amount' => (float) $summary['cash_inflow']];
                }
                if (($summary['outflow_source'] ?? null) === 'statement' && (float) ($summary['cash_outflow'] ?? 0) != 0.0) {
                    $outflowLines[] = ['name' => 'Recorded cash outflows · '.$report['period_label'], 'amount' => (float) $summary['cash_outflow']];
                }
            }
            $activities = $this->mergeActivities(array_map(
                fn (array $report): array => $report['summary']['activities'] ?? [],
                $reports
            ));
            foreach ($activities as $activity) {
                if ($activity['inflow'] != 0.0) {
                    $inflowLines[] = ['name' => $activity['name'], 'amount' => $activity['inflow']];
                }
                if ($activity['outflow'] != 0.0) {
                    $outflowLines[] = ['name' => $activity['name'], 'amount' => $activity['outflow']];
                }
            }

            $firstPeriod = (string) ($reports[0]['period_label'] ?? 'the reporting period');
            $lastPeriod = (string) ($reports[array_key_last($reports)]['period_label'] ?? 'the reporting period');
            $periodRange = $firstPeriod === $lastPeriod ? $firstPeriod : $firstPeriod.' to '.$lastPeriod;
            $ending = round($beginningBalance
                + array_sum(array_column($inflowLines, 'amount'))
                - array_sum(array_column($outflowLines, 'amount')), 2);

            $this->fillCashSheet($this->requiredFormSheet($spreadsheet, 4), $receipts);
            $this->fillCashSheet($this->requiredFormSheet($spreadsheet, 5), $disbursements);
            $this->fillStatement($this->requiredFormSheet($spreadsheet, 3), $inflowLines, $outflowLines, $beginningBalance, $firstPeriod, $lastPeriod);
            $activitiesSheet = $this->requiredFormSheet($spreadsheet, 2);
            $excessRow = $this->fillActivities($activitiesSheet, $inflowLines, $outflowLines);
            $this->fillNetWorth($this->requiredFormSheet($spreadsheet, 1), $activitiesSheet->getTitle(), $excessRow, $beginningBalance, $ending, $lastPeriod);
            $this->fillCashNote($spreadsheet, $ending);
            $this->clearSampleSheet($spreadsheet);

            $writer = new XlsxWriter($spreadsheet);
            $writer->setPreCalculateFormulas(true);
            $writer->save($output);
            $spreadsheet->disconnectWorksheets();
            $this->restoreTemplateDrawings($output, [
                ['/^supreme student council$/i', mb_strtoupper($organization)],
                ['/^for the semester ended\b/i', 'For '.$periodRange],
                ['/^[a-z]+\s+\d{1,2}\s*,\s*\d{4}$/i', 'As of the end of '.$lastPeriod],
            ]);
        } catch (Throwable $exception) {
            @unlink($output);
            throw $exception;
        } finally {
            @unlink($copy);
        }

        return $output;
    }

    private function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'orgchain-fr-');
        if ($path === false) {
            throw new RuntimeException('A temporary workbook file could not be created.');
        }

        return $path;
    }

    private function load(string $path): Spreadsheet
    {
        $this->assertGenuineXlsx($path);

        $reader = new XlsxReader();
        $reader->setReadEmptyCells(false);
        $reader->setIncludeCharts(false);

        try {
            return $reader->load($path);
        } catch (Throwable) {
            throw new InvalidArgumentException('The workbook could not be read. Upload the completed Financial Report as a regular .xlsx file.');
        }
    }

    private function assertGenuineXlsx(string $path): void
    {
        $invalid = new InvalidArgumentException('The file is not a genuine Excel .xlsx workbook.');
        if (! is_file($path)) {
            throw $invalid;
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw $invalid;
        }

        try {
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('xl/workbook.xml') === false) {
                throw $invalid;
            }
            $uncompressed = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $uncompressed += (int) ($zip->statIndex($index)['size'] ?? 0);
                if ($uncompressed > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new InvalidArgumentException('The workbook is too large to process. Remove unused sheets or rows and upload it again.');
                }
            }
        } finally {
            $zip->close();
        }

        if (! (new XlsxReader())->canRead($path)) {
            throw $invalid;
        }
    }

    /**
     * @return array{summary: array<string, mixed>, overrides: array<string, array<string, float>>}
     */
    private function parse(Spreadsheet $spreadsheet): array
    {
        $statementSheet = $this->formSheet($spreadsheet, 3);
        $receiptSheet = $this->formSheet($spreadsheet, 4);
        $disbursementSheet = $this->formSheet($spreadsheet, 5);
        if (! $statementSheet || ! $receiptSheet || ! $disbursementSheet) {
            throw new InvalidArgumentException('This workbook does not follow the Financial Report format. The “Form 3 - SCF”, “Form 4 - Cash Receipt”, and “Form 5 - Cash Disbursement” tabs are required.');
        }

        $statement = $this->statement($statementSheet);
        $receipts = $this->cashRows($receiptSheet);
        $disbursements = $this->cashRows($disbursementSheet);

        if ($receipts['rows'] === [] && $disbursements['rows'] === []
            && $statement['inflow'] == 0.0 && $statement['outflow'] == 0.0 && $statement['beginning'] == 0.0) {
            throw new InvalidArgumentException('The workbook has no recorded cash receipts, disbursements, or statement amounts. Complete the Financial Report before uploading it.');
        }

        $inflow = $receipts['rows'] !== [] ? $receipts['total'] : $statement['inflow'];
        $outflow = $disbursements['rows'] !== [] ? $disbursements['total'] : $statement['outflow'];
        $beginning = $statement['beginning'];

        return [
            'summary' => [
                'beginning_balance' => $beginning,
                'cash_inflow' => $inflow,
                'cash_outflow' => $outflow,
                'ending_balance' => round($beginning + $inflow - $outflow, 2),
                'inflow_source' => $receipts['rows'] !== [] ? 'cash_receipts' : 'statement',
                'outflow_source' => $disbursements['rows'] !== [] ? 'cash_disbursements' : 'statement',
                'activities' => $this->mergeActivities([
                    array_map(fn (array $row): array => ['name' => $row['event'], 'inflow' => $row['amount']], $receipts['rows']),
                    array_map(fn (array $row): array => ['name' => $row['event'], 'outflow' => $row['amount']], $disbursements['rows']),
                ]),
                'receipts' => $receipts['rows'],
                'disbursements' => $disbursements['rows'],
            ],
            'overrides' => [
                $statementSheet->getTitle() => $statement['overrides'],
                $receiptSheet->getTitle() => $receipts['overrides'],
                $disbursementSheet->getTitle() => $disbursements['overrides'],
            ],
        ];
    }

    private function formSheet(Spreadsheet $spreadsheet, int $form): ?Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (preg_match('/^form\s*'.$form.'(?!\d)/i', trim($sheet->getTitle()))) {
                return $sheet;
            }
        }

        return null;
    }

    private function requiredFormSheet(Spreadsheet $spreadsheet, int $form): Worksheet
    {
        return $this->formSheet($spreadsheet, $form)
            ?? throw new RuntimeException('The Financial Report template is missing Form '.$form.'.');
    }

    /**
     * Existing cells of columns 1..$maxColumn grouped by row.
     *
     * @return array<int, array<int, Cell>>
     */
    private function grid(Worksheet $sheet, int $maxColumn): array
    {
        $grid = [];
        foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
            [$column, $row] = Coordinate::indexesFromString($coordinate);
            if ($column <= $maxColumn) {
                $grid[$row][$column] = $sheet->getCell($coordinate);
            }
        }
        ksort($grid);

        return $grid;
    }

    private function rawValue(?Cell $cell): mixed
    {
        if (! $cell) {
            return null;
        }

        $value = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        return is_string($value) ? trim($value) : $value;
    }

    private function numberFormat(Cell $cell): NumberFormat
    {
        return ($cell->getWorksheet()->getParent()?->getCellXfByIndexOrNull($cell->getXfIndex())
            ?? $cell->getStyle())->getNumberFormat();
    }

    private function text(?Cell $cell): string
    {
        $value = $this->rawValue($cell);

        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'TRUE' : 'FALSE',
            is_int($value), is_float($value) => trim(NumberFormat::toFormattedString($value, $this->numberFormat($cell)->getFormatCode() ?? NumberFormat::FORMAT_GENERAL)),
            default => (string) $value,
        };
    }

    private function number(?Cell $cell): ?float
    {
        $value = $this->rawValue($cell);
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }
        if (! is_string($value) || $value === '') {
            return null;
        }

        $normalized = str_replace([',', ' ', '₱', 'PHP', 'Php'], '', $value);
        if (preg_match('/^\((.+)\)$/', $normalized, $match)) {
            $normalized = '-'.$match[1];
        }

        return is_numeric($normalized) && is_finite((float) $normalized) ? (float) $normalized : null;
    }

    private function date(?Cell $cell): ?string
    {
        $value = $this->rawValue($cell);
        if ($value === null || $value === '') {
            return null;
        }

        if ((is_int($value) || is_float($value)) && ExcelDate::isDateTimeFormat($this->numberFormat($cell))) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (Throwable) {
                return $this->text($cell);
            }
        }

        $text = $this->text($cell);
        if (preg_match('/\b\d{4}\b/', $text)) {
            try {
                return Carbon::parse($text)->format('Y-m-d');
            } catch (Throwable) {
                return $text;
            }
        }

        return $text;
    }

    /**
     * @param  array<int, Cell>  $cells
     */
    private function isCashHeading(array $cells): bool
    {
        return strcasecmp($this->text($cells[2] ?? null), 'date') === 0
            && str_contains(strtolower($this->text($cells[10] ?? null)), 'amount');
    }

    /**
     * @param  array<int, Cell>  $cells
     */
    private function isTotalRow(array $cells): bool
    {
        $amount = $cells[10] ?? null;
        if ($amount && $amount->isFormula() && preg_match('/^=\s*SUM\s*\(/i', (string) $amount->getValue())) {
            return true;
        }

        foreach (range(1, 9) as $column) {
            if (preg_match('/^((sub|grand)[\s-]?)?totals?\s*:?$|^(balance|amount)?\s*(brought|carried)\s+forward\s*:?$/i', $this->text($cells[$column] ?? null))) {
                return true;
            }
        }

        return false;
    }

    private function cashHeaderRow(Worksheet $sheet): int
    {
        for ($row = 1; $row <= 40; $row++) {
            $cells = [
                2 => $sheet->cellExists('B'.$row) ? $sheet->getCell('B'.$row) : null,
                10 => $sheet->cellExists('J'.$row) ? $sheet->getCell('J'.$row) : null,
            ];
            if ($this->isCashHeading($cells)) {
                return $row;
            }
        }

        return self::CASH_HEADER_ROW;
    }

    /**
     * Actual Form 4 / Form 5 cash rows. Blank numbered template rows,
     * repeated headings, and total rows are skipped.
     *
     * @return array{rows: list<array<string, mixed>>, total: float, overrides: array<string, float>}
     */
    private function cashRows(Worksheet $sheet): array
    {
        $header = $this->cashHeaderRow($sheet);
        $rows = [];
        $overrides = [];
        $total = 0.0;
        $lastTotalCell = null;

        foreach ($this->grid($sheet, 11) as $row => $cells) {
            if ($row <= $header || $this->isCashHeading($cells)) {
                continue;
            }
            if ($this->isTotalRow($cells)) {
                $lastTotalCell = 'J'.$row;

                continue;
            }

            $quantity = $this->number($cells[8] ?? null);
            $unitCost = $this->number($cells[9] ?? null);
            $amount = $quantity !== null && $unitCost !== null
                ? round($quantity * $unitCost, 2)
                : $this->number($cells[10] ?? null);
            if ($amount === null) {
                continue;
            }

            $entry = [
                'row' => $row,
                'date' => $this->date($cells[2] ?? null),
                'event' => $this->text($cells[3] ?? null),
                'reference' => $this->text($cells[4] ?? null),
                'payee' => $this->text($cells[5] ?? null),
                'account' => $this->text($cells[6] ?? null),
                'description' => $this->text($cells[7] ?? null),
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'amount' => round($amount, 2),
                'ref' => $this->text($cells[11] ?? null),
            ];
            $hasDetails = $entry['date'] !== null || collect($entry)
                ->only(['event', 'reference', 'payee', 'account', 'description', 'ref'])
                ->contains(fn (string $value): bool => $value !== '');
            if ($entry['amount'] == 0.0 && ! $hasDetails) {
                continue;
            }

            $rows[] = $entry;
            $overrides['J'.$row] = $entry['amount'];
            $total += $entry['amount'];
        }

        $total = round($total, 2);
        if ($lastTotalCell !== null) {
            $overrides[$lastTotalCell] = $total;
        }

        return ['rows' => $rows, 'total' => $total, 'overrides' => $overrides];
    }

    /**
     * Locate the Form 3 statement rows by their labels, falling back to the
     * original template positions.
     *
     * @return array<string, int>
     */
    private function statementLayout(Worksheet $sheet): array
    {
        $found = [];
        $balances = [];
        $highest = min($sheet->getHighestRow(), 2000);
        for ($row = 1; $row <= $highest; $row++) {
            $label = '';
            foreach (['B', 'C'] as $column) {
                if ($label === '' && $sheet->cellExists($column.$row)) {
                    $label = strtolower((string) preg_replace('/\s+/', ' ', $this->text($sheet->getCell($column.$row))));
                }
            }
            if ($label === '') {
                continue;
            }

            $key = match (true) {
                (bool) preg_match('/^cash inflows?$/', $label) => 'inflow_header',
                (bool) preg_match('/^total cash inflows?$/', $label) => 'inflow_total',
                (bool) preg_match('/^cash outflows?$/', $label) => 'outflow_header',
                (bool) preg_match('/^total cash outflows?$/', $label) => 'outflow_total',
                (bool) preg_match('/^net cash/', $label) => 'net',
                default => null,
            };
            if ($key !== null) {
                $found[$key] ??= $row;
            } elseif (str_starts_with($label, 'cash balance')) {
                $balances[] = $row;
            }
        }
        if ($balances !== []) {
            $found['beginning'] = $balances[0];
            $found['ending'] = count($balances) > 1 ? $balances[array_key_last($balances)] : null;
        }

        $layout = array_merge(self::STATEMENT_ROWS, array_filter($found));
        if (array_key_exists('ending', $found) && $found['ending'] === null) {
            $layout['ending'] = 0;
        }
        $ordered = $layout['inflow_header'] < $layout['inflow_total']
            && $layout['inflow_total'] < $layout['outflow_header']
            && $layout['outflow_header'] < $layout['outflow_total']
            && $layout['outflow_total'] < $layout['beginning'];

        return $ordered ? $layout : self::STATEMENT_ROWS;
    }

    /**
     * @return array{inflow: float, outflow: float, beginning: float, overrides: array<string, float>}
     */
    private function statement(Worksheet $sheet): array
    {
        $layout = $this->statementLayout($sheet);
        $sum = function (int $from, int $to) use ($sheet): float {
            $total = 0.0;
            for ($row = $from; $row <= $to; $row++) {
                $total += $sheet->cellExists('F'.$row) ? ($this->number($sheet->getCell('F'.$row)) ?? 0.0) : 0.0;
            }

            return round($total, 2);
        };

        $inflow = $sum($layout['inflow_header'] + 1, $layout['inflow_total'] - 1);
        $outflow = $sum($layout['outflow_header'] + 1, $layout['outflow_total'] - 1);
        $beginning = $sheet->cellExists('F'.$layout['beginning'])
            ? round($this->number($sheet->getCell('F'.$layout['beginning'])) ?? 0.0, 2)
            : 0.0;

        $overrides = [
            'F'.$layout['inflow_total'] => $inflow,
            'F'.$layout['outflow_total'] => $outflow,
            'F'.$layout['net'] => round($inflow - $outflow, 2),
        ];
        if ($layout['ending'] > 0) {
            $overrides['F'.$layout['ending']] = round($beginning + $inflow - $outflow, 2);
        }

        return ['inflow' => $inflow, 'outflow' => $outflow, 'beginning' => $beginning, 'overrides' => $overrides];
    }

    /**
     * @param  array<string, array<string, float>>  $overrides
     * @return list<array{name: string, rows: list<list<array<string, mixed>>>, total_rows: int, total_columns: int}>
     */
    private function sheets(Spreadsheet $spreadsheet, array $overrides): array
    {
        $sheets = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $totalRows = $sheet->getHighestDataRow();
            $totalColumns = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $rowLimit = min($totalRows, self::PREVIEW_ROWS);
            $columnLimit = min($totalColumns, self::PREVIEW_COLUMNS);
            $sheetOverrides = $overrides[$sheet->getTitle()] ?? [];

            $rows = [];
            for ($row = 1; $row <= $rowLimit; $row++) {
                $cells = [];
                for ($column = 1; $column <= $columnLimit; $column++) {
                    $coordinate = Coordinate::stringFromColumnIndex($column).$row;
                    $cell = $sheet->cellExists($coordinate) ? $sheet->getCell($coordinate) : null;
                    $cells[] = ['text' => array_key_exists($coordinate, $sheetOverrides)
                        ? $this->formatAmount($cell, $sheetOverrides[$coordinate])
                        : $this->text($cell)];
                }
                $rows[] = $cells;
            }

            foreach ($sheet->getMergeCells() as $range) {
                [[$firstColumn, $firstRow], [$lastColumn, $lastRow]] = Coordinate::rangeBoundaries($range);
                if ($firstRow > $rowLimit || $firstColumn > $columnLimit) {
                    continue;
                }
                $lastRow = min($lastRow, $rowLimit);
                $lastColumn = min($lastColumn, $columnLimit);
                for ($row = $firstRow; $row <= $lastRow; $row++) {
                    for ($column = $firstColumn; $column <= $lastColumn; $column++) {
                        if ($row !== $firstRow || $column !== $firstColumn) {
                            $rows[$row - 1][$column - 1] = ['text' => '', 'skip' => true];
                        }
                    }
                }
                if ($lastRow > $firstRow) {
                    $rows[$firstRow - 1][$firstColumn - 1]['rowspan'] = $lastRow - $firstRow + 1;
                }
                if ($lastColumn > $firstColumn) {
                    $rows[$firstRow - 1][$firstColumn - 1]['colspan'] = $lastColumn - $firstColumn + 1;
                }
            }

            $sheets[] = [
                'name' => $sheet->getTitle(),
                'rows' => $rows,
                'total_rows' => $totalRows,
                'total_columns' => $totalColumns,
            ];
        }

        return $sheets;
    }

    private function formatAmount(?Cell $cell, float $amount): string
    {
        $format = $cell ? $this->numberFormat($cell)->getFormatCode() : null;
        if (! $format || $format === NumberFormat::FORMAT_GENERAL) {
            $format = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        }

        return trim(NumberFormat::toFormattedString($amount, $format));
    }

    private function totalRowAfter(Worksheet $sheet, int $header): int
    {
        $highest = $sheet->getHighestRow();
        for ($row = $header + 1; $row <= $highest; $row++) {
            if ($sheet->cellExists('J'.$row)) {
                $cell = $sheet->getCell('J'.$row);
                if ($cell->isFormula() && preg_match('/^=\s*SUM\s*\(/i', (string) $cell->getValue())) {
                    return $row;
                }
            }
        }

        throw new RuntimeException('The Financial Report template total row was not found on '.$sheet->getTitle().'.');
    }

    /**
     * Rows are inserted above the last template slot so the total row's SUM
     * range grows with them.
     */
    private function growSlots(Worksheet $sheet, int $firstSlot, int $total, int $needed): int
    {
        $capacity = $total - $firstSlot;
        if ($needed <= $capacity) {
            return $total;
        }

        $sheet->insertNewRowBefore($total - 1, $needed - $capacity);

        return $total + $needed - $capacity;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function fillCashSheet(Worksheet $sheet, array $rows): void
    {
        $header = $this->cashHeaderRow($sheet);
        $firstSlot = $header + 1;
        $originalTotal = $this->totalRowAfter($sheet, $header);
        $total = $this->growSlots($sheet, $firstSlot, $originalTotal, count($rows));

        if ($total !== $originalTotal) {
            $numberedByFormula = $sheet->getCell('A'.($firstSlot + 1))->isFormula();
            for ($row = $originalTotal - 1; $row < $total; $row++) {
                $sheet->setCellValue('A'.$row, $numberedByFormula ? '=A'.($row - 1).'+1' : $row - $header);
            }
        }

        foreach (array_values($rows) as $index => $entry) {
            $row = $firstSlot + $index;
            $date = $entry['date'] ?? null;
            $isoDate = is_string($date) ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
            if ($isoDate) {
                $sheet->setCellValue('B'.$row, ExcelDate::PHPToExcel($isoDate));
            } else {
                $this->setText($sheet, 'B'.$row, $date);
            }
            foreach (['C' => 'event', 'D' => 'reference', 'E' => 'payee', 'F' => 'account', 'G' => 'description', 'K' => 'ref'] as $column => $key) {
                $this->setText($sheet, $column.$row, $entry[$key] ?? null);
            }
            foreach (['H' => 'quantity', 'I' => 'unit_cost'] as $column => $key) {
                $this->setNumber($sheet, $column.$row, $entry[$key] ?? null);
            }
            $this->setNumber($sheet, 'J'.$row, $entry['amount'] ?? 0);
        }
    }

    /**
     * Workbook-derived and user-supplied text is always stored as a literal
     * string, so a value such as "=HYPERLINK(...)" never becomes a formula.
     */
    private function setText(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $sheet->getCell($coordinate)->setValueExplicit(null, DataType::TYPE_NULL);

            return;
        }

        $sheet->getCell($coordinate)->setValueExplicit(
            is_scalar($value) ? (string) $value : '',
            DataType::TYPE_STRING
        );
    }

    private function setNumber(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        if ((is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) && is_finite((float) $value)) {
            $sheet->getCell($coordinate)->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

            return;
        }

        $this->setText($sheet, $coordinate, $value);
    }

    /**
     * Replace the template's sample statement lines with actual lines.
     *
     * @param  list<array{name: string, amount: float}>  $lines
     */
    private function fillStatementSection(Worksheet $sheet, int $header, int $total, array $lines): void
    {
        $firstSlot = $header + 1;
        $total = $this->growSlots($sheet, $firstSlot, $total, count($lines));
        for ($row = $firstSlot; $row < $total; $row++) {
            $line = $lines[$row - $firstSlot] ?? null;
            $this->setText($sheet, 'C'.$row, $line['name'] ?? null);
            $this->setNumber($sheet, 'F'.$row, $line['amount'] ?? null);
        }
    }

    /**
     * @param  list<array{name: string, amount: float}>  $inflowLines
     * @param  list<array{name: string, amount: float}>  $outflowLines
     */
    private function fillStatement(
        Worksheet $sheet,
        array $inflowLines,
        array $outflowLines,
        float $beginningBalance,
        string $firstPeriod,
        string $lastPeriod,
    ): void {
        $layout = $this->statementLayout($sheet);
        $this->fillStatementSection($sheet, $layout['outflow_header'], $layout['outflow_total'], $outflowLines);
        $this->fillStatementSection($sheet, $layout['inflow_header'], $layout['inflow_total'], $inflowLines);

        $layout = $this->statementLayout($sheet);
        $sheet->setCellValue('F'.$layout['beginning'], round($beginningBalance, 2));
        $this->setText($sheet, 'B'.$layout['beginning'], 'Cash Balance, beginning of '.$firstPeriod);
        if ($layout['ending'] > 0) {
            $this->setText($sheet, 'B'.$layout['ending'], 'CASH BALANCE, END OF '.mb_strtoupper($lastPeriod));
        }
    }

    /**
     * Row of the first column-B/C label matching $pattern, or $fallback.
     */
    private function labelRow(Worksheet $sheet, string $pattern, int $fallback, int $from = 1): int
    {
        $highest = min($sheet->getHighestDataRow(), 2000);
        for ($row = $from; $row <= $highest; $row++) {
            foreach (['B', 'C'] as $column) {
                if ($sheet->cellExists($column.$row)
                    && preg_match($pattern, strtolower((string) preg_replace('/\s+/', ' ', $this->text($sheet->getCell($column.$row)))))) {
                    return $row;
                }
            }
        }

        return $fallback;
    }

    /**
     * Form 2 sources/expenses carry the same actual event aggregates as Form 3
     * instead of the template's sample fund categories.
     *
     * @param  list<array{name: string, amount: float}>  $inflowLines
     * @param  list<array{name: string, amount: float}>  $outflowLines
     */
    private function fillActivities(Worksheet $sheet, array $inflowLines, array $outflowLines): int
    {
        $sources = $this->labelRow($sheet, '/^sources of funds$/', 11);
        $sourcesTotal = $this->labelRow($sheet, '/^total fund generated$/', 16, $sources + 1);
        $expenses = $this->labelRow($sheet, '/^expenses$/', 18, $sourcesTotal + 1);
        $expensesTotal = $this->labelRow($sheet, '/^total expenses$/', 22, $expenses + 1);
        if (! ($sources < $sourcesTotal && $sourcesTotal < $expenses && $expenses < $expensesTotal)) {
            [$sources, $sourcesTotal, $expenses, $expensesTotal] = [11, 16, 18, 22];
        }

        $this->fillStatementSection($sheet, $expenses, $expensesTotal, $outflowLines);
        $this->fillStatementSection($sheet, $sources, $sourcesTotal, $inflowLines);

        return $this->labelRow($sheet, '/^excess of fund/', 24);
    }

    /**
     * Form 1 cash and net worth follow the saved ledger opening balance and
     * actual cash movement; the cross-sheet link to Form 2 is re-pointed
     * because row insertion only updates references on the same sheet.
     */
    private function fillNetWorth(Worksheet $sheet, string $activitiesTitle, int $excessRow, float $beginning, float $ending, string $lastPeriod): void
    {
        $cash = $this->labelRow($sheet, '/^cash$/', 12);
        $prior = $this->labelRow($sheet, '/^net worth, prior/', 25);
        $closing = $this->labelRow($sheet, '/^net worth, (?!prior)/', 27, $prior + 1);
        $unsupported = [
            $this->labelRow($sheet, '/^property and equipment$/', 13),
            $this->labelRow($sheet, '/^other non-cash assets$/', 14),
            $this->labelRow($sheet, '/^accrued expenses$/', 19),
            $this->labelRow($sheet, '/^short-term borrowings$/', 20),
            $this->labelRow($sheet, '/^other liabilities$/', 21),
        ];

        foreach ($unsupported as $row) {
            if (! $sheet->getCell('F'.$row)->isFormula()) {
                $sheet->setCellValue('F'.$row, null);
            }
        }
        $sheet->setCellValue('F'.$cash, round($ending, 2));
        $sheet->setCellValue('F'.$prior, round($beginning, 2));
        $this->setText($sheet, 'C'.$closing, 'Net Worth, end of '.$lastPeriod);

        foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
            $cell = $sheet->getCell($coordinate);
            if ($cell->isFormula() && preg_match("/^=\s*'?".preg_quote($activitiesTitle, '/')."'?!\\\$?F\\\$?\d+$/i", (string) $cell->getValue())) {
                $cell->setValue("='".str_replace("'", "''", $activitiesTitle)."'!F".$excessRow);
            }
        }
    }

    /**
     * Notes to Financial Statements: only the total cash is known from the
     * cash flow compilation, so the on-hand / bank split stays blank.
     */
    private function fillCashNote(Spreadsheet $spreadsheet, float $ending): void
    {
        $sheet = $spreadsheet->getSheetByName('Re');
        if (! $sheet) {
            return;
        }

        $total = $this->labelRow($sheet, '/^total cash$/', 16);
        foreach ([
            $this->labelRow($sheet, '/^cash on hand$/', 13),
            $this->labelRow($sheet, '/^cash on bank$/', 14),
            $this->labelRow($sheet, '/^cash on bank \(time deposit\)$/', 15),
        ] as $row) {
            $sheet->setCellValue('D'.$row, null);
        }
        $this->setText($sheet, 'C'.$total, 'Total Cash (cash flow compilation; on-hand / bank split not recorded)');
        $sheet->setCellValue('D'.$total, round($ending, 2));
    }

    /**
     * The hidden Sheet1 holds sample collections (water guns, glow sticks)
     * that are not organization data; its values are cleared, the sheet and
     * its styles stay.
     */
    private function clearSampleSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('Sheet1');
        if (! $sheet) {
            return;
        }
        foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
            if (Coordinate::indexesFromString($coordinate)[1] > 2) {
                $sheet->getCell($coordinate)->setValue(null);
            }
        }
    }

    /**
     * Replace the template letterhead's sample organization and dates.
     *
     * @param  list<array{0: string, 1: string}>  $replacements  [regex on paragraph text, new text]
     */
    private function rewriteDrawingText(string $xml, array $replacements): string
    {
        $document = $this->xml($xml);
        if (! $document) {
            return $xml;
        }
        $drawingMl = 'http://schemas.openxmlformats.org/drawingml/2006/main';
        foreach ($document->getElementsByTagNameNS($drawingMl, 'p') as $paragraph) {
            /** @var DOMElement $paragraph */
            $texts = iterator_to_array($paragraph->getElementsByTagNameNS($drawingMl, 't'));
            if ($texts === []) {
                continue;
            }
            $content = trim(implode('', array_map(fn (DOMElement $text): string => $text->textContent, $texts)));
            foreach ($replacements as [$pattern, $replacement]) {
                if (preg_match($pattern, $content)) {
                    foreach ($texts as $index => $text) {
                        $text->textContent = $index === 0 ? $replacement : '';
                    }

                    break;
                }
            }
        }

        return (string) $document->saveXML();
    }

    /**
     * PhpSpreadsheet drops drawing text boxes (the university letterhead) on
     * save. The header drawings sit above every row this service inserts, so
     * the template's original drawing parts are copied back, with the sample
     * organization name and dates replaced.
     *
     * @param  list<array{0: string, 1: string}>  $textReplacements
     */
    private function restoreTemplateDrawings(string $outputPath, array $textReplacements = []): void
    {
        $templateZip = new ZipArchive();
        if ($templateZip->open($this->templatePath(), ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('The Financial Report template could not be reopened.');
        }
        $outputZip = new ZipArchive();
        if ($outputZip->open($outputPath, ZipArchive::RDONLY) !== true) {
            $templateZip->close();
            throw new RuntimeException('The generated workbook could not be reopened.');
        }

        $entries = [];
        for ($index = 0; $index < $outputZip->numFiles; $index++) {
            $name = (string) $outputZip->getNameIndex($index);
            $entries[$name] = (string) $outputZip->getFromIndex($index);
        }
        $outputZip->close();

        $template = fn (string $name): ?string => ($content = $templateZip->getFromName($name)) === false ? null : $content;
        $output = function (string $name) use (&$entries): ?string {
            return $entries[$name] ?? null;
        };

        try {
            $templateSheets = $this->worksheetParts($template);
            $outputSheets = $this->worksheetParts($output);
            $replacedTargets = [];
            $mediaExtensions = [];

            foreach ($templateSheets as $title => $templateSheet) {
                $outputSheet = $outputSheets[$title] ?? null;
                $templateDrawing = $this->relatedPart($template, $templateSheet, 'drawing');
                $outputDrawing = $outputSheet ? $this->relatedPart($output, $outputSheet, 'drawing') : null;
                $templateXml = $templateDrawing ? $template($templateDrawing) : null;
                if (! $outputDrawing || $templateXml === null || dirname($templateDrawing) !== dirname($outputDrawing)) {
                    continue;
                }

                array_push($replacedTargets, ...array_values($this->relationshipTargets($output, $outputDrawing)));
                $entries[$outputDrawing] = $textReplacements === [] ? $templateXml : $this->rewriteDrawingText($templateXml, $textReplacements);
                $outputRels = $this->relsPath($outputDrawing);
                $templateRels = $template($this->relsPath($templateDrawing));
                if ($templateRels === null) {
                    unset($entries[$outputRels]);

                    continue;
                }
                $entries[$outputRels] = $templateRels;
                foreach ($this->relationshipTargets($template, $templateDrawing) as $target) {
                    $content = $template($target);
                    if ($content !== null) {
                        $entries[$target] = $content;
                        $mediaExtensions[] = strtolower(pathinfo($target, PATHINFO_EXTENSION));
                    }
                }
            }

            $referenced = [];
            foreach (array_keys($entries) as $name) {
                if (str_ends_with($name, '.rels')) {
                    $source = $this->relsSource($name);
                    array_push($referenced, ...array_values($this->relationshipTargets($output, $source)));
                }
            }
            foreach (array_diff(array_unique($replacedTargets), $referenced) as $orphan) {
                unset($entries[$orphan]);
            }

            $entries['[Content_Types].xml'] = $this->withDefaultContentTypes(
                (string) ($entries['[Content_Types].xml'] ?? ''),
                array_unique($mediaExtensions)
            );
        } finally {
            $templateZip->close();
        }

        $rewritten = new ZipArchive();
        if ($rewritten->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The generated workbook could not be written.');
        }
        foreach ($entries as $name => $content) {
            $rewritten->addFromString($name, $content);
        }
        $rewritten->close();
    }

    /**
     * @param  callable(string): ?string  $read
     * @return array<string, string> sheet title => worksheet part path
     */
    private function worksheetParts(callable $read): array
    {
        $workbook = $this->xml($read('xl/workbook.xml'));
        $targets = $this->relationshipTargets($read, 'xl/workbook.xml');
        $parts = [];
        if (! $workbook) {
            return $parts;
        }
        foreach ($workbook->getElementsByTagNameNS(self::NS_MAIN, 'sheet') as $sheet) {
            /** @var DOMElement $sheet */
            $id = $sheet->getAttributeNS(self::NS_RELATIONSHIPS, 'id');
            if (isset($targets[$id])) {
                $parts[$sheet->getAttribute('name')] = $targets[$id];
            }
        }

        return $parts;
    }

    /**
     * @param  callable(string): ?string  $read
     */
    private function relatedPart(callable $read, string $part, string $type): ?string
    {
        foreach ($this->relationshipTargets($read, $part, $type) as $target) {
            return $target;
        }

        return null;
    }

    /**
     * Internal relationship targets of a part, resolved to package paths.
     *
     * @param  callable(string): ?string  $read
     * @return array<string, string> relationship id => part path
     */
    private function relationshipTargets(callable $read, string $part, ?string $type = null): array
    {
        $rels = $this->xml($read($this->relsPath($part)));
        $targets = [];
        if (! $rels) {
            return $targets;
        }
        foreach ($rels->getElementsByTagNameNS(self::NS_PACKAGE_RELATIONSHIPS, 'Relationship') as $relationship) {
            /** @var DOMElement $relationship */
            if (strcasecmp($relationship->getAttribute('TargetMode'), 'External') === 0) {
                continue;
            }
            if ($type !== null && ! str_ends_with($relationship->getAttribute('Type'), '/'.$type)) {
                continue;
            }
            $targets[$relationship->getAttribute('Id')] = $this->resolvePart($part, $relationship->getAttribute('Target'));
        }

        return $targets;
    }

    private function relsPath(string $part): string
    {
        $directory = dirname($part);

        return ($directory === '.' ? '' : $directory.'/').'_rels/'.basename($part).'.rels';
    }

    private function relsSource(string $relsPath): string
    {
        $directory = dirname(dirname($relsPath));

        return ($directory === '.' ? '' : $directory.'/').basename($relsPath, '.rels');
    }

    private function resolvePart(string $sourcePart, string $target): string
    {
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        $directory = dirname($sourcePart);
        $segments = $directory === '.' ? [] : explode('/', $directory);
        foreach (explode('/', $target) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    private function xml(?string $content): ?DOMDocument
    {
        if ($content === null || $content === '') {
            return null;
        }

        $document = new DOMDocument();

        return $document->loadXML($content, LIBXML_NONET) ? $document : null;
    }

    /**
     * @param  list<string>  $extensions
     */
    private function withDefaultContentTypes(string $contentTypes, array $extensions): string
    {
        $document = $this->xml($contentTypes);
        if (! $document || $extensions === []) {
            return $contentTypes;
        }

        $existing = [];
        foreach ($document->getElementsByTagNameNS(self::NS_CONTENT_TYPES, 'Default') as $default) {
            /** @var DOMElement $default */
            $existing[] = strtolower($default->getAttribute('Extension'));
        }

        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'emf' => 'image/x-emf',
            'wmf' => 'image/x-wmf',
        ];
        foreach (array_diff($extensions, $existing) as $extension) {
            $default = $document->createElementNS(self::NS_CONTENT_TYPES, 'Default');
            $default->setAttribute('Extension', $extension);
            $default->setAttribute('ContentType', $mimeTypes[$extension] ?? 'application/octet-stream');
            $document->documentElement->insertBefore($default, $document->documentElement->firstChild);
        }

        return (string) $document->saveXML();
    }
}

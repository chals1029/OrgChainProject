<?php

namespace App\Services;

use App\Models\TosaApplicant;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Throwable;
use ZipArchive;

final class TosaWorkbookService
{
    public const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const PREVIEW_ROWS = 700;
    public const PREVIEW_COLUMNS = 32;
    private const MAX_SHEETS = 12;
    private const MAX_PACKAGE_BYTES = 32 * 1024 * 1024;
    private const MAX_PART_BYTES = 8 * 1024 * 1024;

    /** Read actual reference values and existing formula caches, never calculate formulas. */
    public function referencePreview(string $path): array
    {
        $this->assertBoundedPackage($path);
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setIncludeCharts(false);
        try {
            $names = $reader->listWorksheetNames($path);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('The reference workbook could not be read as a regular XLSX file.', 0, $exception);
        }
        if (count($names) > self::MAX_SHEETS || $names === []) {
            throw new InvalidArgumentException('The reference workbook has too many worksheets or no worksheets.');
        }
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= TosaWorkbookService::PREVIEW_ROWS
                    && Coordinate::columnIndexFromString($columnAddress) <= TosaWorkbookService::PREVIEW_COLUMNS;
            }
        });
        try {
            $workbook = $reader->load($path);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('The reference workbook could not be read as a regular XLSX file.', 0, $exception);
        }
        try {
            return ['sheets' => $this->sheets($workbook), 'reference' => true,
                'title' => 'Original TOSA computation reference',
                'notices' => [
                    'This is the current original-format reference, not your evaluated score. Any sample achievements, names, dates, cached results or errors in this workbook never award you points.',
                    'Known errors in the bundled source: SUMMARY F20 points to SC1 H216 instead of local leadership, and some L3 positions use national weights. An office-uploaded replacement may differ. System scoring always uses the corrected guideline rubric '.TosaScoringService::RUBRIC_VERSION.', not reference formulas.',
                    'Formulas are shown as inert text with their saved cache, never executed. Dates and numeric values are displayed as stored in Excel. Preview is bounded to the first '.self::PREVIEW_ROWS.' rows and '.self::PREVIEW_COLUMNS.' columns of each worksheet.',
                ]];
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function personalPreview(TosaApplicant $applicant): array
    {
        $workbook = $this->personalWorkbook($applicant);
        try {
            return ['sheets' => $this->sheets($workbook), 'reference' => false,
                'title' => 'System-computed TOSA workbook',
                'notices' => [
                    'Only this applicant’s saved evaluator inputs and server-calculated points appear here. No original demonstration entries, evaluator names or invented dates are copied.',
                    'Draft evaluations are provisional and do not establish an official rank. Finalized scores still require accepted, eligible applications for ranking.',
                    'This generated seven-sheet workbook uses the corrected rubric. Numeric results are authoritative saved calculations; calculation rules and raw/capped totals are included as text. It is not the unchanged official reference.',
                ]];
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    /** Write only to a caller-owned private temporary path. No formula payload is emitted. */
    public function writePersonal(TosaApplicant $applicant, string $path): void
    {
        $workbook = $this->personalWorkbook($applicant);
        try {
            $writer = new XlsxWriter($workbook);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function personalWorkbook(TosaApplicant $applicant): Spreadsheet
    {
        $evaluation = app(TosaScoringService::class)->evaluation($applicant);
        if ($evaluation === null) {
            throw new InvalidArgumentException('No saved verified-input evaluation is available for this applicant.');
        }
        $b = $evaluation['breakdown'];
        $input = $evaluation['input'];
        $points = static fn (string $key): float => $b[$key] / 1000;
        $summary = [
            ['System-computed TOSA workbook', $evaluation['rubric_version']],
            ['Student', (string) $applicant->full_name], ['SR code', (string) $applicant->sr_code],
            ['Academic year', (string) $applicant->academic_year], ['Evaluation status', $evaluation['status']],
            ['GWA', $input['gwa'] ?? 'Not entered'],
            ['GWA verified', ($input['gwa_verified'] ?? false) ? 'Yes' : 'No'],
            ['Eligibility verified', ($input['eligible_verified'] ?? false) ? 'Yes' : 'No'],
            ['Verified at', $evaluation['verified_at'] ?? 'Not finalized'],
            ['Calculation', 'Raw points', 'Cap', 'Counted points', 'Explanation'],
            ['Grades', $points('grades_milli'), 30, $points('grades_milli'), 'GWA hundredths lookup; 1.34 = 26, 1.35 = 25. Draft/unverified values are provisional and cannot establish an official rank.'],
            ['Academic awards', $points('academic_awards_raw_milli'), 10, $points('academic_awards_milli'), 'min(sum of award entry points, 10)'],
            ['Academic total', $points('grades_milli') + $points('academic_awards_milli'), 40, $points('academic_milli'), 'Grades + capped awards'],
            ['International leadership', $points('leadership_international_raw_milli'), 6, $points('leadership_international_milli'), 'min(L1 entry points, 6)'],
            ['National leadership', $points('leadership_national_raw_milli'), 12, $points('leadership_national_milli'), 'min(L2 entry points, 12)'],
            ['Local leadership', $points('leadership_local_raw_milli'), 12, $points('leadership_local_milli'), 'min(L3 entry points, 12); independent of social activities'],
            ['Leadership total', $points('leadership_international_milli') + $points('leadership_national_milli') + $points('leadership_local_milli'), 30, $points('leadership_milli'), 'Capped L1 + L2 + L3'],
            ['Non-school social', $points('social_non_school_raw_milli'), 18, $points('social_non_school_milli'), 'min(SC1 entry points, 18)'],
            ['School social', $points('social_school_raw_milli'), 12, $points('social_school_milli'), 'min(SC2 entry points, 12)'],
            ['Social total', $points('social_non_school_milli') + $points('social_school_milli'), 30, $points('social_milli'), 'Capped SC1 + SC2'],
            ['Total', $points('academic_milli') + $points('leadership_milli') + $points('social_milli'), 100, $points('total_milli'), 'Academic + leadership + social'],
            ['Source corrections', 'Original SUMMARY F20 and L3 position errors are not used. GWA uses the original lookup thresholds; seminar scores use verified days, not date subtraction.'],
            ['Ranking', 'Drafts are provisional. Finalized scores do not confer rank unless the application is accepted and verified eligible.'],
        ];
        $definitions = [
            ['Acad', 'academic_awards', null, null, 'Academic award points by place 1 / 2 / 3 / 4–10: international 3 / 2 / 1 / 0.5; national 2 / 1 / 0.5 / 0.25; local 1 / 0.5 / 0.25 / 0.125. Combined cap 10.', 'academic_awards_raw_milli', 'academic_awards_milli', 10],
            ['L1', 'leadership', 'international', null, 'Seminars: verified days × 0.6 (1–5 days). Position president / vice president / other: 1 / 0.75 / 0.5. Leadership award: 3 each. Combined cap 6.', 'leadership_international_raw_milli', 'leadership_international_milli', 6],
            ['L2', 'leadership', 'national', null, 'Seminars: verified days × 0.4 (1–5 days). Position president / vice president / other: 0.75 / 0.5 / 0.25. Leadership award: 2 each. Combined cap 12.', 'leadership_national_raw_milli', 'leadership_national_milli', 12],
            ['L3', 'leadership', 'local', null, 'Seminars: verified days × 0.2 (1–5 days). Position president / vice president / other: 0.5 / 0.25 / 0.125. Leadership award: 1 each. Combined cap 12.', 'leadership_local_raw_milli', 'leadership_local_milli', 12],
            ['SC1', 'social_activities', null, 'non_school', 'Organizer / team leader: international 3, national 2, provincial 1, local 0.5. Participant / performer: 1.5 / 1 / 0.5 / 0.25 respectively. Non-school cap 18.', 'social_non_school_raw_milli', 'social_non_school_milli', 18],
            ['SC2', 'social_activities', null, 'school', 'Organizer / team leader: international 3, national 2, provincial 1, local 0.5. Participant / performer: 1.5 / 1 / 0.5 / 0.25 respectively. School cap 12.', 'social_school_raw_milli', 'social_school_milli', 12],
        ];
        $tables = ['SUMMARY' => $summary];
        foreach ($definitions as [$name, $category, $level, $scope, $rule, $raw, $counted, $cap]) {
            $rows = [[$name.' — saved evaluator entries'], ['Rule', $rule],
                ['Category', 'Achievement', 'Evidence reference', 'Level', 'Scope', 'Role', 'Place', 'Verified days', 'Points', 'Calculation']];
            foreach ($evaluation['entries'] as $entry) {
                $matches = $category === 'leadership'
                    ? str_starts_with($entry['category'], 'leadership_')
                    : $entry['category'] === $category;
                if (! $matches || ($level !== null && $entry['level'] !== $level)
                    || ($scope !== null && ($entry['scope'] ?? null) !== $scope)) {
                    continue;
                }
                $value = $entry['points_milli'] / 1000;
                $calculation = isset($entry['days'])
                    ? $entry['days'].' verified days × '.($value / $entry['days']).' = '.$value
                    : 'Rubric rate for recorded level/role/place = '.$value;
                $rows[] = [$entry['category'], $entry['label'], $entry['evidence'], $entry['level'],
                    $entry['scope'] ?? '', $entry['role'] ?? '', $entry['place'] ?? '', $entry['days'] ?? '', $value, $calculation];
            }
            if (count($rows) === 3) {
                $rows[] = ['No verified entries recorded'];
            }
            $rows[] = ['Raw points', $points($raw)];
            $rows[] = ['Cap', $cap];
            $rows[] = ['Counted points', $points($counted), 'min(raw points, cap)'];
            $tables[$name] = $rows;
        }
        $workbook = new Spreadsheet();
        $workbook->getProperties()->setCreator('OrgChain')->setTitle('System-computed TOSA evaluation');
        foreach ($tables as $name => $rows) {
            $sheet = $name === 'SUMMARY' ? $workbook->getActiveSheet() : $workbook->createSheet();
            $sheet->setTitle($name);
            foreach ($rows as $rowIndex => $values) {
                foreach ($values as $columnIndex => $value) {
                    $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], $value,
                        is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
            $sheet->freezePane($name === 'SUMMARY' ? 'B11' : 'B4');
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1:J'.$sheet->getHighestDataRow())->getAlignment()->setWrapText(true);
            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setWidth(in_array($column, ['B', 'C', 'J'], true) ? 40 : 20);
            }
        }
        $workbook->setActiveSheetIndex(0);

        return $workbook;
    }

    private function sheets(Spreadsheet $workbook): array
    {
        $sheets = [];
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            $rows = min($sheet->getHighestDataRow(), self::PREVIEW_ROWS);
            $columns = min(Coordinate::columnIndexFromString($sheet->getHighestDataColumn()), self::PREVIEW_COLUMNS);
            $cells = [];
            for ($row = 1; $row <= $rows; $row++) {
                $values = [];
                for ($column = 1; $column <= $columns; $column++) {
                    $address = Coordinate::stringFromColumnIndex($column).$row;
                    $value = '';
                    $formula = null;
                    if ($sheet->cellExists($address)) {
                        $cell = $sheet->getCell($address);
                        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                            $formula = $this->text($cell->getValue());
                            $value = $this->text($cell->getOldCalculatedValue());
                        } else {
                            $value = $this->text($cell->getValue());
                        }
                    }
                    $values[] = ['value' => $value, 'formula' => $formula];
                }
                $cells[] = $values;
            }
            $sheets[] = ['name' => $sheet->getTitle(), 'columns' => array_map(Coordinate::stringFromColumnIndex(...), range(1, $columns)), 'rows' => $cells];
        }

        return $sheets;
    }

    private function text(mixed $value): string
    {
        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return mb_substr(is_scalar($value) ? (string) $value : '', 0, 4000);
    }

    private function assertBoundedPackage(string $path): void
    {
        if (! is_file($path) || filesize($path) > self::MAX_PACKAGE_BYTES) {
            throw new InvalidArgumentException('The reference workbook exceeds the safe preview size.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new InvalidArgumentException('The reference must be a native XLSX workbook.');
        }
        try {
            if ($zip->numFiles > 512 || $zip->locateName('[Content_Types].xml') === false
                || $zip->locateName('xl/workbook.xml') === false) {
                throw new InvalidArgumentException('The reference workbook package is invalid or too large.');
            }
            $total = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $part = $zip->statIndex($index);
                if ($part === false || $part['size'] > self::MAX_PART_BYTES
                    || ($total += $part['size']) > self::MAX_PACKAGE_BYTES) {
                    throw new InvalidArgumentException('The reference workbook expands beyond the safe preview size.');
                }
                if (preg_match('/\.(?:xml|rels)$/i', $part['name'])) {
                    $xml = $zip->getFromIndex($index);
                    if ($xml === false || preg_match('/<!\s*(?:DOCTYPE|ENTITY)\b/i', $xml)
                        || (str_ends_with($part['name'], '.rels') && preg_match('/TargetMode\s*=\s*[\x22\x27]External[\x22\x27]/i', $xml))) {
                        throw new InvalidArgumentException('External resources or XML entities are not supported in the safe workbook preview.');
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }
}

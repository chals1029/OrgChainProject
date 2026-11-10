<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetExportService
{
    private const CURRENCY = '"₱"#,##0.00';

    public function exportAnalytics(array $data): string
    {
        $spreadsheet = $this->workbook('Financial & Analytics Report');
        $sheet = $spreadsheet->getActiveSheet();
        $overview = $data['overview'];
        $this->section($sheet, 5, 'Overview / Budget Health', 2);
        $metrics = [
            ['Health score', ($overview['healthScore'] ?? 0) / 100, 'percent'],
            ['Total budget allocated', $overview['totalAllocated'] ?? 0, 'currency'],
            ['Total budget utilized', $overview['totalUtilized'] ?? 0, 'currency'],
            ['Remaining reserve', $overview['remainingBalance'] ?? 0, 'currency'],
            ['Burn rate', ($overview['burnRate'] ?? 0) / 100, 'percent'],
            ['Compliance rate', ($overview['complianceRate'] ?? null) === null ? 'N/A' : $overview['complianceRate'] / 100, 'percent'],
        ];
        $this->table($sheet, 6, ['Metric', 'Value'], array_map(fn ($metric) => [$metric[0], $metric[1]], $metrics));
        foreach ($metrics as $index => $metric) {
            if (is_numeric($metric[1])) {
                $sheet->getStyle('B'.($index + 7))->getNumberFormat()->setFormatCode($metric[2] === 'currency' ? self::CURRENCY : '0.0%');
            }
        }
        $row = 6 + count($metrics) + 2;
        $this->section($sheet, $row++, 'College Performance', 6);
        $collegeRows = [];
        foreach ($data['collegeStats'] as $item) {
            $collegeRows[] = [$item['college'] ?? '', $item['activities'] ?? 0, ($item['completion_percent'] ?? 0) / 100, ($item['utilization_percent'] ?? 0) / 100, $item['approved_budget'] ?? 0, $item['implemented_budget'] ?? 0];
        }
        $this->table($sheet, $row, ['College', 'Activities', 'Completion %', 'Utilization %', 'Approved budget', 'Implemented budget'], $collegeRows, ['percent' => [3, 4], 'currency' => [5, 6]]);
        $row += count($collegeRows) + 2;
        $this->section($sheet, $row++, 'Activity Financials', 10);
        $activityRows = [];
        foreach ($data['activityFinancials'] as $item) {
            $activityRows[] = [$item['name'] ?? '', $item['scope_label'] ?? '', $item['college'] ?? '', $item['allocated'] ?? 0, $item['utilized'] ?? 0, $item['remaining'] ?? 0, ($item['burn_rate'] ?? 0) / 100, $item['status'] ?? '', $item['month'] ?? '', $item['year'] ?? ''];
        }
        $this->table($sheet, $row, ['Activity', 'Scope', 'College', 'Allocated', 'Utilized', 'Remaining', 'Burn rate %', 'Status', 'Month', 'Year'], $activityRows, ['currency' => [4, 5, 6], 'percent' => [7], 'center' => [8, 9, 10]]);

        return $this->save($spreadsheet);
    }

    public function exportTable(string $title, array $headers, array $rows, array $options = []): string
    {
        $spreadsheet = $this->workbook($title);
        $this->table($spreadsheet->getActiveSheet(), 5, $headers, $rows, $options);

        return $this->save($spreadsheet);
    }

    public function exportReceiptPackage(array $receiptRows): string
    {
        return $this->exportTable('Receipt Compilation', ['Receipt ID', 'Activity ID', 'Organization', 'Activity', 'Expense date', 'Item', 'Supplier', 'Quantity', 'Unit cost', 'Total', 'Reference', 'Seal status', 'Hash', 'Receipt files', 'Receipt type', 'Payment method'], $receiptRows, ['currency' => [9, 10], 'center' => [5, 12]]);
    }

    private function workbook(string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(9.5);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setShowGridlines(true);
        foreach (['BATANGAS STATE UNIVERSITY', $title, 'Generated on: '.now()->format('F j, Y · g:i A').' | OrgChain Portal'] as $index => $text) {
            $sheet->setCellValueExplicit('A'.($index + 1), $text, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1')->getFont()->setSize(9)->setBold(true)->getColor()->setRGB('718096');
        $sheet->getStyle('A2')->getFont()->setSize(14)->setBold(true)->getColor()->setRGB('8B1828');
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');

        return $spreadsheet;
    }

    private function section(Worksheet $sheet, int $row, string $title, int $columns): void
    {
        $sheet->setCellValueExplicit('A'.$row, $title, DataType::TYPE_STRING);
        $sheet->getStyle('A'.$row.':'.Coordinate::stringFromColumnIndex($columns).$row)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('8B1828');
    }

    private function table(Worksheet $sheet, int $start, array $headers, array $rows, array $options = []): void
    {
        $last = Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).$start, (string) $header, DataType::TYPE_STRING);
        }
        $headerStyle = $sheet->getStyle("A{$start}:{$last}{$start}");
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('8B1828');
        $headerStyle->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($start)->setRowHeight(26);
        foreach ($rows as $index => $values) {
            $row = $start + $index + 1;
            foreach (array_values($values) as $column => $value) {
                $cell = Coordinate::stringFromColumnIndex($column + 1).$row;
                $numeric = is_int($value) || is_float($value);
                $sheet->setCellValueExplicit($cell, $value === null ? '' : ($numeric ? $value : (string) $value), $numeric ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
            $style = $sheet->getStyle("A{$row}:{$last}{$row}");
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($index % 2 === 0 ? 'FFFFFF' : 'FAF8F9');
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(20);
        }
        if (count($rows) > 0) {
            $end = $start + count($rows);
            foreach (['currency' => self::CURRENCY, 'percent' => '0.0%'] as $type => $format) {
                foreach ($options[$type] ?? [] as $column) {
                    $letter = Coordinate::stringFromColumnIndex($column);
                    $sheet->getStyle("{$letter}".($start + 1).":{$letter}{$end}")->getNumberFormat()->setFormatCode($format);
                }
            }
            foreach ($options['center'] ?? [] as $column) {
                $letter = Coordinate::stringFromColumnIndex($column);
                $sheet->getStyle("{$letter}".($start + 1).":{$letter}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
        foreach ($headers as $index => $header) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $width = mb_strlen((string) $header);
            foreach ($rows as $values) {
                $width = max($width, mb_strlen((string) (array_values($values)[$index] ?? '')));
            }
            $dimension = $sheet->getColumnDimension($letter);
            $dimension->setWidth(max($dimension->getWidth(), 12, min(255, $width + 4)));
        }
    }

    private function save(Spreadsheet $spreadsheet): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'orgchain-');
        if ($temporary === false) {
            $spreadsheet->disconnectWorksheets();
            throw new \RuntimeException('Unable to create a temporary spreadsheet.');
        }
        $path = $temporary.'.xlsx';
        try {
            (new Xlsx($spreadsheet))->save($path);

            return $path;
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $e;
        } finally {
            unlink($temporary);
            $spreadsheet->disconnectWorksheets();
        }
    }
}

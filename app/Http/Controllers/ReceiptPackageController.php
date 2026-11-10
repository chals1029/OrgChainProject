<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Services\ActivityBudgetService;
use App\Services\SpreadsheetExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ReceiptPackageController extends Controller
{
    public function __invoke(Request $request, ActivityBudgetService $budget, SpreadsheetExportService $exports)
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active
            && in_array($office->office_role, ['so', 'oso'], true), 403);
        $organization = null;
        if ($office->office_role === 'so') {
            $assigned = $office->studentOrganization;
            abort_unless($assigned && $assigned->is_active && trim((string) $assigned->name) !== '', 403);
            $organization = $assigned->name;
        }
        $filters = $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'activity_id' => ['nullable', 'integer', 'exists:mysql.org_activities,id'],
            'academic_year' => ['nullable', 'string'], 'semester' => ['nullable', 'string'],
        ]);
        if ($organization !== null) {
            $filters['organization'] = $organization;
            if (! empty($filters['activity_id'])) {
                abort_unless(OrgActivity::query()->whereKey($filters['activity_id'])
                    ->where('organization_name', $organization)->exists(), 403);
            }
        }
        $query = $budget->receipts($filters['organization'] ?? null)
            ->when($organization !== null, fn ($q) => $q->whereIn('org_activity_id',
                OrgActivity::query()->where('organization_name', $organization)->select('id')))
            ->when(! empty($filters['activity_id']), fn ($q) => $q->where('org_activity_id', $filters['activity_id']));
        if (! empty($filters['academic_year'])) {
            $query->whereBetween('expense_date', $budget->dates($filters['academic_year'], $filters['semester'] ?? 'Annual'));
        }
        $rows = $query->orderBy('expense_date')->orderBy('id')->get();
        File::ensureDirectoryExists(storage_path('app/private/exports'));
        $path = tempnam(storage_path('app/private/exports'), 'receipts-');
        if ($path === false) {
            abort(500, 'The receipt compilation could not be created. Please retry.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            File::delete($path);
            abort(500, 'The receipt compilation could not be created. Please retry.');
        }
        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Receipt ID', 'Activity ID', 'Organization', 'Activity', 'Expense date', 'Item', 'Supplier', 'Quantity', 'Unit cost', 'Total', 'Reference', 'Seal status', 'Hash', 'Receipt files', 'Receipt type', 'Payment method'], ',', '"', '');
        $clean = fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : $value;
        $archiveByFile = [];
        $spreadsheetPath = null;
        $receiptRows = [];
        try {
            foreach ($rows as $row) {
                $zipNames = [];
                foreach ($row->receiptFiles() as $index => $file) {
                    $disk = Storage::disk('local');
                    $fileKey = 'local|'.$file['path'];
                    if (array_key_exists($fileKey, $archiveByFile)) {
                        $zipNames[] = $archiveByFile[$fileKey].' (shared: '.$file['name'].')';
                        continue;
                    }
                    $available = $disk->exists($file['path']);
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $name = 'receipts/'.$row->id.'-'.($index + 1).'-'.Str::slug(pathinfo($file['name'], PATHINFO_FILENAME)).($extension ? '.'.$extension : '');
                    if ($available) {
                        if (! $zip->addFile($disk->path($file['path']), $name)) {
                            throw new \RuntimeException('Unable to add a receipt to the compilation.');
                        }
                        $archiveByFile[$fileKey] = $name.' ('.$file['name'].')';
                    } else {
                        $archiveByFile[$fileKey] = 'MISSING: '.$file['name'];
                    }
                    $zipNames[] = $archiveByFile[$fileKey];
                }
                $receiptRow = [
                    $row->id, $row->org_activity_id, $row->organization_name, $row->activity_title,
                    $row->expense_date->toDateString(), $row->item_name, $row->supplier, (float) $row->quantity, (float) $row->unit_cost,
                    (float) $row->quantity * (float) $row->unit_cost, $row->receipt_reference,
                    $row->verification_status, $row->chain_hash, implode(' | ', $zipNames),
                    $row->receipt_type, $row->payment_method,
                ];
                $receiptRows[] = $receiptRow;
                $csvRow = $receiptRow;
                $csvRow[9] = number_format($receiptRow[9], 2, '.', '');
                fputcsv($csv, array_map($clean, $csvRow), ',', '"', '');
            }
            rewind($csv);
            $csvContents = stream_get_contents($csv);
            if ($csvContents === false || ! $zip->addFromString('expense-register.csv', $csvContents)
                || ! $zip->addFromString('receipts.csv', $csvContents)) {
                throw new \RuntimeException('Unable to add the expense register to the receipt compilation.');
            }
            $spreadsheetPath = $exports->exportReceiptPackage($receiptRows);
            $spreadsheetContents = file_get_contents($spreadsheetPath);
            if ($spreadsheetContents === false || ! $zip->addFromString('Receipt-Compilation.xlsx', $spreadsheetContents)) {
                throw new \RuntimeException('Unable to add the spreadsheet to the receipt compilation.');
            }
            if (! $zip->addFromString('README.txt', "OrgChain receipt compilation\nGenerated: ".now()->toIso8601String()."\nFilters: ".json_encode($filters)."\nOriginal receipts and the complete expense register are included. Missing originals are marked in the register. Pending seals are not confirmed blockchain records.\n") || ! $zip->close()) {
                throw new \RuntimeException('Unable to finish the receipt compilation.');
            }
            return response()->download($path, 'Receipt-Compilation-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            $zip->close();
            File::delete($path);
            throw $e;
        } finally {
            if ($spreadsheetPath !== null) {
                File::delete($spreadsheetPath);
            }
            fclose($csv);
        }
    }
}

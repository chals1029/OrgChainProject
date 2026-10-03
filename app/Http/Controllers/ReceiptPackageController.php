<?php

namespace App\Http\Controllers;

use App\Services\ActivityBudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ReceiptPackageController extends Controller
{
    public function __invoke(Request $request, ActivityBudgetService $budget)
    {
        abort_unless(in_array(Auth::guard('office')->user()?->office_role, ['so', 'oso'], true), 403);
        $filters = $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'activity_id' => ['nullable', 'integer', 'exists:mysql.org_activities,id'],
            'academic_year' => ['nullable', 'string'], 'semester' => ['nullable', 'string'],
        ]);
        $query = $budget->receipts($filters['organization'] ?? null)
            ->when(! empty($filters['activity_id']), fn ($q) => $q->where('org_activity_id', $filters['activity_id']));
        if (! empty($filters['academic_year'])) {
            $query->whereBetween('expense_date', $budget->dates($filters['academic_year'], $filters['semester'] ?? 'Annual'));
        }
        $rows = $query->orderBy('expense_date')->orderBy('id')->get();
        File::ensureDirectoryExists(storage_path('app/private/exports'));
        $path = tempnam(storage_path('app/private/exports'), 'receipts-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            File::delete($path);
            abort(500, 'The receipt compilation could not be created. Please retry.');
        }
        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Receipt ID', 'Activity ID', 'Organization', 'Activity', 'Expense date', 'Item', 'Supplier', 'Quantity', 'Unit cost', 'Total', 'Reference', 'Seal status', 'Hash', 'Receipt files', 'Receipt type', 'Payment method'], ',', '"', '');
        $clean = fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : $value;
        $archiveByFile = [];
        try {
            foreach ($rows as $row) {
                $zipNames = [];
                foreach ($row->receiptFiles() as $index => $file) {
                    $disk = Storage::disk($file['disk'] ?: $row->receipt_disk ?: 'public');
                    $fileKey = ($file['disk'] ?: $row->receipt_disk ?: 'public').'|'.$file['path'];
                    if (array_key_exists($fileKey, $archiveByFile)) {
                        $zipNames[] = $archiveByFile[$fileKey].' (shared: '.$file['name'].')';
                        continue;
                    }
                    $available = $disk->exists($file['path']);
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $name = 'receipts/'.$row->id.'-'.($index + 1).'-'.Str::slug(pathinfo($file['name'], PATHINFO_FILENAME)).($extension ? '.'.$extension : '');
                    if ($available) {
                        $zip->addFile($disk->path($file['path']), $name);
                        $archiveByFile[$fileKey] = $name.' ('.$file['name'].')';
                    } else {
                        $archiveByFile[$fileKey] = 'MISSING: '.$file['name'];
                    }
                    $zipNames[] = $archiveByFile[$fileKey];
                }
                fputcsv($csv, array_map($clean, [
                    $row->id, $row->org_activity_id, $row->organization_name, $row->activity_title,
                    $row->expense_date->toDateString(), $row->item_name, $row->supplier, $row->quantity, $row->unit_cost,
                    number_format($row->quantity * (float) $row->unit_cost, 2, '.', ''), $row->receipt_reference,
                    $row->verification_status, $row->chain_hash, implode(' | ', $zipNames),
                    $row->receipt_type, $row->payment_method,
                ]), ',', '"', '');
            }
            rewind($csv);
            $zip->addFromString('expense-register.csv', stream_get_contents($csv));
            $zip->addFromString('README.txt', "OrgChain receipt compilation\nGenerated: ".now()->toIso8601String()."\nFilters: ".json_encode($filters)."\nOriginal receipts and the complete expense register are included. Missing originals are marked in the register. Pending seals are not confirmed blockchain records.\n");
            $zip->close();
            return response()->download($path, 'Receipt-Compilation-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            $zip->close();
            File::delete($path);
            throw $e;
        } finally {
            fclose($csv);
        }
    }
}

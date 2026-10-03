<?php

namespace App\Services;

use App\Models\OfficeUser;
use App\Models\ReceiptScan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ReceiptScanner
{
    public function scan(UploadedFile $file, OfficeUser $actor): ReceiptScan
    {
        try {
            $response = Http::connectTimeout(3)->timeout(config('receipt_scanner.timeout'))
                ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
                ->post(rtrim(config('receipt_scanner.url'), '/').'/scan');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(503, 'The local receipt scanner is unavailable. Retry shortly; no expense was recorded.');
        }
        if ($response->status() === 422 || $response->status() === 413) {
            throw ValidationException::withMessages(['receipt' => 'Cannot read this image safely. Upload a JPEG, PNG or WebP photo under 24 megapixels and 10 MB.']);
        }
        abort_unless($response->successful(), 503, 'The local receipt scanner is busy or unavailable. Please retry.');
        $ocr = $response->json();
        if (!is_array($ocr) || !is_array($ocr['lines'] ?? null)) abort(503, 'The scanner returned an invalid result. Please retry.');
        if (mb_strlen(trim($ocr['text'] ?? '')) < 12 || ($ocr['confidence'] ?? 0) < 45) {
            throw ValidationException::withMessages(['receipt' => 'This photo is not readable enough. Retake the full receipt in good light, with the text in focus.']);
        }
        $extracted = app(ReceiptFieldExtractor::class)->extract($ocr) + ['preprocessing' => $ocr['preprocessing'] ?? []];
        if (($extracted['quality'] ?? 'partial') !== 'complete') {
            throw ValidationException::withMessages([
                'receipt' => 'Receipt is blurry or incomplete. Upload a clear, uncropped photo showing the merchant, total amount, transaction date, and receipt or reference number.',
            ]);
        }
        return ReceiptScan::create([
            'uploaded_by' => $actor->id, 'file_hash' => hash_file('sha256', $file->getRealPath()),
            'engine' => mb_substr($ocr['engine'] ?? 'local-tesseract', 0, 80),
            'raw_text' => mb_substr($ocr['text'], 0, 30000),
            'confidence' => max(0, min(100, (int) round($ocr['confidence']))),
            'extracted' => $extracted,
            'expires_at' => now()->addHour(),
        ]);
    }

    public function validatedMetadata(UploadedFile $file, OfficeUser $actor, array $data): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'docx') {
            $document = app(ReceiptDocumentValidator::class)->validate($file);
            if (! $document['valid']) {
                throw ValidationException::withMessages(['receipt' => $document['message']]);
            }
            return [
                'receipt_scan_id' => null, 'ocr_quality' => 'partial', 'ocr_confidence' => null,
                'ocr_corrections' => ['document_validation' => $document['signals'] ?? [], 'document_confidence' => $document['confidence'] ?? 0],
            ];
        }
        if ($file->getMimeType() === 'application/pdf') {
            $document = app(ReceiptDocumentValidator::class)->validate($file);
            if (! $document['valid']) throw ValidationException::withMessages(['receipt' => $document['message']]);
            return ['receipt_scan_id' => null, 'ocr_quality' => 'partial', 'ocr_confidence' => null, 'ocr_corrections' => null];
        }
        $scan = ReceiptScan::whereKey($data['receipt_scan_id'] ?? '')->where('uploaded_by', $actor->id)->first();
        if (!$scan || $scan->expires_at->isPast() || !hash_equals($scan->file_hash, hash_file('sha256', $file->getRealPath()))) {
            throw ValidationException::withMessages(['receipt' => 'Scan this exact receipt photo again before saving. Scans expire after one hour and cannot be shared between users or files.']);
        }
        if (($scan->extracted['quality'] ?? 'partial') !== 'complete') {
            throw ValidationException::withMessages([
                'receipt' => 'Receipt is blurry or incomplete. Upload a clear, uncropped photo showing the merchant, total amount, transaction date, and receipt or reference number.',
            ]);
        }
        $corrections = [];
        foreach ($scan->extracted['fields'] as $key => $field) {
            $reviewed = trim((string) ($data[$key] ?? ''));
            if ($key === 'unit_cost') $reviewed = number_format((float)$reviewed * (int)$data['quantity'], 2, '.', '');
            if ($reviewed !== (string) ($field['value'] ?? '')) $corrections[$key] = ['detected' => $field['value'], 'reviewed' => $reviewed];
        }
        return ['receipt_scan_id' => $scan->id, 'ocr_quality' => $scan->extracted['quality'], 'ocr_confidence' => $scan->confidence, 'ocr_corrections' => $corrections];
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Pre-save file classifier for receipts. It checks the file container and
 * visible document text; it does not prove that a receipt is authentic.
 */
class ReceiptDocumentValidator
{
    public const EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'docx'];

    public function validate(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'docx' => $this->validateDocx($file),
            'pdf' => $this->validatePdf($file),
            'png', 'jpg', 'jpeg', 'webp' => [
                'valid' => true,
                'kind' => 'photo',
                'confidence' => null,
                'message' => 'Receipt photo attached. Details entered manually.',
            ],
            default => [
                'valid' => false,
                'kind' => 'unsupported',
                'confidence' => 0,
                'message' => 'Use a receipt photo, PDF, or DOCX receipt document.',
            ],
        };
    }

    private function validatePdf(UploadedFile $file): array
    {
        $handle = @fopen($file->getRealPath(), 'rb');
        $signature = $handle ? fread($handle, 5) : '';
        if (is_resource($handle)) fclose($handle);

        return [
            'valid' => $signature === '%PDF-',
            'kind' => 'pdf',
            'confidence' => null,
            'message' => $signature === '%PDF-'
                ? 'PDF attached. It will be saved as a manually reviewed receipt document.'
                : 'This file is not a valid PDF.',
        ];
    }

    private function validateDocx(UploadedFile $file): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['valid' => false, 'kind' => 'docx', 'confidence' => 0, 'message' => 'DOCX validation is unavailable on this server. Upload a photo or PDF instead.'];
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($file->getRealPath()) === true;
        if (! $opened || $zip->locateName('word/document.xml') === false) {
            if ($opened) $zip->close();
            return ['valid' => false, 'kind' => 'docx', 'confidence' => 0, 'message' => 'This file is not a readable DOCX document.'];
        }

        $uncompressed = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = (string) ($stat['name'] ?? '');
            $size = (int) ($stat['size'] ?? 0);
            if (str_contains($name, '..') || str_starts_with($name, '/') || $size > 20 * 1024 * 1024 || ($uncompressed += $size) > 50 * 1024 * 1024) {
                $zip->close();
                return ['valid' => false, 'kind' => 'docx', 'confidence' => 0, 'message' => 'This DOCX is too large or has an unsafe archive structure.'];
            }
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (! is_string($xml) || $xml === '') {
            return ['valid' => false, 'kind' => 'docx', 'confidence' => 0, 'message' => 'The DOCX has no readable document text.'];
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return ['valid' => false, 'kind' => 'docx', 'confidence' => 0, 'message' => 'The DOCX text could not be read safely.'];
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode($document->textContent ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8')));
        $receiptSignals = array_filter([
            preg_match('/\b(?:OFFICIAL\s+RECEIPT|SALES\s+INVOICE|RECEIPT|INVOICE)\b/i', $text),
            preg_match('/\b(?:TOTAL|AMOUNT\s+(?:DUE|PAID|SENT)|SUBTOTAL|VAT|CASH|CHANGE|GCASH|MAYA|PAID\s+TO|MERCHANT)\b/i', $text),
            preg_match('/\b(?:REFERENCE|REF\.?|OR\.?\s*(?:NO|NUMBER)|TRANSACTION\s*(?:ID|NO|NUMBER))\b/i', $text),
            preg_match('/(?:PHP|₱|\bP)\s*[0-9][0-9,]*(?:\.[0-9]{2})?/iu', $text),
            preg_match('/\b(?:20\d{2}[-\/.]\d{1,2}[-\/.]\d{1,2}|\d{1,2}[-\/.]\d{1,2}[-\/.]20\d{2})\b/', $text),
        ]);
        $activitySignals = preg_match('/\b(?:PROJECT\s+PROPOSAL|ACTIVITY\s+PROPOSAL|OBJECTIVES|VENUE|PARTICIPANTS|BUDGET\s+PROPOSAL|CHECKLIST\s+OF\s+REQUIREMENTS|FACULTY[- ]IN[- ]CHARGE|PROGRAMME|SCHEDULE\s+OF\s+ACTIVITIES|RESOLUTION\s+OF\s+THE\s+ORGANIZATION)\b/i', $text);
        $valid = count($receiptSignals) >= 2 && ! ($activitySignals && count($receiptSignals) < 3);
        $confidence = min(98, 25 + count($receiptSignals) * 15 - ($activitySignals && ! $valid ? 35 : 0));

        return [
            'valid' => $valid,
            'kind' => 'docx',
            'confidence' => max(0, $confidence),
            'message' => $valid
                ? 'DOCX contains receipt-like labels and values. Confirm the details before saving.'
                : 'This DOCX does not look like a receipt. Upload the official receipt/invoice document, or use a clear receipt photo.',
            'signals' => ['receipt' => count($receiptSignals), 'activity' => (bool) $activitySignals, 'characters' => mb_strlen($text)],
            'text_excerpt' => mb_substr($text, 0, 240),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
        ];
    }
}

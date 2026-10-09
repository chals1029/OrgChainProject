<?php

namespace App\Services;

use App\Models\OfficeUser;
use App\Models\OrgReportDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Records, per signed-in session, which exact stored file version of a
 * financial report the SO actually opened. Submitting requires a mark that
 * still matches the current file, so a replaced or edited file must be
 * viewed again.
 */
class FinancialReportPreviewGate
{
    private const SESSION_KEY = 'financial_report_previews';

    public function mark(OfficeUser $office, OrgReportDocument $document): void
    {
        $fingerprint = $this->fingerprint($document);
        if ($fingerprint !== null) {
            session()->put($this->key($office, $document), $fingerprint);
        }
    }

    public function wasViewed(OfficeUser $office, OrgReportDocument $document): bool
    {
        $stored = session()->get($this->key($office, $document));
        $current = $this->fingerprint($document);

        return is_string($stored) && $current !== null && hash_equals($stored, $current);
    }

    /**
     * Legacy files the browser can display inline: an actual PDF or PNG /
     * JPEG / GIF / WebP image, judged by the stored bytes and not only the
     * recorded MIME type.
     */
    public function isInlineViewable(OrgReportDocument $document): bool
    {
        $mime = strtolower((string) $document->mime_type);
        if (($mime !== 'application/pdf' && ! str_starts_with($mime, 'image/')) || ! $document->hasStoredFile()) {
            return false;
        }

        $handle = @fopen(Storage::disk('public')->path($document->file_path), 'rb');
        if ($handle === false) {
            return false;
        }
        $header = (string) fread($handle, 12);
        fclose($handle);

        return match (true) {
            $mime === 'application/pdf' => str_starts_with($header, '%PDF-'),
            $mime === 'image/png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg'], true) => str_starts_with($header, "\xFF\xD8\xFF"),
            $mime === 'image/gif' => str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a'),
            $mime === 'image/webp' => str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP',
            default => false,
        };
    }

    private function key(OfficeUser $office, OrgReportDocument $document): string
    {
        return self::SESSION_KEY.'.'.$office->getKey().'_'.$document->getKey();
    }

    private function fingerprint(OrgReportDocument $document): ?string
    {
        if (! $document->hasStoredFile()) {
            return null;
        }

        $contentHash = hash_file('sha256', Storage::disk('public')->path($document->file_path));
        if ($contentHash === false) {
            return null;
        }

        return hash('sha256', implode('|', [
            $document->getKey(),
            $document->file_path,
            (string) $document->file_size,
            (string) $document->updated_at?->getTimestamp(),
            $contentHash,
        ]));
    }
}

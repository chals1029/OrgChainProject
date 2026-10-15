<?php

namespace App\Services;

use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class ApprovedPlanOfActivitiesService
{
    /** @return list<array{key: string, title: string, reference: string, document_id: int, document_version: string}> */
    public function options(string $organizationName): array
    {
        $window = OrgRenewalWindow::query()->latest('id')->first();
        $submission = $window ? OrgRenewalSubmission::query()
            ->where('renewal_window_id', $window->id)
            ->where('organization_name', $organizationName)
            ->latest('id')->first() : null;
        if (! $submission || $submission->status !== 'approved') {
            throw ValidationException::withMessages(['plan_activity_key' => 'Your organization needs an approved renewal packet in the current renewal window before selecting an activity.']);
        }
        $document = $submission->documents()->where('doc_key', 'plan_of_activities')->latest('id')->first();
        if (! $document || ! $document->hasStoredFile()) {
            throw ValidationException::withMessages(['plan_activity_key' => 'The approved Attachment I (Plan of Activities) file is missing or empty. Ask OSO to correct the approved packet.']);
        }
        $path = Storage::disk('local')->path($document->file_path);
        $version = hash_file('sha256', $path);
        if ($version === false) {
            throw ValidationException::withMessages(['plan_activity_key' => 'The approved Attachment I could not be read. Ask OSO to correct the approved packet.']);
        }
        try {
            $extension = strtolower(pathinfo($document->file_name ?: $path, PATHINFO_EXTENSION));
            $entries = match ($extension) {
                'docx' => $this->docxEntries($path),
                'pdf' => $this->textEntries(app(OfficeDocumentConverter::class)->pdfText($path)),
                'doc' => $this->legacyEntries($path),
                default => throw new RuntimeException('Attachment I must be a readable DOCX, PDF or Word DOC document.'),
            };
        } catch (RuntimeException $error) {
            $message = $extension === 'docx'
                ? 'Unable to read the approved Attachment I: '.$error->getMessage()
                : (in_array($extension, ['doc', 'pdf'], true)
                    ? 'Unable to extract text from the approved Attachment I. Ask OSO to check that the PDF/Word document is readable and the office document converter is available.'
                    : 'Attachment I must be a readable DOCX, PDF or Word DOC document. Ask OSO to correct the approved packet.');
            throw ValidationException::withMessages(['plan_activity_key' => $message]);
        }
        if ($entries === []) {
            throw ValidationException::withMessages(['plan_activity_key' => 'The approved Attachment I contains no readable ACTIVITY/PROJECT TITLE rows. A blank template or scanned PDF cannot supply activities. Ask OSO for a completed, text-readable approved plan.']);
        }
        $options = [];
        foreach ($entries as $index => $entry) {
            $reference = 'Attachment I — '.$window->academic_year.' · '.$window->semester.' · Activity '.($index + 1).' — '.$entry['title'];
            if ($entry['date'] !== '') {
                $reference .= ' · '.$entry['date'];
            }
            $identity = json_encode([$window->id, $submission->id, $organizationName, $document->id, $version, $index, $entry['title'], $reference], JSON_THROW_ON_ERROR);
            $options[] = [
                'key' => hash_hmac('sha256', $identity, (string) config('app.key')),
                'title' => $entry['title'],
                'reference' => $reference,
                'document_id' => (int) $document->id,
                'document_version' => $version,
            ];
        }

        return $options;
    }

    public function resolve(string $organizationName, string $key): array
    {
        foreach ($this->options($organizationName) as $option) {
            if (hash_equals($option['key'], $key)) {
                if (mb_strlen($option['title']) > 255) {
                    throw ValidationException::withMessages(['plan_activity_key' => 'The approved activity title exceeds the 255-character limit. Ask OSO to shorten that plan entry before submitting it.']);
                }
                return $option;
            }
        }
        throw ValidationException::withMessages(['plan_activity_key' => 'Choose an activity from your organization’s current approved Attachment I. The selected entry is invalid or the approved plan changed; reload the form.']);
    }

    private function legacyEntries(string $path): array
    {
        $converter = app(OfficeDocumentConverter::class);
        $converted = $converter->toPdf($path);
        try {
            return $this->textEntries($converter->pdfText($converted['path']));
        } finally {
            $converter->release($converted);
        }
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $value) ?? '');
    }

    private function realTitle(string $title): bool
    {
        return $title !== '' && preg_match('/[\p{L}\p{N}]/u', $title)
            && ! preg_match('/^(?:(?:1ST|2ND|FIRST|SECOND)\s+SEM(?:ESTER)?|SEMESTER|SUMMER)\b/iu', $title)
            && ! preg_match('/^(?:ACTIVITY\s*\/?\s*PROJECT\s*TITLE|ACTIVITY\s*TITLE|PROJECT\s*TITLE|CATEGORY|TARGET DATE|PLAN OF ACTIVITIES|ATTACHMENT I|N\/A|NONE|TBD)(?:\s*[:\-–—].*)?$/iu', $title);
    }

    /** Attachment I's real template is a nine-column table with a split ACTIVITY/ PROJECT TITLE header. */
    private function docxEntries(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The DOCX file is not a valid Word archive.');
        }
        try {
            $xml = $zip->getFromName('word/document.xml', 20 * 1024 * 1024 + 1);
        } finally {
            $zip->close();
        }
        if (! is_string($xml) || strlen($xml) > 20 * 1024 * 1024) {
            throw new RuntimeException('The DOCX document body is missing or too large.');
        }
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $dom->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('The DOCX document body is malformed.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $entries = [];
        foreach ($xpath->query('//w:tbl') as $table) {
            $titleColumn = null;
            $dateColumn = null;
            foreach ($xpath->query('./w:tr', $table) as $row) {
                $cells = [];
                foreach ($xpath->query('./w:tc', $row) as $cell) {
                    $parts = [];
                    foreach ($xpath->query('.//w:p', $cell) as $paragraph) {
                        $text = '';
                        foreach ($xpath->query('.//w:t | .//w:br | .//w:tab', $paragraph) as $node) {
                            $text .= $node instanceof DOMElement && $node->localName === 't' ? $node->textContent : ' ';
                        }
                        $parts[] = $text;
                    }
                    $cells[] = $this->clean(implode(' ', $parts));
                }
                $headerFound = false;
                foreach ($cells as $column => $text) {
                    if (preg_match('/^(?:ACTIVITY\s*\/\s*PROJECT|ACTIVITY|PROJECT)\s+TITLE$/iu', $text)) {
                        $titleColumn = $column;
                        $dateColumn = array_search('TARGET DATE', array_map('strtoupper', $cells), true);
                        $headerFound = true;
                        break;
                    }
                }
                if ($headerFound || $titleColumn === null || ! isset($cells[$titleColumn]) || count($cells) < 2) {
                    continue;
                }
                $title = $cells[$titleColumn];
                if ($this->realTitle($title)) {
                    $entries[] = ['title' => $title, 'date' => $dateColumn !== false && $dateColumn !== null ? ($cells[$dateColumn] ?? '') : ''];
                }
            }
        }

        return $entries;
    }

    /** Read the actual title column from pdftotext -layout, joining its wrapped lines. */
    private function textEntries(string $text): array
    {
        $entries = [];
        $start = null;
        $end = null;
        $dateStart = null;
        $budgetStart = null;
        $title = [];
        $flush = function () use (&$title, &$entries): void {
            $value = $this->clean(implode(' ', $title));
            if ($this->realTitle($value)) {
                $entries[] = ['title' => $value, 'date' => ''];
            }
            $title = [];
        };
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            // The official table's CATEGORY header fixes the title column's right edge.
            if (preg_match('/ACTIVITY\s*\/?(?:\s*PROJECT)?(?:\s+TITLE)?/i', $line, $match, PREG_OFFSET_CAPTURE)
                && preg_match('/\bCATEGORY\b/i', $line, $category, PREG_OFFSET_CAPTURE)) {
                $flush();
                $start = 0;
                $end = $category[0][1];
                $dateStart = preg_match('/\\bTARGET\\b/i', $line, $dateHeader, PREG_OFFSET_CAPTURE) ? $dateHeader[0][1] : null;
                $budgetStart = preg_match('/\\bPROJECTED\\b/i', $line, $budgetHeader, PREG_OFFSET_CAPTURE) ? $budgetHeader[0][1] : null;
                continue;
            }
            if ($start === null || $end === null || $end <= $start) {
                continue;
            }
            if (str_contains($line, "\f") || preg_match('/(?:Prepared by:|Noted by:|Approved by:|Leading Innovations, Transforming Lives)/i', $line)) {
                $flush();
                $start = $end = null;
                continue;
            }
            if (trim($line) === '') {
                $flush();
                continue;
            }
            $column = $this->clean(trim(mb_substr($line, $start, $end - $start, 'UTF-8'), " |\t"));
            if (preg_match('/^(?:PROJECT\s+TITLE|TITLE|ACTIVITY\s*\/?|[-_─]+)$/iu', $column)) {
                continue;
            }
            if ($column === '') {
                continue; // Other columns may wrap below the title in the same table row.
            }
            if (! $this->realTitle($column)) {
                $flush();
                continue;
            }
            $remainingColumns = trim(mb_substr($line, $end, null, 'UTF-8'));
            if ($title === [] && $remainingColumns === '') {
                continue; // Single-column page headings/signatures are not table rows.
            }
            // Some PDFs omit blank lines between table rows. A fresh full date or
            // budget in its own column marks a new row, not a wrapped title line.
            $date = $dateStart !== null ? mb_substr($line, $dateStart, $budgetStart !== null ? max(0, $budgetStart - $dateStart) : null, 'UTF-8') : '';
            $budget = $budgetStart !== null ? trim(mb_substr($line, $budgetStart, null, 'UTF-8')) : '';
            if ($title !== [] && $remainingColumns !== ''
                && (preg_match('/(?:\\b(?:January|February|March|April|May|June|July|August|September|October|November|December)\\b|\\d{1,4}[\\/\\-]\\d{1,2}[\\/\\-]\\d{1,4})/i', $date)
                    || preg_match('/^(?:₱|PHP|P)?\\s*\\d[\\d,]*(?:\\.\\d{2})?$/i', $budget))) {
                $flush();
            }
            $title[] = $column;
        }
        $flush();

        return $entries;
    }
}

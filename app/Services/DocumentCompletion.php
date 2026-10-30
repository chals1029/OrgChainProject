<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Detects unchanged official templates and known unfinished Word placeholders. */
class DocumentCompletion
{
    private const MAX_XML_BYTES = 8 * 1024 * 1024;

    public function issue(string $path, string $name, array $requirement, string $type): ?string
    {
        $sourceDir = $type === 'local_off_campus'
            ? (is_dir(base_path('Local Off Campus')) ? base_path('Local Off Campus') : storage_path('Template/Creation Of Activties/Off Campus'))
            : (is_dir(base_path('In Campus')) ? base_path('In Campus') : storage_path('Template/Creation Of Activties/In Campus Activities'));
        $source = $sourceDir.DIRECTORY_SEPARATOR.$requirement['source_file'];
        $placeholder = match ($requirement['key']) {
            'project_proposal' => '/\binsert\s+text\s+here\b/i',
            'resolution' => '/\binsert\s+table\s+here\b/i',
            default => null,
        };

        return $this->issueAgainstTemplate($path, $name, $source, $placeholder);
    }

    public function issueAgainstTemplate(string $path, string $name, string $source, ?string $placeholder = null, bool $compareText = false): ?string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (is_file($source) && hash_file('sha256', $path) === hash_file('sha256', $source)) {
            return 'This is the unchanged official template. Complete it before submitting.';
        }
        if ($extension !== 'docx' && mime_content_type($path) !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return null;
        }

        $document = $this->wordDocument($path);
        if ($document === null) {
            return 'This Word document is unreadable. Upload a valid, completed DOCX file.';
        }
        if (trim($document['text']) === '' && $document['media'] === []) {
            return 'This Word document is empty. Upload a completed document.';
        }
        if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'docx' && is_file($source)) {
            $template = $this->wordDocument($source);
            $sameContent = $template !== null && ($compareText
                ? $this->normalizedText($document['text']) === $this->normalizedText($template['text'])
                : $this->withoutRevisionIds($document['xml']) === $this->withoutRevisionIds($template['xml']));
            if ($sameContent && $document['media'] === $template['media']) {
                return 'This is the unchanged official template. Complete it before submitting.';
            }
        }

        if ($placeholder && preg_match($placeholder, $document['text'])) {
            return 'This Word document still contains an unfilled template placeholder. Complete it before submitting.';
        }

        return null;
    }

    /** @return array{xml: string, text: string, media: list<string>}|null */
    private function wordDocument(string $path): ?array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return null;
        }
        try {
            if ($zip->locateName('[Content_Types].xml') === false) {
                return null;
            }
            $xml = $zip->getFromName('word/document.xml', self::MAX_XML_BYTES + 1);
            $media = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                if (is_array($entry) && str_starts_with($entry['name'], 'word/media/') && ! str_ends_with($entry['name'], '/')) {
                    $media[] = $entry['size'].':'.$entry['crc'];
                }
            }
            sort($media);
        } finally {
            $zip->close();
        }
        if (! is_string($xml) || $xml === '' || strlen($xml) > self::MAX_XML_BYTES) {
            return null;
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($xml, LIBXML_NONET)) {
                return null;
            }
            $xpath = new DOMXPath($document);
            $paragraphs = [];
            foreach ($xpath->query('//*[local-name()="p"]') as $paragraph) {
                $text = '';
                foreach ($xpath->query('.//*[local-name()="t"]', $paragraph) as $node) {
                    $text .= $node->textContent;
                }
                $paragraphs[] = $text;
            }

            return ['xml' => $xml, 'text' => implode("\n", $paragraphs), 'media' => $media];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function normalizedText(string $text): string
    {
        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    }

    private function withoutRevisionIds(string $xml): string
    {
        return preg_replace('/\s+w:rsid\w+="[^"]*"/', '', $xml) ?? $xml;
    }
}

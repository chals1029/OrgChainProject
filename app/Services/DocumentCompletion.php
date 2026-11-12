<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Validates that uploaded Word documents can be read. */
class DocumentCompletion
{
    private const MAX_XML_BYTES = 8 * 1024 * 1024;

    public function issue(string $path, string $name, array $requirement, string $type): ?string
    {
        $sourceDir = $type === 'local_off_campus'
            ? (is_dir(base_path('Local Off Campus')) ? base_path('Local Off Campus') : storage_path('Template/Creation Of Activties/Off Campus'))
            : (is_dir(base_path('In Campus')) ? base_path('In Campus') : storage_path('Template/Creation Of Activties/In Campus Activities'));
        $source = $sourceDir.DIRECTORY_SEPARATOR.$requirement['source_file'];

        return $this->issueAgainstTemplate($path, $name, $source);
    }

    public function issueAgainstTemplate(string $path, string $name, string $source, ?string $placeholder = null, bool $compareText = false): ?string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($extension !== 'docx' && mime_content_type($path) !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return null;
        }

        $document = $this->wordDocument($path);
        if ($document === null) {
            return 'This Word document is unreadable. Upload a valid, completed DOCX file.';
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

}

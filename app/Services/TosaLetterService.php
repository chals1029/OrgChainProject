<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

final class TosaLetterService
{
    /** Generate a Word document from the corresponding original letter template. */
    public function generate(string $decision, array $notice): string
    {
        $filename = match ($decision) {
            'qualified' => 'qualified.docx',
            'not_qualified' => 'not-qualified.docx',
            default => throw new RuntimeException('Invalid TOSA decision.'),
        };
        $name = trim((string) ($notice['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('The published applicant name is unavailable.');
        }
        $template = resource_path('tosa/letters/'.$filename);
        $temporary = tempnam(sys_get_temp_dir(), 'tosa-letter-');
        if ($temporary === false) {
            throw new RuntimeException('The TOSA letter could not be prepared.');
        }
        try {
            if (! copy($template, $temporary)) {
                throw new RuntimeException('The TOSA letter template is unavailable.');
            }
            $zip = new ZipArchive;
            if ($zip->open($temporary) !== true) {
                throw new RuntimeException('The TOSA letter template is invalid.');
            }
            try {
                $xml = $zip->getFromName('word/document.xml');
                if ($xml === false || substr_count($xml, '{{NAME}}') !== 2 || substr_count($xml, '{{PROGRAM}}') !== 1) {
                    throw new RuntimeException('The TOSA letter template is incomplete.');
                }
                $escape = fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml = str_replace(['{{NAME}}', '{{PROGRAM}}'], [$escape($name), $escape(trim((string) ($notice['program'] ?? '')))], $xml);
                if (! $zip->addFromString('word/document.xml', $xml)) {
                    throw new RuntimeException('The TOSA letter could not be written.');
                }
            } finally {
                if (! $zip->close()) {
                    throw new RuntimeException('The TOSA letter could not be completed.');
                }
            }
            $contents = file_get_contents($temporary);
            if ($contents === false) {
                throw new RuntimeException('The TOSA letter could not be read.');
            }

            return $contents;
        } finally {
            @unlink($temporary);
        }
    }
}

<?php

namespace App\Services;

use App\Models\OfficeTemplate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class TosaLetterService
{
    public const TEMPLATES = [
        'qualified' => ['title' => 'Qualified Decision Letter', 'filename' => 'qualified.docx'],
        'not_qualified' => ['title' => 'Not Qualified Decision Letter', 'filename' => 'not-qualified.docx'],
    ];

    public function resolveTemplatePath(string $decision): string
    {
        $definition = self::TEMPLATES[$decision] ?? throw new RuntimeException('Invalid TOSA decision.');
        $template = null;
        try {
            $template = OfficeTemplate::query()->where('category', 'TOSA_LETTER')
                ->where('name', $definition['title'])->latest()->orderByDesc('id')->first();
        } catch (\Throwable) {
            $template = null;
        }
        $path = $template?->file_path;
        if (is_string($path) && $path !== '' && strpbrk($path, "\\:\0") === false
            && ! in_array('..', explode('/', $path), true) && ! str_starts_with($path, '/')) {
            $disk = Storage::disk('public');
            $root = realpath($disk->path(''));
            $absolute = realpath($disk->path($path));
            if ($root && $absolute && str_starts_with($absolute, $root.DIRECTORY_SEPARATOR)
                && $disk->exists($path) && $this->validateTemplateFile($absolute)) {
                return $absolute;
            }
        }

        return resource_path('tosa/letters/'.$definition['filename']);
    }

    public function validateTemplateFile(string $filePath): bool
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return false;
        }
        $zip = new ZipArchive;
        if ($zip->open($filePath, ZipArchive::RDONLY) !== true) {
            return false;
        }
        try {
            $xml = $zip->getFromName('word/document.xml');

            return $xml !== false && substr_count($xml, '{{NAME}}') === 2 && substr_count($xml, '{{PROGRAM}}') === 1;
        } finally {
            $zip->close();
        }
    }

    public function templatesSummary(): array
    {
        $templates = [];
        foreach (self::TEMPLATES as $key => $definition) {
            $path = $this->resolveTemplatePath($key);
            $url = route('office.tosa.letters.template.download', ['decision' => $key]);
            $templates[] = [
                'key' => $key,
                'title' => $definition['title'],
                'description' => 'Decision letter template for '.$definition['title'].'.',
                'filename' => $definition['filename'],
                'is_custom' => $path !== resource_path('tosa/letters/'.$definition['filename']),
                'download_url' => $url,
                'preview_url' => $url.'?preview=1',
            ];
        }

        return $templates;
    }

    /** Generate a Word document from the corresponding original letter template. */
    public function generate(string $decision, array $notice): string
    {
        $template = $this->resolveTemplatePath($decision);
        $name = trim((string) ($notice['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('The published applicant name is unavailable.');
        }
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
                $names = [$escape($name), $escape($this->formatSalutation($name))];
                $nameIndex = 0;
                $program = $escape(trim((string) ($notice['program'] ?? '')));
                $xml = preg_replace_callback('/\{\{NAME\}\}|\{\{PROGRAM\}\}/', function (array $match) use ($names, &$nameIndex, $program): string {
                    return $match[0] === '{{NAME}}' ? $names[$nameIndex++] : $program;
                }, $xml);
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

    private function formatSalutation(string $fullName): string
    {
        if (str_contains($fullName, ',')) {
            [$surname, $givenNames] = explode(',', $fullName, 2);
            $givenName = preg_split('/\s+/u', trim($givenNames))[0];
        } else {
            $parts = preg_split('/\s+/u', trim($fullName));
            $givenName = $parts[0];
            $surname = $parts[count($parts) - 1];
        }

        $femaleNames = ['LILIAN', 'MICHELLE', 'ALTHEA', 'ANA', 'LIZA', 'MIA', 'MARA', 'ISABEL', 'QUEENIE', 'MARIA', 'BEA', 'ANGEL', 'MARY', 'CHRISTINE'];
        $maleNames = ['CHARLES', 'BRANDON', 'MARK', 'JON', 'CARLO', 'KENJI', 'RAFAEL', 'PAOLO', 'NICO', 'LORENZO', 'JOHN', 'JOSE', 'MICHAEL'];
        $givenName = mb_strtoupper($givenName, 'UTF-8');
        $honorific = in_array($givenName, $femaleNames, true) ? 'Ms.' : (in_array($givenName, $maleNames, true) ? 'Mr.' : 'Candidate');

        return $honorific.' '.mb_convert_case(trim($surname), MB_CASE_TITLE, 'UTF-8');
    }
}

<?php

namespace App\Services;

use App\Models\OfficeTemplate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;

final class TosaRequirements
{
    public const REFERENCES = [
        'front_page' => [
            'id' => 1,
            'title' => 'Front Page',
            'filename' => 'Front Page.doc',
            'source_id' => 'source-tosa-front-page',
            'format' => 'DOC',
            'extensions' => ['doc'],
            'description' => 'Use the supplied front-page document for the TOSA package.',
            'icon' => 'file-earmark-word',
            'color' => 'blue',
        ],
        'guidelines' => [
            'id' => 2,
            'title' => 'Guidelines TOSA 2025',
            'filename' => 'Guidelines TOSA 2025.pdf',
            'source_id' => 'source-tosa-guidelines',
            'format' => 'PDF',
            'extensions' => ['pdf'],
            'description' => 'Official guidelines included in the supplied TOSA package.',
            'icon' => 'file-earmark-pdf',
            'color' => 'green',
        ],
        'application_form' => [
            'id' => 3,
            'title' => 'TOSA Application Form 2025',
            'filename' => 'TOSA Application Form 2025.docx',
            'source_id' => 'source-tosa-application',
            'format' => 'DOCX',
            'extensions' => ['docx'],
            'description' => 'Complete the supplied TOSA application form in Word format.',
            'icon' => 'file-earmark-word',
            'color' => 'violet',
        ],
        'computation' => [
            'id' => 4,
            'title' => 'TOSA COMPUTATION',
            'filename' => 'TOSA COMPUTATION.xlsx',
            'source_id' => 'source-tosa-computation',
            'format' => 'XLSX',
            'extensions' => ['xlsx'],
            'description' => 'Use the supplied TOSA computation workbook in Excel format.',
            'icon' => 'file-earmark-excel',
            'color' => 'green',
        ],
        'submission_guide' => [
            'id' => 5,
            'title' => 'TOSA Submission Guide',
            'filename' => 'TOSA Submission Guide.doc',
            'source_id' => 'source-tosa-submission-guide',
            'format' => 'DOC',
            'extensions' => ['doc'],
            'description' => 'Submission guide included in the supplied TOSA package.',
            'icon' => 'file-earmark-word',
            'color' => 'gold',
        ],
    ];

    public const SUBMISSIONS = [
        'table_of_contents' => [
            'code' => 'A',
            'title' => 'Table of Contents',
            'description' => 'Include a labeled cover and a table of contents following the submission guide order.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'list-ol',
            'color' => 'blue',
        ],
        'application_form' => [
            'code' => 'B',
            'title' => 'Completed TOSA Application Form',
            'description' => 'Download TOSA Application Form 2025.docx from the official references, fill it out with the candidate photo and acknowledgement, and obtain the candidate, chairperson and dean signatures. The guide calls this same document the Nomination Application Form. Upload the completed DOCX, not the blank template.',
            'extensions' => ['docx'],
            'format' => 'DOCX',
            'icon' => 'file-earmark-word',
            'color' => 'violet',
        ],
        'cv' => [
            'code' => 'C',
            'title' => 'Summary of Profile / Résumé / Curriculum Vitae',
            'description' => 'Submit the candidate profile, résumé or curriculum vitae.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'person-lines-fill',
            'color' => 'blue',
        ],
        'good_moral' => [
            'code' => 'D',
            'title' => 'Good Moral Character',
            'description' => 'Submit the candidate’s good moral certificate.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'shield-check',
            'color' => 'green',
        ],
        'academic' => [
            'code' => 'E',
            'title' => 'Academic Evidence',
            'description' => 'Include certified grades with GWA signed by the Campus Registrar, the authenticated list of academic semestral awards with evidence, and certified seminar/workshop and organization participation documents for the achievements claimed.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'mortarboard',
            'color' => 'orange',
        ],
        'leadership' => [
            'code' => 'F',
            'title' => 'Leadership Evidence',
            'description' => 'Include leadership positions and awards from freshman year to the present, with certified, identified and labeled certificate or award copies supporting the claims.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'award',
            'color' => 'violet',
        ],
        'social_responsibility' => [
            'code' => 'G',
            'title' => 'Social Responsibility Evidence',
            'description' => 'Include certified certificates supporting community involvement and civic activities, organized into non-school and school activities.',
            'extensions' => ['pdf'],
            'format' => 'PDF',
            'icon' => 'people',
            'color' => 'green',
        ],
    ];

    public static function mimeType(string $key): string
    {
        return self::formatMimeType(self::REFERENCES[$key]['format']);
    }

    public static function formatMimeType(string $format): string
    {
        return match (strtoupper($format)) {
            'DOC' => 'application/msword',
            'PDF' => 'application/pdf',
            'DOCX' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'XLSX' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }

    public static function validReferenceFile(string $key, string $path, ?string $name = null): bool
    {
        return self::validNativeFile(self::REFERENCES[$key]['extensions'] ?? [], $path, $name);
    }

    public static function validSubmissionFile(string $key, string $path, ?string $name = null): bool
    {
        return self::validNativeFile(self::SUBMISSIONS[$key]['extensions'] ?? [], $path, $name);
    }

    private static function validNativeFile(array $extensions, string $path, ?string $name): bool
    {
        $extension = strtolower(pathinfo($name ?? $path, PATHINFO_EXTENSION));
        if (! in_array($extension, $extensions, true) || ! is_file($path) || ! is_readable($path) || filesize($path) <= 0) {
            return false;
        }
        if ($extension === 'doc' || $extension === 'pdf') {
            return (new \finfo(FILEINFO_MIME_TYPE))->file($path) === self::formatMimeType($extension);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $entry = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
        $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($entry) !== false;
        $zip->close();

        return $valid;
    }

    public static function sourceDefinitions(): array
    {
        return array_map(fn (array $document): array => [
            'id' => $document['source_id'],
            'name' => $document['title'],
            'category' => 'TOSA',
            'source_path' => 'TOSA/'.$document['filename'],
            'description' => $document['description'],
            'icon' => $document['icon'],
            'color' => $document['color'],
        ], array_values(self::REFERENCES));
    }

    public static function resolveTemplate(string $key): ?array
    {
        $definition = self::REFERENCES[$key] ?? null;
        if (! $definition) {
            return null;
        }

        foreach (OfficeTemplate::query()->where('category', 'TOSA')->where('name', $definition['title'])
            ->latest()->orderByDesc('id')->get() as $template) {
            $resolved = self::uploadedTemplate($key, $template);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return self::bundledTemplate($key);
    }

    public static function uploadedTemplate(string $key, OfficeTemplate $template): ?array
    {
        $definition = self::REFERENCES[$key] ?? null;
        if (! $definition || $template->category !== 'TOSA' || $template->name !== $definition['title']) {
            return null;
        }
        $relative = $template->file_path;
        if (! is_string($relative) || (! str_starts_with($relative, 'tosa-templates/') && ! str_starts_with($relative, 'templates/'))
            || strpbrk($relative, "\\:\0") !== false || in_array('..', explode('/', $relative), true)) {
            return null;
        }
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($relative));
        $name = $template->original_name ?: basename($relative);
        if (! $root || ! $path || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)
            || ! in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $definition['extensions'], true)
            || ! self::validReferenceFile($key, $path)) {
            return null;
        }

        return ['id' => $template->id, 'path' => $path, 'name' => $name,
            'format' => $definition['format'], 'source' => 'uploaded', 'size' => $template->size];
    }

    public static function bundledTemplate(string $key): ?array
    {
        $definition = self::REFERENCES[$key] ?? null;
        if (! $definition) {
            return null;
        }
        $path = base_path('TOSA/'.$definition['filename']);
        if (! self::validReferenceFile($key, $path)) {
            return null;
        }

        return ['id' => $definition['source_id'], 'path' => $path, 'name' => $definition['filename'],
            'format' => $definition['format'], 'source' => 'bundled', 'size' => null];
    }

    public static function fileResponse(string $key, string $path, string $name, bool $preview): BinaryFileResponse
    {
        return self::privateFileResponse(self::REFERENCES[$key]['format'], $path, $name, $preview);
    }

    public static function submissionFileResponse(string $path, string $name, bool $preview): BinaryFileResponse
    {
        return self::privateFileResponse(pathinfo($path, PATHINFO_EXTENSION), $path, $name, $preview);
    }

    private static function privateFileResponse(string $format, string $path, string $name, bool $preview): BinaryFileResponse
    {
        $name = str_replace(["\r", "\n", '"', '/', '\\'], '_', $name);
        $response = response()->file($path, [
            'Content-Type' => self::formatMimeType($format),
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContentDisposition(
            $preview && strtoupper($format) === 'PDF'
                ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name !== '' ? $name : basename($path),
        );
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}

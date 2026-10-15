<?php

namespace Tests\Support;

use App\Models\OrgRenewalDocument;
use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Services\ApprovedPlanOfActivitiesService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

trait CreatesApprovedActivityPlan
{
    protected function approvedActivityPlan(string $organizationName, array $titles, ?OrgRenewalWindow $window = null): array
    {
        $window ??= OrgRenewalWindow::create([
            'academic_year' => '2026-2027', 'semester' => '1st Semester', 'is_open' => true,
            'required_docs' => OrgRenewalWindow::defaultRequiredDocs(),
        ]);
        $submission = OrgRenewalSubmission::create([
            'renewal_window_id' => $window->id, 'organization_name' => $organizationName,
            'status' => 'approved', 'submitted_at' => now(), 'reviewed_at' => now(),
        ]);
        $path = 'renewal-fixtures/'.Str::uuid().'/Attachment I.docx';
        Storage::disk('local')->put($path, $this->activityPlanDocx($titles));
        $document = OrgRenewalDocument::create([
            'submission_id' => $submission->id, 'doc_key' => 'plan_of_activities', 'title' => 'Plan of Activities',
            'file_path' => $path, 'file_name' => 'Attachment I.docx', 'review_status' => 'verified',
        ]);

        return ['window' => $window, 'submission' => $submission, 'document' => $document,
            'options' => app(ApprovedPlanOfActivitiesService::class)->options($organizationName)];
    }

    /** A real text PDF table, with explicit row spacing and a wrapped title column. */
    protected function activityPlanPdf(array $titles, bool $textReadable = true): string
    {
        $stream = '';
        $text = static function (int $x, int $y, string $value) use (&$stream): void {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
            $stream .= "BT /F1 10 Tf 1 0 0 1 {$x} {$y} Tm ({$escaped}) Tj ET\n";
        };
        if ($textReadable) {
            $text(40, 750, 'APPROVED PLAN OF ACTIVITIES');
            $text(40, 710, 'ACTIVITY/');
            $text(240, 710, 'CATEGORY');
            $text(420, 710, 'TARGET DATE');
            $text(40, 698, 'PROJECT TITLE');
            $text(40, 675, '1st Semester');
            $y = 645;
            foreach ($titles as $title) {
                $text(240, $y, 'Extension');
                $text(420, $y, 'October 2026');
                foreach (explode("\n", $title) as $line) {
                    $text(40, $y, $line);
                    $y -= 12;
                }
                $y -= 24;
            }
            $text(40, 80, 'Prepared by:');
            $text(40, 60, 'Student Organization President');
        }
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    protected function activityPlanDocx(array $titles): string
    {
        $cell = static function (string $text): string {
            $paragraphs = '';
            foreach (explode("\n", $text) as $paragraph) {
                $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($paragraph, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r></w:p>';
            }
            return '<w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr>'.$paragraphs.'</w:tc>';
        };
        $rows = '<w:tr>'.$cell("ACTIVITY/\nPROJECT TITLE").$cell('CATEGORY').$cell('TARGET DATE').'</w:tr>';
        $rows .= '<w:tr>'.$cell('1st Semester').$cell('').$cell('').'</w:tr>';
        foreach ($titles as $title) {
            $rows .= '<w:tr>'.$cell($title).$cell('Community Extension').$cell('October 2026').'</w:tr>';
        }
        $rows .= '<w:tr>'.$cell('').$cell('').$cell('').'</w:tr>';
        $xml = '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Attachment I — Plan of Activities</w:t></w:r></w:p><w:tbl><w:tblPr><w:tblW w:w="5400" w:type="dxa"/></w:tblPr><w:tblGrid><w:gridCol w:w="1800"/><w:gridCol w:w="1800"/><w:gridCol w:w="1800"/></w:tblGrid>'.$rows.'</w:tbl><w:sectPr/></w:body></w:document>';
        $temporary = tempnam(sys_get_temp_dir(), 'activity-plan-');
        $zip = new ZipArchive();
        try {
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create the activity plan test fixture.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml', $xml);
            $zip->close();
            return file_get_contents($temporary);
        } finally {
            unlink($temporary);
        }
    }
}

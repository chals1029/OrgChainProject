<?php

namespace Tests\Unit;

use App\Services\ReceiptDocumentValidator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class ReceiptDocumentValidatorTest extends TestCase
{
    private function docx(string $text, string $name = 'receipt.docx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'orgchain-docx-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $safe = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.$safe.'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    public function test_receipt_like_docx_passes_preflight(): void
    {
        $result = app(ReceiptDocumentValidator::class)->validate($this->docx('Official Receipt Sales Invoice Total PHP 300.00 Reference No. 123456 Date 09/20/2026 Paid to Savemore'));
        $this->assertTrue($result['valid']);
        $this->assertSame('docx', $result['kind']);
        $this->assertGreaterThanOrEqual(60, $result['confidence']);
    }

    public function test_activity_template_docx_is_rejected_as_not_a_receipt(): void
    {
        $result = app(ReceiptDocumentValidator::class)->validate($this->docx('Activity Proposal Programme Schedule of Activities Objectives Venue Participants Budget Proposal'));
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('does not look like a receipt', $result['message']);
    }

    public function test_malformed_zip_named_docx_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('fake.docx', 'not a zip document');
        $result = app(ReceiptDocumentValidator::class)->validate($file);
        $this->assertFalse($result['valid']);
        $this->assertSame('docx', $result['kind']);
    }
}

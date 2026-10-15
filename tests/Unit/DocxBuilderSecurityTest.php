<?php

namespace Tests\Unit;

use App\Services\DocxBuilder;
use Tests\TestCase;
use ZipArchive;

class DocxBuilderSecurityTest extends TestCase
{
    public function test_build_returns_valid_docx_without_leaving_base_or_zip_temp_files(): void
    {
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'docx*') ?: [];
        $binary = (new DocxBuilder())->title('Synthetic security document')->p('Private synthetic content')->build();
        $after = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'docx*') ?: [];
        $this->assertSame([], array_values(array_diff($after, $before)));
        $path = tempnam(sys_get_temp_dir(), 'security-document-');
        $this->assertNotFalse($path);
        try {
            file_put_contents($path, $binary);
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path));
            try {
                $document = $zip->getFromName('word/document.xml');
                $this->assertIsString($document);
                $this->assertStringContainsString('Private synthetic content', $document);
                $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }
}

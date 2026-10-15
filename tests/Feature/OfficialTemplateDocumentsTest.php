<?php

namespace Tests\Feature;

use App\Models\OfficeTemplate;
use App\Services\TosaRequirements;
use Illuminate\Support\Facades\DB;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OfficialTemplateDocumentsTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::beginTransaction();
        OfficeTemplate::query()->where('category', 'TOSA')->delete();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }


    public function test_source_template_downloads_preserve_the_original_file_type_and_name(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get(route('office.updates.templates.document', 'source-in-campus-project-proposal'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('content-disposition', 'attachment; filename="3. Project Proposal.docx"');

        $this->get(route('office.updates.templates.document', 'source-waste-policy-compliance'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertDownload('Waste-Policy-Compliance-Form-2026 (1).docx');

        foreach (TosaRequirements::REFERENCES as $key => $document) {
            $download = $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get(route('office.updates.templates.document', $document['source_id']))->assertOk()
                ->assertHeader('Content-Type', TosaRequirements::mimeType($key))->assertDownload($document['filename']);
            $this->assertSame(hash_file('sha256', base_path('TOSA/'.$document['filename'])),
                hash_file('sha256', $download->baseResponse->getFile()->getPathname()));
            $this->assertTrue($download->headers->hasCacheControlDirective('private'));
            $this->assertTrue($download->headers->hasCacheControlDirective('no-store'));
            $download->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_source_template_previews_keep_the_actual_file_type_and_disposition(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get(route('office.updates.templates.document', [
                'id' => 'source-in-campus-project-proposal',
                'preview' => 1,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('content-disposition', 'inline; filename="3. Project Proposal.docx"');

        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get(route('office.updates.templates.document', [
                'id' => 'source-waste-policy-compliance',
                'preview' => 1,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="Waste-Policy-Compliance-Form-2026 (1).pdf"');
        foreach (TosaRequirements::REFERENCES as $key => $document) {
            $preview = $this->get(route('office.updates.templates.document', ['id' => $document['source_id'], 'preview' => 1]))
                ->assertOk()->assertHeader('Content-Type', TosaRequirements::mimeType($key));
            if ($document['format'] === 'PDF') {
                $this->assertStringStartsWith('inline;', $preview->headers->get('Content-Disposition'));
            } else {
                $preview->assertDownload($document['filename']);
            }
        }
    }
}

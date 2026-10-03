<?php

namespace Tests\Feature;

use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OfficialTemplateDocumentsTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_official_template_page_uses_the_supplied_source_catalogue(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk/updates')
            ->assertOk()
            ->assertSee('Project Proposal', false)
            ->assertSee('Budget Proposal', false)
            ->assertSee('Local Off-Campus Activity Request', false)
            ->assertSee('TOSA Application Form 2025', false)
            ->assertSee('Accomplishment &amp; Financial Report', false)
            ->assertDontSee('Attendance Sheet Template', false)
            ->assertDontSee('Budget Allocation Sheet', false);
    }

    public function test_source_template_downloads_preserve_the_original_file_type_and_name(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get(route('office.updates.templates.document', 'source-in-campus-project-proposal'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('content-disposition', 'attachment; filename="3. Project Proposal.docx"');

        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get(route('office.updates.templates.document', 'source-tosa-computation'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename="TOSA COMPUTATION.xlsx"');
    }

    public function test_source_template_previews_are_inline_and_use_the_real_document_or_rendered_legacy_preview(): void
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
    }
}

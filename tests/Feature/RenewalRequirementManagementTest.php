<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgRenewalDocument;
use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Models\StudentOrganization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class RenewalRequirementManagementTest extends TestCase
{
    use UsesLaragonDatabase;

    private const CONNECTIONS = ['mysql', 'orgchain'];

    private OfficeUser $oso;
    private OfficeUser $so;
    private StudentOrganization $organization;
    private OrgRenewalWindow $window;
    private string $suffix;
    private array $docA;
    private array $docB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (self::CONNECTIONS as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Storage::fake('public');

        $this->oso = $this->officeUser('oso');
        $this->so = $this->officeUser('so');

        $this->suffix = Str::lower(Str::random(10));
        $this->organization = StudentOrganization::query()->create([
            'name' => 'Renewal Requirement Fixture '.$this->suffix,
            'short_name' => 'RQF-'.$this->suffix,
            'college' => 'Renewal Requirement Test College',
            'is_active' => true,
            'is_qualified_for_renewal' => true,
        ]);

        $this->docA = $this->definition('a', withPdf: true);
        $this->docB = $this->definition('b', withPdf: false);
        $this->window = $this->renewalWindow([$this->docA, $this->docB]);
    }

    protected function tearDown(): void
    {
        try {
            foreach (self::CONNECTIONS as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_oso_creates_requirement_with_internal_key_and_edits_only_title_and_description(): void
    {
        $code = 'ANX'.Str::upper(Str::random(8));
        $templateBytes = 'new requirement template '.Str::random(16);

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), [
                'window_id' => $this->window->id,
                'code' => $code,
                'title' => 'Fixture Created Requirement',
                'description' => 'Two signed copies are required.',
                'document' => UploadedFile::fake()->createWithContent('created-template.docx', $templateBytes),
            ])
            ->assertSessionHasNoErrors();

        $definitions = $this->definitions();
        $this->assertCount(3, $definitions);
        $created = $this->definitionByLabel($code);
        $this->assertNotNull($created);
        $key = $created['key'];
        $this->assertStringStartsWith(Str::slug($code), $key);
        $this->assertNotSame(Str::slug($code), $key);
        $this->assertSame('Fixture Created Requirement', $created['title']);
        $this->assertSame('Two signed copies are required.', $created['description']);
        $this->assertSame('created-template.docx', $created['template_name']);
        $this->assertSame($templateBytes, Storage::disk('public')->get($created['template_path']));
        $this->assertEquals($this->docA, $this->definitionByKey($this->docA['key']));
        $this->assertEquals($this->docB, $this->definitionByKey($this->docB['key']));

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->patch(route('office.renewal.requirements.update', ['docKey' => $key]), [
                'window_id' => $this->window->id,
                'title' => 'Fixture Renamed Requirement',
                'description' => 'One notarized copy is required.',
            ])
            ->assertSessionHasNoErrors();

        $edited = $this->definitionByKey($key);
        $this->assertSame($code, $edited['attachment_label']);
        $this->assertSame('Fixture Renamed Requirement', $edited['title']);
        $this->assertSame('One notarized copy is required.', $edited['description']);
        $this->assertSame($created['template_path'], $edited['template_path']);
        $this->assertSame($created['template_name'], $edited['template_name']);
        $this->assertSame($this->window->id, (int) OrgRenewalWindow::query()->max('id'));

        $beforeForgery = $this->definitions();
        foreach ([['code' => 'FORGED'], ['key' => 'forged_key']] as $forged) {
            $this->actingAs($this->oso, 'office')
                ->from(route('office.renewal'))
                ->patch(route('office.renewal.requirements.update', ['docKey' => $key]), array_merge([
                    'window_id' => $this->window->id,
                    'title' => 'Forged Title',
                    'description' => 'Forged description.',
                ], $forged))
                ->assertSessionHasErrors(array_key_first($forged));
        }
        $this->assertSame($beforeForgery, $this->definitions());
    }

    public function test_duplicate_code_and_requirement_limit_are_rejected_without_changes(): void
    {
        $before = $this->definitions();

        foreach ([Str::lower($this->docA['attachment_label']), Str::upper($this->docB['attachment_label'])] as $duplicate) {
            $this->actingAs($this->oso, 'office')
                ->from(route('office.renewal'))
                ->post(route('office.renewal.requirements.store'), $this->storePayload(['code' => $duplicate]))
                ->assertSessionHasErrors('code');
        }
        $this->assertSame($before, $this->definitions());

        $full = $this->renewalWindow(array_map(
            fn (int $i): array => ['key' => 'limit_'.$i.'_'.$this->suffix, 'title' => 'Limit '.$i, 'attachment_label' => 'LIMIT '.$i],
            range(1, 30),
        ));
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['window_id' => $full->id]))
            ->assertSessionHasErrors();
        $this->assertCount(30, $full->fresh()->required_docs);
    }

    public function test_invalid_stale_and_unauthorized_mutations_leave_requirements_unchanged(): void
    {
        $keyA = $this->docA['key'];
        $invalidStores = [
            'window_id' => ['window_id' => null],
            'code' => ['code' => str_repeat('C', 81)],
            'title' => ['title' => ''],
            'description' => ['description' => str_repeat('d', 5001)],
            'document' => ['document' => UploadedFile::fake()->createWithContent('notes.txt', 'plain text')],
        ];
        foreach ($invalidStores as $field => $override) {
            $this->actingAs($this->oso, 'office')
                ->from(route('office.renewal'))
                ->post(route('office.renewal.requirements.store'), $this->storePayload($override))
                ->assertSessionHasErrors($field);
        }
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['document' => UploadedFile::fake()->create('huge.pdf', 20481, 'application/pdf')]))
            ->assertSessionHasErrors('document');
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), array_diff_key($this->storePayload(), ['document' => true]))
            ->assertSessionHasErrors('document');

        $this->actingAs($this->oso, 'office')
            ->patch(route('office.renewal.requirements.update', ['docKey' => 'unknown_'.$this->suffix]), [
                'window_id' => $this->window->id,
                'title' => 'Unknown',
            ])
            ->assertNotFound();
        $this->actingAs($this->oso, 'office')
            ->delete(route('office.renewal.requirements.destroy', ['docKey' => 'unknown_'.$this->suffix]), ['window_id' => $this->window->id])
            ->assertNotFound();

        $mutations = fn (int $windowId): array => [
            fn () => $this->post(route('office.renewal.requirements.store'), $this->storePayload(['window_id' => $windowId])),
            fn () => $this->patch(route('office.renewal.requirements.update', ['docKey' => $keyA]), ['window_id' => $windowId, 'title' => 'Mutated', 'description' => 'Mutated.']),
            fn () => $this->delete(route('office.renewal.requirements.destroy', ['docKey' => $keyA]), ['window_id' => $windowId]),
            fn () => $this->post(route('office.renewal.requirements.template'), [
                'window_id' => $windowId,
                'doc_key' => $keyA,
                'document' => UploadedFile::fake()->createWithContent('mutated.pdf', '%PDF mutated'),
            ]),
            fn () => $this->post(route('office.renewal.window'), $this->windowPayload(['window_id' => $windowId, 'notes' => 'Mutated notes'])),
        ];

        $windowCount = OrgRenewalWindow::query()->count();
        $before = $this->window->fresh()->getAttributes();
        foreach ([$this->so, $this->officeUser('sdo')] as $unauthorized) {
            foreach ($mutations($this->window->id) as $mutation) {
                $this->actingAs($unauthorized, 'office')->from(route('office.renewal'));
                $mutation()->assertForbidden();
            }
        }
        $this->assertEquals($before, $this->window->fresh()->getAttributes());
        $this->assertSame($windowCount, OrgRenewalWindow::query()->count());

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.window'), $this->windowPayload(['required_docs' => [['key' => 'bulk', 'title' => 'Bulk']]]))
            ->assertSessionHasErrors('required_docs');
        $this->assertEquals($before, $this->window->fresh()->getAttributes());

        $stale = $this->window;
        $current = $this->renewalWindow([$this->docA, $this->docB], 'Current '.$this->suffix);
        $staleBefore = $stale->fresh()->getAttributes();
        $currentBefore = $current->fresh()->getAttributes();
        $windowCount = OrgRenewalWindow::query()->count();
        foreach ($mutations($stale->id) as $mutation) {
            $this->actingAs($this->oso, 'office')->from(route('office.renewal'));
            $mutation()->assertSessionHasErrors('renewal');
        }
        $this->assertEquals($staleBefore, $stale->fresh()->getAttributes());
        $this->assertEquals($currentBefore, $current->fresh()->getAttributes());
        $this->assertSame($windowCount, OrgRenewalWindow::query()->count());
        Storage::disk('public')->assertExists([$this->docA['template_path'], $this->docA['pdf_path'], $this->docB['template_path']]);
    }

    public function test_template_replacement_serves_new_file_and_preserves_signed_uploads(): void
    {
        $keyA = $this->docA['key'];
        $submission = $this->submission();
        $signed = $this->document($submission, $keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $signedBytes = Storage::disk('public')->get($signed->file_path);

        $this->assertSame(
            Storage::disk('public')->get($this->docA['pdf_path']),
            $this->body($this->requirementFile($keyA, 'preview')->assertOk()),
        );
        $originalDownload = $this->requirementFile($keyA, 'download')->assertOk()->assertDownload($this->docA['template_name']);
        $this->assertSame(Storage::disk('public')->get($this->docA['template_path']), $this->body($originalDownload));

        $replacementBytes = 'replacement template bytes '.Str::random(16);
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.template'), [
                'window_id' => $this->window->id,
                'doc_key' => $keyA,
                'document' => UploadedFile::fake()->createWithContent('replacement-a.docx', $replacementBytes),
            ])
            ->assertSessionHasNoErrors();

        $replaced = $this->definitionByKey($keyA);
        $this->assertSame($this->docA['attachment_label'], $replaced['attachment_label']);
        $this->assertSame($this->docA['title'], $replaced['title']);
        $this->assertSame($this->docA['description'], $replaced['description']);
        $this->assertSame('replacement-a.docx', $replaced['template_name']);
        $this->assertNotSame($this->docA['template_path'], $replaced['template_path']);
        $this->assertSame($replacementBytes, Storage::disk('public')->get($replaced['template_path']));
        $this->assertEmpty($replaced['pdf_path'] ?? null);
        $this->assertEquals($this->docB, $this->definitionByKey($this->docB['key']));
        Storage::disk('public')->assertExists($this->docA['template_path']);

        $this->assertSame($replacementBytes, $this->body($this->requirementFile($keyA, 'preview')->assertOk()));
        $download = $this->requirementFile($keyA, 'download')->assertOk()->assertDownload('replacement-a.docx');
        $this->assertSame($replacementBytes, $this->body($download));

        $signedAfter = $signed->fresh();
        $this->assertSame($signed->file_path, $signedAfter->file_path);
        $this->assertSame($signed->file_name, $signedAfter->file_name);
        $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $signedAfter->review_status);
        $this->assertSame($this->oso->id, (int) $signedAfter->reviewed_by);
        $this->assertNotNull($signedAfter->reviewed_at);
        $this->assertSame($signedBytes, Storage::disk('public')->get($signedAfter->file_path));
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_requirement_files_are_limited_to_oso_and_so_and_missing_files_are_not_found(): void
    {
        $keyB = $this->docB['key'];
        $templateB = Storage::disk('public')->get($this->docB['template_path']);

        foreach ([$this->oso, $this->so] as $allowed) {
            $this->assertSame($templateB, $this->body($this->requirementFile($keyB, 'preview', $allowed)->assertOk()));
            $this->requirementFile($keyB, 'download', $allowed)->assertOk()->assertDownload($this->docB['template_name']);
        }
        $sdo = $this->officeUser('sdo');
        $this->requirementFile($keyB, 'preview', $sdo)->assertForbidden();
        $this->requirementFile($keyB, 'download', $sdo)->assertForbidden();

        $this->requirementFile('unknown_'.$this->suffix, 'preview')->assertNotFound();
        $this->requirementFile('unknown_'.$this->suffix, 'download')->assertNotFound();

        Storage::disk('public')->delete($this->docB['template_path']);
        $this->requirementFile($keyB, 'preview')->assertNotFound();
        $this->requirementFile($keyB, 'download')->assertNotFound();
    }

    public function test_legacy_replaced_default_template_ignores_stale_original_pdf_while_original_pair_still_previews_pdf(): void
    {
        $default = collect(OrgRenewalWindow::defaultRequiredDocs())->firstWhere('key', 'commitment_letter');
        $disk = Storage::disk('public');
        $originalPdfBytes = '%PDF-1.4 stale original attachment A '.$this->suffix;
        $originalWordBytes = 'original attachment A word '.$this->suffix;
        $disk->put($default['pdf_path'], $originalPdfBytes);
        $disk->put($default['template_path'], $originalWordBytes);

        $currentPath = 'renewal-templates/legacy-replacement-'.$this->suffix.'.docx';
        $currentBytes = 'legacy replacement template '.Str::random(16);
        $disk->put($currentPath, $currentBytes);
        $legacyDefinition = array_merge($default, [
            'template_path' => $currentPath,
            'template_name' => 'Legacy Replacement Attachment A.docx',
        ]);
        $legacyWindow = $this->renewalWindow([$legacyDefinition]);
        $submission = $this->submission('submitted', $legacyWindow);
        $signed = $this->document($submission, 'commitment_letter', OrgRenewalDocument::REVIEW_VERIFIED);
        $signedBytes = $disk->get($signed->file_path);

        foreach (['preview', 'download'] as $mode) {
            $response = $this->requirementFile('commitment_letter', $mode, window: $legacyWindow)->assertOk();
            $this->assertSame($currentBytes, $this->body($response));
            $this->assertNotSame($originalPdfBytes, $this->body($response));
        }
        $this->requirementFile('commitment_letter', 'download', window: $legacyWindow)
            ->assertDownload('Legacy Replacement Attachment A.docx');

        $this->assertEquals([$legacyDefinition], $legacyWindow->fresh()->required_docs);
        $signedAfter = $signed->fresh();
        $this->assertSame($signed->file_path, $signedAfter->file_path);
        $this->assertSame($signed->file_name, $signedAfter->file_name);
        $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $signedAfter->review_status);
        $this->assertSame($signedBytes, $disk->get($signedAfter->file_path));
        $disk->assertExists([$default['pdf_path'], $default['template_path'], $currentPath]);

        $pairedWindow = $this->renewalWindow([$default]);
        $strippedPair = $default;
        unset($strippedPair['pdf_path']);
        $strippedWindow = $this->renewalWindow([$strippedPair]);
        foreach ([$pairedWindow, $strippedWindow] as $originalWindow) {
            $preview = $this->requirementFile('commitment_letter', 'preview', window: $originalWindow)->assertOk();
            $this->assertSame($originalPdfBytes, $this->body($preview));
            $download = $this->requirementFile('commitment_letter', 'download', window: $originalWindow)
                ->assertOk()
                ->assertDownload($default['template_name']);
            $this->assertSame($originalWordBytes, $this->body($download));
        }
        $this->assertEquals([$default], $pairedWindow->fresh()->required_docs);
        $this->assertEquals([$strippedPair], $strippedWindow->fresh()->required_docs);
    }

    public function test_removal_preserves_submitted_files_keeps_last_requirement_and_never_reuses_keys(): void
    {
        $keyA = $this->docA['key'];
        $keyB = $this->docB['key'];
        $submission = $this->submission();
        $signed = $this->document($submission, $keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $signedBytes = Storage::disk('public')->get($signed->file_path);

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->delete(route('office.renewal.requirements.destroy', ['docKey' => $keyA]), ['window_id' => $this->window->id])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->definitionByKey($keyA));
        $this->assertEquals([$this->docB], $this->definitions());
        $signedAfter = $signed->fresh();
        $this->assertNotNull($signedAfter);
        $this->assertSame($signed->file_path, $signedAfter->file_path);
        $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $signedAfter->review_status);
        $this->assertSame($signedBytes, Storage::disk('public')->get($signed->file_path));
        Storage::disk('public')->assertExists([$this->docA['template_path'], $this->docA['pdf_path']]);

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->delete(route('office.renewal.requirements.destroy', ['docKey' => $keyB]), ['window_id' => $this->window->id])
            ->assertSessionHasErrors('renewal');
        $this->assertEquals([$this->docB], $this->definitions());

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['code' => $this->docA['attachment_label']]))
            ->assertSessionHasNoErrors();
        $recreatedA = $this->definitionByLabel($this->docA['attachment_label']);
        $this->assertNotNull($recreatedA);
        $this->assertNotSame($keyA, $recreatedA['key']);

        $code = 'RMV'.Str::upper(Str::random(8));
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['code' => $code]))
            ->assertSessionHasNoErrors();
        $firstKey = $this->definitionByLabel($code)['key'];

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->delete(route('office.renewal.requirements.destroy', ['docKey' => $firstKey]), ['window_id' => $this->window->id])
            ->assertSessionHasNoErrors();
        $this->assertNull($this->definitionByKey($firstKey));

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['code' => $code]))
            ->assertSessionHasNoErrors();
        $secondKey = $this->definitionByLabel($code)['key'];
        $this->assertNotSame($firstKey, $secondKey);
        $this->assertNotSame($keyA, $secondKey);
        $this->assertNotSame($recreatedA['key'], $secondKey);
        $this->assertSame($signed->doc_key, $signed->fresh()->doc_key);
    }

    public function test_window_settings_preserve_metadata_and_period_switch_starts_a_clean_window(): void
    {
        $definitions = $this->definitions();
        $approved = $this->submission('approved');
        $this->document($approved, $this->docA['key'], OrgRenewalDocument::REVIEW_VERIFIED);
        $windowCount = OrgRenewalWindow::query()->count();

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.window'), $this->windowPayload([
                'notes' => 'Same period notes',
                'instructions' => 'Same period instructions',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($windowCount, OrgRenewalWindow::query()->count());
        $sameWindow = $this->window->fresh();
        $this->assertSame('Same period notes', $sameWindow->notes);
        $this->assertSame('Same period instructions', $sameWindow->instructions);
        $this->assertDefinitionsPreserved($definitions, $sameWindow->required_docs);

        $oldYear = $sameWindow->academic_year;
        $oldSemester = $sameWindow->semester;
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.window'), $this->windowPayload([
                'semester' => 'Next '.$this->suffix,
                'notes' => 'Next period notes',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($windowCount + 1, OrgRenewalWindow::query()->count());
        $newWindow = OrgRenewalWindow::query()->latest('id')->first();
        $this->assertGreaterThan($this->window->id, $newWindow->id);
        $this->assertSame('Next '.$this->suffix, $newWindow->semester);
        $this->assertSame('Next period notes', $newWindow->notes);
        $this->assertDefinitionsPreserved($definitions, $newWindow->required_docs);
        $this->assertSame(0, $newWindow->submissions()->count());

        $oldWindow = $this->window->fresh();
        $this->assertSame($oldYear, $oldWindow->academic_year);
        $this->assertSame($oldSemester, $oldWindow->semester);
        $this->assertSame('Same period notes', $oldWindow->notes);
        $this->assertDefinitionsPreserved($definitions, $oldWindow->required_docs);
        $approvedAfter = $approved->fresh();
        $this->assertSame('approved', $approvedAfter->status);
        $this->assertSame($this->window->id, (int) $approvedAfter->renewal_window_id);
        $this->assertSame(1, OrgRenewalDocument::query()->where('submission_id', $approved->id)->count());

        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.window'), $this->windowPayload([
                'semester' => 'Next '.$this->suffix,
                'window_id' => $this->window->id,
                'notes' => 'Stale notes',
            ]))
            ->assertSessionHasErrors('renewal');
        $this->assertSame('Next period notes', $newWindow->fresh()->notes);
        $this->assertSame('Same period notes', $this->window->fresh()->notes);
    }

    public function test_oso_roster_standing_uses_current_window_approval_separately_from_admin_flags(): void
    {
        $prior = $this->submission('approved');
        $this->document($prior, $this->docA['key'], OrgRenewalDocument::REVIEW_VERIFIED);
        $this->document($prior, $this->docB['key'], OrgRenewalDocument::REVIEW_VERIFIED);

        $docC = $this->definition('c', withPdf: false);
        $current = $this->renewalWindow([$this->docA, $this->docB, $docC]);
        $draft = $this->submission('draft', $current);
        $draft->forceFill(['organization_name' => mb_strtoupper($this->organization->name), 'submitted_at' => null])->save();

        [$row, $stats] = $this->rosterStanding();
        $this->assertSame($draft->id, $row['submission_id']);
        $this->assertSame('draft', $row['submission_status']);
        $this->assertNull($row['submitted_at']);
        $this->assertFalse($row['officially_active']);
        $this->assertTrue($row['can_file_renewal']);
        $this->assertSame(3, $row['requirements_required']);
        $this->assertSame(0, $row['requirements_verified']);
        $this->assertSame(3, $row['requirements_pending']);
        $this->assertSame(3, $row['requirements_missing']);
        $this->assertSame(0, $row['docs_count']);
        $draftStats = $stats;

        $approved = $draft->fresh();
        $approved->update(['status' => 'submitted', 'submitted_at' => now()]);
        foreach ([$this->docA, $this->docB, $docC] as $doc) {
            $this->document($approved, $doc['key'], OrgRenewalDocument::REVIEW_VERIFIED);
        }
        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $approved), ['decision' => 'approved'])
            ->assertSessionHasNoErrors();
        $approved->refresh();
        $this->actingAs($this->oso, 'office')
            ->from(route('office.renewal'))
            ->post(route('office.renewal.requirements.store'), $this->storePayload(['window_id' => $current->id]))
            ->assertSessionHasNoErrors();
        $this->assertCount(4, $current->fresh()->required_docs);

        [$row, $stats] = $this->rosterStanding();
        $this->assertSame($approved->id, $row['submission_id']);
        $this->assertSame('approved', $row['submission_status']);
        $this->assertTrue($row['officially_active']);
        $this->assertTrue($row['can_file_renewal']);
        $this->assertSame(4, $row['requirements_required']);
        $this->assertSame(3, $row['requirements_verified']);
        $this->assertSame(1, $row['requirements_pending']);
        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame($draftStats['qualified'], $stats['qualified']);
        $this->assertSame($draftStats['inactive'] - 1, $stats['inactive']);
        $approvedStats = $stats;

        $this->organization->forceFill(['is_active' => false, 'is_qualified_for_renewal' => true])->save();
        [$row, $stats] = $this->rosterStanding();
        $this->assertFalse($row['is_active']);
        $this->assertTrue($row['is_qualified_for_renewal']);
        $this->assertFalse($row['can_file_renewal']);
        $this->assertFalse($row['officially_active']);
        $this->assertSame('approved', $row['submission_status']);
        $this->assertSame($approvedStats['total'], $stats['total']);
        $this->assertSame($approvedStats['qualified'] - 1, $stats['qualified']);
        $this->assertSame($approvedStats['not_qualified'] + 1, $stats['not_qualified']);
        $this->assertSame($approvedStats['inactive'] + 1, $stats['inactive']);

        $this->organization->forceFill(['is_active' => true, 'is_qualified_for_renewal' => false])->save();
        [$row, $stats] = $this->rosterStanding();
        $this->assertTrue($row['is_active']);
        $this->assertFalse($row['is_qualified_for_renewal']);
        $this->assertFalse($row['can_file_renewal']);
        $this->assertTrue($row['officially_active']);
        $this->assertSame($approvedStats['qualified'] - 1, $stats['qualified']);
        $this->assertSame($approvedStats['not_qualified'] + 1, $stats['not_qualified']);
        $this->assertSame($approvedStats['inactive'], $stats['inactive']);

        $this->assertSame('approved', $prior->fresh()->status);
        $this->assertSame('approved', $approved->fresh()->status);
    }

    /** @return array{0: array, 1: array} */
    private function rosterStanding(): array
    {
        $response = $this->actingAs($this->oso, 'office')->get(route('office.renewal'))->assertOk();
        $rows = collect($response->viewData('allOrganizations'));
        $stats = $response->viewData('orgStats');

        $this->assertSame($rows->count(), $stats['total']);
        $this->assertSame($rows->where('can_file_renewal', true)->count(), $stats['qualified']);
        $this->assertSame($rows->where('can_file_renewal', false)->count(), $stats['not_qualified']);
        $this->assertSame($rows->where('officially_active', false)->count(), $stats['inactive']);
        $this->assertSame($stats['total'], $stats['qualified'] + $stats['not_qualified']);

        $own = $rows->where('id', $this->organization->id);
        $this->assertCount(1, $own);

        return [$own->first(), $stats];
    }

    private function assertDefinitionsPreserved(array $expected, ?array $actual): void
    {
        $this->assertIsArray($actual);
        $this->assertSame(array_column($expected, 'key'), array_column($actual, 'key'));
        $actualByKey = collect($actual)->keyBy('key');
        foreach ($expected as $definition) {
            foreach ($definition as $field => $value) {
                $this->assertSame($value, $actualByKey[$definition['key']][$field] ?? null, "Definition {$definition['key']} lost {$field}.");
            }
        }
    }

    private function definitions(?OrgRenewalWindow $window = null): array
    {
        return ($window ?? $this->window)->fresh()->required_docs ?? [];
    }

    private function definitionByKey(string $key): ?array
    {
        return collect($this->definitions())->firstWhere('key', $key);
    }

    private function definitionByLabel(string $label): ?array
    {
        return collect($this->definitions())->firstWhere('attachment_label', $label);
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'window_id' => $this->window->id,
            'code' => 'NEW'.Str::upper(Str::random(8)),
            'title' => 'Fixture New Requirement',
            'description' => 'Fixture requirement description.',
            'document' => UploadedFile::fake()->createWithContent('new-template.pdf', '%PDF new template '.Str::random(8)),
        ], $overrides);
    }

    private function windowPayload(array $overrides = []): array
    {
        $window = $this->window->fresh();

        return array_merge([
            'window_id' => (int) OrgRenewalWindow::query()->max('id'),
            'academic_year' => $window->academic_year,
            'semester' => $window->semester,
            'is_open' => 1,
            'instructions' => $window->instructions,
            'notes' => $window->notes,
        ], $overrides);
    }

    private function requirementFile(string $key, string $mode, ?OfficeUser $user = null, ?OrgRenewalWindow $window = null): TestResponse
    {
        return $this->actingAs($user ?? $this->oso, 'office')
            ->get(route('office.renewal.requirements.file', [
                'docKey' => $key,
                'window_id' => ($window ?? $this->window)->id,
                $mode => 1,
            ]));
    }

    private function body(TestResponse $response): string
    {
        $base = $response->baseResponse;
        if ($base instanceof BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }
        if ($base instanceof StreamedResponse) {
            return (string) $response->streamedContent();
        }

        return (string) $base->getContent();
    }

    private function definition(string $letter, bool $withPdf): array
    {
        $base = 'renewal-templates/fixture-'.$letter.'-'.$this->suffix;
        Storage::disk('public')->put($base.'.docx', 'blank template '.$letter.' '.$this->suffix);

        $definition = [
            'key' => 'fixture_'.$letter.'_'.$this->suffix,
            'title' => 'Fixture Requirement '.Str::upper($letter),
            'attachment_label' => 'Fixture '.Str::upper($letter).' '.$this->suffix,
            'description' => 'Fixture description '.$letter,
            'template_path' => $base.'.docx',
            'template_name' => 'Fixture '.Str::upper($letter).'.docx',
        ];
        if ($withPdf) {
            Storage::disk('public')->put($base.'.pdf', '%PDF-1.4 paired preview '.$letter.' '.$this->suffix);
            $definition['pdf_path'] = $base.'.pdf';
        }

        return $definition;
    }

    private function officeUser(string $role): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'Renewal Requirement '.strtoupper($role),
            'email' => 'renewal-requirement-'.Str::uuid().'@example.test',
            'username' => 'renewal_requirement_'.Str::lower(Str::random(16)),
            'password' => Str::random(32),
            'office_role' => $role,
            'office_title' => 'Renewal requirement test desk',
            'student_organization_id' => null,
            'is_active' => true,
        ]);
    }

    private function renewalWindow(array $requiredDocs, ?string $semester = null): OrgRenewalWindow
    {
        return OrgRenewalWindow::query()->create([
            'academic_year' => '2099-2100',
            'semester' => $semester ?? 'Requirement '.Str::lower(Str::random(10)),
            'is_open' => true,
            'instructions' => 'Fixture instructions',
            'notes' => 'Fixture notes',
            'required_docs' => $requiredDocs,
        ]);
    }

    private function submission(string $status = 'submitted', ?OrgRenewalWindow $window = null): OrgRenewalSubmission
    {
        return OrgRenewalSubmission::query()->create([
            'renewal_window_id' => ($window ?? $this->window)->id,
            'organization_name' => $this->organization->name,
            'college' => $this->organization->college,
            'submitted_by' => $this->so->id,
            'adviser_name' => 'Fixture Adviser',
            'dean_name' => 'Fixture Dean',
            'status' => $status,
            'submitted_at' => now(),
            'reviewed_at' => $status === 'submitted' ? null : now(),
        ]);
    }

    private function document(OrgRenewalSubmission $submission, string $key, string $reviewStatus = OrgRenewalDocument::REVIEW_PENDING): OrgRenewalDocument
    {
        $path = 'renewal-documents/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 signed upload '.Str::random(16));

        return OrgRenewalDocument::query()->create([
            'submission_id' => $submission->id,
            'doc_key' => $key,
            'title' => 'Fixture '.$key,
            'file_path' => $path,
            'file_name' => $key.'.pdf',
            'review_status' => $reviewStatus,
            'reviewed_at' => $reviewStatus === OrgRenewalDocument::REVIEW_PENDING ? null : now(),
            'reviewed_by' => $reviewStatus === OrgRenewalDocument::REVIEW_PENDING ? null : $this->oso->id,
        ]);
    }
}

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
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class RenewalDocumentReviewTest extends TestCase
{
    use UsesLaragonDatabase;

    private const CONNECTIONS = ['mysql'];

    private OfficeUser $oso;
    private OfficeUser $so;
    private StudentOrganization $organization;
    private OrgRenewalWindow $window;
    private string $keyA;
    private string $keyB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (self::CONNECTIONS as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Storage::fake('local');

        $this->oso = $this->officeUser('oso');
        $this->so = $this->officeUser('so');

        $suffix = Str::lower(Str::random(10));
        $this->organization = StudentOrganization::query()->create([
            'name' => 'Renewal Review Fixture '.$suffix,
            'short_name' => 'RRF-'.$suffix,
            'college' => 'Renewal Review Test College',
            'is_active' => true,
            'is_qualified_for_renewal' => true,
        ]);
        $this->so->update(['student_organization_id' => $this->organization->id]);

        $this->keyA = 'review_fixture_a_'.$suffix;
        $this->keyB = 'review_fixture_b_'.$suffix;
        $this->window = $this->renewalWindow([
            ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ['key' => $this->keyB, 'title' => 'Fixture Requirement B'],
        ]);
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

    public function test_only_oso_can_review_and_only_oso_or_owning_so_can_open_files(): void
    {
        $submission = $this->submission();
        $document = $this->document($submission, $this->keyA);
        $otherSo = $this->officeUser('so');
        $foreignOrganization = StudentOrganization::query()->create([
            'name' => 'Foreign renewal fixture '.Str::uuid(), 'college' => 'Fixture college', 'is_active' => true,
        ]);
        $otherSo->update(['student_organization_id' => $foreignOrganization->id]);
        $sdo = $this->officeUser('sdo');

        $this->actingAs($this->so, 'office')
            ->get(route('office.renewal.submissions.show', $submission))
            ->assertForbidden();
        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.documents.review', $document), ['decision' => 'verified', 'file_version' => $document->fileVersion()])
            ->assertForbidden();
        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
            ->assertForbidden();
        $this->actingAs($otherSo, 'office')
            ->get(route('office.renewal.documents.file', $document))
            ->assertForbidden();
        $this->actingAs($sdo, 'office')
            ->get(route('office.renewal.documents.file', $document))
            ->assertForbidden();

        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $document->fresh()->review_status);
        $this->assertSame('submitted', $submission->fresh()->status);

        $this->actingAs($this->so, 'office')
            ->get(route('office.renewal.documents.file', $document))
            ->assertOk();
        $this->actingAs($this->oso, 'office')
            ->get(route('office.renewal.documents.file', ['document' => $document, 'download' => 1]))
            ->assertOk()
            ->assertDownload($document->file_name);
    }

    public function test_missing_document_file_or_record_returns_not_found(): void
    {
        $submission = $this->submission();
        $document = $this->document($submission, $this->keyA);
        Storage::disk('local')->delete($document->file_path);

        $this->actingAs($this->oso, 'office')
            ->get(route('office.renewal.documents.file', $document))
            ->assertNotFound();
        $this->actingAs($this->oso, 'office')
            ->get('/office-desk/renewal/documents/'.(OrgRenewalDocument::query()->max('id') + 1000).'/file')
            ->assertNotFound();
    }

    public function test_incomplete_or_unverified_packet_cannot_be_approved(): void
    {
        $submission = $this->submission();
        $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $pending = $this->document($submission, $this->keyB);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
            ->assertSessionHasErrors('renewal');
        $this->assertSame('submitted', $submission->fresh()->status);

        $pending->delete();
        $this->document($submission, 'unknown_fixture_key_'.Str::random(6), OrgRenewalDocument::REVIEW_VERIFIED);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
            ->assertSessionHasErrors('renewal');
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_packet_with_all_required_documents_verified_can_be_approved_against_its_own_window(): void
    {
        $submission = $this->submission();
        $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $this->document($submission, $this->keyB, OrgRenewalDocument::REVIEW_VERIFIED);
        $this->renewalWindow([['key' => 'later_window_only_'.Str::random(6), 'title' => 'Later Window Requirement']]);
        $this->organization->update(['is_active' => false, 'is_qualified_for_renewal' => false]);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
            ->assertSessionHasNoErrors();

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertNotNull($submission->reviewed_at);
        $this->organization->refresh();
        $this->assertFalse($this->organization->is_active);
        $this->assertFalse($this->organization->is_qualified_for_renewal);
    }

    public function test_document_return_and_reject_require_remarks_and_persist_review_state(): void
    {
        $submission = $this->submission();
        $returned = $this->document($submission, $this->keyA);
        $rejected = $this->document($submission, $this->keyB);

        foreach ([null, '   '] as $blank) {
            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.documents.review', $returned), ['decision' => 'returned', 'remarks' => $blank, 'file_version' => $returned->fileVersion()])
                ->assertSessionHasErrors('remarks');
            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.documents.review', $rejected), ['decision' => 'rejected', 'remarks' => $blank, 'file_version' => $rejected->fileVersion()])
                ->assertSessionHasErrors('remarks');
        }
        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $returned->fresh()->review_status);
        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $rejected->fresh()->review_status);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $returned), ['decision' => 'returned', 'remarks' => 'Missing adviser signature.', 'file_version' => $returned->fileVersion()])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $rejected), ['decision' => 'rejected', 'remarks' => 'Wrong template used.', 'file_version' => $rejected->fileVersion()])
            ->assertSessionHasNoErrors();

        $returned->refresh();
        $this->assertSame(OrgRenewalDocument::REVIEW_RETURNED, $returned->review_status);
        $this->assertSame('For Revision', $returned->reviewStatusLabel());
        $this->assertSame('Missing adviser signature.', $returned->review_remarks);
        $this->assertSame($this->oso->id, (int) $returned->reviewed_by);
        $this->assertNotNull($returned->reviewed_at);

        $rejected->refresh();
        $this->assertSame(OrgRenewalDocument::REVIEW_REJECTED, $rejected->review_status);
        $this->assertSame('Wrong template used.', $rejected->review_remarks);
        $this->assertSame('submitted', $submission->fresh()->status);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $returned), ['decision' => 'verified', 'file_version' => $returned->fileVersion()])
            ->assertSessionHasNoErrors();
        $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $returned->fresh()->review_status);
        $this->assertNull($returned->fresh()->review_remarks);
    }

    public function test_packet_return_and_reject_require_remarks(): void
    {
        $submission = $this->submission();

        foreach (['returned', 'rejected'] as $decision) {
            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.review', $submission), ['decision' => $decision, 'remarks' => '  '])
                ->assertSessionHasErrors('remarks');
        }
        $this->assertSame('submitted', $submission->fresh()->status);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'rejected', 'remarks' => 'Organization did not meet requirements.'])
            ->assertSessionHasNoErrors();

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('Organization did not meet requirements.', $submission->review_remarks);
    }

    public function test_jpg_upload_preserves_the_filename_and_can_be_opened_by_its_owner(): void
    {
        $file = UploadedFile::fake()->image('signed-adviser-commitment.jpg', 24, 16);
        $bytes = file_get_contents($file->getRealPath());

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload([
                $this->keyA => $file,
                $this->keyB => $this->pdfUpload('second-requirement.pdf'),
            ]))
            ->assertRedirect(route('office.renewal'))
            ->assertSessionHasNoErrors();

        $submission = OrgRenewalSubmission::where('renewal_window_id', $this->window->id)
            ->where('organization_name', $this->organization->name)->firstOrFail();
        $this->assertSame('submitted', $submission->status);
        $document = $submission->documents()->where('doc_key', $this->keyA)->firstOrFail();
        $this->assertSame('signed-adviser-commitment.jpg', $document->file_name);
        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $document->review_status);
        $this->assertTrue($document->hasStoredFile());

        $this->get(route('office.renewal.documents.file', ['document' => $document, 'v' => $document->fileVersion()]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertStreamedContent($bytes);
    }

    public function test_saved_document_without_a_real_file_cannot_complete_a_submit(): void
    {
        foreach (['metadata_only', 'missing', 'zero_byte', 'directory'] as $variant) {
            $window = $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
                ['key' => $this->keyB, 'title' => 'Fixture Requirement B'],
            ]);
            $submission = $this->submission('returned', $window);
            $broken = $this->document($submission, $this->keyA);
            $this->breakStoredFile($broken, $variant);

            $submission->load('documents');
            $this->assertSame([], $submission->uploadedKeys(), $variant);
            $this->assertSame(0, $submission->completionPercent($window->requiredDocList()), $variant);

            $before = $submission->fresh()->getAttributes();
            $brokenBefore = $broken->fresh()->getAttributes();
            $filesBefore = Storage::disk('local')->allFiles();

            $this->actingAs($this->so, 'office')
                ->post(route('office.renewal.submit'), $this->submissionPayload([
                    $this->keyB => $this->pdfUpload('second.pdf'),
                ], $submission))
                ->assertSessionHasErrors('renewal');

            $this->assertSame($before, $submission->fresh()->getAttributes(), $variant);
            $this->assertSame($brokenBefore, $broken->fresh()->getAttributes(), $variant);
            $this->assertFalse($submission->documents()->where('doc_key', $this->keyB)->exists(), $variant);
            $this->assertSame($filesBefore, Storage::disk('local')->allFiles(), $variant);
        }
    }

    public function test_selected_zero_byte_upload_is_rejected_without_persisting_anything(): void
    {
        $submissionCount = OrgRenewalSubmission::count();
        $documentCount = OrgRenewalDocument::count();

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload([
                $this->keyA => UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf'),
                $this->keyB => $this->pdfUpload('second.pdf'),
            ]))
            ->assertSessionHasErrors('documents.'.$this->keyA);
        $this->assertSame($submissionCount, OrgRenewalSubmission::count());
        $this->assertSame($documentCount, OrgRenewalDocument::count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $submission = $this->submission('returned');
        $document = $this->document($submission, $this->keyA);
        $this->document($submission, $this->keyB);
        $before = $submission->fresh()->getAttributes();
        $documentBefore = $document->fresh()->getAttributes();
        $filesBefore = Storage::disk('local')->allFiles();

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload([
                $this->keyA => UploadedFile::fake()->create('empty.pdf', 0, 'application/pdf'),
            ], $submission))
            ->assertSessionHasErrors('documents.'.$this->keyA);
        $this->assertSame($before, $submission->fresh()->getAttributes());
        $this->assertSame($documentBefore, $document->fresh()->getAttributes());
        $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
    }

    public function test_document_without_a_real_file_cannot_be_reviewed(): void
    {
        foreach (['metadata_only', 'missing', 'zero_byte', 'directory'] as $variant) {
            $submission = $this->submission('submitted', $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $broken = $this->document($submission, $this->keyA);
            $this->breakStoredFile($broken, $variant);
            $broken->refresh();

            foreach ([OrgRenewalDocument::REVIEW_VERIFIED, OrgRenewalDocument::REVIEW_RETURNED, OrgRenewalDocument::REVIEW_REJECTED] as $decision) {
                $this->actingAs($this->oso, 'office')
                    ->post(route('office.renewal.documents.review', $broken), [
                        'decision' => $decision,
                        'remarks' => 'Fixture remarks.',
                        'file_version' => $broken->fileVersion(),
                    ])
                    ->assertSessionHasErrors('renewal');
            }

            $broken->refresh();
            $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $broken->review_status, $variant);
            $this->assertNull($broken->reviewed_at, $variant);
            $this->assertNull($broken->reviewed_by, $variant);
        }
    }

    public function test_verified_document_without_a_real_file_is_missing_and_blocks_approval(): void
    {
        foreach (['metadata_only', 'missing', 'zero_byte', 'directory'] as $variant) {
            $submission = $this->submission('submitted', $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
                ['key' => $this->keyB, 'title' => 'Fixture Requirement B'],
            ]));
            $broken = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
            $valid = $this->document($submission, $this->keyB, OrgRenewalDocument::REVIEW_VERIFIED);
            $this->breakStoredFile($broken, $variant);
            $broken->refresh();

            $this->assertFalse($broken->hasStoredFile(), $variant);
            $this->assertTrue($valid->hasStoredFile(), $variant);
            $submission->load(['documents', 'window']);
            $requiredDocs = $submission->window->requiredDocList();
            $this->assertSame(['required' => 2, 'verified' => 1, 'missing' => 1], $submission->requiredDocumentReview($requiredDocs), $variant);
            $this->assertFalse($submission->canBeApproved($requiredDocs), $variant);

            $this->actingAs($this->oso, 'office')
                ->get(route('office.renewal.submissions.show', $submission))
                ->assertOk()
                ->assertViewHas('verifiedCount', 1)
                ->assertViewHas('missingCount', 1)
                ->assertViewHas('canApproveRenewal', false);
            $this->actingAs($this->oso, 'office')
                ->get(route('office.renewal.documents.file', $broken))
                ->assertNotFound();
            $this->actingAs($this->oso, 'office')
                ->get(route('office.renewal.documents.file', $valid))
                ->assertOk();
            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.documents.review', $broken), ['decision' => 'returned', 'remarks' => 'Fixture remarks.', 'file_version' => $broken->fileVersion()])
                ->assertSessionHasErrors('renewal');
            $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $broken->fresh()->review_status, $variant);

            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
                ->assertSessionHasErrors('renewal');
            $this->assertSame('submitted', $submission->fresh()->status, $variant);
        }
    }

    public function test_legacy_draft_packet_is_not_offered_to_oso_or_served_directly(): void
    {
        $draft = $this->submission('draft');
        $draft->update(['submitted_at' => null]);
        $this->document($draft, $this->keyA);
        $this->document($draft, $this->keyB);

        $response = $this->actingAs($this->oso, 'office')
            ->get(route('office.renewal'))
            ->assertOk();
        $row = collect($response->viewData('allOrganizations'))->firstWhere('id', $this->organization->id);
        $this->assertNotNull($row);
        $this->assertSame('none', $row['submission_status']);
        $this->assertNull($row['submission_id']);
        $this->assertSame(0, $row['docs_count']);
        $this->assertFalse(collect($response->viewData('renewalSubmissions'))->contains('id', $draft->id));
        $this->assertFalse(collect($response->viewData('otherRenewalSubmissions'))->contains('id', $draft->id));
        $this->actingAs($this->oso, 'office')
            ->get(route('office.renewal.submissions.show', $draft))
            ->assertNotFound();
        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $draft), ['decision' => 'approved'])
            ->assertSessionHasErrors('renewal');
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_replacement_upload_clears_prior_verification_and_blocks_approval(): void
    {
        $submission = $this->submission('returned');
        $replaced = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $this->document($submission, $this->keyB, OrgRenewalDocument::REVIEW_VERIFIED);
        $originalPath = $replaced->file_path;

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload([
                $this->keyA => $this->pdfUpload('replacement.pdf'),
            ], $submission))
            ->assertSessionHasNoErrors();

        $replaced->refresh();
        $this->assertNotSame($originalPath, $replaced->file_path);
        $this->assertSame('replacement.pdf', $replaced->file_name);
        Storage::disk('local')->assertExists($replaced->file_path);
        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $replaced->review_status);
        $this->assertNull($replaced->review_remarks);
        $this->assertNull($replaced->reviewed_at);
        $this->assertNull($replaced->reviewed_by);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'approved'])
            ->assertSessionHasErrors('renewal');
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_review_of_stale_file_version_is_refused_after_replacement(): void
    {
        $submission = $this->submission();
        $document = $this->document($submission, $this->keyA);
        $staleVersion = $document->fileVersion();
        $this->document($submission, $this->keyB, OrgRenewalDocument::REVIEW_VERIFIED);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $document), ['decision' => 'verified'])
            ->assertSessionHasErrors('file_version');

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.review', $submission), ['decision' => 'returned', 'remarks' => 'Replace the outdated file.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload([
                $this->keyA => $this->pdfUpload('replacement.pdf'),
            ], $submission))
            ->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertNotSame($staleVersion, $document->fileVersion());
        $replacedAt = $document->updated_at;

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $document), ['decision' => 'verified', 'file_version' => $staleVersion])
            ->assertSessionHasErrors('renewal');

        $document->refresh();
        $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $document->review_status);
        $this->assertNull($document->reviewed_at);
        $this->assertNull($document->reviewed_by);
        $this->assertEquals($replacedAt, $document->updated_at);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $document), ['decision' => 'verified', 'file_version' => $document->fileVersion()])
            ->assertSessionHasNoErrors();
        $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $document->fresh()->review_status);
    }

    public function test_review_decisions_cannot_be_changed_without_resubmission(): void
    {
        foreach (['approved', 'returned', 'rejected'] as $status) {
            $submission = $this->submission($status, $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.documents.review', $document), ['decision' => 'rejected', 'remarks' => 'Late change.', 'file_version' => $document->fileVersion()])
                ->assertSessionHasErrors('renewal');
            foreach (['approved', 'returned', 'rejected'] as $decision) {
                $this->post(route('office.renewal.review', $submission), ['decision' => $decision, 'remarks' => 'Late change.'])
                    ->assertSessionHasErrors('renewal');
            }
            $this->assertSame($status, $submission->fresh()->status);
            $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $document->fresh()->review_status);
        }
    }

    public function test_submitted_and_approved_packets_reject_repeated_submission_and_information_mutations(): void
    {
        foreach (['submitted', 'approved'] as $status) {
            $submission = $this->submission($status, $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
            $before = $submission->fresh()->getAttributes();
            $documentBefore = $document->fresh()->getAttributes();
            $filesBefore = Storage::disk('local')->allFiles();
            $this->assertTrue($submission->submissionLocked());
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $payload = array_replace(
                    $this->submissionPayload([$this->keyA => $this->pdfUpload('late.pdf')], $submission),
                    ['adviser_name' => 'Late Adviser', 'dean_name' => 'Late Dean']
                );
                $this->actingAs($this->so, 'office')->post(route('office.renewal.submit'), $payload)
                    ->assertSessionHasErrors('renewal');
                unset($payload['documents']);
                $this->postJson(route('office.renewal.information.save'), $payload)
                    ->assertUnprocessable()->assertJsonValidationErrors('renewal');
            }
            $this->assertSame($before, $submission->fresh()->getAttributes());
            $this->assertSame($documentBefore, $document->fresh()->getAttributes());
            $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        }
    }

    public function test_returned_and_rejected_packets_can_be_revised_and_resubmitted(): void
    {
        foreach (['returned', 'rejected'] as $status) {
            $submission = $this->submission($status, $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $submission->update(['review_remarks' => 'Correct the attachment.', 'reviewed_at' => now()]);
            $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_REJECTED);
            $originalPath = $document->file_path;
            $this->assertFalse($submission->submissionLocked());
            $this->actingAs($this->so, 'office')->post(route('office.renewal.submit'), array_replace(
                $this->submissionPayload([$this->keyA => $this->pdfUpload('revised.pdf')], $submission),
                ['adviser_name' => 'Revised Adviser']
            ))->assertSessionHasNoErrors()->assertRedirect(route('office.renewal'));
            $submission->refresh();
            $this->assertSame('submitted', $submission->status);
            $this->assertSame('Revised Adviser', $submission->adviser_name);
            $this->assertNull($submission->review_remarks);
            $this->assertNull($submission->reviewed_at);
            $this->assertNotSame($originalPath, $document->fresh()->file_path);
            $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $document->fresh()->review_status);
        }
    }

    public function test_information_save_creates_a_draft_without_pending_files_and_keeps_it_out_of_oso_review(): void
    {
        $documentsBefore = OrgRenewalDocument::count();
        $payload = array_replace($this->submissionPayload([
            $this->keyA => $this->pdfUpload('not-submitted.pdf'),
        ]), [
            'college' => 'Forged College',
            'notes' => 'Not saved by this endpoint',
            'status' => 'submitted',
            'adviser_name' => ' Saved Adviser ',
            'dean_name' => ' Saved Dean ',
        ]);
        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.information.save'), $payload, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('information.adviser_name', 'Saved Adviser')
            ->assertJsonPath('information.dean_name', 'Saved Dean')
            ->assertJsonPath('information.college', $this->organization->college)
            ->assertJsonPath('information.status', 'draft');
        $draft = OrgRenewalSubmission::where('renewal_window_id', $this->window->id)
            ->where('organization_name', $this->organization->name)->firstOrFail();
        $this->assertNull($draft->submitted_at);
        $this->assertNull($draft->notes);
        $this->assertSame($this->so->id, $draft->submitted_by);
        $this->assertSame($documentsBefore, OrgRenewalDocument::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get(route('office.renewal'))->assertOk()
            ->assertViewHas('myRenewalSubmission', fn ($submission) => $submission?->id === $draft->id);
        $response = $this->actingAs($this->oso, 'office')->get(route('office.renewal'))->assertOk();
        $this->assertFalse($response->viewData('renewalSubmissions')->contains('id', $draft->id));
        $this->assertSame('none', collect($response->viewData('allOrganizations'))->firstWhere('id', $this->organization->id)['submission_status']);
        $this->actingAs($this->so, 'office')->post(route('office.renewal.submit'), array_replace(
            $this->submissionPayload([
                $this->keyA => $this->pdfUpload('first-submitted.pdf'),
                $this->keyB => $this->pdfUpload('second-submitted.pdf'),
            ]),
            ['adviser_name' => 'Saved Adviser', 'dean_name' => 'Saved Dean']
        ))->assertSessionHasNoErrors()->assertRedirect(route('office.renewal'));
        $draft->refresh();
        $this->assertSame('submitted', $draft->status);
        $this->assertNotNull($draft->submitted_at);
        $this->assertSame(2, $draft->documents()->count());
        $this->assertSame(1, OrgRenewalSubmission::where('renewal_window_id', $this->window->id)
            ->where('organization_name', $this->organization->name)->count());
    }

    public function test_information_save_requires_valid_names_and_an_active_assignment(): void
    {
        $payload = $this->submissionPayload([]);
        $this->actingAs($this->so, 'office')->postJson(route('office.renewal.information.save'), array_replace(
            $payload, ['adviser_name' => '   ', 'dean_name' => str_repeat('x', 256)]
        ))->assertUnprocessable()->assertJsonValidationErrors(['adviser_name', 'dean_name']);
        $this->actingAs($this->officeUser('so'), 'office')
            ->postJson(route('office.renewal.information.save'), $payload)->assertForbidden();
        $this->assertFalse(OrgRenewalSubmission::where('renewal_window_id', $this->window->id)
            ->where('organization_name', $this->organization->name)->exists());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_information_save_preserves_returned_and_rejected_review_state_and_existing_documents(): void
    {
        foreach (['returned', 'rejected'] as $status) {
            $submission = $this->submission($status, $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $submission->update(['notes' => 'Existing notes', 'review_remarks' => 'Original OSO remarks', 'reviewed_at' => now()]);
            $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_REJECTED);
            $before = $submission->fresh();
            $documentBefore = $document->fresh()->getAttributes();
            $filesBefore = Storage::disk('local')->allFiles();
            $payload = array_replace($this->submissionPayload([], $submission), [
                'adviser_name' => 'New Adviser', 'dean_name' => 'New Dean',
                'notes' => 'Must not replace notes', 'review_remarks' => null,
            ]);
            $this->actingAs($this->so, 'office')->postJson(route('office.renewal.information.save'), $payload)
                ->assertOk()->assertJsonPath('information.status', $status);
            $submission->refresh();
            $this->assertSame('New Adviser', $submission->adviser_name);
            $this->assertSame('New Dean', $submission->dean_name);
            foreach (['status', 'notes', 'review_remarks', 'reviewed_at', 'submitted_at'] as $attribute) {
                $this->assertEquals($before->$attribute, $submission->$attribute, $attribute);
            }
            $this->assertSame($documentBefore, $document->fresh()->getAttributes());
            $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        }
    }

    public function test_information_save_rejects_foreign_organization_closed_stale_or_ineligible_windows(): void
    {
        $payload = $this->submissionPayload([]);
        $foreign = StudentOrganization::query()->create([
            'name' => 'Foreign information fixture '.Str::uuid(), 'college' => 'Foreign college', 'is_active' => true,
        ]);
        $this->actingAs($this->so, 'office')
            ->postJson(route('office.renewal.information.save'), array_replace($payload, ['organization_name' => $foreign->name]))
            ->assertForbidden();
        $this->organization->update(['is_qualified_for_renewal' => false]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->organization->update(['is_qualified_for_renewal' => true, 'is_active' => false]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertForbidden();
        $this->organization->update(['is_active' => true]);
        $this->window->update(['is_open' => false]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->window->update(['is_open' => true]);
        $this->postJson(route('office.renewal.information.save'), array_replace($payload, ['window_id' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->window->update(['opens_at' => now()->addDay()]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->window->update(['opens_at' => null, 'closes_at' => now()->subDay()]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->window->update(['closes_at' => null]);
        $this->renewalWindow([['key' => $this->keyA, 'title' => 'New current requirement']]);
        $this->postJson(route('office.renewal.information.save'), $payload)->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->assertFalse(OrgRenewalSubmission::whereIn('organization_name', [$this->organization->name, $foreign->name])->exists());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_draft_missing_submit_action_and_incomplete_packets_do_not_persist_details_or_files(): void
    {
        $submissionCount = OrgRenewalSubmission::count();
        $documentCount = OrgRenewalDocument::count();
        foreach (['draft', null, 'incomplete'] as $attempt) {
            $payload = $this->submissionPayload([
                $this->keyA => UploadedFile::fake()->image('adviser.jpg'),
                $this->keyB => $this->pdfUpload('second.pdf'),
            ]);
            if ($attempt === 'incomplete') {
                unset($payload['documents'][$this->keyB]);
            } else {
                $payload['action'] = $attempt;
            }
            $this->actingAs($this->so, 'office')
                ->post(route('office.renewal.submit'), $payload)
                ->assertSessionHasErrors($attempt === 'incomplete' ? 'renewal' : 'action');
            $this->assertSame($submissionCount, OrgRenewalSubmission::count());
            $this->assertSame($documentCount, OrgRenewalDocument::count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_failed_replacement_does_not_change_saved_details_review_state_or_files(): void
    {
        $submission = $this->submission('returned');
        $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $before = $submission->fresh()->getAttributes();
        $documentBefore = $document->fresh()->getAttributes();
        $filesBefore = Storage::disk('local')->allFiles();
        $this->actingAs($this->so, 'office')->post(route('office.renewal.submit'), array_replace(
            $this->submissionPayload([$this->keyA => UploadedFile::fake()->image('replacement.jpg')], $submission),
            ['adviser_name' => 'Not yet saved']
        ))->assertSessionHasErrors('renewal');
        $this->assertSame($before, $submission->fresh()->getAttributes());
        $this->assertSame($documentBefore, $document->fresh()->getAttributes());
        $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
    }

    public function test_complete_submit_creates_the_packet_and_all_documents_together(): void
    {
        $this->actingAs($this->so, 'office')->post(route('office.renewal.submit'), $this->submissionPayload([
            $this->keyA => UploadedFile::fake()->image('adviser.jpg'),
            $this->keyB => $this->pdfUpload('second.pdf'),
        ]))->assertRedirect(route('office.renewal'))->assertSessionHasNoErrors();
        $submission = OrgRenewalSubmission::where('renewal_window_id', $this->window->id)
            ->where('organization_name', $this->organization->name)->firstOrFail();
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('Fixture Adviser', $submission->adviser_name);
        $this->assertSame('Fixture Dean', $submission->dean_name);
        $this->assertNotNull($submission->submitted_at);
        foreach ([$this->keyA => 'adviser.jpg', $this->keyB => 'second.pdf'] as $key => $filename) {
            $document = $submission->documents()->where('doc_key', $key)->firstOrFail();
            $this->assertSame($filename, $document->file_name);
            $this->assertSame(OrgRenewalDocument::REVIEW_PENDING, $document->review_status);
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    public function test_ineligible_or_stale_window_submission_does_not_store_the_packet_or_files(): void
    {
        $files = [
            $this->keyA => UploadedFile::fake()->image('adviser.jpg'),
            $this->keyB => $this->pdfUpload('second.pdf'),
        ];
        $this->organization->update(['is_qualified_for_renewal' => false]);
        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.submit'), $this->submissionPayload($files))
            ->assertSessionHasErrors('renewal');
        $this->organization->update(['is_qualified_for_renewal' => true]);
        $this->post(route('office.renewal.submit'), array_replace($this->submissionPayload($files), ['window_id' => 0]))
            ->assertSessionHasErrors('renewal');
        $this->assertFalse(OrgRenewalSubmission::where('renewal_window_id', $this->window->id)->exists());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function pdfUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    }

    private function submissionPayload(array $files, ?OrgRenewalSubmission $submission = null): array
    {
        return [
            'action' => 'submit',
            'window_id' => $submission?->renewal_window_id ?? $this->window->id,
            'organization_name' => $this->organization->name,
            'college' => $this->organization->college,
            'adviser_name' => 'Fixture Adviser',
            'dean_name' => 'Fixture Dean',
            'documents' => $files,
        ];
    }

    private function officeUser(string $role): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'Renewal Review '.strtoupper($role),
            'email' => 'renewal-review-'.Str::uuid().'@example.test',
            'username' => 'renewal_review_'.Str::lower(Str::random(16)),
            'password' => Str::random(32),
            'office_role' => $role,
            'office_title' => 'Renewal review test desk',
            'student_organization_id' => null,
            'is_active' => true,
        ]);
    }

    private function renewalWindow(array $requiredDocs): OrgRenewalWindow
    {
        return OrgRenewalWindow::query()->create([
            'academic_year' => '2099-2100',
            'semester' => 'Renewal Review Test',
            'is_open' => true,
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
        ]);
    }

    private function document(OrgRenewalSubmission $submission, string $key, string $reviewStatus = OrgRenewalDocument::REVIEW_PENDING): OrgRenewalDocument
    {
        $path = 'renewal-documents/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 renewal review fixture');

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

    private function breakStoredFile(OrgRenewalDocument $document, string $variant): void
    {
        $disk = Storage::disk('local');
        match ($variant) {
            'metadata_only' => $document->update(['file_path' => '']),
            'missing' => $disk->delete($document->file_path),
            'zero_byte' => $disk->put($document->file_path, ''),
            'directory' => $disk->delete($document->file_path) && $disk->makeDirectory($document->file_path),
        };
    }
}

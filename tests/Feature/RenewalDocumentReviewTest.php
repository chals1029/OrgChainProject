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

    private const CONNECTIONS = ['mysql', 'orgchain'];

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
        Storage::fake('public');

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
        Storage::disk('public')->delete($document->file_path);

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

    public function test_replacement_upload_clears_prior_verification_and_blocks_approval(): void
    {
        $submission = $this->submission();
        $replaced = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
        $this->document($submission, $this->keyB, OrgRenewalDocument::REVIEW_VERIFIED);
        $originalPath = $replaced->file_path;

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.documents'), [
                'submission_id' => $submission->id,
                'doc_key' => $this->keyA,
                'document' => UploadedFile::fake()->create('replacement.pdf', 12, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $replaced->refresh();
        $this->assertNotSame($originalPath, $replaced->file_path);
        $this->assertSame('replacement.pdf', $replaced->file_name);
        Storage::disk('public')->assertExists($replaced->file_path);
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

        $this->actingAs($this->oso, 'office')
            ->post(route('office.renewal.documents.review', $document), ['decision' => 'verified'])
            ->assertSessionHasErrors('file_version');

        $this->actingAs($this->so, 'office')
            ->post(route('office.renewal.documents'), [
                'submission_id' => $submission->id,
                'doc_key' => $this->keyA,
                'document' => UploadedFile::fake()->create('replacement.pdf', 12, 'application/pdf'),
            ])
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

    public function test_terminal_decisions_cannot_be_edited(): void
    {
        foreach (OrgRenewalSubmission::TERMINAL_STATUSES as $status) {
            $submission = $this->submission($status, $this->renewalWindow([
                ['key' => $this->keyA, 'title' => 'Fixture Requirement A'],
            ]));
            $document = $this->document($submission, $this->keyA, OrgRenewalDocument::REVIEW_VERIFIED);
            $originalPath = $document->file_path;

            $this->actingAs($this->oso, 'office')
                ->post(route('office.renewal.documents.review', $document), ['decision' => 'rejected', 'remarks' => 'Late change.', 'file_version' => $document->fileVersion()])
                ->assertSessionHasErrors('renewal');
            foreach (['approved', 'returned', 'rejected'] as $decision) {
                $this->actingAs($this->oso, 'office')
                    ->post(route('office.renewal.review', $submission), ['decision' => $decision, 'remarks' => 'Late change.'])
                    ->assertSessionHasErrors('renewal');
            }

            $this->actingAs($this->so, 'office')
                ->post(route('office.renewal.documents'), [
                    'submission_id' => $submission->id,
                    'doc_key' => $this->keyA,
                    'document' => UploadedFile::fake()->create('late.pdf', 12, 'application/pdf'),
                ])
                ->assertSessionHasErrors('document');
            $this->actingAs($this->so, 'office')
                ->post(route('office.renewal.submit'), [
                    'organization_name' => $submission->organization_name,
                    'adviser_name' => 'Late Adviser',
                    'dean_name' => 'Late Dean',
                    'action' => 'draft',
                ])
                ->assertSessionHasErrors('renewal');

            $submission->refresh();
            $this->assertSame($status, $submission->status);
            $this->assertSame('Fixture Adviser', $submission->adviser_name);
            $document->refresh();
            $this->assertSame(OrgRenewalDocument::REVIEW_VERIFIED, $document->review_status);
            $this->assertSame($originalPath, $document->file_path);
        }
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
        Storage::disk('public')->put($path, '%PDF-1.4 renewal review fixture');

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

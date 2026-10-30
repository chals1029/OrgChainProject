<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Models\UserAccount;
use App\Services\TosaRequirements;
use App\Services\TosaWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TosaManualVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory, 'mail.default' => 'array']);
        DB::purge('mysql');
        Storage::fake('local');
        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table): void {
            $table->increments('user_id');
            foreach (['sr_code', 'full_name', 'email', 'account_status', 'role'] as $column) {
                $table->text($column)->nullable();
            }
        });
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'program', 'year_level', 'email', 'college', 'organization_name', 'status', 'subsection', 'requirements', 'remarks'] as $column) {
                $table->text($column)->nullable();
            }
            $table->decimal('gwa', 4, 2)->nullable();
            $table->boolean('gwa_verified')->default(false);
            $table->json('score_evaluation')->nullable();
            $table->json('document_review')->nullable();
            $table->json('committee_members')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_users', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'email', 'username', 'password', 'office_role', 'tosa_clearance', 'remember_token'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->integer('auth_version')->default(0);
            $table->unsignedBigInteger('student_organization_id')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_31_000001_create_tosa_workspace_tables.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function officer(string $role = 'oso', ?string $clearance = null): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'Review officer', 'office_role' => $role, 'tosa_clearance' => $clearance, 'is_active' => true,
        ]);
    }

    private function applicant(string $srCode = 'manual-fixture', string $name = 'Synthetic Applicant'): TosaApplicant
    {
        $requirements = [];
        $pdf = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        $docx = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $format = $key === 'application_form' ? 'docx' : 'pdf';
            $path = 'tosa/'.$srCode.'/'.$key.'.'.$format;
            Storage::disk('local')->put($path, $format === 'docx' ? $docx : $pdf);
            $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$format];
        }

        $applicant = TosaApplicant::query()->create([
            'sr_code' => $srCode, 'full_name' => $name, 'program' => 'Test Program',
            'status' => 'submitted', 'subsection' => 'screening', 'requirements' => $requirements,
            'gwa' => 1.25, 'gwa_verified' => true,
        ]);
        $applicant->forceFill([
            'score_evaluation' => ['status' => 'historical'],
            'committee_members' => ['members' => [7]],
            'document_review' => ['records_management' => ['note' => 'historical']],
        ])->save();

        return $applicant;
    }

    public function test_one_confirmation_reviews_every_current_file_and_adds_a_private_draft(): void
    {
        $officer = $this->officer();
        $applicant = $this->applicant();
        $url = route('office.tosa.top10.add', $applicant);
        $this->actingAs($officer, 'office');

        $this->postJson($url, [])->assertOk()->assertJsonPath('added', true)->assertJsonPath('draft_count', 1);
        $saved = $applicant->fresh();
        $this->assertTrue(app(TosaWorkflowService::class)->isVerified($saved));
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $attestation = $saved->document_review['documents'][$key];
            $this->assertSame($saved->requirementPath($key), $attestation['path']);
            $this->assertSame($officer->id, $attestation['verified_by']);
        }
        $this->assertSame(['note' => 'historical'], $saved->document_review['records_management']);
        $this->assertSame(['status' => 'historical'], $saved->score_evaluation);
        $this->assertSame(['members' => [7]], $saved->committee_members);
        $this->assertSame(1.25, $saved->gwa);
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $this->assertSame([$applicant->id], $shortlist->draft_ids);
        $this->assertNull($shortlist->published_entries);
        foreach (['reviewed', 'top10_saved'] as $action) {
            $this->assertDatabaseHas('tosa_history_events', [
                'action' => $action, 'actor_id' => $officer->id, 'applicant_id' => $applicant->id,
            ], 'mysql');
        }

        $this->postJson($url, [])->assertOk()->assertJsonPath('added', false)->assertJsonPath('draft_count', 1);
        $this->assertSame($saved->document_review, $applicant->fresh()->document_review);
        $this->assertSame([$applicant->id], $shortlist->fresh()->draft_ids);
        $this->assertSame(2, TosaHistoryEvent::query()->count());
    }

    public function test_missing_or_replaced_original_blocks_addition_until_reviewed_again(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office');
        $url = route('office.tosa.top10.add', $applicant);
        $old = $applicant->requirementPath('academic');
        $initialReview = $applicant->document_review;
        Storage::disk('local')->delete($old);
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('documents');
        $this->assertSame($initialReview, $applicant->fresh()->document_review);
        $this->assertSame([], TosaShortlist::query()->findOrFail(1)->draft_ids ?? []);

        Storage::disk('local')->put($old, file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf')));
        $this->postJson($url, [])->assertOk()->assertJsonPath('added', true);
        $previousReview = $applicant->fresh()->document_review;
        Storage::disk('local')->delete($old);
        $this->assertFalse(app(TosaWorkflowService::class)->isVerified($applicant->fresh()));
        $this->postJson($url, [])->assertUnprocessable();
        $this->assertSame($previousReview, $applicant->fresh()->document_review);
        $this->assertSame([$applicant->id], TosaShortlist::query()->findOrFail(1)->draft_ids);

        $replacement = 'tosa/manual-fixture/new-academic.pdf';
        Storage::disk('local')->put($replacement, file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf')));
        $requirements = $applicant->fresh()->requirements;
        $requirements['academic']['path'] = $replacement;
        $applicant->update(['requirements' => $requirements]);
        $this->assertFalse(app(TosaWorkflowService::class)->isVerified($applicant->fresh()));
        $this->postJson($url, [])->assertOk()->assertJsonPath('added', false);
        $this->assertSame($replacement, $applicant->fresh()->document_review['documents']['academic']['path']);
        $this->assertTrue(app(TosaWorkflowService::class)->isVerified($applicant->fresh()));
        $this->assertSame(1, TosaHistoryEvent::query()->where('action', 'top10_saved')->count());
    }

    public function test_only_oso_can_add_and_requests_cannot_spoof_attestations(): void
    {
        $applicant = $this->applicant();
        $url = route('office.tosa.top10.add', $applicant);
        $this->postJson($url, [])->assertUnauthorized();
        $this->actingAs($this->officer('ovcaa', 'Level 2 Evaluator'), 'office')
            ->postJson($url, [])->assertForbidden();
        $this->actingAs($this->officer(), 'office');
        $this->postJson($url, ['verified_by' => 999])->assertUnprocessable();
        $this->postJson($url, ['documents' => array_keys(TosaRequirements::SUBMISSIONS)])->assertUnprocessable();
        $applicant->update(['status' => 'draft']);
        $this->postJson($url, [])->assertNotFound();
        $this->assertSame(['records_management' => ['note' => 'historical']], $applicant->fresh()->document_review);
        $this->assertSame([], TosaShortlist::query()->findOrFail(1)->draft_ids ?? []);
    }

    public function test_eleventh_applicant_cannot_be_reviewed_or_added_to_a_full_draft(): void
    {
        $this->actingAs($this->officer(), 'office');
        $ids = [];
        foreach (range(1, 10) as $number) {
            $applicant = $this->applicant('manual-limit-'.$number);
            $this->postJson(route('office.tosa.top10.add', $applicant), [])
                ->assertOk()->assertJsonPath('draft_count', $number);
            $ids[] = $applicant->id;
        }
        $extra = $this->applicant('manual-limit-11');
        $this->postJson(route('office.tosa.top10.add', $extra), [])
            ->assertUnprocessable()->assertJsonValidationErrors('applicant');
        $this->assertSame(['records_management' => ['note' => 'historical']], $extra->fresh()->document_review);
        $this->assertSame($ids, TosaShortlist::query()->findOrFail(1)->draft_ids);
        $this->assertNull(TosaShortlist::query()->findOrFail(1)->published_entries);
    }

    public function test_oso_returns_a_specific_document_and_emails_the_linked_student(): void
    {
        $applicant = $this->applicant();
        $applicant->update(['email' => 'outdated@example.invalid']);
        UserAccount::query()->create([
            'sr_code' => $applicant->sr_code, 'full_name' => 'Synthetic Student',
            'email' => 'linked-student@example.invalid', 'account_status' => 'active', 'role' => 'student',
        ]);
        $url = route('office.tosa.subsection', $applicant);
        $payload = ['subsection' => 'returned', 'document_key' => 'application_form', 'reason' => 'Please add the missing signature.'];
        $this->actingAs($this->officer('ovcaa', 'Level 2 Evaluator'), 'office')
            ->postJson($url, $payload)->assertForbidden();
        $this->actingAs($this->officer(), 'office');
        $addition = route('office.tosa.top10.add', $applicant);
        $this->postJson($addition, [])->assertOk()->assertJsonPath('added', true);
        $this->postJson($url, ['subsection' => 'returned'])->assertUnprocessable()
            ->assertJsonValidationErrors(['document_key', 'reason']);
        $this->postJson($url, $payload + ['gwa' => 1.0])->assertUnprocessable();
        $this->postJson($url, array_merge($payload, ['document_key' => 'unknown']))->assertUnprocessable();
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('email_sent', true);
        $this->assertSame('returned', $applicant->fresh()->subsection);
        $event = TosaHistoryEvent::query()->where('action', 'returned')->where('applicant_id', $applicant->id)->sole();
        $this->assertSame(['document_key' => 'application_form', 'reason' => $payload['reason']], $event->details);
        $mail = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('linked-student@example.invalid', $mail->getTo()[0]->getAddress());
        $this->assertStringContainsString('Completed TOSA Application Form', $mail->getTextBody());
        $this->assertStringContainsString($payload['reason'], $mail->getTextBody());
        $this->assertStringContainsString('/portal#tosa', $mail->getTextBody());
        $this->assertCount(0, $mail->getAttachments());
        $this->assertSame(1.25, $applicant->fresh()->gwa);
        $this->assertFalse(app(TosaWorkflowService::class)->isVerified($applicant->fresh()));
        $this->postJson($addition, [])->assertUnprocessable();
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('subsection');
        $this->assertCount(1, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_unlinked_or_ambiguous_student_addresses_never_receive_the_correction_email(): void
    {
        $this->actingAs($this->officer(), 'office');
        $transport = Mail::mailer('array')->getSymfonyTransport();
        foreach (['missing-owner', 'ambiguous-owner'] as $srCode) {
            $applicant = $this->applicant($srCode);
            $applicant->update(['email' => 'unverified-snapshot@example.invalid']);
            if ($srCode === 'ambiguous-owner') {
                foreach (['first@example.invalid', 'second@example.invalid'] as $email) {
                    UserAccount::query()->create(['sr_code' => $srCode, 'email' => $email, 'account_status' => 'active']);
                }
            }
            $this->postJson(route('office.tosa.subsection', $applicant), [
                'subsection' => 'returned', 'document_key' => 'application_form', 'reason' => 'Upload the signed form.',
            ])->assertOk()->assertJsonPath('email_sent', false);
            $this->assertSame('returned', $applicant->fresh()->subsection);
        }
        $this->assertCount(0, $transport->messages());
    }

    public function test_failed_mail_transport_reports_failure_and_keeps_the_correction_reason(): void
    {
        $applicant = $this->applicant();
        UserAccount::query()->create(['sr_code' => $applicant->sr_code, 'email' => 'student@example.invalid', 'account_status' => 'active']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Simulated mail failure'));
        $this->actingAs($this->officer(), 'office')->postJson(route('office.tosa.subsection', $applicant), [
            'subsection' => 'returned', 'document_key' => 'application_form', 'reason' => 'Add a signature.',
        ])->assertOk()->assertJsonPath('email_sent', false);
        $this->assertSame('returned', $applicant->fresh()->subsection);
        $this->assertSame('Add a signature.', TosaHistoryEvent::query()->where('action', 'returned')->sole()->details['reason']);
    }

    public function test_removed_scoring_and_assignment_endpoints_cannot_change_a_packet(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office');
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/scoring', ['gwa' => 1.0])->assertNotFound();
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/committee', ['member_ids' => [1]])->assertNotFound();
        $this->assertSame(1.25, $applicant->fresh()->gwa);
        $this->assertSame(['members' => [7]], $applicant->fresh()->committee_members);
    }
}

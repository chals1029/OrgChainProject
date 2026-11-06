<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Models\UserAccount;
use App\Services\TosaRequirements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemporaryTosaDropzoneHeightVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => $memory,
            'database.connections.voting' => $memory,
            'mail.default' => 'array',
        ]);
        DB::purge('mysql');
        Storage::fake('local');

        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table): void {
            $table->increments('user_id');
            foreach (['sr_code', 'full_name', 'email', 'college', 'program', 'year_level', 'account_status', 'role'] as $column) {
                $table->text($column)->nullable();
            }
        });
        Schema::connection('mysql')->create('student_organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('college')->nullable();
            $table->string('academic_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('scope')->unique();
            $table->json('values')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('org_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('workflow_status')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('activity_workflow_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('org_activity_id');
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::connection('mysql')->create('office_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->text('body');
            $table->string('priority')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_templates', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'category', 'format', 'size', 'description', 'file_path', 'original_name'] as $column) {
                $table->text($column)->nullable();
            }
            $table->integer('downloads')->default(0);
            $table->timestamps();
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
            foreach (['name', 'email', 'username', 'password', 'office_role', 'office_title', 'tosa_clearance', 'remember_token'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->integer('auth_version')->default(0);
            $table->unsignedBigInteger('student_organization_id')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_31_000001_create_tosa_workspace_tables.php'))->up();
        (require database_path('migrations/2026_11_05_000001_add_tosa_published_decisions.php'))->up();
        if (is_file(database_path('migrations/2026_11_06_000001_add_tosa_shortlist_captions.php'))) {
            (require database_path('migrations/2026_11_06_000001_add_tosa_shortlist_captions.php'))->up();
        }
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function officer(string $role = 'oso'): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'OSO Reviewer',
            'office_role' => $role,
            'is_active' => true,
            'tosa_clearance' => 'Level 3 Master',
        ]);
    }

    private function applicant(int $number, bool $verified = true, ?string $name = null, string $program = 'BSIT'): TosaApplicant
    {
        $srCode = '26-000'.$number;
        $requirements = [];
        $reviews = [];
        $pdf = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        $docx = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $format = $key === 'application_form' ? 'docx' : 'pdf';
            $path = 'tosa/'.$srCode.'/'.$key.'.'.$format;
            Storage::disk('local')->put($path, $format === 'docx' ? $docx : $pdf);
            $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$format];
            $reviews[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
        }
        $applicant = TosaApplicant::query()->create([
            'sr_code' => $srCode,
            'full_name' => $name ?? 'Applicant '.$number,
            'program' => $program,
            'year_level' => '4th Year',
            'status' => 'submitted',
            'subsection' => 'screening',
            'requirements' => $requirements,
        ]);
        if ($verified) {
            $applicant->forceFill(['document_review' => ['documents' => $reviews]])->save();
        }

        return $applicant->fresh();
    }

    public function test_multiple_document_correction_removes_from_published_and_draft_without_same_name_ambiguity(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $sameNameFirst = $this->applicant(1, true, 'Alex Dela Cruz', 'BSIT');
        $sameNameSecond = $this->applicant(2, true, 'Alex Dela Cruz', 'BSIT');
        $otherSelected = $this->applicant(3, true, 'Bea Santos', 'BSCS');
        $otherNotQualified = $this->applicant(4, true, 'Carlo Reyes', 'BSIT');

        UserAccount::query()->create([
            'sr_code' => $sameNameFirst->sr_code,
            'full_name' => $sameNameFirst->full_name,
            'email' => 'alex1@g.batstate-u.edu.ph',
            'account_status' => 'active',
            'role' => 'student',
        ]);

        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->forceFill([
            'draft_ids' => [$sameNameFirst->id, $sameNameSecond->id, $otherSelected->id],
            'published_at' => now(),
            'published_entries' => [
                ['name' => 'Alex Dela Cruz', 'program' => 'BSIT'],
                ['name' => 'Alex Dela Cruz', 'program' => 'BSIT'],
                ['name' => 'Bea Santos', 'program' => 'BSCS'],
            ],
            'published_decisions' => [
                (string) $sameNameFirst->id => [
                    'status' => 'qualified',
                    'review' => TosaShortlist::reviewFingerprint($sameNameFirst),
                    'name' => $sameNameFirst->full_name,
                    'program' => $sameNameFirst->program,
                    'caption' => 'Qualified notice',
                ],
                (string) $sameNameSecond->id => [
                    'status' => 'qualified',
                    'review' => TosaShortlist::reviewFingerprint($sameNameSecond),
                    'name' => $sameNameSecond->full_name,
                    'program' => $sameNameSecond->program,
                    'caption' => 'Qualified notice',
                ],
                (string) $otherSelected->id => [
                    'status' => 'qualified',
                    'review' => TosaShortlist::reviewFingerprint($otherSelected),
                    'name' => $otherSelected->full_name,
                    'program' => $otherSelected->program,
                    'caption' => 'Qualified notice',
                ],
                (string) $otherNotQualified->id => [
                    'status' => 'not_qualified',
                    'review' => TosaShortlist::reviewFingerprint($otherNotQualified),
                    'name' => $otherNotQualified->full_name,
                    'program' => $otherNotQualified->program,
                    'caption' => 'Not qualified notice',
                ],
            ],
        ])->save();

        $url = route('office.tosa.subsection', $sameNameFirst);
        $this->postJson($url, ['subsection' => 'returned', 'document_keys' => [], 'reason' => 'Missing'])->assertUnprocessable();
        $this->postJson($url, ['subsection' => 'returned', 'document_keys' => ['cv', 'cv'], 'reason' => 'Duplicate'])->assertUnprocessable();
        $this->postJson($url, ['subsection' => 'returned', 'document_keys' => ['invalid_key'], 'reason' => 'Invalid'])->assertUnprocessable();

        $this->postJson($url, [
            'subsection' => 'returned',
            'document_keys' => ['application_form', 'good_moral'],
            'reason' => 'Please sign the application form and attach certified good moral.',
        ])->assertOk()->assertJsonPath('email_sent', true);

        $shortlist->refresh();
        $this->assertNotNull($shortlist->published_at);
        $this->assertSame([$sameNameSecond->id, $otherSelected->id], $shortlist->draft_ids);
        $this->assertArrayNotHasKey((string) $sameNameFirst->id, $shortlist->published_decisions);
        $this->assertSame('qualified', $shortlist->decisionFor($sameNameSecond->fresh(), true));
        $this->assertSame('qualified', $shortlist->decisionFor($otherSelected->fresh(), true));
        $this->assertSame('not_qualified', $shortlist->decisionFor($otherNotQualified->fresh(), true));
        $this->assertSame([
            ['name' => 'Alex Dela Cruz', 'program' => 'BSIT'],
            ['name' => 'Bea Santos', 'program' => 'BSCS'],
        ], $shortlist->published_entries);

        $this->assertDatabaseHas('tosa_history_events', [
            'action' => 'top10_removed',
            'applicant_id' => $sameNameFirst->id,
            'actor_id' => $office->id,
        ], 'mysql');
        $returnedEvent = TosaHistoryEvent::query()->where('action', 'returned')->where('applicant_id', $sameNameFirst->id)->sole();
        $this->assertSame(['application_form', 'good_moral'], $returnedEvent->details['document_keys']);
        $this->assertSame('Please sign the application form and attach certified good moral.', $returnedEvent->details['reason']);

        $mail = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertStringContainsString('Documents: Completed TOSA Application Form, Good Moral Character', $mail->getTextBody());
    }

    public function test_legacy_published_entries_without_decisions_preserve_unrelated_and_ambiguous_entries(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $uniqueReturned = $this->applicant(1, true, 'Unique Returned', 'BSIT');
        $duplicateOne = $this->applicant(2, true, 'Ambiguous Student', 'BSCS');
        $duplicateTwo = $this->applicant(3, true, 'Ambiguous Student', 'BSCS');

        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->forceFill([
            'draft_ids' => [$uniqueReturned->id, $duplicateOne->id],
            'published_at' => now(),
            'published_decisions' => null,
            'published_entries' => [
                ['name' => 'Unique Returned', 'program' => 'BSIT'],
                ['name' => 'Ambiguous Student', 'program' => 'BSCS'],
                ['name' => 'Preserved Legacy Entry', 'program' => 'BSEE'],
            ],
        ])->save();

        $this->postJson(route('office.tosa.subsection', $uniqueReturned), [
            'subsection' => 'returned',
            'document_keys' => ['cv'],
            'reason' => 'Update CV.',
        ])->assertOk();

        $this->assertSame([
            ['name' => 'Ambiguous Student', 'program' => 'BSCS'],
            ['name' => 'Preserved Legacy Entry', 'program' => 'BSEE'],
        ], $shortlist->fresh()->published_entries);

        $this->postJson(route('office.tosa.subsection', $duplicateOne), [
            'subsection' => 'returned',
            'document_keys' => ['academic'],
            'reason' => 'Update grades.',
        ])->assertOk();

        $this->assertSame([
            ['name' => 'Ambiguous Student', 'program' => 'BSCS'],
            ['name' => 'Preserved Legacy Entry', 'program' => 'BSEE'],
        ], $shortlist->fresh()->published_entries);
    }

    public function test_submissions_status_filters_and_history_filters_with_dynamic_shortlist(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $selectedReviewed = $this->applicant(1, true, 'Alpha Qualified', 'BSIT');
        $nonSelectedReviewed = $this->applicant(2, true, 'Bravo Reviewed', 'BSCS');
        $pendingApplicant = $this->applicant(3, false, 'Charlie Pending', 'BSEE');
        $returnedApplicant = $this->applicant(4, true, 'Delta Returned', 'BSME');
        $returnedApplicant->update(['subsection' => 'returned']);

        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->forceFill(['draft_ids' => [$selectedReviewed->id], 'published_at' => null])->save();
        file_put_contents(sys_get_temp_dir().'/tosa-dropzone-height.html', $this->get(route('office.tosa', ['tab' => 'documents']))->assertOk()->getContent());


        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'qualified']))
            ->assertOk()
            ->assertViewHas('tosaEditing', true)
            ->assertViewHas('tosaApplicants', fn ($p) => $p->pluck('id')->all() === [$selectedReviewed->id]);

        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'reviewed']))
            ->assertOk()
            ->assertViewHas('tosaApplicants', fn ($p) => $p->pluck('id')->all() === [$nonSelectedReviewed->id]);

        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'not_qualified']))
            ->assertOk()
            ->assertViewHas('tosaApplicants', fn ($p) => $p->total() === 0);

        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'pending_review']))
            ->assertOk()
            ->assertViewHas('tosaApplicants', fn ($p) => $p->pluck('id')->all() === [$pendingApplicant->id]);

        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'for_correction']))
            ->assertOk()
            ->assertViewHas('tosaApplicants', fn ($p) => $p->pluck('id')->all() === [$returnedApplicant->id]);

        $shortlist->forceFill([
            'published_at' => now(),
            'published_decisions' => [
                (string) $selectedReviewed->id => [
                    'status' => 'qualified',
                    'review' => TosaShortlist::reviewFingerprint($selectedReviewed),
                    'name' => $selectedReviewed->full_name,
                    'program' => $selectedReviewed->program,
                ],
                (string) $nonSelectedReviewed->id => [
                    'status' => 'not_qualified',
                    'review' => TosaShortlist::reviewFingerprint($nonSelectedReviewed),
                    'name' => $nonSelectedReviewed->full_name,
                    'program' => $nonSelectedReviewed->program,
                ],
            ],
        ])->save();

        $this->get(route('office.tosa', ['tab' => 'submissions', 'result' => 'not_qualified']))
            ->assertOk()
            ->assertViewHas('tosaEditing', false)
            ->assertViewHas('tosaApplicants', fn ($p) => $p->pluck('id')->all() === [$nonSelectedReviewed->id]);

        TosaHistoryEvent::record('submitted', 'student', 1, 'Alpha Qualified', $selectedReviewed->id);
        TosaHistoryEvent::record('reviewed', 'office', $office->id, $office->name, $selectedReviewed->id);
        TosaHistoryEvent::record('top10_saved', 'office', $office->id, $office->name, $selectedReviewed->id);
        TosaHistoryEvent::record('selection_updated', 'office', $office->id, $office->name, null, ['draft_count' => 1]);
        TosaHistoryEvent::record('returned', 'office', $office->id, $office->name, $returnedApplicant->id, [
            'document_keys' => ['good_moral', 'academic'],
            'reason' => 'Missing registrar signature',
        ]);
        TosaHistoryEvent::record('top10_published', 'office', $office->id, $office->name, null, ['names' => ['Alpha Qualified']]);
        TosaHistoryEvent::record('top10_unpublished', 'office', $office->id, $office->name);

        foreach (['submissions', 'documents', 'top10', 'history'] as $tabName) {
            $this->get(route('office.tosa', ['tab' => $tabName]))
                ->assertOk()
                ->assertViewHas('tosaShortlist', fn ($s) => $s instanceof TosaShortlist && $s->id === 1);
        }

        $this->get(route('office.tosa', ['tab' => 'history', 'activity' => 'publishing']))
            ->assertOk()
            ->assertViewHas('tosaEvents', fn ($events) => $events->pluck('action')->all() === ['top10_unpublished', 'top10_published']);

        $this->get(route('office.tosa', ['tab' => 'history', 'activity' => 'applicant_changes']))
            ->assertOk()
            ->assertViewHas('tosaEvents', fn ($events) => $events->pluck('action')->all() === ['returned', 'selection_updated', 'top10_saved']);

        $this->get(route('office.tosa', ['tab' => 'history', 'activity' => 'all']))
            ->assertOk()
            ->assertViewHas('tosaEvents', fn ($events) => $events->total() === 7);

        $this->get(route('office.tosa', ['tab' => 'history', 'history_search' => 'Good Moral']))
            ->assertOk()
            ->assertViewHas('tosaEvents', fn ($events) => $events->pluck('action')->all() === ['returned']);
    }
}

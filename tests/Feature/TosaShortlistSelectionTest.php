<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Services\TosaRequirements;
use App\Services\TosaSelectionEdit;
use App\Services\TosaWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TosaShortlistSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory]);
        DB::purge('mysql');
        Storage::fake('local');

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
        (require database_path('migrations/2026_11_05_000001_add_tosa_published_decisions.php'))->up();
        (require database_path('migrations/2026_11_06_000001_add_tosa_shortlist_captions.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function officer(string $role = 'oso'): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'TOSA Officer',
            'office_role' => $role,
            'is_active' => true,
            'auth_version' => 1,
        ]);
    }

    private function applicant(int $number, bool $reviewed = true): TosaApplicant
    {
        $srCode = 'selection-test-'.$number;
        $requirements = [];
        $documents = [];
        $pdf = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        $docx = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));

        foreach (TosaRequirements::SUBMISSIONS as $key => $_definition) {
            $extension = $key === 'application_form' ? 'docx' : 'pdf';
            $path = 'tosa/'.$srCode.'/'.$key.'.'.$extension;
            Storage::disk('local')->put($path, $extension === 'docx' ? $docx : $pdf);
            $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$extension];
            $documents[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
        }

        $applicant = TosaApplicant::query()->create([
            'sr_code' => $srCode,
            'full_name' => sprintf('Applicant %02d', $number),
            'email' => $srCode.'@example.test',
            'college' => 'CICS',
            'program' => 'BSCS',
            'year_level' => '4th Year',
            'status' => 'submitted',
            'subsection' => 'screening',
            'requirements' => $requirements,
        ]);

        if ($reviewed) {
            $applicant->forceFill(['document_review' => ['documents' => $documents]])->save();
        }

        return $applicant;
    }

    public function test_publication_requires_nonblank_captions_up_to_300_chars_and_retains_both_across_unpublish(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $selected = collect(range(1, 10))->map(fn (int $index): TosaApplicant => $this->applicant($index, true));
        $ids = $selected->pluck('id')->all();
        foreach ($selected as $applicant) {
            $this->postJson(route('office.tosa.top10.add', $applicant), [])->assertOk();
        }

        $saveUrl = route('office.tosa.top10.save');

        // Missing or blank captions are rejected with 422
        $this->postJson($saveUrl, ['intent' => 'publish', 'applicant_ids' => $ids])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['caption_qualified', 'caption_not_qualified']);

        $this->postJson($saveUrl, [
            'intent' => 'publish',
            'applicant_ids' => $ids,
            'caption_qualified' => '   ',
            'caption_not_qualified' => "\n\t ",
        ])->assertUnprocessable()->assertJsonValidationErrors(['caption_qualified', 'caption_not_qualified']);

        // Captions exceeding 300 characters are rejected
        $this->postJson($saveUrl, [
            'intent' => 'publish',
            'applicant_ids' => $ids,
            'caption_qualified' => str_repeat('Q', 301),
            'caption_not_qualified' => str_repeat('N', 301),
        ])->assertUnprocessable()->assertJsonValidationErrors(['caption_qualified', 'caption_not_qualified']);

        // Exact 300-character captions succeed and redirect to section=published for web requests
        $qualifiedCaption = str_repeat('A', 300);
        $notQualifiedCaption = str_repeat('B', 300);
        $this->post($saveUrl, [
            'intent' => 'publish',
            'applicant_ids' => $ids,
            'caption_qualified' => $qualifiedCaption,
            'caption_not_qualified' => $notQualifiedCaption,
        ])->assertRedirect(route('office.tosa', ['tab' => 'top10', 'section' => 'published']));

        $shortlist = TosaShortlist::query()->findOrFail(1);
        $this->assertNotNull($shortlist->published_at);
        $this->assertSame($qualifiedCaption, $shortlist->caption_qualified);
        $this->assertSame($notQualifiedCaption, $shortlist->caption_not_qualified);

        // Even when all reviewed applicants were selected (0 not_qualified decisions), both captions persist across unpublish
        $this->post($saveUrl, ['intent' => 'unpublish'])
            ->assertRedirect(route('office.tosa', ['tab' => 'top10', 'section' => 'selection']));

        $shortlist->refresh();
        $this->assertNull($shortlist->published_at);
        $this->assertNull($shortlist->published_entries);
        $this->assertNull($shortlist->published_decisions);
        $this->assertSame($qualifiedCaption, $shortlist->caption_qualified);
        $this->assertSame($notQualifiedCaption, $shortlist->caption_not_qualified);
    }

    public function test_unpublishing_preserves_legacy_long_captions_for_editing(): void
    {
        $office = $this->officer();
        $qualified = str_repeat('Q', 301);
        $notQualified = str_repeat('N', 301);
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->update(['published_at' => now(), 'published_decisions' => [
            '1' => ['status' => 'qualified', 'caption' => $qualified],
            '2' => ['status' => 'not_qualified', 'caption' => $notQualified],
        ]]);

        $this->actingAs($office, 'office')->postJson(route('office.tosa.top10.save'), ['intent' => 'unpublish'])->assertOk();
        $this->assertSame($qualified, $shortlist->fresh()->caption_qualified);
        $this->assertSame($notQualified, $shortlist->fresh()->caption_not_qualified);
    }

    public function test_published_lock_blocks_edits_and_cannot_be_bypassed_by_removed_edit_route_or_session(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $selected = collect(range(1, 10))->map(fn (int $index): TosaApplicant => $this->applicant($index, true));
        $extra = $this->applicant(11, true);
        $ids = $selected->pluck('id')->all();

        TosaShortlist::query()->findOrFail(1)->update(['draft_ids' => $ids]);
        $saveUrl = route('office.tosa.top10.save');

        $this->postJson($saveUrl, [
            'intent' => 'publish',
            'applicant_ids' => $ids,
            'caption_qualified' => 'Qualified caption',
            'caption_not_qualified' => 'Not qualified caption',
        ])->assertOk();

        $shortlist = TosaShortlist::query()->findOrFail(1);
        $this->assertFalse(app(TosaSelectionEdit::class)->isEditing(request(), $office, $shortlist));

        // Obsolete edit endpoint is gone
        $this->postJson('/office-desk/tosa/top-ten/edit', [])->assertNotFound();

        // Forged session cannot unlock published state
        $forgedSession = [
            'tosa_selection_edit' => [
                'office_id' => $office->id,
                'auth_version' => $office->auth_version,
                'publication' => '1:'.$shortlist->published_at->toIso8601String(),
            ],
        ];

        $this->withSession($forgedSession)
            ->postJson(route('office.tosa.top10.remove', $ids[0]), [])
            ->assertForbidden();

        $this->withSession($forgedSession)
            ->postJson(route('office.tosa.top10.add', $extra), [])
            ->assertForbidden();

        // Published check runs before trusting caption inputs on publish
        $this->withSession($forgedSession)
            ->postJson($saveUrl, ['intent' => 'publish', 'applicant_ids' => $ids])
            ->assertForbidden();

        $this->withSession($forgedSession)
            ->postJson($saveUrl, [
                'intent' => 'publish',
                'applicant_ids' => $ids,
                'caption_qualified' => 'Forged caption',
                'caption_not_qualified' => 'Forged caption 2',
            ])->assertForbidden();

        // Unknown intents are rejected
        $this->postJson($saveUrl, ['intent' => 'save'])->assertUnprocessable();

        $this->assertSame('Qualified caption', $shortlist->fresh()->caption_qualified);
    }

    public function test_selection_updated_history_event_is_recorded_on_real_add_and_remove_changes_only(): void
    {
        $office = $this->officer();
        $this->actingAs($office, 'office');

        $first = $this->applicant(1, true);
        $second = $this->applicant(2, true);
        $unreviewed = $this->applicant(3, false);

        // Unreviewed applicant cannot be added
        $this->postJson(route('office.tosa.top10.add', $unreviewed), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('applicant');
        $this->assertSame(0, TosaHistoryEvent::query()->where('action', 'selection_updated')->count());

        // Real additions record top10_saved and selection_updated
        $this->postJson(route('office.tosa.top10.add', $first), [])
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('draft_count', 1);
        $this->postJson(route('office.tosa.top10.add', $second), [])
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('draft_count', 2);

        $this->assertSame(2, TosaHistoryEvent::query()->where('action', 'top10_saved')->count());
        $this->assertSame(2, TosaHistoryEvent::query()->where('action', 'selection_updated')->count());

        // Duplicate addition is a no-op and does not record extra history
        $this->postJson(route('office.tosa.top10.add', $first), [])
            ->assertOk()
            ->assertJsonPath('added', false)
            ->assertJsonPath('draft_count', 2);
        $this->assertSame(2, TosaHistoryEvent::query()->where('action', 'top10_saved')->count());
        $this->assertSame(2, TosaHistoryEvent::query()->where('action', 'selection_updated')->count());

        // Removing a selected applicant keeps them reviewed and records top10_removed + selection_updated
        $this->post(route('office.tosa.top10.remove', $first->id), [])
            ->assertRedirect(route('office.tosa', ['tab' => 'top10', 'section' => 'selection']));

        $this->assertSame([$second->id], TosaShortlist::query()->findOrFail(1)->draft_ids);
        $this->assertTrue(app(TosaWorkflowService::class)->isVerified($first->fresh()));
        $this->assertDatabaseHas('tosa_history_events', [
            'action' => 'top10_removed',
            'applicant_id' => $first->id,
            'actor_id' => $office->id,
        ], 'mysql');
        $this->assertSame(3, TosaHistoryEvent::query()->where('action', 'selection_updated')->count());

        // Duplicate removal is a no-op and does not record extra history
        $this->postJson(route('office.tosa.top10.remove', $first->id), [])
            ->assertOk()
            ->assertJsonPath('removed', false);
        $this->assertSame(3, TosaHistoryEvent::query()->where('action', 'selection_updated')->count());
    }
}

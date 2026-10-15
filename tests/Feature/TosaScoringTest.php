<?php

namespace Tests\Feature;

use App\Http\Controllers\TosaScoringController;
use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\UserAccount;
use App\Services\TosaRequirements;
use App\Services\TosaScoringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TosaScoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.orgchain' => $memory]);
        DB::purge('mysql');
        DB::purge('orgchain');
        Storage::fake('local');
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'status', 'subsection', 'requirements', 'remarks'] as $column) {
                $table->text($column)->nullable();
            }
            $table->decimal('gwa', 4, 2)->nullable();
            $table->boolean('gwa_verified')->default(false);
            $table->string('academic_year')->default('2025-2026');
            $table->json('score_evaluation')->nullable();
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
        Schema::connection('mysql')->create('student_organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });
        DB::connection('mysql')->table('student_organizations')->insert(['id' => 1, 'name' => 'Synthetic Organization', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        DB::purge('orgchain');
        parent::tearDown();
    }

    private function officer(string $role = 'oso', ?string $clearance = null, bool $active = true): OfficeUser
    {
        return OfficeUser::query()->create(['name' => 'Server Evaluator', 'office_role' => $role,
            'tosa_clearance' => $clearance, 'is_active' => $active, 'student_organization_id' => $role === 'so' ? 1 : null]);
    }

    private function applicant(): TosaApplicant
    {
        $requirements = [];
        $bytes = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $path = 'tosa/synthetic-scoring/'.$key.'.pdf';
            Storage::disk('local')->put($path, $bytes);
            $requirements[$key] = ['path' => $path, 'name' => $key.'.pdf'];
        }
        return TosaApplicant::query()->create(['sr_code' => 'synthetic-scoring', 'full_name' => 'Synthetic Candidate',
            'status' => 'submitted', 'subsection' => 'accepted', 'academic_year' => '2025-2026',
            'gwa' => 1.34, 'gwa_verified' => true, 'requirements' => $requirements]);
    }

    private function payload(string $intent = 'finalize'): array
    {
        return ['intent' => $intent, 'academic_year' => '2025-2026', 'gwa' => 1.34,
            'gwa_verified' => true, 'eligible_verified' => true, 'academic_awards' => [],
            'leadership_seminars' => [], 'leadership_positions' => [
                ['label' => '  Actual local position  ', 'evidence' => '  Section F, page 4  ', 'level' => 'local', 'role' => 'other'],
            ], 'leadership_awards' => [], 'social_activities' => []];
    }

    private function scoreUrl(TosaApplicant $applicant): string
    {
        return route('office.tosa.scoring.save', $applicant);
    }

    public function test_finalization_computes_actual_evidence_and_server_identity_without_changing_packet_stage(): void
    {
        $office = $this->officer();
        $applicant = $this->applicant();
        $response = $this->actingAs($office, 'office')->postJson($this->scoreUrl($applicant), $this->payload())
            ->assertOk()->assertJsonPath('ok', true)->assertJsonPath('evaluation.status', 'finalized')
            ->assertJsonPath('evaluation.breakdown.grades_milli', 26000)
            ->assertJsonPath('evaluation.breakdown.leadership_local_milli', 125)
            ->assertJsonPath('evaluation.breakdown.total_milli', 26125)
            ->assertJsonPath('evaluation.verified_by.id', $office->id)
            ->assertJsonPath('evaluation.verified_by.name', 'Server Evaluator')
            ->assertJsonPath('evaluation.entries.0.label', 'Actual local position')
            ->assertJsonPath('ranking.0.rank', 1);
        $this->assertNotNull($response->json('evaluation.verified_at'));
        $saved = $applicant->fresh();
        $this->assertSame('submitted', $saved->status);
        $this->assertSame('accepted', $saved->subsection);
        $this->assertSame($response->json('evaluation'), app(TosaScoringService::class)->evaluation($saved));
    }

    public function test_evaluator_draft_removes_official_rank_and_clears_verification_metadata(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), $this->payload())->assertOk();
        $this->postJson($this->scoreUrl($applicant), $this->payload('draft'))->assertOk()
            ->assertJsonPath('evaluation.status', 'draft')->assertJsonPath('evaluation.verified_at', null)
            ->assertJsonPath('evaluation.verified_by', null)->assertJsonPath('ranking', []);
    }

    public function test_guest_and_student_sessions_cannot_write_evaluator_scores(): void
    {
        $applicant = $this->applicant();
        $this->postJson($this->scoreUrl($applicant), $this->payload())->assertUnauthorized();
        $student = (new UserAccount)->forceFill(['user_id' => 99, 'sr_code' => 'synthetic-scoring', 'role' => 'student']);
        $this->actingAs($student, 'student')->postJson($this->scoreUrl($applicant), $this->payload())->assertUnauthorized();
        $this->assertNull($applicant->fresh()->score_evaluation);
    }

    #[DataProvider('forbiddenActors')]
    public function test_foreign_and_readonly_office_actors_cannot_change_scores(string $role, ?string $clearance): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer($role, $clearance), 'office')->postJson($this->scoreUrl($applicant), $this->payload())->assertForbidden();
        $this->assertNull($applicant->fresh()->score_evaluation);
    }

    public static function forbiddenActors(): array
    {
        return [['so', null], ['sdo', null], ['oc', null], ['ovcaa', null], ['ovcaa', 'Level 1 Read-only']];
    }

    public function test_active_ovcaa_evaluator_can_finalize_and_inactive_evaluator_cannot(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer('ovcaa', 'Level 2 Evaluator'), 'office')
            ->postJson($this->scoreUrl($applicant), $this->payload())->assertOk();
        $before = $applicant->fresh()->score_evaluation;
        $this->actingAs($this->officer('oso', null, false), 'office')
            ->postJson($this->scoreUrl($applicant), $this->payload('draft'))->assertUnauthorized();
        $this->assertSame($before, $applicant->fresh()->score_evaluation);
    }

    #[DataProvider('invalidFinalizations')]
    public function test_finalization_requires_verified_eligibility_gwa_and_academic_year(array $overrides): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), [...$this->payload(), ...$overrides])->assertUnprocessable();
        $this->assertNull($applicant->fresh()->score_evaluation);
    }

    public static function invalidFinalizations(): array
    {
        return [[['gwa_verified' => false]], [['eligible_verified' => false]], [['gwa' => null]],
            [['gwa' => 3.01]], [['gwa' => 1.345]], [['academic_year' => null]],
            [['academic_year' => '2025-2027']], [['academic_year' => ['2025-2026']]]];
    }

    public function test_all_seven_valid_original_files_are_required(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office');
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $path = $applicant->requirementPath($key);
            $bytes = Storage::disk('local')->get($path);
            Storage::disk('local')->delete($path);
            $this->postJson($this->scoreUrl($applicant), $this->payload())->assertUnprocessable();
            $this->assertNull($applicant->fresh()->score_evaluation);
            Storage::disk('local')->put($path, 'renamed HTML, not an original PDF');
            $this->postJson($this->scoreUrl($applicant), $this->payload())->assertUnprocessable();
            Storage::disk('local')->put($path, $bytes);
        }
        $this->postJson($this->scoreUrl($applicant), $this->payload())->assertOk();
    }

    #[DataProvider('forgedFields')]
    public function test_client_totals_identity_and_nested_points_are_rejected(array $overrides): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), [...$this->payload(), ...$overrides])->assertUnprocessable();
        $this->assertNull($applicant->fresh()->score_evaluation);
    }

    public static function forgedFields(): array
    {
        return [[['total_milli' => 100000]], [['verified_by' => ['id' => 999, 'name' => 'Forged']]],
            [['breakdown' => ['total_milli' => 100000]]], [['rubric_version' => 'forged']],
            [['leadership_positions' => [['label' => 'Position', 'evidence' => 'F page 4', 'level' => 'local', 'role' => 'other', 'points_milli' => 100000]]]]];
    }

    public function test_draft_supports_no_gwa_and_empty_claims_but_enforces_bounded_lists(): void
    {
        $applicant = $this->applicant();
        $payload = [...$this->payload('draft'), 'gwa' => null, 'gwa_verified' => false, 'eligible_verified' => false,
            'academic_year' => null, 'leadership_positions' => []];
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), $payload)->assertOk()
            ->assertJsonPath('evaluation.breakdown.total_milli', 0)->assertJsonPath('evaluation.entries', [])
            ->assertJsonPath('academicYear', '2025-2026');
        $payload['leadership_awards'] = array_fill(0, 201, ['label' => 'Award', 'evidence' => 'F page 1', 'level' => 'local']);
        $this->postJson($this->scoreUrl($applicant), $payload)->assertUnprocessable();
        unset($payload['leadership_awards']);
        $this->postJson($this->scoreUrl($applicant), $payload)->assertUnprocessable();
    }

    #[DataProvider('closedStages')]
    public function test_returned_rejected_and_unsubmitted_packets_cannot_be_finalized_or_drafted(string $status, string $subsection): void
    {
        $applicant = $this->applicant();
        $applicant->update(compact('status', 'subsection'));
        $this->actingAs($this->officer(), 'office');
        foreach (['draft', 'finalize'] as $intent) {
            $this->postJson($this->scoreUrl($applicant), $this->payload($intent))->assertUnprocessable();
        }
        $this->assertNull($applicant->fresh()->score_evaluation);
    }

    public static function closedStages(): array
    {
        return [['submitted', 'returned'], ['submitted', 'rejected'], ['draft', 'accepted']];
    }

    public function test_stale_route_bound_applicant_is_refreshed_before_finalization(): void
    {
        $stale = $this->applicant();
        $this->actingAs($this->officer(), 'office');
        TosaApplicant::query()->whereKey($stale->id)->update(['subsection' => 'returned']);
        $request = Request::create('/office-desk/tosa/'.$stale->id.'/scoring', 'POST', $this->payload());
        try {
            app(TosaScoringController::class)->save($request, $stale);
            $this->fail('A stale accepted route model must not finalize a returned packet.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('evaluation', $exception->errors());
        }
        $this->assertSame('returned', $stale->fresh()->subsection);
        $this->assertNull($stale->fresh()->score_evaluation);
    }

    public function test_qualification_gwa_change_invalidates_and_recomputes_evaluation_with_evidence_retained(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), $this->payload())->assertOk();
        $this->postJson(route('office.tosa.subsection', $applicant), ['subsection' => 'accepted', 'gwa' => 1.35, 'gwa_verified' => true])
            ->assertOk()->assertJsonPath('evaluation.status', 'draft')->assertJsonPath('gwa', 1.35)
            ->assertJsonPath('evaluation.breakdown.grades_milli', 25000)->assertJsonPath('evaluation.breakdown.total_milli', 25125)
            ->assertJsonPath('evaluation.entries.0.label', 'Actual local position')->assertJsonPath('ranking', [])
            ->assertJsonPath('evaluation.verified_by', null);
        $this->assertSame('draft', $applicant->fresh()->score_evaluation['status']);
    }

    #[DataProvider('invalidatingReviews')]
    public function test_qualification_return_reject_and_gwa_unverification_remove_official_rank(array $review): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer(), 'office')->postJson($this->scoreUrl($applicant), $this->payload())->assertOk();
        $this->postJson(route('office.tosa.subsection', $applicant), $review)->assertOk()
            ->assertJsonPath('evaluation.status', 'draft')->assertJsonPath('evaluation.verified_at', null)
            ->assertJsonPath('ranking', []);
        $this->assertSame([], app(TosaScoringService::class)->ranking([$applicant->fresh()]));
    }

    public static function invalidatingReviews(): array
    {
        return [[['subsection' => 'returned']], [['subsection' => 'rejected']], [['subsection' => 'accepted', 'gwa_verified' => false]]];
    }
}

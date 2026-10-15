<?php

namespace Tests\Unit;

use App\Models\TosaApplicant;
use App\Services\TosaScoringService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TosaScoringServiceTest extends TestCase
{
    private function input(?float $gwa = 1.34): array
    {
        return ['gwa' => $gwa, 'gwa_verified' => true, 'eligible_verified' => true,
            'academic_awards' => [], 'leadership_seminars' => [], 'leadership_positions' => [],
            'leadership_awards' => [], 'social_activities' => []];
    }

    private function row(array $fields): array
    {
        return ['label' => 'Actual achievement', 'evidence' => 'Section F, page 4', ...$fields];
    }

    private function applicant(int $id, float $gwa = 1.34, string $year = '2025-2026'): TosaApplicant
    {
        $input = $this->input($gwa);
        $service = new TosaScoringService;
        return (new TosaApplicant)->forceFill(['id' => $id, 'status' => 'submitted', 'subsection' => 'accepted',
            'academic_year' => $year, 'gwa' => $gwa, 'gwa_verified' => true, 'score_evaluation' => [
                'rubric_version' => TosaScoringService::RUBRIC_VERSION, 'status' => 'finalized', 'input' => $input,
                ...$service->calculate($gwa, $input), 'verified_at' => '2026-10-11T12:00:00+08:00',
                'verified_by' => ['id' => 5, 'name' => 'Verified Evaluator'], 'updated_at' => '2026-10-11T12:00:00+08:00',
            ]]);
    }

    #[DataProvider('gradeBands')]
    public function test_actual_workbook_gwa_thresholds(float $gwa, int $points): void
    {
        $result = (new TosaScoringService)->calculate($gwa, $this->input($gwa));
        $this->assertSame($points * 1000, $result['breakdown']['grades_milli']);
        $this->assertSame($points * 1000, $result['breakdown']['total_milli']);
        $this->assertSame([], $result['entries']);
    }

    public static function gradeBands(): array
    {
        $thresholds = [100, 107, 114, 121, 128, 135, 141, 148, 155, 162, 169, 176, 183, 190, 197, 203, 210, 217, 224, 231, 238, 245, 252, 259, 266, 272, 279, 286, 293, 300];
        $cases = [];
        foreach ($thresholds as $index => $threshold) {
            $cases['at '.$threshold] = [$threshold / 100, 30 - $index];
            if ($index > 0) {
                $cases['before '.$threshold] = [($threshold - 1) / 100, 31 - $index];
            }
        }
        $cases['ineligible'] = [3.01, 0];
        return $cases;
    }

    public function test_all_actual_award_places_and_levels_use_integer_milli_points(): void
    {
        $input = $this->input(null);
        $expected = [];
        foreach (['international' => [3000, 2000, 1000, 500], 'national' => [2000, 1000, 500, 250], 'local' => [1000, 500, 250, 125]] as $level => $points) {
            for ($place = 1; $place <= 10; $place++) {
                $input['academic_awards'][] = $this->row(compact('level', 'place'));
                $expected[] = $points[min($place, 4) - 1];
            }
        }
        $result = (new TosaScoringService)->calculate(null, $input);
        $this->assertSame($expected, array_column($result['entries'], 'points_milli'));
        $this->assertSame(array_sum($expected), $result['breakdown']['academic_awards_raw_milli']);
        $this->assertSame(10000, $result['breakdown']['academic_milli']);
    }

    public function test_local_positions_and_attended_seminar_days_do_not_use_erroneous_national_or_social_formulas(): void
    {
        $input = $this->input(null);
        $expected = [];
        foreach (['international' => [1000, 750, 500], 'national' => [750, 500, 250], 'local' => [500, 250, 125]] as $level => $points) {
            foreach (['president', 'vice_president', 'other'] as $index => $role) {
                $input['leadership_positions'][] = $this->row(compact('level', 'role'));
                $expected[] = $points[$index];
            }
        }
        $result = (new TosaScoringService)->calculate(null, $input);
        $this->assertSame($expected, array_column($result['entries'], 'points_milli'));
        $this->assertSame(875, $result['breakdown']['leadership_local_milli']);
        $this->assertSame(0, $result['breakdown']['social_milli']);
        foreach (['international' => 600, 'national' => 400, 'local' => 200] as $level => $rate) {
            foreach ([1, 5] as $days) {
                $seminars = $this->input(null);
                $seminars['leadership_seminars'][] = $this->row(compact('level', 'days'));
                $this->assertSame($rate * $days, (new TosaScoringService)->calculate(null, $seminars)['entries'][0]['points_milli']);
            }
        }
    }

    public function test_category_and_level_caps_total_exactly_one_hundred(): void
    {
        $input = $this->input(1.0);
        foreach (['international', 'national', 'local'] as $level) {
            for ($index = 0; $index < 20; $index++) {
                $input['academic_awards'][] = $this->row(['level' => $level, 'place' => 1]);
                $input['leadership_awards'][] = $this->row(['level' => $level]);
            }
        }
        foreach (['non_school', 'school'] as $scope) {
            for ($index = 0; $index < 20; $index++) {
                $input['social_activities'][] = $this->row(['level' => 'international', 'scope' => $scope, 'role' => 'organizer']);
            }
        }
        $breakdown = (new TosaScoringService)->calculate(1.0, $input)['breakdown'];
        foreach (['academic_milli' => 40000, 'leadership_international_milli' => 6000,
            'leadership_national_milli' => 12000, 'leadership_local_milli' => 12000,
            'leadership_milli' => 30000, 'social_non_school_milli' => 18000, 'social_school_milli' => 12000,
            'social_milli' => 30000, 'total_milli' => 100000] as $key => $expected) {
            $this->assertSame($expected, $breakdown[$key], $key);
        }
        $this->assertSame(60000, $breakdown['leadership_international_raw_milli']);
    }

    public function test_social_roles_and_levels_have_exact_guideline_rates(): void
    {
        $input = $this->input(null);
        $expected = [];
        foreach (['international' => 3000, 'national' => 2000, 'provincial' => 1000, 'local' => 500] as $level => $rate) {
            foreach (['organizer', 'team_leader', 'participant', 'performer'] as $role) {
                $input['social_activities'][] = $this->row(['level' => $level, 'scope' => 'school', 'role' => $role]);
                $expected[] = in_array($role, ['organizer', 'team_leader'], true) ? $rate : intdiv($rate, 2);
            }
        }
        $this->assertSame($expected, array_column((new TosaScoringService)->calculate(null, $input)['entries'], 'points_milli'));
    }

    public function test_competition_ranking_partitions_years_and_preserves_cutoff_ties(): void
    {
        $service = new TosaScoringService;
        $applicants = [];
        for ($id = 1; $id <= 9; $id++) {
            $applicants[] = $this->applicant($id, 1.0);
        }
        $applicants[] = $this->applicant(10, 1.34);
        $applicants[] = $this->applicant(11, 1.34);
        $applicants[] = $this->applicant(12, 2.0);
        $applicants[] = $this->applicant(13, 3.0, '2026-2027');
        $ranking = $service->ranking($applicants);
        $this->assertCount(12, $ranking);
        $this->assertSame([1, 1, 1, 1, 1, 1, 1, 1, 1, 10, 10, 1], array_column($ranking, 'rank'));
        $this->assertSame([10, 11], array_column(array_filter($ranking, fn ($row) => $row['cutoff_tie']), 'applicant_id'));
        $this->assertCount(11, $service->ranking($applicants, '2025-2026'));
        $this->assertSame(13, $service->ranking($applicants, '2026-2027')[0]['applicant_id']);
    }

    public function test_unverified_draft_malformed_and_nonaccepted_candidates_never_rank(): void
    {
        $service = new TosaScoringService;
        $candidates = [];
        foreach (['unverified', 'draft', 'screening', 'returned', 'rejected', 'wrong_total', 'wrong_version', 'null_score', 'invalid_year', 'stale_gwa', 'ineligible', 'missing_verifier'] as $index => $case) {
            $applicant = $this->applicant($index + 1);
            $evaluation = $applicant->score_evaluation;
            match ($case) {
                'unverified' => $applicant->gwa_verified = false,
                'draft' => [$evaluation['status'] = 'draft', $evaluation['verified_at'] = null, $evaluation['verified_by'] = null],
                'screening', 'returned', 'rejected' => $applicant->subsection = $case,
                'wrong_total' => $evaluation['breakdown']['total_milli'] = 100000,
                'wrong_version' => $evaluation['rubric_version'] = 'demo-workbook',
                'null_score' => $evaluation = null,
                'invalid_year' => $applicant->academic_year = '2025-2027',
                'stale_gwa' => $applicant->gwa = 1.35,
                'ineligible' => $evaluation['input']['eligible_verified'] = false,
                'missing_verifier' => $evaluation['verified_by'] = null,
            };
            $applicant->score_evaluation = $evaluation;
            $candidates[] = $applicant;
        }
        $this->assertSame([], $service->ranking($candidates));
    }

    public function test_mysql_json_key_order_is_not_merit_and_invalidation_retains_evidence(): void
    {
        $service = new TosaScoringService;
        $applicant = $this->applicant(1);
        $input = $this->input();
        $input['leadership_positions'][] = $this->row(['level' => 'local', 'role' => 'other']);
        $evaluation = $applicant->score_evaluation;
        $evaluation['input'] = $input;
        $evaluation = [...$evaluation, ...$service->calculate($input['gwa'], $input)];
        $evaluation['breakdown'] = array_reverse($evaluation['breakdown'], true);
        $evaluation['entries'][0] = array_reverse($evaluation['entries'][0], true);
        $applicant->score_evaluation = $evaluation;
        $this->assertCount(1, $service->ranking([$applicant]));
        $applicant->gwa = 1.35;
        $applicant->gwa_verified = false;
        $service->invalidate($applicant, 'GWA changed during review');
        $safe = $service->evaluation($applicant);
        $this->assertSame('draft', $safe['status']);
        $this->assertSame(25000, $safe['breakdown']['grades_milli']);
        $this->assertSame($input['leadership_positions'], $safe['input']['leadership_positions']);
        $this->assertNull($safe['verified_at']);
        $this->assertNull($safe['verified_by']);
        $this->assertSame('GWA changed during review', $safe['invalidated_reason']);
        $this->assertSame([], $service->ranking([$applicant]));
    }

    #[DataProvider('invalidRows')]
    public function test_evidence_validation_rejects_forged_or_invalid_rows(string $category, array $row): void
    {
        $input = $this->input();
        $input[$category] = [$row];
        $this->expectException(ValidationException::class);
        (new TosaScoringService)->validatedInput($input);
    }

    public static function invalidRows(): array
    {
        $base = ['label' => 'Achievement', 'evidence' => 'Section E page 2', 'level' => 'local', 'place' => 1];
        return [
            'forged points' => ['academic_awards', [...$base, 'points_milli' => 100000]],
            'blank label' => ['academic_awards', [...$base, 'label' => '   ']],
            'missing evidence' => ['academic_awards', array_diff_key($base, ['evidence' => true])],
            'invalid place' => ['academic_awards', [...$base, 'place' => 11]],
            'fractional place' => ['academic_awards', [...$base, 'place' => 1.5]],
            'invalid level' => ['academic_awards', [...$base, 'level' => 'provincial']],
            'six seminar days' => ['leadership_seminars', ['label' => 'Seminar', 'evidence' => 'F page 2', 'level' => 'local', 'days' => 6]],
            'invalid position' => ['leadership_positions', ['label' => 'Position', 'evidence' => 'F page 2', 'level' => 'local', 'role' => 'invented']],
            'long evidence' => ['academic_awards', [...$base, 'evidence' => str_repeat('x', 201)]],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\TosaApplicant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class TosaScoringService
{
    public const RUBRIC_VERSION = 'tosa-2025-corrected-v1';

    private const GWA_THRESHOLDS = [100, 107, 114, 121, 128, 135, 141, 148, 155, 162, 169, 176, 183, 190, 197, 203, 210, 217, 224, 231, 238, 245, 252, 259, 266, 272, 279, 286, 293, 300];

    private const ROW_FIELDS = [
        'academic_awards' => ['label', 'evidence', 'level', 'place'],
        'leadership_seminars' => ['label', 'evidence', 'level', 'days'],
        'leadership_positions' => ['label', 'evidence', 'level', 'role'],
        'leadership_awards' => ['label', 'evidence', 'level'],
        'social_activities' => ['label', 'evidence', 'level', 'scope', 'role'],
    ];

    /** Every row is an evaluator's verified claim, never a template/demo row. */
    public function validatedInput(array $input): array
    {
        $allowed = ['gwa', 'gwa_verified', 'eligible_verified', ...array_keys(self::ROW_FIELDS)];
        if (array_diff(array_keys($input), $allowed)) {
            throw ValidationException::withMessages(['evaluation' => 'Unknown evaluation fields are not allowed.']);
        }
        $rules = [
            'gwa' => ['present', 'nullable', 'numeric', 'decimal:0,2', 'between:1,5'],
            'gwa_verified' => ['required', 'boolean'],
            'eligible_verified' => ['required', 'boolean'],
        ];
        foreach (self::ROW_FIELDS as $category => $fields) {
            $rules[$category] = ['present', 'array', 'list', 'max:200'];
            $rules[$category.'.*'] = ['required', 'array:'.implode(',', $fields)];
            $rules[$category.'.*.label'] = ['required', 'string', 'max:200'];
            $rules[$category.'.*.evidence'] = ['required', 'string', 'max:200'];
            $levels = $category === 'social_activities'
                ? ['international', 'national', 'provincial', 'local']
                : ['international', 'national', 'local'];
            $rules[$category.'.*.level'] = ['required', Rule::in($levels)];
            if (is_array($input[$category] ?? null)) {
                foreach ($input[$category] as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    foreach (['label', 'evidence'] as $field) {
                        if (is_string($row[$field] ?? null)) {
                            $input[$category][$index][$field] = trim($row[$field]);
                        }
                    }
                }
            }
        }
        $rules['academic_awards.*.place'] = ['required', 'integer', 'between:1,10'];
        $rules['leadership_seminars.*.days'] = ['required', 'integer', 'between:1,5'];
        $rules['leadership_positions.*.role'] = ['required', Rule::in(['president', 'vice_president', 'other'])];
        $rules['social_activities.*.scope'] = ['required', Rule::in(['non_school', 'school'])];
        $rules['social_activities.*.role'] = ['required', Rule::in(['organizer', 'team_leader', 'participant', 'performer'])];
        $validated = Validator::make($input, $rules)->validate();
        $validated['gwa'] = $validated['gwa'] === null ? null : (float) $validated['gwa'];
        $validated['gwa_verified'] = (bool) $validated['gwa_verified'];
        $validated['eligible_verified'] = (bool) $validated['eligible_verified'];
        foreach (['academic_awards' => 'place', 'leadership_seminars' => 'days'] as $category => $field) {
            foreach ($validated[$category] as &$row) {
                $row[$field] = (int) $row[$field];
            }
            unset($row);
        }

        return $validated;
    }

    public function validAcademicYear(?string $year): bool
    {
        return $year !== null && preg_match('/^(19|20|21)\d{2}-(19|20|21)\d{2}$/D', $year) === 1
            && (int) substr($year, 5, 4) === (int) substr($year, 0, 4) + 1;
    }

    public function calculate(?float $gwa, array $input): array
    {
        $input['gwa'] = $gwa;
        $input = $this->validatedInput($input);
        $grades = 0;
        if ($gwa !== null && $gwa <= 3) {
            $hundredths = (int) round($gwa * 100);
            foreach (self::GWA_THRESHOLDS as $index => $threshold) {
                if ($hundredths < $threshold) {
                    break;
                }
                $grades = (30 - $index) * 1000;
            }
        }
        $academicAwards = 0;
        $leadership = ['international' => 0, 'national' => 0, 'local' => 0];
        $social = ['non_school' => 0, 'school' => 0];
        $entries = [];
        foreach (self::ROW_FIELDS as $category => $fields) {
            foreach ($input[$category] as $row) {
                $level = $row['level'];
                $points = match ($category) {
                    'academic_awards' => match ($level) {
                        'international' => [3000, 2000, 1000, 500][min($row['place'], 4) - 1],
                        'national' => [2000, 1000, 500, 250][min($row['place'], 4) - 1],
                        'local' => [1000, 500, 250, 125][min($row['place'], 4) - 1],
                    },
                    // The guidelines use attended days (maximum five), not an Excel date subtraction.
                    'leadership_seminars' => $row['days'] * ['international' => 600, 'national' => 400, 'local' => 200][$level],
                    'leadership_positions' => [
                        'international' => ['president' => 1000, 'vice_president' => 750, 'other' => 500],
                        'national' => ['president' => 750, 'vice_president' => 500, 'other' => 250],
                        'local' => ['president' => 500, 'vice_president' => 250, 'other' => 125],
                    ][$level][$row['role']],
                    'leadership_awards' => ['international' => 3000, 'national' => 2000, 'local' => 1000][$level],
                    'social_activities' => intdiv(['international' => 3000, 'national' => 2000, 'provincial' => 1000, 'local' => 500][$level],
                        in_array($row['role'], ['organizer', 'team_leader'], true) ? 1 : 2),
                };
                $points = (int) $points;
                if ($category === 'academic_awards') {
                    $academicAwards += $points;
                } elseif ($category === 'social_activities') {
                    $social[$row['scope']] += $points;
                } else {
                    $leadership[$level] += $points;
                }
                $entries[] = ['category' => $category, ...$row, 'points_milli' => $points];
            }
        }
        $breakdown = [
            'grades_milli' => $grades,
            'academic_awards_raw_milli' => $academicAwards,
            'academic_awards_milli' => min(10000, $academicAwards),
            'academic_milli' => $grades + min(10000, $academicAwards),
        ];
        foreach (['international' => 6000, 'national' => 12000, 'local' => 12000] as $level => $cap) {
            $breakdown['leadership_'.$level.'_raw_milli'] = $leadership[$level];
            $breakdown['leadership_'.$level.'_milli'] = min($cap, $leadership[$level]);
        }
        // Local leadership is independent of SC1; the original SUMMARY F20 is erroneous.
        $breakdown['leadership_milli'] = $breakdown['leadership_international_milli'] + $breakdown['leadership_national_milli'] + $breakdown['leadership_local_milli'];
        foreach (['non_school' => 18000, 'school' => 12000] as $scope => $cap) {
            $breakdown['social_'.$scope.'_raw_milli'] = $social[$scope];
            $breakdown['social_'.$scope.'_milli'] = min($cap, $social[$scope]);
        }
        $breakdown['social_milli'] = $breakdown['social_non_school_milli'] + $breakdown['social_school_milli'];
        $breakdown['total_milli'] = $breakdown['academic_milli'] + $breakdown['leadership_milli'] + $breakdown['social_milli'];

        return ['breakdown' => $breakdown, 'entries' => $entries];
    }

    /** Return only a recognized, structurally valid server packet with recomputed points. */
    public function evaluation(TosaApplicant $applicant): ?array
    {
        $evaluation = $applicant->score_evaluation;
        if (! is_array($evaluation) || ($evaluation['rubric_version'] ?? null) !== self::RUBRIC_VERSION
            || ! in_array($evaluation['status'] ?? null, ['draft', 'finalized'], true)
            || ! is_array($evaluation['input'] ?? null)
            || ! is_string($evaluation['updated_at'] ?? null)
            || ! $this->validTimestamp($evaluation['updated_at'])
            || ! array_key_exists('verified_at', $evaluation) || ! array_key_exists('verified_by', $evaluation)) {
            return null;
        }
        try {
            $input = $this->validatedInput($evaluation['input']);
            $calculated = $this->calculate($input['gwa'], $input);
        } catch (ValidationException $exception) {
            return null;
        }
        if (! is_array($evaluation['breakdown'] ?? null) || ! is_array($evaluation['entries'] ?? null)
            || ! $this->sameData($evaluation['breakdown'], $calculated['breakdown']) || ! $this->sameData($evaluation['entries'], $calculated['entries'])) {
            return null;
        }
        if ($evaluation['status'] === 'finalized') {
            $verifier = $evaluation['verified_by'];
            if (! $input['gwa_verified'] || ! $input['eligible_verified'] || $input['gwa'] === null || $input['gwa'] > 3
                || ! is_string($evaluation['verified_at']) || ! $this->validTimestamp($evaluation['verified_at'])
                || ! is_array($verifier) || ! is_int($verifier['id'] ?? null) || $verifier['id'] < 1
                || ! is_string($verifier['name'] ?? null) || trim($verifier['name']) === '') {
                return null;
            }
        } elseif ($evaluation['verified_at'] !== null || $evaluation['verified_by'] !== null) {
            return null;
        }
        $safe = [
            'rubric_version' => self::RUBRIC_VERSION,
            'status' => $evaluation['status'],
            'input' => $input,
            ...$calculated,
            'verified_at' => $evaluation['verified_at'],
            'verified_by' => $evaluation['verified_by'] === null ? null : [
                'id' => $evaluation['verified_by']['id'], 'name' => $evaluation['verified_by']['name'],
            ],
            'updated_at' => $evaluation['updated_at'],
        ];
        if (is_string($evaluation['invalidated_reason'] ?? null)) {
            $safe['invalidated_reason'] = $evaluation['invalidated_reason'];
        }

        return $safe;
    }

    public function ranking(iterable $applicants, ?string $academicYear = null): array
    {
        $cohorts = [];
        foreach ($applicants as $applicant) {
            if (! $applicant instanceof TosaApplicant || $applicant->status !== 'submitted' || $applicant->subsection !== 'accepted'
                || ! $this->validAcademicYear($applicant->academic_year)
                || ($academicYear !== null && $applicant->academic_year !== $academicYear)) {
                continue;
            }
            $evaluation = $this->evaluation($applicant);
            if ($evaluation === null || $evaluation['status'] !== 'finalized' || ! $applicant->gwa_verified
                || $applicant->gwa === null || $evaluation['input']['gwa'] !== (float) $applicant->gwa) {
                continue;
            }
            $cohorts[$applicant->academic_year][] = [
                'applicant_id' => (int) $applicant->id,
                'academic_year' => $applicant->academic_year,
                'total_milli' => $evaluation['breakdown']['total_milli'],
            ];
        }
        ksort($cohorts);
        $result = [];
        foreach ($cohorts as $cohort) {
            // IDs stabilize display within equal scores, never decide merit or break ties.
            usort($cohort, fn (array $a, array $b): int => ($b['total_milli'] <=> $a['total_milli']) ?: ($a['applicant_id'] <=> $b['applicant_id']));
            $count = count($cohort);
            for ($start = 0; $start < $count;) {
                $end = $start + 1;
                while ($end < $count && $cohort[$end]['total_milli'] === $cohort[$start]['total_milli']) {
                    $end++;
                }
                $rank = $start + 1;
                if ($rank > 10) {
                    break;
                }
                for ($index = $start; $index < $end; $index++) {
                    $result[] = [...$cohort[$index], 'rank' => $rank, 'tied' => $end - $start > 1, 'cutoff_tie' => $end - $start > 1 && $end > 10];
                }
                $start = $end;
            }
        }

        return $result;
    }

    /** Mutate the locked model; its caller persists in the same review transaction. */
    public function invalidate(TosaApplicant $applicant, string $reason): void
    {
        $evaluation = $applicant->score_evaluation;
        if (! is_array($evaluation)) {
            return;
        }
        if (! is_array($evaluation['input'] ?? null)) {
            $applicant->score_evaluation = null;

            return;
        }
        try {
            $input = $this->validatedInput([
                ...($evaluation['input'] ?? []),
                'gwa' => $applicant->gwa,
                'gwa_verified' => (bool) $applicant->gwa_verified,
            ]);
        } catch (ValidationException $exception) {
            $applicant->score_evaluation = null;

            return;
        }
        $applicant->score_evaluation = [
            'rubric_version' => self::RUBRIC_VERSION,
            'status' => 'draft',
            'input' => $input,
            ...$this->calculate($input['gwa'], $input),
            'verified_at' => null,
            'verified_by' => null,
            'updated_at' => now()->toIso8601String(),
            'invalidated_reason' => $reason,
        ];
    }

    /** MySQL JSON may reorder object keys; numeric types and list order must still match. */
    private function sameData(mixed $stored, mixed $expected): bool
    {
        if (! is_array($expected)) {
            return $stored === $expected;
        }
        if (! is_array($stored) || count($stored) !== count($expected)
            || array_is_list($stored) !== array_is_list($expected)) {
            return false;
        }
        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $stored) || ! $this->sameData($stored[$key], $value)) {
                return false;
            }
        }

        return true;
    }

    private function validTimestamp(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value) === 1
            && strtotime($value) !== false;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Services\TosaRequirements;
use App\Services\TosaScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class TosaScoringController extends Controller
{
    public function save(Request $request, TosaApplicant $applicant): JsonResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active
            && in_array($office->office_role, ['oso', 'ovcaa'], true)
            && $office->tosaClearanceLevel() >= 2, 403, 'Evaluator clearance is required.');

        $payload = $request->except('_token');
        $allowed = ['intent', 'academic_year', 'gwa', 'gwa_verified', 'eligible_verified',
            'academic_awards', 'leadership_seminars', 'leadership_positions', 'leadership_awards', 'social_activities'];
        if (array_diff(array_keys($payload), $allowed)) {
            throw ValidationException::withMessages(['evaluation' => 'Unknown scoring fields are not allowed.']);
        }
        $service = app(TosaScoringService::class);
        $header = Validator::make($payload, [
            'intent' => ['required', 'in:draft,finalize'],
            'academic_year' => ['present', 'nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($service): void {
                if (! is_string($value) || ! $service->validAcademicYear($value)) {
                    $fail('Use a consecutive academic year such as 2025-2026.');
                }
            }],
        ])->validate();
        unset($payload['intent'], $payload['academic_year']);
        $input = $service->validatedInput($payload);
        $finalize = $header['intent'] === 'finalize';

        $saved = DB::connection('mysql')->transaction(function () use ($applicant, $office, $header, $input, $finalize, $service): TosaApplicant {
            $locked = TosaApplicant::query()->whereKey($applicant->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'submitted' || ! in_array($locked->subsection, ['pending', 'screening', 'interview', 'accepted'], true)) {
                throw ValidationException::withMessages(['evaluation' => 'Only an active submitted packet can be evaluated.']);
            }
            if ($finalize) {
                if (! $input['gwa_verified'] || ! $input['eligible_verified'] || $input['gwa'] === null || $input['gwa'] > 3) {
                    throw ValidationException::withMessages(['evaluation' => 'Finalization requires verified eligibility and verified GWA from 1.00 to 3.00.']);
                }
                if (! $service->validAcademicYear($header['academic_year'])) {
                    throw ValidationException::withMessages(['academic_year' => 'A consecutive academic year is required to finalize.']);
                }
                foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
                    if ($locked->requirementFile($key) === null) {
                        throw ValidationException::withMessages(['evaluation' => 'The original '.$definition['title'].' must be available before finalization.']);
                    }
                }
            }
            $time = now()->toIso8601String();
            $locked->forceFill([
                'gwa' => $input['gwa'],
                'gwa_verified' => $input['gwa_verified'],
                'academic_year' => $header['academic_year'] ?? $locked->academic_year,
                'score_evaluation' => [
                    'rubric_version' => TosaScoringService::RUBRIC_VERSION,
                    'status' => $finalize ? 'finalized' : 'draft',
                    'input' => $input,
                    ...$service->calculate($input['gwa'], $input),
                    'verified_at' => $finalize ? $time : null,
                    'verified_by' => $finalize ? ['id' => (int) $office->id, 'name' => $office->name] : null,
                    'updated_at' => $time,
                ],
            ])->save();

            return $locked;
        });

        return response()->json([
            'ok' => true,
            'evaluation' => $service->evaluation($saved),
            'gwa' => $saved->gwa,
            'gwaVerified' => (bool) $saved->gwa_verified,
            'ranking' => $service->ranking(TosaApplicant::query()->where('status', 'submitted')->get()),
            'academicYear' => $saved->academic_year,
        ]);
    }
}

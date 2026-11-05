<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Services\TosaWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TosaShortlistController extends Controller
{
    public function review(Request $request, TosaApplicant $applicant): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        if ($request->except('_token') !== []) {
            throw ValidationException::withMessages(['applicant' => 'Unknown review fields are not allowed.']);
        }

        DB::connection('mysql')->transaction(function () use ($applicant, $office): void {
            $locked = TosaApplicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            abort_unless($locked->status === 'submitted', 404);
            if ($locked->subsection === 'returned') {
                throw ValidationException::withMessages(['applicant' => 'Wait for the corrected documents before marking this applicant reviewed.']);
            }
            app(TosaWorkflowService::class)->confirmAll($locked, $actor);
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Application marked as reviewed. You may now add it to the Top 10 draft.');
    }

    public function add(Request $request, TosaApplicant $applicant): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        if ($request->except('_token') !== []) {
            throw ValidationException::withMessages(['applicant' => 'Unknown Top 10 fields are not allowed.']);
        }

        $result = DB::connection('mysql')->transaction(function () use ($applicant, $office): array {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $locked = TosaApplicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            abort_unless($locked->status === 'submitted', 404);
            if ($locked->subsection === 'returned') {
                throw ValidationException::withMessages(['applicant' => 'Wait for the corrected documents before adding this applicant.']);
            }

            $ids = array_map('intval', $shortlist->draft_ids ?? []);
            $alreadyAdded = in_array((int) $locked->id, $ids, true);
            if (! $alreadyAdded && count($ids) >= 10) {
                throw ValidationException::withMessages(['applicant' => 'The Top 10 draft is full. Remove someone in the Top 10 tab first.']);
            }
            if (! app(TosaWorkflowService::class)->isVerified($locked)) {
                throw ValidationException::withMessages(['applicant' => 'Mark the current documents as reviewed before adding this applicant.']);
            }
            if (! $alreadyAdded) {
                $ids[] = (int) $locked->id;
                $shortlist->draft_ids = $ids;
                $shortlist->save();
                TosaHistoryEvent::record('top10_saved', 'office', (int) $actor->id, $actor->name, (int) $locked->id);
            }

            return ['added' => ! $alreadyAdded, 'draft_count' => count($ids)];
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true] + $result);
        }

        return back()->with('tosa_open_applicant', (int) $applicant->id)->with('success', $result['added']
            ? 'Added to the Top 10 draft. Publish it from the Top 10 tab when ready.'
            : 'This applicant is already in the Top 10 draft.');
    }

    public function remove(Request $request, int $applicantId): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        abort_if($applicantId < 1, 404);
        if ($request->except('_token') !== []) {
            throw ValidationException::withMessages(['applicant' => 'Unknown Top 10 fields are not allowed.']);
        }

        $removed = DB::connection('mysql')->transaction(function () use ($applicantId, $office): bool {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);

            $ids = array_map('intval', $shortlist->draft_ids ?? []);
            if (! in_array($applicantId, $ids, true)) {
                return false;
            }
            $shortlist->draft_ids = array_values(array_filter($ids, fn (int $id): bool => $id !== $applicantId));
            $shortlist->save();
            TosaHistoryEvent::record('top10_removed', 'office', (int) $actor->id, $actor->name, $applicantId);

            return true;
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'removed' => $removed]);
        }

        return redirect()->route('office.tosa', ['tab' => 'top10'])
            ->with('success', $removed ? 'Removed from the Top 10 draft.' : 'This applicant is no longer in the draft.');
    }

    public function save(Request $request): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        if (array_diff(array_keys($request->except('_token')), ['intent', 'applicant_ids', 'notice_event', 'notice_contact', 'notice_signatory']) !== []) {
            throw ValidationException::withMessages(['applicant_ids' => 'Unknown Top 10 fields are not allowed.']);
        }
        $data = $request->validate([
            'intent' => ['required', 'in:publish,unpublish'],
            'applicant_ids' => ['sometimes', 'array', 'list', 'max:10'],
            'applicant_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'notice_event' => ['nullable', 'string', 'max:1000'],
            'notice_contact' => ['nullable', 'string', 'max:500'],
            'notice_signatory' => ['nullable', 'string', 'max:500'],
        ]);
        $ids = array_map('intval', $data['applicant_ids'] ?? []);
        if (count(array_unique($ids)) !== count($ids)) {
            throw ValidationException::withMessages(['applicant_ids' => 'Choose each applicant only once.']);
        }
        if ($data['intent'] === 'publish' && count($ids) !== 10) {
            throw ValidationException::withMessages(['applicant_ids' => 'Choose exactly ten reviewed applicants before publishing.']);
        }

        $shortlist = DB::connection('mysql')->transaction(function () use ($office, $data, $ids): TosaShortlist {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $currentIds = array_map('intval', $shortlist->draft_ids ?? []);
            $applicants = $data['intent'] === 'unpublish'
                ? collect()
                : TosaApplicant::query()->where('status', 'submitted')->orderBy('id')->lockForUpdate()->get();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            if ($data['intent'] === 'publish') {
                $expected = $ids;
                $actual = $currentIds;
                sort($expected);
                sort($actual);
                if ($expected !== $actual) {
                    throw ValidationException::withMessages(['applicant_ids' => 'The Top 10 draft changed. Refresh and try again.']);
                }
                $workflow = app(TosaWorkflowService::class);
                $allApplicants = $applicants;
                $applicants = $allApplicants->whereIn('id', $currentIds);
                if ($applicants->count() !== count($currentIds) || $applicants->contains(fn (TosaApplicant $applicant): bool => ! $workflow->isVerified($applicant))) {
                    throw ValidationException::withMessages(['applicant_ids' => 'Choose only applicants whose current documents have been reviewed.']);
                }
                $entries = $applicants->sortBy(fn (TosaApplicant $applicant): string => mb_strtolower($applicant->full_name))
                    ->map(fn (TosaApplicant $applicant): array => [
                        'name' => $applicant->full_name,
                        'program' => $applicant->program,
                    ])->values()->all();
                $decisions = [];
                foreach ($allApplicants as $candidate) {
                    if ($workflow->isVerified($candidate)) {
                        $decisions[(string) $candidate->id] = [
                            'status' => in_array((int) $candidate->id, $currentIds, true) ? 'qualified' : 'not_qualified',
                            'review' => TosaShortlist::reviewFingerprint($candidate),
                            'name' => $candidate->full_name,
                            'program' => $candidate->program,
                            'event' => $data['notice_event'] ?? null,
                            'contact' => $data['notice_contact'] ?? null,
                            'signatory' => $data['notice_signatory'] ?? null,
                        ];
                    }
                }
                $shortlist->published_decisions = $decisions;
                $shortlist->published_entries = $entries;
                $shortlist->published_at = now();
                TosaHistoryEvent::record('top10_published', 'office', (int) $actor->id, $actor->name, null, ['names' => array_column($entries, 'name')]);
            } elseif ($data['intent'] === 'unpublish' && $shortlist->published_at !== null) {
                $shortlist->published_entries = null;
                $shortlist->published_decisions = null;
                $shortlist->published_at = null;
                TosaHistoryEvent::record('top10_unpublished', 'office', (int) $actor->id, $actor->name);
            }
            $shortlist->save();

            return $shortlist;
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'draft_ids' => $shortlist->draft_ids, 'published_at' => $shortlist->published_at?->toIso8601String()]);
        }

        $message = $data['intent'] === 'publish'
            ? 'Top 10 published to students.'
            : 'Top 10 hidden from students.';

        return redirect()->route('office.tosa', ['tab' => 'top10'])->with('success', $message);
    }
}

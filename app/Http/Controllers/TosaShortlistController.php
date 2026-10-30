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
            app(TosaWorkflowService::class)->confirmAll($locked, $actor);
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

        return back()->with('success', $result['added']
            ? 'Added to the Top 10 draft. Publish it from the Top 10 tab when ready.'
            : 'This applicant is already in the Top 10 draft.');
    }

    public function save(Request $request): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        if (array_diff(array_keys($request->except('_token')), ['intent', 'applicant_ids']) !== []) {
            throw ValidationException::withMessages(['applicant_ids' => 'Unknown Top 10 fields are not allowed.']);
        }
        $data = $request->validate([
            'intent' => ['required', 'in:save,publish,unpublish'],
            'applicant_ids' => ['sometimes', 'array', 'list', 'max:10'],
            'applicant_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
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
            $applicants = $data['intent'] === 'unpublish'
                ? collect()
                : TosaApplicant::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            if ($data['intent'] !== 'unpublish') {
                $workflow = app(TosaWorkflowService::class);
                if ($applicants->count() !== count($ids) || $applicants->contains(fn (TosaApplicant $applicant): bool => ! $workflow->isVerified($applicant))) {
                    throw ValidationException::withMessages(['applicant_ids' => 'Choose only applicants whose current documents have been reviewed.']);
                }
                $shortlist->draft_ids = $ids;
            }
            if ($data['intent'] === 'publish') {
                $entries = $applicants->sortBy(fn (TosaApplicant $applicant): string => mb_strtolower($applicant->full_name))
                    ->map(fn (TosaApplicant $applicant): array => [
                        'name' => $applicant->full_name,
                        'program' => $applicant->program,
                    ])->values()->all();
                $shortlist->published_entries = $entries;
                $shortlist->published_at = now();
                TosaHistoryEvent::record('top10_published', 'office', (int) $actor->id, $actor->name, null, ['names' => array_column($entries, 'name')]);
            } elseif ($data['intent'] === 'unpublish' && $shortlist->published_at !== null) {
                $shortlist->published_entries = null;
                $shortlist->published_at = null;
                TosaHistoryEvent::record('top10_unpublished', 'office', (int) $actor->id, $actor->name);
            } elseif ($data['intent'] === 'save') {
                TosaHistoryEvent::record('top10_saved', 'office', (int) $actor->id, $actor->name);
            }
            $shortlist->save();

            return $shortlist;
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'draft_ids' => $shortlist->draft_ids, 'published_at' => $shortlist->published_at?->toIso8601String()]);
        }

        $message = match ($data['intent']) {
            'publish' => 'Top 10 published to students.',
            'unpublish' => 'Top 10 hidden from students.',
            default => 'Top 10 draft saved.',
        };

        return redirect()->route('office.tosa', ['tab' => 'top10'])->with('success', $message);
    }
}

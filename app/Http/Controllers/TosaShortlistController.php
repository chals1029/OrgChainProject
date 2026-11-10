<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Services\TosaNotificationService;
use App\Services\TosaSelectionEdit;
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

        $result = DB::connection('mysql')->transaction(function () use ($applicant, $office, $request): array {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $locked = TosaApplicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            abort_unless(app(TosaSelectionEdit::class)->isEditing($request, $actor, $shortlist), 403, 'Unpublish the current results before changing the Top 10 selection.');
            abort_unless($locked->status === 'submitted', 404);
            if ($locked->subsection === 'returned') {
                throw ValidationException::withMessages(['applicant' => 'Wait for the corrected documents before adding this applicant.']);
            }

            $ids = $this->normalizeIds($shortlist->draft_ids ?? []);
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
                TosaHistoryEvent::record('selection_updated', 'office', (int) $actor->id, $actor->name, (int) $locked->id, [
                    'draft_count' => count($ids),
                ]);
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

        $removed = DB::connection('mysql')->transaction(function () use ($applicantId, $office, $request): bool {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);
            abort_unless(app(TosaSelectionEdit::class)->isEditing($request, $actor, $shortlist), 403, 'Unpublish the current results before changing the Top 10 selection.');

            $ids = $this->normalizeIds($shortlist->draft_ids ?? []);
            if (! in_array($applicantId, $ids, true)) {
                return false;
            }
            $remaining = array_values(array_filter($ids, fn (int $id): bool => $id !== $applicantId));
            $shortlist->draft_ids = $remaining;
            $shortlist->save();
            TosaHistoryEvent::record('top10_removed', 'office', (int) $actor->id, $actor->name, $applicantId);
            TosaHistoryEvent::record('selection_updated', 'office', (int) $actor->id, $actor->name, $applicantId, [
                'draft_count' => count($remaining),
            ]);

            return true;
        }, 3);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'removed' => $removed]);
        }

        return redirect()->route('office.tosa', ['tab' => 'top10', 'section' => 'selection'])
            ->with('success', $removed ? 'Removed from the Top 10 draft.' : 'This applicant is no longer in the draft.');
    }

    public function save(Request $request): JsonResponse|RedirectResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->is_active && $office->office_role === 'oso', 403);
        if (array_diff(array_keys($request->except('_token')), ['intent', 'applicant_ids', 'caption_qualified', 'caption_not_qualified']) !== []) {
            throw ValidationException::withMessages(['applicant_ids' => 'Unknown Top 10 fields are not allowed.']);
        }
        $intentData = $request->validate([
            'intent' => ['required', 'string', 'in:publish,unpublish'],
        ]);
        $intent = $intentData['intent'];

        $shortlist = DB::connection('mysql')->transaction(function () use ($office, $intent, $request): TosaShortlist {
            $shortlist = TosaShortlist::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $allApplicants = $intent === 'unpublish'
                ? collect()
                : TosaApplicant::query()->where('status', 'submitted')->orderBy('id')->lockForUpdate()->get();
            $actor = OfficeUser::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && $actor->office_role === 'oso'
                && (int) $actor->auth_version === (int) $office->auth_version, 403);

            if ($intent === 'unpublish') {
                if ($shortlist->published_at !== null) {
                    $this->preserveLegacyCaptions($shortlist);
                    $shortlist->published_entries = null;
                    $shortlist->published_decisions = null;
                    $shortlist->published_at = null;
                    $shortlist->save();
                    TosaHistoryEvent::record('top10_unpublished', 'office', (int) $actor->id, $actor->name);
                }

                return $shortlist;
            }

            abort_unless(app(TosaSelectionEdit::class)->isEditing($request, $actor, $shortlist), 403, 'Unpublish the current results before changing the Top 10 selection.');

            $data = $request->validate([
                'applicant_ids' => ['required', 'array', 'list'],
                'applicant_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
                'caption_qualified' => ['required', 'string', 'max:300', function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->normalizeCaption($value) === null) {
                        $fail('Enter the announcement caption for qualified applicants.');
                    }
                }],
                'caption_not_qualified' => ['required', 'string', 'max:300', function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->normalizeCaption($value) === null) {
                        $fail('Enter the announcement caption for not qualified applicants.');
                    }
                }],
            ]);
            $ids = array_map('intval', $data['applicant_ids']);
            if (count(array_unique($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['applicant_ids' => 'Choose each applicant only once.']);
            }
            if (count($ids) !== 10) {
                throw ValidationException::withMessages(['applicant_ids' => 'Choose exactly ten reviewed applicants before publishing.']);
            }

            $currentIds = $this->normalizeIds($shortlist->draft_ids ?? []);
            $expected = $ids;
            $actual = $currentIds;
            sort($expected);
            sort($actual);
            if ($expected !== $actual) {
                throw ValidationException::withMessages(['applicant_ids' => 'The Top 10 draft changed. Refresh and try again.']);
            }

            $workflow = app(TosaWorkflowService::class);
            $applicants = $allApplicants->whereIn('id', $currentIds);
            if ($applicants->count() !== count($currentIds) || $applicants->contains(fn (TosaApplicant $applicant): bool => ! $workflow->isVerified($applicant))) {
                throw ValidationException::withMessages(['applicant_ids' => 'Choose only applicants whose current documents have been reviewed.']);
            }

            $qualifiedCaption = (string) $this->normalizeCaption($data['caption_qualified']);
            $notQualifiedCaption = (string) $this->normalizeCaption($data['caption_not_qualified']);
            $entries = $applicants->sortBy(fn (TosaApplicant $applicant): string => mb_strtolower($applicant->full_name))
                ->map(fn (TosaApplicant $applicant): array => [
                    'name' => $applicant->full_name,
                    'program' => $applicant->program,
                ])->values()->all();
            $decisions = [];
            foreach ($allApplicants as $candidate) {
                if ($workflow->isVerified($candidate)) {
                    $isQualified = in_array((int) $candidate->id, $currentIds, true);
                    $decisions[(string) $candidate->id] = [
                        'status' => $isQualified ? 'qualified' : 'not_qualified',
                        'review' => TosaShortlist::reviewFingerprint($candidate),
                        'name' => $candidate->full_name,
                        'program' => $candidate->program,
                        'caption' => $isQualified ? $qualifiedCaption : $notQualifiedCaption,
                    ];
                }
            }

            $shortlist->draft_ids = $currentIds;
            $shortlist->caption_qualified = $qualifiedCaption;
            $shortlist->caption_not_qualified = $notQualifiedCaption;
            $shortlist->published_decisions = $decisions;
            $shortlist->published_entries = $entries;
            $shortlist->published_at = now();
            $shortlist->save();

            TosaHistoryEvent::record('top10_published', 'office', (int) $actor->id, $actor->name, null, [
                'names' => array_column($entries, 'name'),
            ]);

            return $shortlist;
        }, 3);

        if ($intent === 'publish') {
            app(TosaNotificationService::class)->sendResultNotifications($shortlist, $office);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'draft_ids' => $shortlist->draft_ids,
                'published_at' => $shortlist->published_at?->toIso8601String(),
            ]);
        }

        $section = $intent === 'publish' ? 'published' : 'selection';
        $message = $intent === 'publish'
            ? 'Private result notices published to students.'
            : 'Result notices hidden from students.';

        return redirect()->route('office.tosa', ['tab' => 'top10', 'section' => $section])->with('success', $message);
    }

    /**
     * @param  array<array-key, mixed>  $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id): bool => $id > 0,
        )));
    }

    private function normalizeCaption(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = preg_replace('/^\s+|\s+$/u', '', $value);

        return is_string($trimmed) && $trimmed !== '' ? $trimmed : null;
    }

    private function preserveLegacyCaptions(TosaShortlist $shortlist): void
    {
        $decisions = collect($shortlist->published_decisions ?? []);
        if ($shortlist->caption_qualified === null) {
            $qualified = $this->normalizeCaption(data_get(
                $decisions->first(fn ($decision): bool => is_array($decision) && ($decision['status'] ?? null) === 'qualified'),
                'caption',
            ));
            if ($qualified !== null) {
                $shortlist->caption_qualified = $qualified;
            }
        }
        if ($shortlist->caption_not_qualified === null) {
            $notQualified = $this->normalizeCaption(data_get(
                $decisions->first(fn ($decision): bool => is_array($decision) && ($decision['status'] ?? null) === 'not_qualified'),
                'caption',
            ));
            if ($notQualified !== null) {
                $shortlist->caption_not_qualified = $notQualified;
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityRegistration;
use App\Models\BudgetItem;
use App\Models\CommunityPost;
use App\Models\ExpenseReceiptReview;
use App\Models\OfficeAnnouncement;
use App\Models\OrgActivity;
use App\Models\StudentFeedback;
use App\Models\TosaApplicant;
use App\Models\UserAccount;
use App\Services\BudgetChainService;
use App\Services\OfficeDocumentConverter;
use App\Services\TosaRequirements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentPortalController extends Controller
{

    public function home(Request $request): View
    {
        return $this->portal($request, 'home');
    }

    public function community(Request $request): View
    {
        return $this->portal($request, 'community');
    }

    public function storeFeedback(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        StudentFeedback::query()->create([
            'student_id' => $student?->id,
            'author_name' => $student?->full_name ?? $student?->name ?? 'Student',
            'program' => null,
            'college' => $student?->college,
            'topic' => $validated['category'] ?? 'campus',
            'body' => $validated['message'],
            'is_anonymous' => false,
            'visibility' => 'oso',
        ]);

        return back()->with('status', 'Thanks — your feedback was sent to OSO.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        // Profile is read-only by design (official records). Reject direct writes.
        abort(403, 'Profile editing is disabled. Contact the registrar for corrections.');
    }

    /**
     * RSVP toggle for an activity (register / cancel). Students only.
     */
    public function toggleRsvp(Request $request, OrgActivity $activity): \Illuminate\Http\JsonResponse
    {
        $student = Auth::guard('student')->user();
        abort_unless($student, 401);

        if (($activity->workflow_status ?? '') !== 'oc_approved') {
            return response()->json([
                'ok' => false,
                'message' => 'This activity is not available until the approval workflow is complete.',
            ], 422);
        }

        if (($activity->status ?? '') === 'completed') {
            return response()->json(['ok' => false, 'message' => 'This activity already ended.'], 422);
        }

        $existing = ActivityRegistration::query()
            ->where('org_activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $registered = false;
        } else {
            ActivityRegistration::query()->create([
                'org_activity_id' => $activity->id,
                'student_id' => $student->id,
                'sr_code' => $student->sr_code ?? null,
                'full_name' => $student->full_name ?? $student->name ?? null,
                'year_level' => $student->year_level ?? null,
            ]);
            $registered = true;
        }

        $count = ActivityRegistration::query()->where('org_activity_id', $activity->id)->count();

        return response()->json(['ok' => true, 'registered' => $registered, 'count' => $count]);
    }

    /**
     * Verify a public expense seal without exposing the private receipt file.
     */
    public function verifyExpenseSeal(Request $request, ExpenseReceiptReview $receipt): JsonResponse
    {
        abort_unless(
            in_array($receipt->verification_status, ['approved', 'verified'], true)
            && filled($receipt->chain_hash)
            && $receipt->org_activity_id
            && OrgActivity::query()->visibleToStudents()->whereKey($receipt->org_activity_id)->exists(),
            404,
        );

        $chain = app(BudgetChainService::class)->verifyHash((string) $receipt->chain_hash);
        $fileCheck = $this->verifyReceiptFileFingerprint($receipt);
        $chainDriver = (string) ($receipt->chain_driver ?: ($chain['chain_driver'] ?? 'file'));
        $nodeTotal = $chainDriver === 'besu'
            ? (int) config('besu.validator_count', 4)
            : BudgetChainService::NODE_COUNT;
        $nodesConfirmed = (int) ($chain['nodes_confirmed'] ?? $receipt->nodes_confirmed ?? 0);
        $verified = (bool) ($chain['ok'] ?? false) && $fileCheck['matches'];

        return response()->json([
            'ok' => $verified,
            'status' => $verified ? 'Verified' : 'Verification needs attention',
            'message' => $verified
                ? 'The ledger seal and receipt-file fingerprint match the stored record.'
                : 'The ledger seal or receipt-file fingerprint could not be fully confirmed.',
            'timestamp' => $chain['sealed_at'] ?? optional($receipt->updated_at ?? $receipt->created_at)->toIso8601String(),
            'chain' => [
                'driver' => $chainDriver,
                'nodes_confirmed' => $nodesConfirmed,
                'node_total' => $nodeTotal,
                'message' => $chain['message'] ?? 'Ledger verification completed.',
                'hash' => (string) $receipt->chain_hash,
                'transaction_hash' => $receipt->chain_tx_hash,
            ],
            'receipt_file' => [
                'matches' => $fileCheck['matches'],
                'status' => $fileCheck['status'],
                'stored_hash' => $fileCheck['stored_hash'],
                'current_hash' => $fileCheck['current_hash'],
            ],
        ]);
    }

    /**
     * Compare the stored receipt fingerprint with the file currently held on
     * the private server disk. The file path and file contents never leave the
     * server through the public verification response.
     *
     * @return array{matches:bool,status:string,stored_hash:?string,current_hash:?string}
     */
    private function verifyReceiptFileFingerprint(ExpenseReceiptReview $receipt): array
    {
        $storedHash = strtolower(trim((string) $receipt->file_hash));
        $file = $receipt->receiptFiles()[0] ?? null;
        if ($storedHash === '' || ! is_array($file) || ! filled($file['path'] ?? null)) {
            return ['matches' => false, 'status' => 'unavailable', 'stored_hash' => $storedHash ?: null, 'current_hash' => null];
        }

        try {
            $disk = Storage::disk('local');
            if (! $disk->exists((string) $file['path'])) {
                return ['matches' => false, 'status' => 'file-missing', 'stored_hash' => $storedHash, 'current_hash' => null];
            }

            $currentHash = hash('sha256', (string) $disk->get((string) $file['path']));
            $matches = hash_equals($storedHash, strtolower($currentHash));

            return [
                'matches' => $matches,
                'status' => $matches ? 'matched' : 'mismatch',
                'stored_hash' => $storedHash,
                'current_hash' => strtolower($currentHash),
            ];
        } catch (\Throwable) {
            return ['matches' => false, 'status' => 'unavailable', 'stored_hash' => $storedHash, 'current_hash' => null];
        }
    }

    /**
     * Student TOSA application row, keyed by the student's SR code.
     */
    private function myTosaApplication($student): ?TosaApplicant
    {
        $srCode = trim((string) ($student?->sr_code ?? ''));

        return $srCode !== ''
            ? TosaApplicant::query()->where('sr_code', $srCode)->first()
            : null;
    }

    public function downloadTosaTemplate(Request $request, string $key): BinaryFileResponse|StreamedResponse|JsonResponse
    {
        abort_unless(Auth::guard('student')->check(), 401);
        $template = TosaRequirements::resolveTemplate($key);
        abort_unless($template, 404, 'This official TOSA template is unavailable.');
        if ($request->boolean('preview') && $template['format'] === 'DOC') {
            $converter = app(OfficeDocumentConverter::class);
            try {
                $result = $converter->toPdf($template['path']);
            } catch (Throwable $error) {
                return response()->json([
                    'message' => $error instanceof RuntimeException ? $error->getMessage() : 'The official document preview could not be prepared.',
                ], 503)->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
            }

            return response()->stream(function () use ($result, $converter): void {
                $stream = null;
                try {
                    $previous = ignore_user_abort(true);
                    $stream = fopen($result['path'], 'rb');
                    if ($stream !== false) fpassthru($stream);
                } finally {
                    if (is_resource($stream)) fclose($stream);
                    $converter->release($result);
                    if (isset($previous)) ignore_user_abort((bool) $previous);
                }
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="document-preview.pdf"',
                'Content-Length' => (string) filesize($result['path']),
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return TosaRequirements::fileResponse($key, $template['path'], $template['name'], $request->boolean('preview'));
    }

    public function downloadTosaDocument(Request $request, string $key): BinaryFileResponse
    {
        abort_unless(Auth::guard('student')->check(), 401);
        abort_unless(array_key_exists($key, TosaRequirements::SUBMISSIONS), 404);
        $applicant = $this->myTosaApplication(Auth::guard('student')->user());
        abort_unless($applicant, 404);
        $absolute = $applicant->requirementFile($key);
        abort_unless($absolute, 404, 'This requirement document is unavailable.');
        return TosaRequirements::submissionFileResponse(
            $absolute, (string) data_get($applicant->requirements, $key.'.name', basename($absolute)),
            ! $request->boolean('download'),
        );
    }

    /**
     * Persist the complete selected packet and submit it in one operation.
     */
    public function submitTosaApplication(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'documents' => ['sometimes', 'array:'.implode(',', array_keys(TosaRequirements::SUBMISSIONS))],
            'documents.*' => ['bail', 'required', 'file', 'max:25600', function ($attribute, $file, $fail): void {
                $key = substr($attribute, strlen('documents.'));
                $definition = TosaRequirements::SUBMISSIONS[$key] ?? null;
                if (! $definition || ! TosaRequirements::validSubmissionFile($key, $file->getPathname(), $file->getClientOriginalName())) {
                    $fail('Choose a valid '.($definition['format'] ?? 'requirement').' file for this packet section.');
                }
            }],
        ]);
        if ($validator->fails()) {
            return $this->tosaRedirect()->withErrors($validator)->withInput();
        }
        $documents = $validator->validated()['documents'] ?? [];
        $student = Auth::guard('student')->user();
        $newPaths = [];
        $replacedPaths = [];
        $srCode = '';
        $committed = false;

        try {
            // An applicant may not exist yet, so lock the persisted student identity
            // on orgchain before taking the mysql applicant lock. Only mysql writes.
            DB::connection('orgchain')->transaction(function () use ($student, $documents, &$newPaths, &$replacedPaths, &$srCode, &$committed): void {
                $ownerStudent = UserAccount::query()->whereKey($student->getAuthIdentifier())->lockForUpdate()->first();
                abort_unless($ownerStudent, 403, 'Your student record is unavailable.');
                $srCode = trim((string) $ownerStudent->sr_code);
                abort_unless($srCode !== '' && ! in_array($srCode, ['.', '..'], true)
                    && strpbrk($srCode, "/\\:\0") === false, 403, 'Your student record has no valid SR code.');

                $replacedPaths = DB::connection('mysql')->transaction(function () use ($ownerStudent, $srCode, $documents, &$newPaths): array {
                    $applicant = TosaApplicant::query()->where('sr_code', $srCode)->lockForUpdate()->first();
                    if ($applicant?->studentSubmissionLocked()) {
                        throw ValidationException::withMessages(['tosa' => 'Your submitted application is locked. Only OSO returning it for revision allows changes or resubmission.']);
                    }
                    foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
                        $available = isset($documents[$key])
                            ? TosaRequirements::validSubmissionFile($key, $documents[$key]->getPathname(), $documents[$key]->getClientOriginalName())
                            : $applicant?->requirementFile($key) !== null;
                        if (! $available) {
                            throw ValidationException::withMessages(['tosa' => 'Choose every required packet section or keep its available saved original before submitting.']);
                        }
                    }

                    $candidate = $applicant ?? new TosaApplicant([
                        'sr_code' => $srCode,
                        'full_name' => $ownerStudent->full_name ?? $ownerStudent->name ?? 'Student',
                        'email' => $ownerStudent->email,
                        'college' => $ownerStudent->college,
                        'program' => $ownerStudent->program,
                        'year_level' => $ownerStudent->year_level,
                    ]);
                    $requirements = is_array($candidate->requirements) ? $candidate->requirements : [];
                    $oldPaths = [];
                    $disk = Storage::disk('local');
                    if ($documents !== []) {
                        $disk->makeDirectory('tosa/'.$srCode);
                        $root = realpath($disk->path(''));
                        $owner = realpath($disk->path('tosa/'.$srCode));
                        abort_unless($root && $owner && $owner === $root.DIRECTORY_SEPARATOR.'tosa'.DIRECTORY_SEPARATOR.$srCode,
                            403, 'The private upload location is unavailable.');

                        foreach ($documents as $key => $file) {
                            $oldPath = $candidate->requirementPath($key);
                            $oldFile = $candidate->ownedRequirementFile($key);
                            if ($oldPath && $oldFile && ! is_link($disk->path($oldPath))) {
                                $oldPaths[$oldPath] = $oldFile;
                            }
                            $filename = $key.'-'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                            $path = 'tosa/'.$srCode.'/'.$filename;
                            abort_if($disk->exists($path) || is_link($disk->path($path)), 403, 'The private upload location is unavailable.');
                            // Record the intended path before a write, including writes
                            // that leave partial bytes and then return false or throw.
                            $newPaths[$path] = $owner.DIRECTORY_SEPARATOR.$filename;
                            $stored = $file->storeAs('tosa/'.$srCode, $filename, 'local');
                            if ($stored !== $path) {
                                throw new \RuntimeException('The selected packet file could not be stored.');
                            }
                            $requirements[$key] = [
                                'title' => TosaRequirements::SUBMISSIONS[$key]['title'],
                                'name' => $file->getClientOriginalName(),
                                'path' => $path,
                                'uploaded_at' => now()->toIso8601String(),
                            ];
                        }
                    }
                    $candidate->requirements = $requirements;
                    foreach (array_keys(TosaRequirements::SUBMISSIONS) as $key) {
                        if ($candidate->requirementFile($key) === null) {
                            throw ValidationException::withMessages(['tosa' => 'Every packet section must have a readable, nonempty private original before submitting.']);
                        }
                    }
                    $candidate->status = 'submitted';
                    $candidate->subsection = 'screening';
                    if ($documents !== []) {
                        $candidate->gwa = null;
                        $candidate->gwa_verified = false;
                        $candidate->remarks = null;
                        $candidate->score_evaluation = null;
                    }
                    if (! $candidate->save()) {
                        throw new \RuntimeException('The selected packet could not be committed.');
                    }

                    // An old path still referenced by any retained historical or
                    // active metadata must not be deleted after replacement.
                    foreach ($requirements as $document) {
                        if (is_array($document) && is_string($document['path'] ?? null)) {
                            unset($oldPaths[$document['path']]);
                        }
                    }

                    return $oldPaths;
                });
                $committed = true;
            });
        } catch (Throwable $exception) {
            if (! $committed) {
                $this->deleteOwnedTosaFiles($srCode, $newPaths);
                if ($exception instanceof ValidationException) {
                    return $this->tosaRedirect()->withErrors($exception->errors())->withInput();
                }
                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    throw $exception;
                }
                report($exception);

                return $this->tosaRedirect()->withErrors(['tosa' => 'The packet could not be saved. Your previous application was not changed. Try again.']);
            }
            // The outer transaction only held an identity lock; mysql has already
            // committed. Never remove newly published originals if that lock's
            // read-only transaction fails while releasing.
            report($exception);
        }

        $this->deleteOwnedTosaFiles($srCode, $replacedPaths);

        return $this->tosaRedirect()->with('status', 'TOSA application submitted for OSO evaluation.');
    }

    private function deleteOwnedTosaFiles(string $srCode, array $paths): void
    {
        if ($paths === [] || $srCode === '') {
            return;
        }
        $disk = Storage::disk('local');
        $root = realpath($disk->path(''));
        $owner = realpath($disk->path('tosa/'.$srCode));
        if (! $root || ! $owner || $owner !== $root.DIRECTORY_SEPARATOR.'tosa'.DIRECTORY_SEPARATOR.$srCode) {
            return;
        }
        foreach ($paths as $relative => $expected) {
            try {
                $absolute = realpath($disk->path($relative));
                if ($absolute === $expected && str_starts_with($absolute, $owner.DIRECTORY_SEPARATOR)
                    && is_file($absolute) && ! is_link($disk->path($relative))) {
                    $disk->delete($relative);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Restore the TOSA tab after validation errors or packet submission.
     */
    private function tosaRedirect(): RedirectResponse
    {
        return redirect()->to(route('portal.home').'#tosa');
    }

    private function portal(Request $request, string $tab): View
    {
        $student = Auth::guard('student')->user();
        $statusFilter = trim((string) $request->query('status', ''));

        // Default activities to the student's college/department; ?college= (empty) = All.
        $studentCollege = trim((string) ($student?->displayCollege() ?: $student?->college ?: ''));
        if ($request->has('college')) {
            $collegeFilter = trim((string) $request->query('college', ''));
        } else {
            $collegeFilter = $studentCollege;
        }

        // Use the approved activity ledger once; legacy category rows must
        // not duplicate new per-activity budget allocations.
        $budgetActivities = OrgActivity::query()->visibleToStudents()
            ->whereIn('activity_scope', ['in_campus', 'off_campus'])->get();
        $budgetCategoryBreakdown = collect([
            'in_campus' => 'In-Campus',
            'off_campus' => 'Off-Campus',
        ])->map(function (string $label, string $scope) use ($budgetActivities): array {
            $rows = $budgetActivities->where('activity_scope', $scope);

            return [
                'category' => $label,
                'allocated' => round((float) $rows->sum('approved_budget'), 2),
                'utilized' => round((float) $rows->sum('implemented_budget'), 2),
            ];
        })->values()->all();
        $budgetItems = collect($budgetCategoryBreakdown)
            ->map(fn (array $row) => new BudgetItem($row + ['title' => $row['category']]));
        $totalAllocated = (float) $budgetActivities->sum('approved_budget');
        $totalUtilized = (float) $budgetActivities->sum('implemented_budget');

        $publicExpenses = ExpenseReceiptReview::query()
            ->whereIn('verification_status', ['approved', 'verified'])
            ->whereNotNull('chain_hash')
            ->whereIn('org_activity_id', OrgActivity::query()->visibleToStudents()->select('id'))
            ->latest()
            ->limit(50)
            ->get();

        // Public activity cards receive summary fields only. Receipt paths and
        // original filenames never enter this payload.
        $publicExpenseItemsByActivity = $publicExpenses
            ->groupBy('org_activity_id')
            ->map(fn ($rows): array => $rows->map(fn (ExpenseReceiptReview $expense): array => [
                'name' => $expense->item_name,
                'unit_price' => (float) $expense->unit_cost,
                'quantity' => (int) $expense->quantity,
                'total' => (float) $expense->unit_cost * (int) $expense->quantity,
                'hash' => (string) $expense->chain_hash,
                'reference' => $expense->receipt_reference,
                'date' => optional($expense->expense_date)->format('M j, Y'),
                'verify_url' => route('portal.expenses.verify', $expense->id),
            ])->values()->all())
            ->all();


        $upcomingQuery = OrgActivity::query()
            ->visibleToStudents()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('starts_at')
            ->limit(50);

        $upcoming = $upcomingQuery->get();

        $recentActivities = OrgActivity::query()
            ->visibleToStudents()
            ->where('status', 'completed')
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get();

        $feedSort = (string) $request->query('sort', 'recent');
        $communitySearch = trim((string) $request->query('community_q', ''));
        if (! in_array($feedSort, ['recent', 'top', 'popular'], true)) {
            $feedSort = 'recent';
        }

        $communityAudience = $request->query('community_audience', 'all');
        if (! in_array($communityAudience, ['all', 'public', 'department', 'org'], true)) {
            $communityAudience = 'all';
        }
        $communityPeriod = $request->query('community_period', 'all');
        if (! in_array($communityPeriod, ['all', 'day', 'week', 'month'], true)) {
            $communityPeriod = 'all';
        }
        $periodDays = ['day' => 1, 'week' => 7, 'month' => 30][$communityPeriod] ?? null;
        $feedNow = now();

        $studentCollegeValues = collect([
            trim((string) ($student?->college ?? '')),
            $studentCollege,
        ])->filter()->unique()->values()->all();
        $studentOrgId = trim((string) ($student?->org_id ?? ''));

        $postsQuery = CommunityPost::query()
            ->where(function ($query) use ($student, $studentCollegeValues, $studentOrgId): void {
                $query
                    ->where(function ($public) {
                        $public->whereNull('audience')->orWhere('audience', 'public');
                    })
                    ->orWhere(function ($department) use ($studentCollegeValues) {
                        $department
                            ->where('audience', 'department')
                            ->whereHas('student', fn ($author) => $author->whereIn('college', $studentCollegeValues));
                    })
                    ->orWhere(function ($organization) use ($student, $studentOrgId) {
                        $organization
                            ->where('audience', 'org')
                            ->whereHas('student', function ($author) use ($student, $studentOrgId): void {
                                if ($studentOrgId !== '') {
                                    $author->where('org_id', $studentOrgId);
                                } else {
                                    $author->where('user_id', $student->id);
                                }
                            });
                    })
                    // Authors can always see their own posts while profile data is incomplete.
                    ->orWhere('student_id', $student->id);
            })
            ->when($communityAudience !== 'all', function ($query) use ($communityAudience): void {
                if ($communityAudience === 'public') {
                    $query->where(function ($public): void {
                        $public->where('audience', 'public')->orWhereNull('audience');
                    });
                } else {
                    $query->where('audience', $communityAudience);
                }
            })
            ->when($periodDays !== null, fn ($query) => $query
                ->where('created_at', '>=', $feedNow->copy()->subDays($periodDays))
                ->where('created_at', '<=', $feedNow))
            ->searchFeed($communitySearch)
            ->withCount(['likes as actual_likes_count', 'comments as actual_comments_count'])
            ->with([
                'student',
                'activity',
                'comments' => function ($query) use ($student): void {
                    $query
                        ->with('student')
                        ->withCount('likes')
                        ->withExists([
                            'likes as liked_by_me' => fn ($q) => $q->where('student_id', $student->id),
                        ]);
                },
                'likes' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->select(['id', 'post_id', 'student_id', 'reaction']),
            ])
            ->withExists([
                'likes as liked_by_me' => fn ($q) => $q->where('student_id', $student->id),
            ]);

        match ($feedSort) {
            'popular' => $postsQuery->orderByDesc('actual_likes_count')->orderByDesc('created_at'),
            'top' => $postsQuery->orderByDesc('actual_comments_count')->orderByDesc('created_at'),
            default => $postsQuery->latest(),
        };

        $posts = $postsQuery->orderByDesc('id')->paginate(10)->withQueryString();

        // Count one view per post per browser session, not on every refresh.
        if ($tab === 'community' && $posts->isNotEmpty()) {
            $viewedPosts = (array) session('community.viewed_posts', []);
            $unseenPostIds = $posts->pluck('id')
                ->reject(fn ($id) => in_array((int) $id, $viewedPosts, true))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($unseenPostIds !== []) {
                CommunityPost::query()->whereIn('id', $unseenPostIds)->increment('views_count');
                $posts->getCollection()->each(function (CommunityPost $post) use ($unseenPostIds): void {
                    if (in_array((int) $post->id, $unseenPostIds, true)) {
                        $post->views_count = (int) $post->views_count + 1;
                    }
                });
                session(['community.viewed_posts' => array_values(array_unique(array_merge($viewedPosts, $unseenPostIds)))]);
            }
        }

        $activities = OrgActivity::query()
            ->visibleToStudents()
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(20)
            ->get();

        $sdgHighlights = OrgActivity::query()
            ->visibleToStudents()
            ->whereNotNull('sdg_goals')
            ->when($collegeFilter !== '', fn ($q) => $q->where('college', $collegeFilter))
            ->orderByDesc('starts_at')
            ->limit(12)
            ->get()
            ->flatMap(fn (OrgActivity $a) => collect($a->sdg_goals ?: [])->map(fn ($sdg) => [
                'sdg' => $sdg,
                'title' => $a->title,
                'college' => $a->college,
            ]))
            ->groupBy('sdg')
            ->map(fn ($rows, $sdg) => [
                'sdg' => $sdg,
                'count' => $rows->count(),
                'activities' => $rows->pluck('title')->values(),
            ])
            ->values();

        $colleges = OrgActivity::query()
            ->visibleToStudents()
            ->whereNotNull('college')
            ->where('college', '!=', '')
            ->distinct()
            ->orderBy('college')
            ->pluck('college');

        if ($studentCollege !== '' && ! $colleges->contains($studentCollege)) {
            $colleges = $colleges->prepend($studentCollege)->unique()->values();
        }

        $announcements = OfficeAnnouncement::query()
            ->latest()->orderByDesc('id')->get()
            ->map(fn (OfficeAnnouncement $announcement): array => $announcement->studentPublication())
            ->all();

        $tosaApplicant = $this->myTosaApplication($student);
        $tosaRequirements = is_array($tosaApplicant?->requirements) ? $tosaApplicant->requirements : [];
        $tosaLocked = (bool) $tosaApplicant?->studentSubmissionLocked();
        $tosaRevision = (bool) ($tosaApplicant && $tosaApplicant->status === 'submitted' && $tosaApplicant->subsection === 'returned');

        $tosaDocs = collect(TosaRequirements::SUBMISSIONS)->map(function (array $document, string $key) use ($tosaApplicant, $tosaRequirements): array {
            $path = $tosaApplicant?->requirementFile($key);
            $uploaded = $path !== null;
            $fileFormat = $path ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : null;
            $canPreview = in_array($fileFormat, ['PDF', 'DOCX'], true);

            return [
                'key' => $key,
                'code' => $document['code'],
                'title' => $document['title'],
                'desc' => $document['description'],
                'icon' => 'is-'.$document['color'],
                'bi' => 'bi-'.$document['icon'],
                'file' => $tosaRequirements[$key] ?? null,
                'uploaded' => $uploaded,
                'format' => $document['format'],
                'file_format' => $fileFormat,
                'accept' => '.'.implode(',.', $document['extensions']),
                'download_url' => route('portal.tosa.documents.show', ['key' => $key, 'download' => 1]),
                'preview_url' => $canPreview ? route('portal.tosa.documents.show', ['key' => $key]) : null,
                'can_preview' => $canPreview,
            ];
        })->values()->all();
        $tosaReferences = collect(TosaRequirements::REFERENCES)->map(function (array $document, string $key): array {
            $template = TosaRequirements::resolveTemplate($key);
            $canPreview = $template !== null && in_array($document['format'], ['PDF', 'DOCX', 'DOC'], true);

            return [
                'key' => $key,
                'title' => $document['title'],
                'description' => $document['description'],
                'name' => $template['name'] ?? $document['filename'],
                'available' => $template !== null,
                'format' => $document['format'],
                'preview_format' => $document['format'] === 'DOC' ? 'PDF' : $document['format'],
                'source' => $template['source'] ?? 'bundled',
                'download_url' => route('portal.tosa.templates.show', ['key' => $key]),
                'preview_url' => $canPreview ? route('portal.tosa.templates.show', ['key' => $key, 'preview' => 1]) : null,
                'can_preview' => $canPreview,
            ];
        })->values()->all();

        $tosaComplete = $tosaApplicant !== null && collect($tosaDocs)->every(fn (array $document): bool => $document['uploaded']);

        // Live bulletins feed: office announcements + upcoming activities + open renewal window.
        $feedItems = collect($announcements);
        $activityItems = OrgActivity::query()
            ->visibleToStudents()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(function (OrgActivity $a) {
                $date = $a->starts_at ?? $a->created_at ?? now();

                return [
                    'id' => 'act'.$a->id,
                    'category' => 'activities',
                    'priority' => 'normal',
                    'title' => $a->title,
                    'body' => trim(($a->description ? \Illuminate\Support\Str::limit(strip_tags($a->description), 400) : '').($a->location ? ' Venue: '.$a->location : '')),
                    'author' => $a->organization_name ?: 'Student Organization',
                    'tag' => 'Activities',
                    'pill_class' => 'sp-pill-activities',
                    'day' => $date->format('d M'),
                    'year' => $date->format('Y'),
                    'date' => $date->format('M j, Y'),
                    'sort' => $date->timestamp,
                ];
            });
        $renewalWindow = \App\Models\OrgRenewalWindow::query()->latest('id')->first();
        $dateItems = collect();
        if ($renewalWindow && $renewalWindow->isAcceptingSubmissions()) {
            $closes = $renewalWindow->closes_at;
            $dateItems->push([
                'id' => 'rn'.$renewalWindow->id,
                'category' => 'dates',
                'priority' => 'high',
                'title' => 'Organization renewal filing open — AY '.$renewalWindow->academic_year,
                'body' => ($renewalWindow->instructions ?: 'Upload the complete renewal packet before the window closes.').($closes ? ' Closes '.$closes->format('M j, Y g:i A') : ''),
                'author' => 'OSO Admin',
                'tag' => 'Important Dates',
                'pill_class' => 'sp-pill-dates',
                'day' => ($closes ?? now())->format('d M'),
                'year' => ($closes ?? now())->format('Y'),
                'date' => ($closes ?? now())->format('M j, Y'),
                'sort' => ($closes ?? now())->timestamp,
            ]);
        }
        $bulletins = $feedItems->concat($activityItems)->concat($dateItems)
            ->sortByDesc('sort')->values()
            ->map(fn ($item, $i) => array_merge($item, ['order' => $i + 1]))
            ->all();

        // Real RSVP data: registration counts, my registrations, year-level breakdowns.
        $allRegs = ActivityRegistration::query()->get();
        $regCounts = $allRegs->groupBy('org_activity_id')->map(fn ($rows) => $rows->count())->all();
        $myRsvps = $allRegs->where('student_id', $student?->id)->pluck('org_activity_id')->all();
        $regBreakdown = $allRegs->groupBy('org_activity_id')->map(function ($rows) {
            $total = $rows->count();
            $groups = $rows->groupBy(fn ($r) => trim((string) ($r->year_level ?: 'Undeclared')));

            return $groups->map(fn ($g, $label) => [
                'label' => $label,
                'count' => $g->count(),
                'pct' => $total > 0 ? (int) round(($g->count() / $total) * 100) : 0,
            ])->sortByDesc('count')->values()->all();
        })->all();

        // Budget items grouped by org for the activity detail modal.
        $budgetByOrg = BudgetItem::query()->get()->groupBy(fn ($i) => trim((string) $i->organization_name))->all();
        // Feedback ownership uses the authenticated OrgChain UserAccount ID, including anonymous reports.
        $ownFeedback = $student
            ? StudentFeedback::query()->where('student_id', $student->id)->latest()->get()
            : collect();
        $myFeedback = $ownFeedback;
        $feedbackNotifications = $ownFeedback
            ->where('status', 'verified')
            ->map(fn (StudentFeedback $feedback): array => [
                'id' => 'feedback-'.$feedback->id,
                'title' => 'Feedback acknowledged',
                'body' => $feedback->review_note ?: 'OSO has acknowledged your feedback.',
                'author' => 'Office of Student Organizations',
                'time' => $feedback->verified_at?->diffForHumans() ?? '',
                'url' => route('portal.home').'#feedback-'.$feedback->id,
                'acknowledgedAt' => $feedback->verified_at?->toIso8601String(),
            ])->values()->all();

        return view('portal.index', [
            'student' => $student,
            'tab' => $tab,
            'budgetItems' => $budgetItems,
            'budgetCategoryBreakdown' => $budgetCategoryBreakdown,
            'totalAllocated' => $totalAllocated,
            'totalUtilized' => $totalUtilized,
            'upcoming' => $upcoming,
            'recentActivities' => $recentActivities,
            'posts' => $posts,
            'activities' => $activities,
            'announcements' => $announcements,
            'tosaDocs' => $tosaDocs,
            'tosaReferences' => $tosaReferences,
            'tosaApplicant' => $tosaApplicant,
            'tosaComplete' => $tosaComplete,
            'tosaLocked' => $tosaLocked,
            'tosaRevision' => $tosaRevision,
            'bulletins' => $bulletins,
            'regCounts' => $regCounts,
            'myRsvps' => $myRsvps,
            'regBreakdown' => $regBreakdown,
            'budgetByOrg' => $budgetByOrg,
            'publicExpenseItemsByActivity' => $publicExpenseItemsByActivity,
            'myFeedback' => $myFeedback,
            'feedbackNotifications' => $feedbackNotifications,
            'sdgHighlights' => $sdgHighlights,
            'colleges' => $colleges,
            'feedSort' => $feedSort,
            'communitySearch' => $communitySearch,
            'communityAudience' => $communityAudience,
            'communityPeriod' => $communityPeriod,
            'selectedCollege' => $collegeFilter,
            'collegeFilterExplicit' => $request->has('college'),
            'selectedStatus' => $statusFilter,
            'overview' => [
                'activities' => $activities->count(),
                'upcoming' => $upcoming->count(),
                'completed' => $recentActivities->count(),
                'sdg_tags' => $sdgHighlights->count(),
            ],
        ]);
    }
}

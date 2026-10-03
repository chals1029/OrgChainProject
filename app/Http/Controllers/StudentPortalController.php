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
use App\Services\BudgetChainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPortalController extends Controller
{
    private const TOSA_TEMPLATE_PATH = 'TOSA/TOSA Application Form 2025.docx';

    private const TOSA_TEMPLATE_NAME = 'TOSA Application Form 2025.docx';

    private const TOSA_DOCS = [
        'cv' => 'Curriculum Vitae',
        'good_moral' => 'Good Moral Certificate',
        'scholastic' => 'Scholastic Record (copy of grades from Registration Services)',
        'certificates' => 'Certificates with proof of legitimacy (Records Office certified)',
        'application_docs' => 'Complete application documents (scanned)',
    ];

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
            $disk = Storage::disk((string) ($file['disk'] ?? $receipt->receipt_disk ?: 'local'));
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

    /**
     * Serve the original university TOSA DOCX to authenticated students.
     * Preview requests stay inline; the explicit download action preserves
     * the official filename and file contents.
     */
    public function downloadTosaTemplate(Request $request): BinaryFileResponse
    {
        $path = base_path(self::TOSA_TEMPLATE_PATH);
        abort_unless(is_file($path), 404, 'The official TOSA application form is unavailable.');

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if ($request->boolean('download')) {
            return response()->download($path, self::TOSA_TEMPLATE_NAME, $headers);
        }

        return response()->file($path, [
            ...$headers,
            'Content-Disposition' => 'inline; filename="'.self::TOSA_TEMPLATE_NAME.'"',
        ]);
    }

    /**
     * Upload (or replace) one TOSA requirement PDF for the logged-in student.
     */
    public function uploadTosaDocument(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();

        $validator = Validator::make($request->all(), [
            'doc_key' => ['required', 'in:'.implode(',', array_keys(self::TOSA_DOCS))],
            'document' => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        if ($validator->fails()) {
            return $this->tosaRedirect()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $srCode = trim((string) ($student?->sr_code ?? ''));
        abort_unless($srCode !== '', 403, 'Your student record has no SR code.');

        $applicant = TosaApplicant::query()->firstOrCreate(
            ['sr_code' => $srCode],
            [
                'full_name' => $student->full_name ?? $student->name ?? 'Student',
                'email' => $student->email,
                'college' => $student->college,
                'program' => $student->program ?? null,
                'subsection' => 'pending',
                'status' => 'draft',
            ]
        );

        if (in_array($applicant->subsection, ['accepted'], true)) {
            return $this->tosaRedirect()->withErrors(['tosa' => 'Your application is already accepted; documents are locked.']);
        }

        $file = $validated['document'];
        $path = $file->storeAs('tosa/'.$srCode, $validated['doc_key'].'.'.$file->getClientOriginalExtension(), 'public');

        $requirements = is_array($applicant->requirements) ? $applicant->requirements : [];
        $requirements[$validated['doc_key']] = [
            'title' => self::TOSA_DOCS[$validated['doc_key']],
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'uploaded_at' => now()->toIso8601String(),
        ];
        $applicant->requirements = $requirements;
        $applicant->save();

        return $this->tosaRedirect()->with('status', self::TOSA_DOCS[$validated['doc_key']].' uploaded.');
    }

    /**
     * Remove one uploaded TOSA requirement.
     */
    public function removeTosaDocument(Request $request, string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, self::TOSA_DOCS), 404);

        $applicant = $this->myTosaApplication(Auth::guard('student')->user());
        abort_unless($applicant, 404);

        if (in_array($applicant->subsection, ['accepted'], true)) {
            return $this->tosaRedirect()->withErrors(['tosa' => 'Your application is already accepted; documents are locked.']);
        }

        $requirements = is_array($applicant->requirements) ? $applicant->requirements : [];
        if (isset($requirements[$key]['path'])) {
            Storage::disk('public')->delete($requirements[$key]['path']);
        }
        unset($requirements[$key]);
        $applicant->requirements = $requirements;
        $applicant->save();

        return $this->tosaRedirect()->with('status', self::TOSA_DOCS[$key].' removed.');
    }

    /**
     * Submit the TOSA application once all 5 requirements are uploaded.
     */
    public function submitTosaApplication(Request $request): RedirectResponse
    {
        $applicant = $this->myTosaApplication(Auth::guard('student')->user());
        abort_unless($applicant, 404, 'Upload your requirements first.');

        $requirements = is_array($applicant->requirements) ? $applicant->requirements : [];
        $missing = array_diff(array_keys(self::TOSA_DOCS), array_keys($requirements));
        if ($missing !== []) {
            return $this->tosaRedirect()->withErrors(['tosa' => 'Upload all '.count($missing).' remaining requirement(s) before submitting.']);
        }

        $applicant->subsection = in_array($applicant->subsection, ['pending', 'returned', 'rejected'], true)
            ? 'screening'
            : $applicant->subsection;
        $applicant->status = 'submitted';
        $applicant->save();

        return $this->tosaRedirect()->with('status', 'TOSA application submitted for OSO evaluation.');
    }

    /**
     * Return to the TOSA workspace after a server-side mutation.
     *
     * The portal tabs are client-side, so the hash must be restored explicitly
     * after uploads, removals, validation errors, and final submission.
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
        $budgetActivities = OrgActivity::query()->visibleToStudents()->get();
        $budgetItems = $budgetActivities->groupBy('activity_scope')->map(function ($rows, $scope) {
            return new BudgetItem([
                'title' => str_contains($scope, 'off') ? 'Off-Campus' : 'In-Campus',
                'category' => str_contains($scope, 'off') ? 'Off-Campus' : 'In-Campus',
                'allocated' => round((float) $rows->sum('approved_budget'), 2),
                'utilized' => round((float) $rows->sum('implemented_budget'), 2),
            ]);
        })->values();
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
        if (! in_array($feedSort, ['recent', 'top', 'popular'], true)) {
            $feedSort = 'recent';
        }

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
            'popular' => $postsQuery->orderByDesc('likes_count')->orderByDesc('created_at'),
            'top' => $postsQuery->orderByDesc('comments_count')->orderByDesc('created_at'),
            default => $postsQuery->latest(),
        };

        $posts = $postsQuery->paginate(10)->withQueryString();

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
                'samples' => $rows->take(3)->pluck('title')->values(),
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
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (OfficeAnnouncement $a) => [
                'title' => $a->title,
                'body' => $a->body,
                'author' => $a->author ?: 'OSO Admin',
                'time' => optional($a->created_at)->diffForHumans() ?? '',
                'priority' => $a->priority ?: 'normal',
            ])
            ->values()
            ->all();

        $tosaApplicant = $this->myTosaApplication($student);
        $tosaRequirements = is_array($tosaApplicant?->requirements) ? $tosaApplicant->requirements : [];
        $tosaComplete = count(array_intersect(array_keys(self::TOSA_DOCS), array_keys($tosaRequirements))) === count(self::TOSA_DOCS);
        $tosaSubmitted = (bool) ($tosaApplicant && ($tosaApplicant->status === 'submitted'));
        $tosaSub = $tosaApplicant?->subsection ?? 'pending';
        $tosaLocked = (bool) ($tosaSubmitted && in_array($tosaSub, ['screening', 'interview', 'accepted'], true));

        $tosaDocMeta = [
            'cv' => ['icon' => 'is-blue', 'bi' => 'bi-person-lines-fill', 'desc' => 'Upload your comprehensive Curriculum Vitae (CV) with academic and leadership records.'],
            'good_moral' => ['icon' => 'is-green', 'bi' => 'bi-shield-check', 'desc' => 'Upload your Certificate of Good Moral Character from Student Affairs / Guidance.'],
            'scholastic' => ['icon' => 'is-orange', 'bi' => 'bi-file-earmark-ruled', 'desc' => 'Upload official copy of grades or Scholastic Record certified by Registration Services.'],
            'certificates' => ['icon' => 'is-purple', 'bi' => 'bi-award', 'desc' => 'Upload certificates with proof of legitimacy duly certified by the Records Office.'],
            'application_docs' => ['icon' => 'is-teal', 'bi' => 'bi-folder-check', 'desc' => 'Complete the official TOSA application form, then upload the signed or scanned PDF dossier.'],
        ];
        $tosaDocs = collect(self::TOSA_DOCS)->map(fn ($title, $key) => [
            'key' => $key,
            'title' => $title,
            'desc' => $tosaDocMeta[$key]['desc'],
            'icon' => $tosaDocMeta[$key]['icon'],
            'bi' => $tosaDocMeta[$key]['bi'],
            'file' => $tosaRequirements[$key] ?? null,
        ])->values()->all();

        // Server-driven 5-step tracker from the applicant's real pipeline stage.
        $tosaUploadedCount = count(array_intersect(array_keys(self::TOSA_DOCS), array_keys($tosaRequirements)));
        $tosaStep = fn (string $state, string $pill, string $time, string $line) => [
            'state' => $state, 'pill' => $pill, 'time' => $time, 'line' => $line,
        ];
        $pend = $tosaStep('is-pending', 'Pending', '', 'is-dashed');
        $tosaSteps = [
            1 => $pend, 2 => $pend, 3 => $pend, 4 => $pend, 5 => $pend,
        ];
        if ($tosaSubmitted || $tosaSub !== 'pending') {
            $tosaSteps[1] = $tosaStep('is-completed', 'Submitted', optional($tosaApplicant?->updated_at)->format('M j, Y') ?? '', 'is-green');
        } elseif ($tosaUploadedCount > 0) {
            $tosaSteps[1] = $tosaStep('is-inprogress', $tosaUploadedCount.'/5 Uploaded', 'Ready to submit below', 'is-dashed');
        } else {
            $tosaSteps[1] = $tosaStep('is-pending', 'Pending', 'Awaiting documents', 'is-dashed');
        }
        if ($tosaSub === 'screening') {
            $tosaSteps[2] = $tosaStep('is-inprogress', 'In Progress', 'Under Evaluators Review...', 'is-blue');
        } elseif (in_array($tosaSub, ['interview', 'accepted'], true)) {
            $tosaSteps[2] = $tosaStep('is-completed', 'Completed', '', 'is-green');
        } elseif ($tosaSub === 'returned') {
            $tosaSteps[2] = $tosaStep('is-inprogress', 'Returned', 'Revise and resubmit below', 'is-blue');
        } elseif ($tosaSub === 'rejected') {
            $tosaSteps[2] = $tosaStep('is-pending', 'Rejected', '', 'is-dashed');
        }
        if ($tosaSub === 'interview') {
            $tosaSteps[3] = $tosaStep('is-inprogress', 'In Progress', 'Panel interview stage', 'is-blue');
        } elseif ($tosaSub === 'accepted') {
            $tosaSteps[3] = $tosaStep('is-completed', 'Completed', '', 'is-green');
            $tosaSteps[4] = $tosaStep('is-completed', 'Completed', '', 'is-green');
            $tosaSteps[5] = $tosaStep('is-completed', 'Approved', optional($tosaApplicant?->updated_at)->format('M j, Y') ?? '', 'is-green');
        }

        // Live bulletins feed: office announcements + upcoming activities + open renewal window.
        $bulletinTypeMap = [
            'Deadline Notice' => ['deadlines', 'Deadline Reminders', 'sp-pill-deadlines'],
            'Compliance Reminder' => ['deadlines', 'Deadline Reminders', 'sp-pill-deadlines'],
            'Policy & Guideline' => ['oso', 'OSO Notices', 'sp-pill-oso'],
            'Official Notice' => ['oso', 'OSO Notices', 'sp-pill-oso'],
            'General Announcement' => ['oso', 'OSO Notices', 'sp-pill-oso'],
        ];
        $feedItems = OfficeAnnouncement::query()->latest()->limit(15)->get()->map(function (OfficeAnnouncement $a) use ($bulletinTypeMap) {
            $cat = $bulletinTypeMap[$a->type] ?? ['oso', 'OSO Notices', 'sp-pill-oso'];
            if (stripos(($a->title ?? '').' '.($a->body ?? ''), 'tosa') !== false) {
                $cat = ['tosa', 'Call for TOSA', 'sp-pill-tosa'];
            }
            $date = $a->created_at ?? now();

            return [
                'id' => 'a'.$a->id,
                'category' => $cat[0],
                'priority' => $a->priority ?: 'normal',
                'title' => $a->title,
                'body' => $a->body,
                'author' => $a->author ?: 'OSO Admin',
                'tag' => $cat[1],
                'pill_class' => $cat[2],
                'day' => $date->format('d M'),
                'year' => $date->format('Y'),
                'date' => $date->format('M j, Y'),
                'sort' => $date->timestamp,
            ];
        });
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
        // Student's own reports with OSO verification statuses.
        $myFeedback = $student
            ? StudentFeedback::query()->where('student_id', $student->id)->latest()->limit(5)->get()
            : collect();

        return view('portal.index', [
            'student' => $student,
            'tab' => $tab,
            'budgetItems' => $budgetItems,
            'totalAllocated' => $totalAllocated,
            'totalUtilized' => $totalUtilized,
            'upcoming' => $upcoming,
            'recentActivities' => $recentActivities,
            'posts' => $posts,
            'activities' => $activities,
            'announcements' => $announcements,
            'tosaDocs' => $tosaDocs,
            'tosaTemplate' => [
                'name' => self::TOSA_TEMPLATE_NAME,
                'available' => is_file(base_path(self::TOSA_TEMPLATE_PATH)),
                'preview_url' => route('portal.tosa.template', ['preview' => 1]),
                'download_url' => route('portal.tosa.template', ['download' => 1]),
            ],
            'tosaApplicant' => $tosaApplicant,
            'tosaComplete' => $tosaComplete,
            'tosaSubmitted' => $tosaSubmitted,
            'tosaLocked' => $tosaLocked,
            'tosaSteps' => $tosaSteps,
            'bulletins' => $bulletins,
            'regCounts' => $regCounts,
            'myRsvps' => $myRsvps,
            'regBreakdown' => $regBreakdown,
            'budgetByOrg' => $budgetByOrg,
            'publicExpenseItemsByActivity' => $publicExpenseItemsByActivity,
            'myFeedback' => $myFeedback,
            'sdgHighlights' => $sdgHighlights,
            'colleges' => $colleges,
            'feedSort' => $feedSort,
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

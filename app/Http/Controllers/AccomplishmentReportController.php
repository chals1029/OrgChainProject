<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgReportDocument;
use App\Models\StudentOrganization;
use App\Services\AccomplishmentDocumentService;
use App\Services\AccomplishmentReportService;
use App\Services\ActivityBudgetService;
use App\Services\OrganizationCashLedger;
use App\Services\SemesterReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccomplishmentReportController extends Controller
{
    public function __construct(
        private readonly AccomplishmentReportService $reports,
        private readonly AccomplishmentDocumentService $documents,
        private readonly SemesterReportService $semesterReports,
        private readonly ActivityBudgetService $budgets,
    ) {}

    public function index(array $context): View
    {
        $actor = $this->actor();
        $organization = $actor->office_role === 'so' ? $this->organization($actor) : trim((string) ($context['selectedOrganization'] ?? ''));
        if ($organization !== '') {
            abort_unless(StudentOrganization::query()->where('name', $organization)->exists(), 404);
        }
        $current = $this->budgets->period();
        $semester = (string) ($context['selectedSemester'] ?? $current['semester']);
        $year = (string) ($context['selectedYear'] ?? $current['academic_year']);
        $this->reports->validatePeriod($semester, $year);
        $locked = $organization !== '' && $this->reports->locked($organization, $semester, $year);
        $visible = $actor->office_role === 'so' || ($organization !== '' && $this->osoVisible($organization, $semester, $year));
        $document = $visible && $organization !== '' ? $this->reports->nativeDocument($organization, $semester, $year) : null;
        $entries = $visible && $organization !== '' ? $this->reports->entries($organization, $semester, $year)->get() : collect();
        $packet = $document && is_array($document->accomplishment_summary)
            ? $this->reports->publicPacket($document->accomplishment_summary)
            : $this->reports->packet($organization, $semester, $year, $entries->map(fn ($entry) => $this->reports->report($entry))->all());
        $rows = $packet['reports'];
        $years = $organization !== '' ? OrgActivityAccomplishment::query()->where('organization_name', $organization)->distinct()->pluck('academic_year')->all() : [];
        $start = (int) substr($current['academic_year'], 0, 4);
        for ($offset = 0; $offset < 5; $offset++) {
            $years[] = ($start - $offset).'-'.($start - $offset + 1);
        }
        $years[] = $year;
        $years = array_values(array_unique($years));
        rsort($years);
        $expenseCents = 0;
        $participants = $evidence = 0;
        foreach ($rows as $row) {
            $expenseCents += OrganizationCashLedger::cents($row['financial']['total_expenses']);
            $participants += $row['participants'];
            $evidence += count($row['evidence']);
        }
        $canEdit = $actor->office_role === 'so' && ! $locked;
        $hasDocument = $document?->hasStoredFile() ?? false;
        $dashboard = [
            'organization' => $organization, 'academic_year' => $year, 'semester' => $semester,
            'years' => $years, 'semesters' => AccomplishmentReportService::SEMESTERS, 'locked' => $locked, 'can_edit' => $canEdit,
            'eligible_activities' => $canEdit ? $this->reports->eligibleActivities($organization)->get()->map(fn ($activity) => $this->reports->activityContext($activity))->all() : [],
            'reports' => $rows, 'stats' => ['activities' => count($rows), 'participants' => $participants, 'evidence' => $evidence, 'expenses' => OrganizationCashLedger::decimal($expenseCents)],
            'document_id' => $document?->id,
            'preview_url' => $hasDocument ? route('office.reports.documents.view', ['document' => $document]) : null,
            'export_url' => $hasDocument ? route('office.reports.documents.view', ['document' => $document, 'download' => 1]) : null,
            'store_url' => route('office.accomplishment.reports.store'), 'template_url' => route('office.accomplishment.template', ['kind' => 'narrative']),
            'particulars_template_url' => route('office.accomplishment.template', ['kind' => 'particulars']),
        ];
        return view('org.accomplishment', [...$context, 'selectedOrganization' => $organization, 'selectedSemester' => $semester,
            'selectedYear' => $year, 'accomplishmentDashboard' => $dashboard, 'accomplishmentPacket' => $packet]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor();
        $this->organization($actor);
        $entry = $this->reports->save($request, $actor);
        return $this->saved($entry);
    }

    public function update(Request $request, OrgActivityAccomplishment $report): RedirectResponse
    {
        $actor = $this->actor();
        abort_unless($report->organization_name === $this->organization($actor), 403);
        $entry = $this->reports->save($request, $actor, $report);
        return $this->saved($entry);
    }

    public function evidence(Request $request, OrgActivityAccomplishment $report, string $evidence): BinaryFileResponse
    {
        $this->authorizeEntry($report);
        $row = $this->savedReport($report);
        $image = collect(array_merge($row['evidence'] ?? [], $row['financial']['receipt_attachments'] ?? []))->firstWhere('id', $evidence);
        abort_unless(is_array($image), 404);
        $info = $this->reports->storedImage($image);
        abort_unless($info, 404);
        return response()->file(Storage::disk('local')->path($image['path']), [
            'Content-Type' => $info['mime'],
            'Content-Disposition' => 'inline', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }

    public function preview(Request $request, OrgActivityAccomplishment $report): View
    {
        $this->authorizeEntry($report);
        $row = $this->reports->publicReport($this->savedReport($report));
        $document = $this->reports->nativeDocument($report->organization_name, $report->semester, $report->academic_year);
        $packet = $document?->accomplishment_summary ?? $this->reports->packet($report->organization_name, $report->semester, $report->academic_year, []);
        $packet['reports'] = [$row];
        $packet['classification'] = $row['classification'] ?? '';
        $packet['signatories'] = $row['signatories'];
        return view('org.accomplishment-print', ['accomplishmentPacket' => $packet]);
    }

    public function authorizeDocument(OrgReportDocument $document): void
    {
        $actor = $this->actor();
        abort_unless($document->report_type === 'ar' && is_array($document->accomplishment_summary), 404);
        if ($actor->office_role === 'so') {
            abort_unless($document->organization_name === $this->organization($actor), 403);
        } else {
            abort_unless($this->osoDocumentVisible($document), 403, 'This Accomplishment Report has not been submitted to OSO.');
        }
    }

    public function document(OrgReportDocument $document): View
    {
        $this->authorizeDocument($document);
        abort_unless($document->hasStoredFile(), 404);
        return view('org.accomplishment-print', ['accomplishmentPacket' => $this->reports->publicPacket($document->accomplishment_summary)]);
    }

    public function template(Request $request): BinaryFileResponse
    {
        $actor = $this->actor();
        if ($actor->office_role === 'so') {
            $this->organization($actor);
        }
        $kind = $request->validate(['kind' => ['nullable', Rule::in(['narrative', 'particulars'])]])['kind'] ?? 'narrative';
        $path = $this->documents->templatePath($kind);
        abort_unless(is_file($path) && is_readable($path), 404);
        return response()->download($path, $kind === 'particulars' ? 'Particulars of the Accomplishments.docx' : 'Accomplishment Report Template.docx', ['Content-Type' => AccomplishmentDocumentService::MIME_TYPE]);
    }

    private function actor(): OfficeUser
    {
        $actor = Auth::guard('office')->user();
        abort_unless($actor instanceof OfficeUser && in_array($actor->office_role, ['so', 'oso'], true), 403);
        return $actor;
    }

    private function organization(OfficeUser $actor): string
    {
        abort_unless($actor->office_role === 'so', 403);
        $name = trim((string) $actor->studentOrganization?->name);
        abort_if($name === '', 403, 'This SO account is not assigned to an organization.');
        return $name;
    }

    private function osoVisible(string $organization, string $semester, string $year): bool
    {
        $document = $this->reports->nativeDocument($organization, $semester, $year);
        return $document !== null && $this->osoDocumentVisible($document);
    }

    private function osoDocumentVisible(OrgReportDocument $document): bool
    {
        $status = $this->semesterReports->find('ar', $document->organization_name, $document->semester, $document->academic_year);
        if (! $status
            || $document->report_type !== 'ar'
            || (int) $status->id !== (int) $document->org_report_status_id
            || ! in_array($status->status, SemesterReportService::VIEWABLE_STATUSES, true)) {
            return false;
        }
        $nativeDocument = $this->reports->nativeDocument($document->organization_name, $document->semester, $document->academic_year);
        $latestDocumentId = OrgReportDocument::query()
            ->where('org_report_status_id', $status->id)
            ->where('report_type', 'ar')
            ->where('organization_name', $document->organization_name)
            ->where('semester', $document->semester)
            ->where('academic_year', $document->academic_year)
            ->max('id');
        return (int) $nativeDocument?->id === (int) $document->id
            && (int) $latestDocumentId === (int) $document->id;
    }

    private function authorizeEntry(OrgActivityAccomplishment $entry): void
    {
        $actor = $this->actor();
        if ($actor->office_role === 'so') {
            abort_unless($entry->organization_name === $this->organization($actor), 403);
        } else {
            abort_unless($this->osoVisible($entry->organization_name, $entry->semester, $entry->academic_year), 403);
            $document = $this->reports->nativeDocument($entry->organization_name, $entry->semester, $entry->academic_year);
            abort_unless($document && collect($document->accomplishment_summary['reports'] ?? [])->contains('id', $entry->id), 404);
        }
    }

    private function savedReport(OrgActivityAccomplishment $entry): array
    {
        $document = $this->reports->nativeDocument($entry->organization_name, $entry->semester, $entry->academic_year);
        $row = collect($document?->accomplishment_summary['reports'] ?? [])->firstWhere('id', $entry->id);
        return $row ?? $this->reports->report($entry, false);
    }

    private function saved(OrgActivityAccomplishment $entry): RedirectResponse
    {
        return redirect()->route('office.accomplishment', ['academic_year' => $entry->academic_year, 'semester' => $entry->semester])
            ->with('success', 'Activity report saved and the semester Word packet regenerated. This stages the AR only; submit the Accomplishment Report separately for OSO review.');
    }
}

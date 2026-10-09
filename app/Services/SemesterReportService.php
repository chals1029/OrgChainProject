<?php

namespace App\Services;

use App\Exceptions\SemesterReportRejected;
use App\Models\OrgActivity;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Each semester report has its own submission and OSO review lifecycle. */
class SemesterReportService
{
    public const LOCKED_STATUSES = ['oso_review', 'verified', 'archived', 'rejected'];

    public const VIEWABLE_STATUSES = ['oso_review', 'verified', 'archived', 'rejected'];

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return ['ar', 'fr'];
    }

    public function college(string $organization): ?string
    {
        return StudentOrganization::query()->where('name', $organization)->value('college')
            ?: OrgActivity::query()->where('organization_name', $organization)->value('college');
    }

    public function find(string $reportType, string $organization, string $semester, string $academicYear): ?OrgReportStatus
    {
        return OrgReportStatus::query()
            ->where('report_type', $reportType)
            ->where('organization_name', $organization)
            ->where('semester', $semester)
            ->where('academic_year', $academicYear)
            ->latest('id')
            ->first();
    }

    public function ensure(string $reportType, string $organization, string $semester, string $academicYear): OrgReportStatus
    {
        return $this->find($reportType, $organization, $semester, $academicYear)
            ?? OrgReportStatus::query()->create([
                'report_type' => $reportType,
                'organization_name' => $organization,
                'college' => $this->college($organization),
                'semester' => $semester,
                'academic_year' => $academicYear,
                'status' => 'draft',
                'batch_key' => (string) Str::uuid(),
            ]);
    }

    /**
     * @param  iterable<OrgReportStatus|null>  $statuses  all rows for the same report type and period
     */
    public function isLocked(iterable $statuses): bool
    {
        return collect($statuses)
            ->filter()
            ->contains(fn (OrgReportStatus $status): bool => in_array($status->status, self::LOCKED_STATUSES, true));
    }

    /**
     * Submit only the latest status and document for the selected report type.
     * The global submission window is locked first, then the organization row
     * serializes status creation, staging and submission. Every duplicate
     * same-type status is locked and checked before proceeding.
     *
     * @param  (callable(OrgReportStatus, OrgReportDocument): void)|null  $guard
     * @throws SemesterReportRejected
     */
    public function submitReport(
        string $reportType,
        string $organization,
        string $semester,
        string $academicYear,
        ?string $notes = null,
        ?callable $guard = null,
        bool $create = true,
    ): void {
        if (! in_array($reportType, $this->types(), true)) {
            throw new SemesterReportRejected(SemesterReportRejected::GUARD, 'Choose AR or FR to submit.', [$reportType]);
        }

        DB::connection('mysql')->transaction(function () use ($reportType, $organization, $semester, $academicYear, $notes, $guard, $create): void {
            app(ReportSubmissionWindowService::class)->assertOpen($reportType, $semester, $academicYear);
            StudentOrganization::on('mysql')->where('name', $organization)->lockForUpdate()->firstOrFail();
            $statuses = OrgReportStatus::query()
                ->where('report_type', $reportType)
                ->where('organization_name', $organization)
                ->where('semester', $semester)
                ->where('academic_year', $academicYear)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($this->isLocked($statuses)) {
                throw new SemesterReportRejected(SemesterReportRejected::LOCKED, '', [$reportType]);
            }

            $status = $statuses->last();
            if (! $status && $create) {
                $status = $this->ensure($reportType, $organization, $semester, $academicYear);
            }
            $document = $status ? OrgReportDocument::query()
                ->where('org_report_status_id', $status->id)
                ->where('report_type', $reportType)
                ->where('organization_name', $organization)
                ->where('semester', $semester)
                ->where('academic_year', $academicYear)
                ->latest('id')
                ->lockForUpdate()
                ->first() : null;
            if (! $document || ! $document->hasStoredFile()) {
                throw new SemesterReportRejected(SemesterReportRejected::MISSING, '', [$reportType]);
            }
            if ($guard !== null) {
                $guard($status, $document);
            }
            if ($reportType === 'ar') {
                try {
                    app(AccomplishmentReportService::class)->assertSubmittable($organization, $semester, $academicYear);
                } catch (ValidationException $invalid) {
                    throw new SemesterReportRejected(
                        SemesterReportRejected::GUARD,
                        (string) collect($invalid->errors())->flatten()->first(),
                        [$reportType],
                    );
                }
            }

            $status->update([
                'status' => 'oso_review',
                'batch_key' => (string) Str::uuid(),
                'returned_to' => null,
                'notes' => $notes,
                'submitted_at' => now(),
                'opened_at' => null,
                'opened_by' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'archived_at' => null,
                'archive_folder_id' => null,
            ]);
        }, 3);
    }
}

<?php

namespace App\Services;

use App\Exceptions\SemesterReportRejected;
use App\Models\OfficeUser;
use App\Models\OrgReportSubmissionWindow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LogicException;

class ReportSubmissionWindowService
{
    public function __construct(private readonly ActivityBudgetService $budgets) {}

    /**
     * @return array{ar: bool, fr: bool}
     */
    public function locks(string $academicYear, string $semester): array
    {
        $locks = ['ar' => false, 'fr' => false];
        foreach (OrgReportSubmissionWindow::query()
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->whereIn('report_type', ['ar', 'fr'])
            ->get(['report_type', 'is_locked']) as $window) {
            $locks[$window->report_type] = $window->is_locked;
        }

        return $locks;
    }

    /**
     * @param  list<string>  $academicYears
     * @return array<string, bool>
     */
    public function lockedPeriods(string $reportType, array $academicYears): array
    {
        if ($academicYears === []) {
            return [];
        }

        $locked = [];
        foreach (OrgReportSubmissionWindow::query()
            ->where('report_type', $reportType)
            ->whereIn('academic_year', $academicYears)
            ->where('is_locked', true)
            ->get(['academic_year', 'semester']) as $window) {
            $locked[$window->academic_year.'|'.$window->semester] = true;
        }

        return $locked;
    }

    /** Must run before organization/report locks inside the submission transaction. */
    public function assertOpen(string $reportType, string $semester, string $academicYear): void
    {
        $this->validatePeriod($reportType, $semester, $academicYear);
        if (DB::connection('mysql')->transactionLevel() === 0) {
            throw new LogicException('The report submission window must be checked inside a mysql transaction.');
        }

        if ($this->lockedWindow($reportType, $semester, $academicYear)->is_locked) {
            throw new SemesterReportRejected(
                SemesterReportRejected::GUARD,
                'OSO has locked '.strtoupper($reportType).' submissions for AY '.$academicYear.' · '.$semester.'. Wait for OSO to reopen submissions.',
                [$reportType],
            );
        }
    }

    public function setLocked(string $reportType, string $semester, string $academicYear, bool $locked, OfficeUser $office): void
    {
        abort_unless($office->office_role === 'oso', 403);
        $this->validatePeriod($reportType, $semester, $academicYear);

        DB::connection('mysql')->transaction(function () use ($reportType, $semester, $academicYear, $locked, $office): void {
            $this->lockedWindow($reportType, $semester, $academicYear)->update([
                'is_locked' => $locked,
                'updated_by' => $office->id,
            ]);
        }, 3);
    }

    private function validatePeriod(string $reportType, string $semester, string $academicYear): void
    {
        Validator::make(['report_type' => $reportType, 'semester' => $semester], [
            'report_type' => ['required', Rule::in(['ar', 'fr'])],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester', 'Midyear'])],
        ])->validate();
        $this->budgets->dates($academicYear, $semester);
    }

    /** Both OSO updates and submissions acquire this same global period row first. */
    private function lockedWindow(string $reportType, string $semester, string $academicYear): OrgReportSubmissionWindow
    {
        $period = ['report_type' => $reportType, 'academic_year' => $academicYear, 'semester' => $semester];
        $now = now();
        OrgReportSubmissionWindow::query()->insertOrIgnore([
            ...$period,
            'is_locked' => false,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return OrgReportSubmissionWindow::query()->where($period)->lockForUpdate()->firstOrFail();
    }
}

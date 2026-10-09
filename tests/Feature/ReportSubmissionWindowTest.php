<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\OrgReportSubmissionWindow;
use App\Models\StudentOrganization;
use App\Services\FinancialWorkbookService;
use App\Services\ReportSubmissionWindowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReportSubmissionWindowTest extends TestCase
{
    use UsesLaragonDatabase;

    private const PERIOD = ['academic_year' => '2095-2096', 'semester' => '1st Semester'];

    private const OTHER_YEAR = '2096-2097';

    private const PDF_BYTES = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

    private StudentOrganization $organization;

    private StudentOrganization $otherOrganization;

    /** @var array<string, OfficeUser> */
    private array $offices = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Storage::fake('public');
        Storage::fake('local');

        // Global windows have no organization owner. Isolate these fixture years
        // inside the rollback transaction, restoring any pre-existing rows afterward.
        OrgReportSubmissionWindow::query()
            ->whereIn('academic_year', [self::PERIOD['academic_year'], self::OTHER_YEAR])
            ->delete();
        $this->organization = $this->organizationFixture('Submission Window');
        $this->otherOrganization = $this->organizationFixture('Other Submission Window');
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->temporaryFiles as $path) {
                @unlink($path);
            }
            foreach (['mysql', 'orgchain'] as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_oso_can_lock_and_reopen_the_same_period_without_mutating_reports(): void
    {
        $document = $this->stageAr();
        $before = $this->reportSnapshot();
        $context = self::PERIOD + ['organization' => $this->organization->name, 'tab' => 'fr'];
        $redirect = route('office.reports.index', $context);

        $this->actingAs($this->office('oso'), 'office')
            ->post($this->lockUrl('ar'), $context + ['is_locked' => true])
            ->assertRedirect($redirect)
            ->assertSessionHasNoErrors();
        $window = $this->window('ar');
        $this->assertTrue($window->is_locked);
        $this->assertSame($this->office('oso')->id, (int) $window->updated_by);
        $id = $window->id;
        $this->assertSame($before, $this->reportSnapshot());

        $this->post($this->lockUrl('ar'), $context + ['is_locked' => true])
            ->assertRedirect($redirect)
            ->assertSessionHasNoErrors();
        $this->assertSame($id, $this->window('ar')->id);
        $this->assertSame(1, $this->fixtureWindows()->count());

        $this->post($this->lockUrl('ar'), $context + ['is_locked' => false])
            ->assertRedirect($redirect)
            ->assertSessionHasNoErrors();
        $this->assertSame($id, $this->window('ar')->id);
        $this->assertFalse($this->window('ar')->is_locked);
        $this->assertSame($before, $this->reportSnapshot());
        $this->assertTrue($document->refresh()->hasStoredFile());
    }

    public function test_so_and_other_offices_cannot_create_change_or_reopen_windows(): void
    {
        $this->stageAr();
        $before = $this->reportSnapshot();
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->office($role), 'office')
                ->post($this->lockUrl('fr'), self::PERIOD + ['is_locked' => true])
                ->assertForbidden();
            $this->assertSame(0, $this->fixtureWindows()->count());
        }

        $this->setLock('ar', true);
        $windowBefore = $this->window('ar')->getAttributes();
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->office($role), 'office')
                ->post($this->lockUrl('ar'), self::PERIOD + ['is_locked' => false])
                ->assertForbidden();
            $this->assertSame($windowBefore, $this->window('ar')->getAttributes());
        }
        $this->assertSame($before, $this->reportSnapshot());
    }

    public function test_invalid_lock_payloads_are_rejected_before_any_state_write(): void
    {
        $this->actingAs($this->office('oso'), 'office');
        $valid = self::PERIOD + ['is_locked' => true];
        $windowsBefore = OrgReportSubmissionWindow::query()->orderBy('id')->get()->map->getAttributes()->all();
        $attempts = [
            [['academic_year' => '2095-2097'], 'academic_year'],
            [['academic_year' => '2095'], 'academic_year'],
            [['academic_year' => null], 'academic_year'],
            [['semester' => 'Annual'], 'semester'],
            [['semester' => null], 'semester'],
            [['is_locked' => 'locked'], 'is_locked'],
            [['is_locked' => null], 'is_locked'],
        ];
        foreach ($attempts as [$invalid, $field]) {
            $this->post($this->lockUrl('ar'), array_replace($valid, $invalid))
                ->assertRedirect()
                ->assertSessionHasErrors($field);
            $this->assertSame($windowsBefore, OrgReportSubmissionWindow::query()->orderBy('id')->get()->map->getAttributes()->all());
        }
        $this->post('/office-desk/reports/other/submission-lock', $valid)->assertNotFound();
        $this->assertSame($windowsBefore, OrgReportSubmissionWindow::query()->orderBy('id')->get()->map->getAttributes()->all());

        $this->setLock('ar', true);
        $before = $this->window('ar')->getAttributes();
        $this->actingAs($this->office('oso'), 'office')
            ->post($this->lockUrl('ar'), self::PERIOD + ['is_locked' => 'open'])
            ->assertSessionHasErrors('is_locked');
        $this->assertSame($before, $this->window('ar')->getAttributes());
        $this->assertSame(0, OrgReportStatus::where('organization_name', $this->organization->name)->count());
    }

    public function test_missing_windows_are_open_and_service_reads_and_get_pages_create_no_windows(): void
    {
        $service = app(ReportSubmissionWindowService::class);
        $this->assertSame(['ar' => false, 'fr' => false], $service->locks(self::PERIOD['academic_year'], self::PERIOD['semester']));
        $this->assertSame([], $service->lockedPeriods('ar', [self::PERIOD['academic_year']]));
        $this->assertSame([], $service->lockedPeriods('fr', []));

        $this->actingAs($this->office('oso'), 'office')
            ->get(route('office.reports.index', self::PERIOD + ['organization' => $this->organization->name]))
            ->assertOk()
            ->assertViewHas('submissionLocks', ['ar' => false, 'fr' => false]);
        $this->actingAs($this->office('so'), 'office')
            ->get(route('office.accomplishment', self::PERIOD))
            ->assertOk()
            ->assertViewHas('reportBundle', fn (array $bundle): bool => $bundle['submission_locks'] === ['ar' => false, 'fr' => false]);
        $this->get(route('office.financial', self::PERIOD))->assertOk();
        $this->assertSame(0, $this->fixtureWindows()->count());
        $this->assertSame(0, OrgReportStatus::where('organization_name', $this->organization->name)->count());

        $document = $this->stageAr();
        $this->assertSame(0, $this->fixtureWindows()->count());
        $this->actingAs($this->office('so'), 'office')
            ->post($this->submitUrl('ar'), $this->periodPayload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('oso_review', $document->reportStatus()->firstOrFail()->status);
        $this->assertFalse($this->window('ar')->is_locked);
        $this->assertFalse(app(ReportSubmissionWindowService::class)->locks(self::PERIOD['academic_year'], self::PERIOD['semester'])['fr']);
    }

    public function test_ar_and_fr_windows_are_independent_and_scoped_to_year_and_semester(): void
    {
        $this->setLock('ar', true);
        $service = app(ReportSubmissionWindowService::class);
        $this->assertSame(['ar' => true, 'fr' => false], $service->locks(self::PERIOD['academic_year'], self::PERIOD['semester']));
        $this->assertSame(['ar' => false, 'fr' => false], $service->locks(self::PERIOD['academic_year'], '2nd Semester'));
        $this->assertSame(['ar' => false, 'fr' => false], $service->locks(self::OTHER_YEAR, self::PERIOD['semester']));

        $this->setLock('fr', true, ['academic_year' => self::OTHER_YEAR, 'semester' => 'Midyear']);
        $this->setLock('ar', false, ['academic_year' => self::PERIOD['academic_year'], 'semester' => '2nd Semester']);
        $this->assertSame([self::PERIOD['academic_year'].'|1st Semester' => true], $service->lockedPeriods('ar', [self::PERIOD['academic_year'], self::OTHER_YEAR]));
        $this->assertSame([], $service->lockedPeriods('fr', [self::PERIOD['academic_year']]));
        $this->assertSame([self::OTHER_YEAR.'|Midyear' => true], $service->lockedPeriods('fr', [self::OTHER_YEAR]));

        $fr = $this->stageFr();
        $this->actingAs($this->office('so'), 'office')->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        $this->post($this->submitUrl('fr'), $this->periodPayload())->assertSessionHasNoErrors()->assertSessionHas('success');
        foreach ([
            ['academic_year' => self::PERIOD['academic_year'], 'semester' => '2nd Semester'],
            ['academic_year' => self::PERIOD['academic_year'], 'semester' => 'Midyear'],
            ['academic_year' => self::OTHER_YEAR, 'semester' => self::PERIOD['semester']],
        ] as $period) {
            $ar = $this->stageAr($period);
            $this->post($this->submitUrl('ar'), $this->periodPayload($period))->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame('oso_review', $ar->reportStatus()->firstOrFail()->status);
        }
        $this->assertTrue($this->window('ar')->is_locked);
    }

    public function test_generic_ar_fr_and_dedicated_fr_submissions_reject_locked_windows_without_mutation(): void
    {
        $ar = $this->stageAr();
        $fr = $this->stageFr();
        $this->actingAs($this->office('so'), 'office')->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        foreach (['ar', 'fr'] as $type) {
            $status = ($type === 'ar' ? $ar : $fr)->reportStatus()->firstOrFail();
            $status->update(['notes' => 'Prepared '.$type.' notes retained on rejection.']);
            $this->setLock($type, true);
        }
        $before = $this->reportSnapshot();

        $this->actingAs($this->office('so'), 'office');
        foreach (['ar', 'fr'] as $type) {
            $this->assertWindowRejection(
                $this->post($this->submitUrl($type), $this->periodPayload() + ['notes' => 'Must not replace saved notes']),
                $type,
            );
            $this->assertSame($before, $this->reportSnapshot());
        }
        $this->assertWindowRejection($this->post(route('office.financial.reports.submit', $fr)), 'fr');
        $this->assertSame($before, $this->reportSnapshot());

        foreach (['ar', 'fr'] as $type) {
            $this->setLock($type, false);
            $this->actingAs($this->office('so'), 'office')
                ->post($this->submitUrl($type), $this->periodPayload())
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');
            $status = ($type === 'ar' ? $ar : $fr)->reportStatus()->firstOrFail();
            $this->assertSame('oso_review', $status->status);
            $this->assertNotNull($status->submitted_at);
        }
        foreach ([$ar, $fr] as $document) {
            $this->assertSame($before['files'][$document->file_path], Storage::disk('public')->get($document->file_path));
        }
    }

    public function test_the_same_closed_window_blocks_another_organization_and_its_assigned_so(): void
    {
        foreach (['ar', 'fr'] as $type) {
            $first = $type === 'ar' ? $this->stageAr() : $this->stageFr();
            $second = $type === 'ar'
                ? $this->stageAr(self::PERIOD, $this->otherOrganization)
                : $this->stageFr(self::PERIOD, $this->otherOrganization);
            if ($type === 'fr') {
                foreach ([$first, $second] as $document) {
                    $organization = $document->organization_name === $this->organization->name ? $this->organization : $this->otherOrganization;
                    $this->actingAs($this->office('so', $organization), 'office')
                        ->getJson(route('office.financial.reports.preview', $document))->assertOk();
                }
            }
            $this->setLock($type, true);
            $before = $this->reportSnapshot();
            foreach ([$first, $second] as $document) {
                $organization = $document->organization_name === $this->organization->name ? $this->organization : $this->otherOrganization;
                $this->actingAs($this->office('so', $organization), 'office');
                $this->assertWindowRejection($this->post($this->submitUrl($type), $this->periodPayload(self::PERIOD, $organization)), $type);
                if ($type === 'fr') {
                    $this->assertWindowRejection($this->post(route('office.financial.reports.submit', $document)), 'fr');
                }
                $this->assertSame($before, $this->reportSnapshot());
            }
        }
        $this->assertSame(2, $this->fixtureWindows()->count());
    }

    public function test_preparing_previewing_and_downloading_reports_remain_available_while_locked(): void
    {
        $this->setLock('ar', true);
        $this->setLock('fr', true);
        $ar = $this->stageAr();
        $fr = $this->stageFr();
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/documents', $this->periodPayload() + [
                'name' => 'Generic staged workbook while closed',
                'document' => $this->workbook(1500),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $latest = $this->latestDocument('fr');
        $this->getJson(route('office.financial.reports.preview', $latest))->assertOk()->assertJsonPath('title', $latest->name);
        $this->get(route('office.accomplishment', self::PERIOD))
            ->assertOk()
            ->assertViewHas('reportBundle', fn (array $bundle): bool => $bundle['submission_locks'] === ['ar' => true, 'fr' => true]);
        foreach ([$ar, $fr, $latest] as $document) {
            $response = $this->get(route('office.reports.documents.view', ['document' => $document, 'download' => 1]))->assertOk();
            $this->assertSame(Storage::disk('public')->get($document->file_path), (string) file_get_contents($response->baseResponse->getFile()->getPathname()));
            $this->assertSame('draft', $document->reportStatus()->firstOrFail()->status);
        }
        $this->assertTrue($this->window('ar')->is_locked);
        $this->assertTrue($this->window('fr')->is_locked);
        $this->actingAs($this->office('oso'), 'office')
            ->get(route('office.reports.index', self::PERIOD + ['organization' => $this->organization->name]))
            ->assertOk()
            ->assertViewHas('submissionLocks', ['ar' => true, 'fr' => true]);
    }

    public function test_reopening_fr_does_not_bypass_the_exact_current_file_preview_requirement(): void
    {
        $this->setLock('fr', true);
        $document = $this->stageFr();
        $this->actingAs($this->office('so'), 'office')->getJson(route('office.financial.reports.preview', $document))->assertOk();
        $replacement = $this->workbook(2500);
        Storage::disk('public')->put($document->file_path, (string) file_get_contents($replacement->getRealPath()));
        $this->setLock('fr', false);
        $before = $this->reportSnapshot();
        $this->actingAs($this->office('so'), 'office');
        $this->post(route('office.financial.reports.submit', $document))->assertSessionHasErrors('report');
        $this->assertSame($before, $this->reportSnapshot());
        $this->post($this->submitUrl('fr'), $this->periodPayload())->assertSessionHasErrors('report');
        $this->assertSame($before, $this->reportSnapshot());
        $this->get(route('office.reports.documents.view', ['document' => $document, 'download' => 1]))->assertOk();
        $this->post(route('office.financial.reports.submit', $document))->assertSessionHasErrors('report');
        $this->getJson(route('office.financial.reports.preview', $document))->assertOk();
        $this->post(route('office.financial.reports.submit', $document))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('oso_review', $document->reportStatus()->firstOrFail()->status);
    }

    public function test_returned_resubmission_keeps_review_remarks_and_prepared_revision_when_closed(): void
    {
        foreach (['ar', 'fr'] as $type) {
            $document = $type === 'ar' ? $this->stageAr() : $this->stageFr();
            $this->actingAs($this->office('so'), 'office');
            if ($type === 'fr') {
                $this->getJson(route('office.financial.reports.preview', $document))->assertOk();
            }
            $this->post($this->submitUrl($type), $this->periodPayload())->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->setLock($type, true);
            $remarks = 'OSO '.$type.' revision remarks must survive the closed window.';
            $this->actingAs($this->office('oso'), 'office')
                ->post(route('office.reports.review', ['reportType' => $type]), $this->periodPayload() + ['decision' => 'return', 'notes' => $remarks])
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');
            $status = $document->reportStatus()->firstOrFail();
            $this->assertSame('returned', $status->status);
            $this->assertSame($remarks, $status->notes);
            $revision = $type === 'ar' ? $this->stageAr() : $this->stageFr();
            $this->assertSame($status->id, $revision->org_report_status_id);
            $this->actingAs($this->office('so'), 'office');
            if ($type === 'fr') {
                $this->getJson(route('office.financial.reports.preview', $revision))->assertOk();
            }
            $before = $this->reportSnapshot();
            $this->assertWindowRejection($this->post($this->submitUrl($type), $this->periodPayload() + ['notes' => 'Attempted replacement']), $type);
            if ($type === 'fr') {
                $this->assertWindowRejection($this->post(route('office.financial.reports.submit', $revision)), 'fr');
            }
            $this->assertSame($before, $this->reportSnapshot());
            $this->assertSame($remarks, $status->refresh()->notes);
            $this->assertSame('so', $status->returned_to);
            $this->setLock($type, false);
            $this->actingAs($this->office('so'), 'office')
                ->post($this->submitUrl($type), $this->periodPayload())
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');
            $this->assertSame('oso_review', $status->refresh()->status);
        }
    }

    public function test_oso_can_accept_an_already_submitted_report_while_the_window_is_closed(): void
    {
        $document = $this->stageAr();
        $this->actingAs($this->office('so'), 'office')
            ->post($this->submitUrl('ar'), $this->periodPayload())->assertSessionHasNoErrors()->assertSessionHas('success');
        $status = $document->reportStatus()->firstOrFail();
        $submittedAt = $status->getRawOriginal('submitted_at');
        $batchKey = $status->batch_key;
        $this->setLock('ar', true);
        $this->actingAs($this->office('oso'), 'office')
            ->post(route('office.reports.review', ['reportType' => 'ar']), $this->periodPayload() + ['decision' => 'accept', 'notes' => 'Reviewed during filing closure.'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('archived', $status->refresh()->status);
        $this->assertSame($submittedAt, $status->getRawOriginal('submitted_at'));
        $this->assertSame($batchKey, $status->batch_key);
        $this->assertNotNull($status->archive_folder_id);
        $this->assertTrue($this->window('ar')->is_locked);
        $this->assertSame(self::PDF_BYTES, Storage::disk('public')->get($document->file_path));
    }

    public function test_financial_submit_availability_uses_each_document_period_not_only_the_page_filter(): void
    {
        $closed = $this->stageFr();
        $openSemester = $this->stageFr(['academic_year' => self::PERIOD['academic_year'], 'semester' => '2nd Semester']);
        $openYear = $this->stageFr(['academic_year' => self::OTHER_YEAR, 'semester' => self::PERIOD['semester']]);
        $this->setLock('fr', true);
        $windowCount = $this->fixtureWindows()->count();
        $this->actingAs($this->office('so'), 'office')
            ->get(route('office.financial', ['academic_year' => self::PERIOD['academic_year'], 'semester' => 'all']))
            ->assertOk()
            ->assertViewHas('submissionLocks', fn ($locks): bool => $locks === null)
            ->assertViewHas('financialDashboard', function (array $dashboard) use ($closed, $openSemester, $openYear): bool {
                $reports = collect(array_merge($dashboard['current_reports'], $dashboard['history_reports']))->keyBy('id');
                $this->assertTrue($reports[$closed->id]['submission_locked']);
                $this->assertFalse($reports[$closed->id]['can_submit']);
                foreach ([$openSemester, $openYear] as $document) {
                    $this->assertFalse($reports[$document->id]['submission_locked']);
                    $this->assertTrue($reports[$document->id]['can_submit']);
                }

                return true;
            });
        $this->assertSame($windowCount, $this->fixtureWindows()->count());
        $this->setLock('fr', false);
        $this->actingAs($this->office('so'), 'office')
            ->get(route('office.financial', self::PERIOD))
            ->assertOk()
            ->assertViewHas('financialDashboard', function (array $dashboard) use ($closed): bool {
                $report = collect($dashboard['current_reports'])->firstWhere('id', $closed->id);
                $this->assertFalse($report['submission_locked']);
                $this->assertTrue($report['can_submit']);

                return true;
            });
    }

    private function organizationFixture(string $prefix): StudentOrganization
    {
        return StudentOrganization::create([
            'name' => $prefix.' Fixture '.Str::uuid(),
            'college' => 'Submission Window Test College',
            'is_active' => true,
        ]);
    }

    private function office(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        $organization ??= $this->organization;
        $key = $role.'|'.$organization->id;

        return $this->offices[$key] ??= OfficeUser::create([
            'name' => strtoupper($role).' Submission Window Fixture',
            'email' => 'window-'.$role.'-'.Str::uuid().'@g.batstate-u.edu.ph',
            'username' => 'window_'.$role.'_'.Str::random(12),
            'password' => 'FixtureOnly@2026!',
            'office_role' => $role,
            'office_title' => strtoupper($role).' Desk',
            'student_organization_id' => $role === 'so' ? $organization->id : null,
            'is_active' => true,
        ]);
    }

    private function lockUrl(string $type): string
    {
        return route('office.reports.submission-lock', ['reportType' => $type]);
    }

    private function submitUrl(string $type): string
    {
        return route('office.reports.submit', ['reportType' => $type]);
    }

    private function periodPayload(array $period = self::PERIOD, ?StudentOrganization $organization = null): array
    {
        return $period + ['organization_name' => ($organization ?? $this->organization)->name];
    }

    private function setLock(string $type, bool $locked, array $period = self::PERIOD): void
    {
        $this->actingAs($this->office('oso'), 'office')
            ->post($this->lockUrl($type), $period + ['is_locked' => $locked])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function window(string $type): OrgReportSubmissionWindow
    {
        return OrgReportSubmissionWindow::query()->where('report_type', $type)
            ->where('academic_year', self::PERIOD['academic_year'])->where('semester', self::PERIOD['semester'])->sole();
    }

    private function fixtureWindows(): \Illuminate\Database\Eloquent\Builder
    {
        return OrgReportSubmissionWindow::query()->whereIn('academic_year', [self::PERIOD['academic_year'], self::OTHER_YEAR]);
    }

    private function stageAr(array $period = self::PERIOD, ?StudentOrganization $organization = null): OrgReportDocument
    {
        $organization ??= $this->organization;
        $this->actingAs($this->office('so', $organization), 'office')
            ->post('/office-desk/reports/ar/documents', $this->periodPayload($period, $organization) + [
                'name' => 'Prepared legacy AR',
                'document' => UploadedFile::fake()->createWithContent('accomplishment.pdf', self::PDF_BYTES),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        return $this->latestDocument('ar', $period, $organization);
    }

    private function stageFr(array $period = self::PERIOD, ?StudentOrganization $organization = null): OrgReportDocument
    {
        $this->actingAs($this->office('so', $organization), 'office')
            ->post(route('office.financial.reports.store'), $period + [
                'name' => 'Prepared financial workbook',
                'document' => $this->workbook(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        return $this->latestDocument('fr', $period, $organization);
    }

    private function latestDocument(string $type, array $period = self::PERIOD, ?StudentOrganization $organization = null): OrgReportDocument
    {
        return OrgReportDocument::query()->where('organization_name', ($organization ?? $this->organization)->name)
            ->where('report_type', $type)->where('academic_year', $period['academic_year'])
            ->where('semester', $period['semester'])->latest('id')->firstOrFail();
    }

    private function workbook(float $beginning = 1000): UploadedFile
    {
        $reader = new XlsxReader();
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load(app(FinancialWorkbookService::class)->templatePath());
        $spreadsheet->getSheetByName('Form 3 - SCF')->setCellValue('F46', $beginning);
        $path = tempnam(sys_get_temp_dir(), 'orgchain-window-fr-');
        $this->temporaryFiles[] = $path;
        $writer = new XlsxWriter($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, 'financial.xlsx', FinancialWorkbookService::MIME_TYPE, null, true);
    }

    private function assertWindowRejection(TestResponse $response, string $type): void
    {
        $response->assertRedirect()->assertSessionHasErrors('report');
        $message = session('errors')->first('report');
        $this->assertStringContainsString('OSO', $message);
        $this->assertStringContainsString(strtoupper($type), $message);
        $this->assertStringContainsString(self::PERIOD['academic_year'], $message);
        $this->assertStringContainsString(self::PERIOD['semester'], $message);
        $this->assertStringContainsString('reopen', strtolower($message));
    }

    private function reportSnapshot(): array
    {
        $organizations = [$this->organization->name, $this->otherOrganization->name];
        $documents = OrgReportDocument::query()->whereIn('organization_name', $organizations)->orderBy('id')->get();
        $files = [];
        foreach ($documents as $document) {
            $files[$document->file_path] = Storage::disk('public')->get($document->file_path);
        }

        return [
            'statuses' => OrgReportStatus::query()->whereIn('organization_name', $organizations)->orderBy('id')->get()->map->getAttributes()->all(),
            'documents' => $documents->map->getAttributes()->all(),
            'files' => $files,
            'paths' => Storage::disk('public')->allFiles(),
        ];
    }
}

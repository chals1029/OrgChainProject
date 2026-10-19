<?php

namespace Tests\Feature;

use App\Http\Controllers\OfficePortalController;
use App\Models\ActivityComplianceDoc;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReportingPeriodContractTest extends TestCase
{
    use UsesLaragonDatabase;

    private OfficeUser $office;

    protected function setUp(): void
    {
        parent::setUp();
        $projectEnv = $this->readProjectEnv();
        foreach (['ORGCHAIN_TEST_DATABASE' => 'DB_DATABASE', 'ORGCHAIN_TEST_ORGCHAIN_DATABASE' => 'DB_ORGCHAIN_DATABASE'] as $testKey => $projectKey) {
            if (! getenv($testKey) || getenv($testKey) === ($projectEnv[$projectKey] ?? null)) {
                throw new \RuntimeException('ReportingPeriodContractTest requires separate isolated ORGCHAIN_TEST_DATABASE and ORGCHAIN_TEST_ORGCHAIN_DATABASE databases.');
            }
        }
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Carbon::setTestNow('2030-10-15 12:00:00');
        // These reporting sources are reset only inside isolated transactions.
        OrgRenewalSubmission::query()->delete();
        OrgReportStatus::query()->delete();
        StudentOrganization::query()->update(['is_active' => false]);
        $this->office = $this->officer();
        $this->actingAs($this->office, 'office');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['orgchain', 'mysql'] as $connection) {
            if (DB::connection($connection)->transactionLevel() > 0) {
                DB::connection($connection)->rollBack();
            }
        }
        parent::tearDown();
    }

    private function officer(string $role = 'oso', ?StudentOrganization $organization = null): OfficeUser
    {
        $key = 'report-period-'.Str::uuid();

        return OfficeUser::create([
            'name' => 'Reporting Fixture', 'username' => $key, 'email' => $key.'@example.test',
            'password' => 'IsolatedFixture@2030!', 'office_role' => $role,
            'office_title' => 'Reporting Fixture', 'is_active' => true, 'must_change_password' => false,
            'student_organization_id' => $organization?->id,
        ]);
    }

    private function organization(string $name, ?string $academicYear = '2030-2031', bool $active = true, string $college = 'CICS'): StudentOrganization
    {
        return StudentOrganization::create([
            'name' => $name, 'short_name' => Str::limit($name, 20, ''), 'college' => $college,
            'academic_year' => $academicYear ?? '', 'is_active' => $active,
        ]);
    }

    private function window(string $academicYear = '2030-2031', string $semester = '1st Semester'): OrgRenewalWindow
    {
        return OrgRenewalWindow::create([
            'academic_year' => $academicYear, 'semester' => $semester, 'is_open' => false,
            'required_docs' => [],
        ]);
    }

    private function renewal(OrgRenewalWindow $window, string $name, string $status = 'approved', ?string $submitted = null, ?string $reviewed = null): OrgRenewalSubmission
    {
        return OrgRenewalSubmission::create([
            'renewal_window_id' => $window->id, 'organization_name' => $name,
            'status' => $status, 'submitted_at' => $submitted, 'reviewed_at' => $reviewed,
        ]);
    }

    private function activity(array $attributes = [], string $created = '2029-03-01 09:00:00'): OrgActivity
    {
        $activity = new OrgActivity(array_merge([
            'title' => 'Reporting Fixture '.Str::uuid(), 'organization_name' => 'Reporting Org '.Str::uuid(),
            'college' => 'CICS', 'workflow_status' => 'oso_review', 'status' => 'draft',
            'activity_scope' => 'in_campus', 'starts_at' => '2030-09-20 09:00:00',
        ], $attributes));
        $activity->created_at = $created;
        $activity->updated_at = '2030-10-14 09:00:00';
        $activity->save();

        return $activity;
    }

    private function packet(OrgActivity $activity, ?string $submitted): InCampusActivitySubmission
    {
        return InCampusActivitySubmission::create([
            'org_activity_id' => $activity->id, 'organization_name' => $activity->organization_name,
            'submitted_at' => $submitted, 'attachments' => [],
        ]);
    }

    private function event(OrgActivity $activity, string $status, ?string $notes, string $created = '2029-03-04 09:00:00'): void
    {
        DB::connection('mysql')->table('activity_workflow_events')->insert([
            'org_activity_id' => $activity->id, 'role' => 'oso', 'from_status' => 'oso_review',
            'to_status' => $status, 'notes' => $notes, 'created_at' => $created,
        ]);
    }

    private function overview(array $activities = []): array
    {
        return (new \ReflectionMethod(OfficePortalController::class, 'buildOsoOverview'))
            ->invoke(new OfficePortalController(), new Collection($activities));
    }

    public function test_recognition_unions_valid_active_rosters_and_approved_windows_once_per_org_and_year(): void
    {
        $this->organization('  Roster Alpha  ');
        $this->organization('Inactive Roster', '2030-2031', false);
        $this->organization('No Year Roster', null);
        $this->organization('Malformed Year Roster', '2030-2032');
        $this->organization('Historic Roster', '2028-2029');
        $first = $this->window();
        $second = $this->window('2030-2031', '2nd Semester');
        $old = $this->window('2029-2030');
        $invalid = $this->window('2028-2030');
        $this->renewal($first, 'roster alpha');
        $this->renewal($second, ' ROSTER ALPHA ');
        $this->renewal($first, 'Renewal Only');
        $this->renewal($second, 'renewal only');
        $this->renewal($old, 'Roster Alpha');
        $this->renewal($first, 'Pending Organization', 'submitted');
        $this->renewal($first, 'Rejected Organization', 'rejected');
        $this->renewal($first, 'Returned Organization', 'returned');
        $this->renewal($invalid, 'Invalid Window Organization');

        $overview = $this->overview();
        $rows = collect($overview['recognitionRows']);
        $this->assertSame(2, $overview['kpis']['totalOrgs']);
        $this->assertEqualsCanonicalizing([
            ['organization_key' => 'roster alpha', 'academic_year' => '2030-2031'],
            ['organization_key' => 'renewal only', 'academic_year' => '2030-2031'],
            ['organization_key' => 'roster alpha', 'academic_year' => '2029-2030'],
            ['organization_key' => 'historic roster', 'academic_year' => '2028-2029'],
        ], $rows->all());
        $this->assertSame(3, $rows->unique('organization_key')->count());
        $this->assertSame(0, $rows->where('academic_year', '2027-2028')->count());
        // In January the selector still explicitly maps calendar 2030 to AY 2030-2031.
        Carbon::setTestNow('2030-01-15 12:00:00');
        $this->assertSame(2, $this->overview()['kpis']['totalOrgs']);
    }

    public function test_missing_recognition_and_timing_are_empty_not_current_master_counts_or_zero_samples(): void
    {
        $this->organization('Active With No Recorded Year', null);
        $overview = $this->overview();
        $this->assertSame([], $overview['recognitionRows']);
        $this->assertSame(0, $overview['kpis']['totalOrgs']);
        $this->assertSame([], $overview['processingRows']);
        $this->assertSame([null, null, null, null, null], $overview['processingTime']);
        $this->assertSame([], $overview['returnRows']);
        $this->assertSame([], $overview['returnCauses']);
        $this->actingAs($this->officer('sdo'), 'office');
        $overview = $this->overview();
        $this->assertSame(['Proposals', 'Renewal', 'TOSA'], $overview['processingLabels']);
        $this->assertSame([null, null, null], $overview['processingTime']);
    }

    public function test_proposal_review_samples_use_latest_packet_and_recorded_decision_not_creation_or_updates(): void
    {
        $approved = $this->activity(['workflow_status' => 'oc_approved', 'approved_at' => '2029-04-03 09:00:00']);
        $this->packet($approved, '2029-03-01 09:00:00');
        $this->packet($approved, '2029-04-01 09:00:00');
        $eventOnly = $this->activity(['workflow_status' => 'completed', 'activity_scope' => 'local_off_campus']);
        $this->packet($eventOnly, '2029-06-01 09:00:00');
        $this->event($eventOnly, 'oc_approved', null, '2029-06-02 21:00:00');
        $activities = [$approved, $eventOnly];
        foreach ([
            ['oso_review', '2029-04-01 09:00:00', '2029-04-03 09:00:00'],
            ['oc_approved', null, '2029-04-03 09:00:00'],
            ['oc_approved', '2029-04-03 09:00:00', '2029-04-01 09:00:00'],
            ['oc_approved', '2031-04-01 09:00:00', '2031-04-03 09:00:00'],
            ['oc_approved', '2029-04-01 09:00:00', null],
            ['oc_approved', '2029-04-01 09:00:00', '2031-04-03 09:00:00'],
        ] as [$status, $submitted, $decided]) {
            $activity = $this->activity(['workflow_status' => $status, 'approved_at' => $decided]);
            $this->packet($activity, $submitted);
            $activities[] = $activity;
        }
        $latestMissing = $this->activity(['workflow_status' => 'oc_approved', 'approved_at' => '2029-04-03 09:00:00']);
        $this->packet($latestMissing, '2029-04-01 09:00:00');
        $this->packet($latestMissing, null);
        $activities[] = $latestMissing;

        $overview = $this->overview($activities);
        $this->assertEquals([
            ['kind' => 'proposal', 'scope' => 'in-campus', 'days' => 2.0, 'year' => 2029, 'sem' => 'sem2', 'mon' => 'Apr'],
            ['kind' => 'proposal', 'scope' => 'off-campus', 'days' => 1.5, 'year' => 2029, 'sem' => 'midyear', 'mon' => 'Jun'],
        ], $overview['processingRows']);
        $this->assertSame([1.8, null, null, null, null], $overview['processingTime']);
        $this->assertSame('Mar', $overview['rows'][0]['mon']);
    }

    public function test_renewal_and_report_samples_require_reviewed_status_and_ordered_nonfuture_explicit_timestamps(): void
    {
        $window = $this->window('2029-2030');
        foreach (['approved', 'returned', 'rejected'] as $status) {
            $this->renewal($window, 'Reviewed '.$status, $status, '2029-08-01 09:00:00', '2029-08-04 09:00:00');
        }
        foreach ([
            ['submitted', '2029-08-01 09:00:00', '2029-08-04 09:00:00'],
            ['approved', null, '2029-08-04 09:00:00'],
            ['approved', '2029-08-01 09:00:00', null],
            ['approved', '2029-08-04 09:00:00', '2029-08-01 09:00:00'],
            ['approved', '2031-08-01 09:00:00', '2031-08-04 09:00:00'],
        ] as $index => [$status, $submitted, $reviewed]) {
            $this->renewal($window, 'Invalid Renewal '.$index, $status, $submitted, $reviewed);
        }
        foreach ([
            ['fr', 'verified', '2029-01-01 09:00:00', '2029-01-03 09:00:00'],
            ['ar', 'archived', '2029-06-01 09:00:00', '2029-06-05 09:00:00'],
            ['fr', 'returned', '2029-01-01 09:00:00', '2029-01-03 09:00:00'],
            ['ar', 'rejected', '2029-06-01 09:00:00', '2029-06-05 09:00:00'],
            ['fr', 'oso_review', '2029-01-01 09:00:00', '2029-01-03 09:00:00'],
            ['fr', 'verified', null, '2029-01-03 09:00:00'],
            ['ar', 'archived', '2029-06-01 09:00:00', null],
            ['fr', 'returned', '2029-01-03 09:00:00', '2029-01-01 09:00:00'],
            ['ar', 'rejected', '2031-06-01 09:00:00', '2031-06-05 09:00:00'],
        ] as $index => [$kind, $status, $submitted, $reviewed]) {
            OrgReportStatus::create([
                'organization_name' => 'Report Fixture '.$index, 'report_type' => $kind,
                'academic_year' => '2029-2030', 'semester' => '1st Semester', 'status' => $status,
                'submitted_at' => $submitted, 'reviewed_at' => $reviewed,
            ]);
        }
        $overview = $this->overview();
        $samples = collect($overview['processingRows']);
        $this->assertCount(7, $samples);
        $this->assertSame(3, $samples->where('kind', 'renewal')->count());
        $this->assertSame(2, $samples->where('kind', 'fr')->count());
        $this->assertSame(2, $samples->where('kind', 'ar')->count());
        $this->assertSame([null, 3.0, 2.0, 4.0, null], $overview['processingTime']);
        $this->assertTrue($samples->every(fn ($row) => $row['year'] === 2029 && $row['scope'] === null));
        $this->assertSame(['Aug'], $samples->where('kind', 'renewal')->pluck('mon')->unique()->values()->all());
        $this->assertCount(0, $samples->where('scope', 'in-campus'));
    }

    public function test_tosa_created_and_updated_dates_do_not_become_decision_timings(): void
    {
        TosaApplicant::create([
            'full_name' => 'Timing Fixture', 'sr_code' => '99-'.substr(str_replace('-', '', Str::uuid()->toString()), 0, 8),
            'academic_year' => '2029-2030', 'status' => 'submitted', 'subsection' => 'accepted',
        ]);
        $overview = $this->overview();
        $this->assertCount(0, collect($overview['processingRows'])->where('kind', 'tosa'));
        $this->assertNull($overview['processingTime'][4]);
    }

    public function test_returns_are_actual_events_with_proposal_cohort_not_duplicate_or_hidden_document_remarks(): void
    {
        $visible = $this->activity();
        $hidden = $this->activity(['workflow_status' => 'created']);
        $this->event($visible, 'returned', 'Correct the signed budget.', '2030-08-01 09:00:00');
        $this->event($visible, 'returned', null, '2030-09-01 09:00:00');
        $this->event($visible, 'sdo_review', 'Not a return');
        $this->event($hidden, 'returned', 'Private draft reason');
        foreach (['budget_proposal', 'faculty_in_charge'] as $key) {
            ActivityComplianceDoc::create([
                'org_activity_id' => $visible->id, 'doc_key' => $key, 'title' => $key,
                'status' => 'returned', 'remarks' => 'Duplicated global signature keyword',
            ]);
        }
        ActivityComplianceDoc::create([
            'org_activity_id' => $hidden->id, 'doc_key' => 'budget_proposal', 'title' => 'Hidden',
            'status' => 'returned', 'remarks' => 'Private draft reason',
        ]);

        $overview = $this->get('/office-desk')->assertOk()->viewData('osoOverview');
        $rows = collect($overview['returnRows'])->where('activity_id', $visible->id)->values();
        $this->assertCount(2, $rows);
        $this->assertSame(['Correct the signed budget.', 'No reason recorded'], $rows->pluck('reason')->all());
        $this->assertSame([2029], $rows->pluck('year')->unique()->values()->all());
        $this->assertSame(['Mar'], $rows->pluck('mon')->unique()->values()->all());
        $this->assertSame(['sem2'], $rows->pluck('sem')->unique()->values()->all());
        $this->assertCount(0, collect($overview['returnRows'])->where('activity_id', $hidden->id));
        $this->assertNotContains('Duplicated global signature keyword', collect($overview['returnRows'])->pluck('reason')->all());
    }

    public function test_pipeline_intersects_college_submission_period_scope_organization_and_scheduled_academic_year(): void
    {
        $club = $this->organization('Computing Club '.Str::uuid(), '2030-2031', true, 'College of Informatics and Computing Sciences');
        $council = $this->organization('Computing Council '.Str::uuid());
        $first = $this->activity(['organization_name' => $club->name, 'college' => null]);
        $second = $this->activity(['organization_name' => $council->name, 'college' => 'cics']);
        $offCampus = $this->activity(['activity_scope' => 'local_off_campus']);
        $otherDepartment = $this->activity(['college' => 'College of Arts and Sciences']);
        $otherMonth = $this->activity([], '2029-04-01 09:00:00');
        $otherSemester = $this->activity([], '2029-08-01 09:00:00');
        $otherSchedule = $this->activity(['starts_at' => '2028-09-01 09:00:00']);
        $ids = collect([$first, $second, $offCampus, $otherDepartment, $otherMonth, $otherSemester, $otherSchedule])->pluck('id')->all();
        $filters = ['college' => 'CICS', 'submission_year' => '2029', 'submission_semester' => 'sem2', 'submission_month' => 'Mar', 'activity_scope' => 'in-campus'];
        $url = '/office-desk/activities?'.http_build_query(array_merge($filters, ['academic_year' => '2030-2031']));
        $response = $this->get($url)->assertOk();
        $actualIds = collect($response->viewData('activities'))->pluck('id')->intersect($ids)->all();
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $actualIds);
        $this->assertSame($filters, $response->viewData('pipelineFilters'));
        $this->assertTrue($response->viewData('pipelineContextActive'));
        $this->assertContains('CICS', $response->viewData('pipelineColleges'));
        $this->assertContains('CAS', $response->viewData('pipelineColleges'));
        $onlyClub = $this->get($url.'&'.http_build_query(['organization' => $club->name]))->assertOk();
        $this->assertSame([$first->id], collect($onlyClub->viewData('activities'))->pluck('id')->all());
        $empty = $this->get($url.'&'.http_build_query(['organization' => 'No Matching Organization']))->assertOk();
        $this->assertSame([], $empty->viewData('activities'));
        $emptyPeriod = $this->get('/office-desk/activities?submission_year=2999')->assertOk();
        $this->assertSame([], $emptyPeriod->viewData('activities'));
        $unfiltered = $this->get('/office-desk/activities')->assertOk();
        $this->assertFalse($unfiltered->viewData('pipelineContextActive'));
        $this->assertSame([
            'college' => '', 'submission_year' => 'all', 'submission_semester' => 'all',
            'submission_month' => 'all', 'activity_scope' => 'all',
        ], $unfiltered->viewData('pipelineFilters'));
    }

    public function test_so_forged_pipeline_params_never_widen_assigned_organization(): void
    {
        $own = $this->organization('Owned Pipeline '.Str::uuid());
        $other = $this->organization('Other Pipeline '.Str::uuid());
        $ownActivity = $this->activity(['organization_name' => $own->name]);
        $otherActivity = $this->activity(['organization_name' => $other->name]);
        $this->activity(['organization_name' => $other->name, 'college' => 'CAS']);
        $this->actingAs($this->officer('so', $own), 'office');
        $response = $this->get('/office-desk/activities?'.http_build_query([
            'organization' => $other->name, 'college' => 'CICS', 'submission_year' => '2029',
            'submission_semester' => 'sem2', 'submission_month' => 'Mar', 'activity_scope' => 'in-campus',
            'activity' => $otherActivity->id,
        ]))->assertOk();
        $this->assertSame([$ownActivity->id], collect($response->viewData('activities'))->pluck('id')->all());
        $this->assertSame($own->name, $response->viewData('selectedOrganization'));
        $this->assertNull($response->viewData('selectedActivity'));
        $this->assertSame(['CICS'], $response->viewData('pipelineColleges'));
        $this->getJson('/office-desk/activities?college=CAS')->assertUnprocessable()->assertJsonValidationErrors('college');
    }

    public function test_invalid_pipeline_periods_and_noncanonical_college_values_are_rejected(): void
    {
        $this->activity();
        foreach ([
            ['submission_year' => '2029-2030'], ['submission_year' => ''], ['submission_year' => ['2029']],
            ['submission_semester' => 'first'], ['submission_month' => 'January'],
            ['activity_scope' => 'local_off_campus'], ['college' => 'Computing Council'],
            ['college' => 'College of Informatics and Computing Sciences'],
        ] as $query) {
            $this->getJson('/office-desk/activities?'.http_build_query($query))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($query));
        }
    }
}

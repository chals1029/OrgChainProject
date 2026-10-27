<?php

namespace Tests\Feature;

use App\Models\OrgActivity;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class LiveOfficeDashboardTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Carbon::setTestNow('2030-10-15 12:00:00');

        // These changes are rolled back and the helper refuses the configured live schemas.
        DB::connection('mysql')->table('org_cash_incomes')->delete();
        DB::connection('mysql')->table('expense_receipt_reviews')->delete();
        OrgActivity::query()->delete();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        parent::tearDown();
    }

    private function activity(string $title, string $stage, string $startsAt = '2030-10-20 09:00:00', array $extra = []): OrgActivity
    {
        return OrgActivity::query()->create(array_merge([
            'title' => $title,
            'organization_name' => 'Synthetic Dashboard Organization',
            'college' => 'CICS',
            'status' => 'draft',
            'workflow_status' => $stage,
            'activity_scope' => 'in_campus',
            'starts_at' => $startsAt,
        ], $extra));
    }

    private function sectionText(string $html, string $label): string
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($document);
            $sections = $xpath->query('//section[@aria-label="'.$label.'"]');
            $this->assertSame(1, $sections->length, 'The dashboard section must be accessible.');

            return trim((string) preg_replace('/\s+/u', ' ', $sections->item(0)->textContent));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function test_sdo_review_overview_and_queue_use_visible_live_workflow_records(): void
    {
        $this->activity('Hidden early proposal', 'oso_review');
        $pending = $this->activity('SDO pending fixture', 'sdo_review');
        $this->activity('OVCAA pending fixture', 'ovcaa_review');
        $this->activity('OC pending fixture', 'oc_review');
        $this->activity('Completed fixture', 'oc_approved');

        $response = $this->actingAs($this->ensureOfficeUser('sdo'), 'office')->get('/office-desk')->assertOk();
        $overview = $response->viewData('reviewOverview');
        $this->assertSame(4, $overview['total']);
        $this->assertSame(1, $overview['pending']);
        $this->assertSame(3, $overview['forwarded']);
        $this->assertSame(1, $overview['finalPending']);
        $this->assertSame(1, $overview['finalApproved']);
        $this->assertEqualsWithDelta(75, $overview['forwardedPercent'], 0.01);
        $this->assertSame([$pending->id], array_column($overview['queue'], 'id'));
        $this->assertStringContainsString('75%', $this->sectionText($response->getContent(), 'SDO review overview'));
        $this->assertStringContainsString($pending->title, $this->sectionText($response->getContent(), 'SDO review queue'));
        $response->assertDontSee('Hidden early proposal');
    }

    public function test_ovcaa_and_oc_queues_follow_the_recorded_workflow_stage(): void
    {
        $this->activity('Hidden before OVCAA', 'sdo_review');
        $ovcaaPending = $this->activity('OVCAA review fixture', 'ovcaa_review');
        $ocPending = $this->activity('OC review fixture', 'oc_review');
        $this->activity('Final approval fixture', 'oc_approved');

        $ovcaa = $this->ensureOfficeUser('ovcaa');
        $response = $this->actingAs($ovcaa, 'office')->get('/office-desk')->assertOk();
        $overview = $response->viewData('reviewOverview');
        $this->assertSame(3, $overview['total']);
        $this->assertSame(1, $overview['pending']);
        $this->assertSame(2, $overview['forwarded']);
        $this->assertEqualsWithDelta(200 / 3, $overview['forwardedPercent'], 0.1);
        $this->assertSame([$ovcaaPending->id], array_column($overview['queue'], 'id'));
        $this->assertStringContainsString($ovcaaPending->title, $this->sectionText($response->getContent(), 'OVCAA review queue'));
        $response->assertDontSee('Hidden before OVCAA');

        $ovcaaPending->update(['workflow_status' => 'oc_review']);
        $response = $this->actingAs($this->ensureOfficeUser('oc'), 'office')->get('/office-desk')->assertOk();
        $overview = $response->viewData('reviewOverview');
        $this->assertSame(3, $overview['total']);
        $this->assertSame(2, $overview['pending']);
        $this->assertSame(1, $overview['finalApproved']);
        $this->assertEqualsCanonicalizing([$ovcaaPending->id, $ocPending->id], array_column($overview['queue'], 'id'));
        $queue = $this->sectionText($response->getContent(), 'OC review queue');
        $this->assertStringContainsString($ovcaaPending->title, $queue);
        $this->assertStringContainsString($ocPending->title, $queue);
    }

    public function test_review_queue_counts_all_pending_packages_but_limits_the_visible_list(): void
    {
        $activities = collect();
        for ($index = 1; $index <= 11; $index++) {
            $activities->push($this->activity('Queue fixture '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'sdo_review'));
        }

        $response = $this->actingAs($this->ensureOfficeUser('sdo'), 'office')->get('/office-desk')->assertOk();
        $overview = $response->viewData('reviewOverview');
        $this->assertSame(11, $overview['queueCount']);
        $this->assertCount(8, $overview['queue']);
        $this->assertSame($activities->take(8)->pluck('id')->all(), array_column($overview['queue'], 'id'));
        $queue = $this->sectionText($response->getContent(), 'SDO review queue');
        $this->assertStringContainsString('Queue fixture 01', $queue);
        $this->assertStringNotContainsString('Queue fixture 09', $queue);
    }

    public function test_sidebar_counts_visible_packages_and_only_future_calendar_entries(): void
    {
        $this->activity('Early hidden package', 'oso_review');
        $this->activity('Future visible package', 'sdo_review');
        $this->activity('Past visible package', 'oc_approved', '2030-10-10 09:00:00');

        $response = $this->actingAs($this->ensureOfficeUser('sdo'), 'office')->get('/office-desk')->assertOk();
        $badges = $response->viewData('navBadges');
        $this->assertSame(2, $badges['activities']);
        $this->assertSame(1, $badges['upcoming']);
    }

    public function test_empty_review_desks_do_not_show_fabricated_percentages_or_actions(): void
    {
        foreach (['sdo', 'ovcaa', 'oc'] as $role) {
            $response = $this->actingAs($this->ensureOfficeUser($role), 'office')->get('/office-desk')->assertOk();
            $overview = $response->viewData('reviewOverview');
            $this->assertSame(0, $overview['total']);
            $this->assertSame(0, $overview['queueCount']);
            $this->assertNull($overview['forwardedPercent']);
            $this->assertSame([], $overview['queue']);
            $this->assertStringContainsString('No data', $this->sectionText($response->getContent(), strtoupper($role).' review overview'));
        }
    }

    public function test_analytics_uses_recorded_zero_amounts_and_unknown_document_compliance(): void
    {
        $officer = $this->ensureOfficeUser('oso');
        $response = $this->actingAs($officer, 'office')->get('/office-desk/analytics')->assertOk();
        $overview = $response->viewData('overview');
        $this->assertSame(0, $overview['totalAllocated']);
        $this->assertSame(0, $overview['totalUtilized']);
        $this->assertSame(0, $overview['remainingBalance']);
        $this->assertNull($overview['complianceRate']);
        $this->assertTrue($response->viewData('collegeStats')->isEmpty());
        $response->assertSee('No Data');

        $csv = $this->get('/office-desk/analytics/export')->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", $csv)));
        $this->assertContains(['Compliance rate', 'N/A'], $rows);

        $activity = $this->activity('Recorded unspent budget', 'oc_approved', extra: [
            'approved_budget' => 2000,
            'implemented_budget' => 0,
        ]);
        $overview = $this->get('/office-desk/analytics')->assertOk()->viewData('overview');
        $this->assertSame(2000, $overview['totalAllocated']);
        $this->assertSame(0, $overview['totalUtilized']);
        $this->assertSame(2000, $overview['remainingBalance']);
        $this->assertEquals(0, $overview['burnRate']);
        $this->assertNull($overview['complianceRate']);

        DB::connection('mysql')->table('activity_compliance_docs')->insert([
            ['org_activity_id' => $activity->id, 'doc_key' => 'one', 'title' => 'First', 'status' => 'approved'],
            ['org_activity_id' => $activity->id, 'doc_key' => 'two', 'title' => 'Second', 'status' => 'pending'],
        ]);
        $overview = $this->get('/office-desk/analytics')->assertOk()->viewData('overview');
        $this->assertEquals(50, $overview['complianceRate']);
    }

    public function test_upcoming_activities_exclude_past_dates_and_are_ordered_soonest_first(): void
    {
        $this->activity('Past fixture', 'sdo_review', '2030-10-10 09:00:00');
        $later = $this->activity('Later fixture', 'sdo_review', '2030-11-20 09:00:00');
        $sooner = $this->activity('Sooner fixture', 'sdo_review', '2030-10-20 09:00:00');

        $response = $this->actingAs($this->ensureOfficeUser('sdo'), 'office')->get('/office-desk')->assertOk();
        $this->assertSame([$sooner->id, $later->id], collect($response->viewData('upcomingList'))->pluck('id')->all());
    }

    public function test_past_only_schedule_has_no_upcoming_activities(): void
    {
        $this->activity('Past-only event', 'sdo_review', '2030-10-10 09:00:00');

        $response = $this->actingAs($this->ensureOfficeUser('sdo'), 'office')->get('/office-desk')->assertOk();
        $this->assertCount(0, $response->viewData('upcomingList'));
        $this->assertNull($response->viewData('upcoming'));
    }

    public function test_student_organization_upcoming_list_stays_scoped_to_its_own_future_activities(): void
    {
        $officer = $this->ensureOfficeUser('so');
        $organization = $officer->organizationName();
        $this->activity('Own past event', 'created', '2030-10-10 09:00:00', ['organization_name' => $organization]);
        $own = $this->activity('Own upcoming event', 'created', '2030-10-20 09:00:00', ['organization_name' => $organization]);
        $this->activity('Other organization event', 'created', '2030-10-19 09:00:00', ['organization_name' => 'Other Synthetic Organization']);

        $response = $this->actingAs($officer, 'office')->get('/office-desk')->assertOk();
        $this->assertSame([$own->id], collect($response->viewData('upcomingList'))->pluck('id')->all());
        $response->assertDontSee('Other organization event');
    }
}

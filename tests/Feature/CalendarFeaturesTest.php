<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\StudentOrganization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class CalendarFeaturesTest extends TestCase
{
    use UsesLaragonDatabase;

    private StudentOrganization $organization;
    private OfficeUser $so;
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        $this->originalTimezone = date_default_timezone_get();
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
        Carbon::setTestNow('2026-10-15 12:00:00');
        $this->organization = StudentOrganization::create([
            'name' => 'Calendar Organization '.Str::uuid(), 'short_name' => 'CAL',
            'college' => 'Calendar Test College', 'academic_year' => '2026-2027', 'is_active' => true,
        ]);
        $this->so = $this->office('so');
    }

    protected function tearDown(): void
    {
        foreach (['orgchain', 'mysql'] as $connection) {
            while (DB::connection($connection)->transactionLevel() > 0) {
                DB::connection($connection)->rollBack();
            }
        }
        Carbon::setTestNow();
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    private function office(string $role): OfficeUser
    {
        return OfficeUser::create([
            'name' => 'Calendar '.$role, 'email' => Str::uuid().'@example.test',
            'username' => 'cal-'.Str::random(15), 'password' => Str::random(30),
            'office_role' => $role, 'office_title' => strtoupper($role).' test desk', 'is_active' => true,
            'student_organization_id' => $role === 'so' ? $this->organization->id : null,
        ]);
    }

    private function activity(array $overrides = []): OrgActivity
    {
        return OrgActivity::create(array_merge([
            'title' => 'Calendar Event '.Str::uuid(), 'description' => 'Owned calendar event',
            'organization_name' => $this->organization->name, 'college' => $this->organization->college,
            'starts_at' => '2026-10-20 09:00:00', 'ends_at' => '2026-10-20 17:00:00',
            'workflow_status' => 'oc_approved', 'status' => 'upcoming',
            'activity_scope' => 'in_campus', 'location' => 'Campus Hall',
            'approved_budget' => 100, 'implemented_budget' => 0,
        ], $overrides));
    }

    private function calendar(array $query = [], ?OfficeUser $office = null)
    {
        return $this->actingAs($office ?? $this->so, 'office')
            ->get('/office-desk/calendar'.($query ? '?'.http_build_query($query) : ''))->assertOk();
    }

    private function idsForDate($response, string $date): array
    {
        return collect($response->viewData('eventsByDate')[$date] ?? [])->pluck('id')->all();
    }

    public function test_time_boundaries_and_approval_state_are_independent(): void
    {
        $pending = $this->activity(['workflow_status' => 'oso_review', 'status' => 'upcoming']);
        $ongoing = $this->activity(['starts_at' => '2026-10-15 11:00:00', 'ends_at' => '2026-10-15 13:00:00']);
        $startBoundary = $this->activity(['starts_at' => '2026-10-15 12:00:00', 'ends_at' => '2026-10-15 13:00:00']);
        $endBoundary = $this->activity(['starts_at' => '2026-10-15 10:00:00', 'ends_at' => '2026-10-15 12:00:00']);
        $completed = $this->activity(['status' => 'completed']);
        $returned = $this->activity(['workflow_status' => 'returned']);
        $point = $this->activity(['starts_at' => '2026-10-15 12:00:00', 'ends_at' => null]);
        $staleDisplay = $this->activity(['workflow_status' => 'oso_review', 'status' => 'completed']);
        $response = $this->calendar();
        $events = $response->viewData('events')->keyBy('id');

        $this->assertSame('pending', $events[$pending->id]['status_key']);
        $this->assertSame('upcoming', $events[$pending->id]['timing_key']);
        $this->assertSame('approved', $events[$ongoing->id]['status_key']);
        $this->assertSame('ongoing', $events[$ongoing->id]['timing_key']);
        $this->assertSame('ongoing', $events[$startBoundary->id]['timing_key']);
        $this->assertSame('past', $events[$endBoundary->id]['timing_key']);
        $this->assertSame('completed', $events[$completed->id]['status_key']);
        $this->assertSame('past', $events[$completed->id]['timing_key']);
        $this->assertSame('returned', $events[$returned->id]['status_key']);
        $this->assertSame('past', $events[$point->id]['timing_key']);
        $this->assertSame('pending', $events[$staleDisplay->id]['status_key']);
        $this->assertSame([$ongoing->id, $startBoundary->id], $response->viewData('ongoingEvents')->pluck('id')->all());
        $this->assertSame([$pending->id, $returned->id, $staleDisplay->id], $response->viewData('upcomingEvents')->pluck('id')->all());
    }

    public function test_spanning_events_appear_each_day_and_midnight_end_is_exclusive(): void
    {
        $span = $this->activity(['starts_at' => '2026-10-30 09:00:00', 'ends_at' => '2026-11-02 17:00:00']);
        $midnight = $this->activity(['starts_at' => '2026-10-31 20:00:00', 'ends_at' => '2026-11-01 00:00:00']);
        $point = $this->activity(['starts_at' => '2026-10-31 08:00:00', 'ends_at' => null]);
        $october = $this->calendar(['month' => '2026-10']);
        $this->assertContains($span->id, $this->idsForDate($october, '2026-10-30'));
        $this->assertContains($span->id, $this->idsForDate($october, '2026-10-31'));
        $this->assertContains($span->id, $this->idsForDate($october, '2026-11-01'));
        $this->assertContains($midnight->id, $this->idsForDate($october, '2026-10-31'));
        $this->assertNotContains($midnight->id, $this->idsForDate($october, '2026-11-01'));
        $this->assertContains($point->id, $this->idsForDate($october, '2026-10-31'));
        $this->assertNotContains($point->id, $this->idsForDate($october, '2026-11-01'));
        $november = $this->calendar(['month' => '2026-11']);
        $this->assertContains($span->id, $this->idsForDate($november, '2026-11-01'));
        $this->assertContains($span->id, $this->idsForDate($november, '2026-11-02'));
        $this->assertNotContains($span->id, $this->idsForDate($november, '2026-11-03'));
        $this->assertSame(['2026-11-01', '2026-11-02'], $november->viewData('agendaDays')->pluck('date')->map->toDateString()->all());
    }

    public function test_year_crossing_long_spans_are_clipped_to_the_requested_grid(): void
    {
        $span = $this->activity(['starts_at' => '2026-12-31 20:00:00', 'ends_at' => '2027-01-02 06:00:00']);
        $long = $this->activity(['starts_at' => '2025-01-01 09:00:00', 'ends_at' => '2028-01-01 00:00:00']);
        $response = $this->calendar(['month' => '2027-01']);
        $days = $response->viewData('days');
        $this->assertSame('2026-12-28', $days[0]['date']->toDateString());
        $this->assertSame('2027-01-31', $days[count($days) - 1]['date']->toDateString());
        $this->assertSame(35, count($days));
        foreach ($days as $day) {
            $this->assertContains($long->id, $this->idsForDate($response, $day['date']->toDateString()));
        }
        $this->assertContains($span->id, $this->idsForDate($response, '2027-01-01'));
        $this->assertContains($span->id, $this->idsForDate($response, '2027-01-02'));
        $this->assertNotContains($span->id, $this->idsForDate($response, '2027-01-03'));
        $this->assertSame(count($days), count($response->viewData('eventsByDate')));
    }

    public function test_calendar_keeps_duplicate_title_identities_and_later_off_campus_events(): void
    {
        $sameA = $this->activity(['title' => 'Same-title session', 'location' => 'Alpha Room']);
        $sameB = $this->activity(['title' => 'Same-title session', 'location' => 'Beta Room']);
        $ids = [$sameA->id, $sameB->id];
        for ($n = 0; $n < 13; $n++) {
            $ids[] = $this->activity(['starts_at' => '2026-10-21 09:00:00', 'ends_at' => '2026-10-21 17:00:00'])->id;
        }
        $later = $this->activity(['starts_at' => '2026-11-10 09:00:00', 'ends_at' => '2026-11-10 17:00:00', 'activity_scope' => 'local_off_campus']);
        $ids[] = $later->id;
        $response = $this->calendar();
        $this->assertSame($ids, $response->viewData('events')->pluck('id')->all());
        $this->assertSame($ids, $response->viewData('upcomingEvents')->pluck('id')->all());
        $offCampus = $this->calendar(['scope' => 'off']);
        $this->assertSame([$later->id], $offCampus->viewData('upcomingEvents')->pluck('id')->all());
        foreach ([$sameA, $sameB] as $activity) {
            $detailUrl = $response->viewData('events')->firstWhere('id', $activity->id)['detail_url'];
            $details = $this->actingAs($this->so, 'office')->get($detailUrl)->assertOk()->viewData('selectedActivity');
            $this->assertSame($activity->id, $details['id']);
            $this->assertSame($activity->location, $details['location']);
        }
    }

    public function test_empty_or_unscheduled_organization_has_no_demo_events_and_does_not_jump_month(): void
    {
        $this->activity(['starts_at' => null, 'ends_at' => null, 'workflow_status' => 'created', 'status' => 'draft']);
        $empty = $this->calendar();
        $this->assertSame([], $empty->viewData('events')->all());
        $this->assertSame([], $empty->viewData('upcomingEvents')->all());
        $this->assertSame('2026-10', $empty->viewData('monthKey'));
        $this->activity(['starts_at' => '2027-03-20 09:00:00', 'ends_at' => '2027-03-20 17:00:00']);
        $current = $this->calendar();
        $this->assertSame('2026-10', $current->viewData('monthKey'));
        $this->assertSame([], $current->viewData('agendaDays')->all());
        $this->assertSame('2026-10', $this->calendar(['month' => '2026-13'])->viewData('monthKey'));
    }

    public function test_assigned_organization_and_office_stage_visibility_are_enforced(): void
    {
        $foreign = $this->activity(['organization_name' => 'Other Calendar Organization '.Str::uuid()]);
        $fixtures = [];
        foreach (['college_review', 'oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'oc_approved', 'completed', 'returned'] as $stage) {
            $fixtures[$stage] = $this->activity(['workflow_status' => $stage])->id;
        }
        $own = $this->calendar()->viewData('events')->pluck('id')->all();
        $this->assertSame(array_values($fixtures), $own);
        $this->assertNotContains($foreign->id, $own);
        foreach ([
            'oso' => ['oso_review', 'sdo_review', 'ovcaa_review', 'oc_review', 'oc_approved', 'completed'],
            'sdo' => ['sdo_review', 'ovcaa_review', 'oc_review', 'oc_approved', 'completed'],
            'ovcaa' => ['ovcaa_review', 'oc_review', 'oc_approved', 'completed'],
            'oc' => ['oc_review', 'oc_approved', 'completed'],
        ] as $role => $stages) {
            $visible = $this->calendar([], $this->office($role))->viewData('events')->pluck('id')->all();
            $fixtureVisible = array_values(array_intersect($visible, array_values($fixtures)));
            $this->assertSame(array_map(fn ($stage) => $fixtures[$stage], $stages), $fixtureVisible, $role);
        }
    }

    public function test_literal_title_and_venue_search_compose_with_status_scope_and_timing_filters(): void
    {
        $match = $this->activity(['title' => 'Travel safety briefing', 'location' => 'City Hall 100%_West', 'workflow_status' => 'oso_review', 'activity_scope' => 'local_off_campus']);
        $this->activity(['title' => 'Travel safety briefing', 'location' => 'City Hall 100%_West']);
        $this->activity(['location' => 'City Hall 100%_West', 'workflow_status' => 'oso_review']);
        $this->activity(['location' => 'City Hall 100%_West', 'workflow_status' => 'oso_review', 'activity_scope' => 'local_off_campus', 'starts_at' => '2026-10-01 09:00:00', 'ends_at' => '2026-10-01 17:00:00']);
        $query = ['q' => 'city hall 100%_', 'scope' => 'off', 'status' => 'pending', 'timing' => 'upcoming', 'view' => 'agenda'];
        $response = $this->calendar($query);
        $this->assertSame([$match->id], $response->viewData('events')->pluck('id')->all());
        $this->assertSame([$match->id], $response->viewData('upcomingEvents')->pluck('id')->all());
        $this->assertSame([$match->id], $this->idsForDate($response, '2026-10-20'));
        $this->assertSame([], $response->viewData('ongoingEvents')->all());
        $this->assertSame([], $this->calendar(['q' => '100X_West'])->viewData('events')->all());
        $title = $this->calendar(['q' => 'TRAVEL SAFETY'])->viewData('events');
        $this->assertSame(['Travel safety briefing', 'Travel safety briefing'], $title->pluck('title')->all());
    }

    public function test_local_timezone_ranges_are_complete_and_navigation_never_reschedules(): void
    {
        config(['app.timezone' => 'Asia/Manila']);
        date_default_timezone_set('Asia/Manila');
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Asia/Manila'));
        $activity = $this->activity(['starts_at' => '2026-10-15 11:00:00', 'ends_at' => '2026-10-16 17:00:00']);
        $before = $activity->fresh()->getRawOriginal();
        $response = $this->calendar(['month' => '2026-10', 'view' => 'agenda']);
        $event = $response->viewData('events')->firstWhere('id', $activity->id);
        $this->assertSame('2026-10-15T11:00:00+08:00', $event['starts_at']);
        $this->assertSame('2026-10-16T17:00:00+08:00', $event['ends_at']);
        $this->assertSame('ongoing', $event['timing_key']);
        $this->assertSame('2026-10-15', $response->viewData('todayKey'));
        $this->assertContains($activity->id, $this->idsForDate($response, '2026-10-16'));
        $this->calendar(['month' => '2026-11', 'status' => 'approved', 'view' => 'month']);
        $this->calendar(['month' => '2026-09', 'scope' => 'in', 'view' => 'agenda']);
        $this->assertSame($before, $activity->refresh()->getRawOriginal());
        $this->assertSame(1, OrgActivity::where('organization_name', $this->organization->name)->count());
    }
}

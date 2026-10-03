<?php

namespace Tests\Feature;

use App\Http\Controllers\OfficePortalController;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class CalendarChartTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        Carbon::setTestNow('2026-10-31 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_server_trend_uses_current_calendar_year_without_month_end_overflow(): void
    {
        $activities = collect(['2025-12-31', '2026-01-01', '2026-02-28', '2026-12-31', '2027-01-01'])
            ->map(fn ($date) => (new OrgActivity)->forceFill(['created_at' => $date, 'workflow_status' => 'created']));
        $payload = (new ReflectionMethod(OfficePortalController::class, 'dashboardChartPayload'))->invoke(app(OfficePortalController::class), $activities);
        $this->assertSame(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], $payload['trendLabels']->all());
        $this->assertSame([1,1,0,0,0,0,0,0,0,0,0,1], $payload['trendCounts']->all());
        $this->assertSame(2026, $payload['trendYear']);
    }

    public function test_office_chart_pages_render_calendar_year_filters_for_all_roles(): void
    {
        foreach (['so','oso','sdo','ovcaa'] as $role) {
            $office = OfficeUser::where('office_role', $role)->firstOrFail();
            $this->actingAs($office, 'office')->get('/office-desk/analytics')->assertOk()
                ->assertSee('2026 · Jan–Dec (Current)', false)->assertSee('All Calendar Years');
            $dashboard = $this->get('/office-desk')->assertOk();
            $this->assertSame('Jan', $dashboard->viewData('chartPayload')['trendLabels']->first());
            $this->assertSame('Dec', $dashboard->viewData('chartPayload')['trendLabels']->last());
            if ($role === 'oso') $dashboard->assertSee('2026 · Jan–Dec (Current)', false)->assertSee('All Calendar Years');
        }
    }

    public function test_calendar_view_renders_cleanly_with_scrollable_upcoming_list(): void
    {
        $oso = OfficeUser::where('office_role', 'oso')->firstOrFail();

        $response = $this->actingAs($oso, 'office')
            ->get('/office-desk/calendar')
            ->assertOk();

        $response->assertSee('Upcoming on Calendar');
        $response->assertSee('org-upcoming-side-list', false);
        $response->assertSee('org-cal-grid-wrapper', false);
        $response->assertSee('org-cal-grid-table', false);
    }
}

<?php

namespace Tests\Feature;

use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReportPrintingTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_budget_print_is_a_dedicated_server_report_for_so_and_oso(): void
    {
        foreach (['so', 'oso'] as $role) {
            $this->actingAs($this->ensureOfficeUser($role), 'office')
                ->get('/office-desk/budget-utilization/print?organization=All')
                ->assertOk()
                ->assertSee('official server-rendered report', false)
                ->assertSee('Budget Utilization Report', false)
                ->assertSee('Activity Utilization Register', false)
                ->assertDontSee('org-sidebar', false)
                ->assertDontSee('Budget Utilization &amp; Financial Intelligence', false);
        }
    }

}

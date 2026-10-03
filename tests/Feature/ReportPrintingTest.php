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

    public function test_accomplishment_print_is_a_dedicated_server_report_for_so_and_oso(): void
    {
        foreach (['so', 'oso'] as $role) {
            $this->actingAs($this->ensureOfficeUser($role), 'office')
                ->get('/office-desk/accomplishment-report/print?semester=1st%20Semester&academic_year=2025-2026')
                ->assertOk()
                ->assertSee('official server-rendered report', false)
                ->assertSee('Semester Accomplishment Report', false)
                ->assertSee('Activity Accomplishment Register', false)
                ->assertDontSee('org-sidebar', false)
                ->assertDontSee('Report Dossier', false);
        }
    }
}

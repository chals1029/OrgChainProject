<?php

namespace Tests\Feature;

use App\Models\ActivityRegistration;
use App\Models\ActivityComplianceDoc;
use App\Models\ExpenseReceiptReview;
use App\Models\OrgActivity;
use App\Models\OrgReportStatus;
use App\Models\OrgRenewalSubmission;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use App\Models\OfficeUser;
use App\Services\BudgetChainService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class DashboardReportingTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('oso');
    }

    public function test_oso_submission_trend_report_keeps_zero_months_and_explains_drops(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk')
            ->assertOk()
            ->assertSee('Month-by-month filings — zero months stay visible so declines are clear', false)
            ->assertSee('function buildTrendAxis', false)
            ->assertSee('Largest month-to-month drop', false)
            ->assertSee('No submissions recorded in the selected period.', false);
    }

    public function test_so_sidebar_brand_uses_the_assigned_organizations_short_name(): void
    {
        $suffix = Str::uuid()->toString();
        $shortName = 'CICS-'.substr(str_replace('-', '', $suffix), 0, 8);
        $organization = StudentOrganization::query()->create([
            'name' => 'Brand Test Organization '.$suffix,
            'short_name' => $shortName,
            'college' => 'College of Informatics and Computing Sciences',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $office = null;

        try {
            $office = OfficeUser::query()->create([
                'name' => 'Brand Test SO',
                'email' => 'so-brand-'.$suffix.'@example.test',
                'username' => 'so-brand-'.$suffix,
                'password' => Str::random(40),
                'office_role' => 'so',
                'student_organization_id' => $organization->id,
                'office_title' => 'Student Organization',
                'is_active' => true,
            ]);

            $this->actingAs($office, 'office')
                ->get('/office-desk')
                ->assertOk()
                ->assertSee('<strong>'.$shortName.'</strong>', false)
                ->assertSee('Student Org Representative');
        } finally {
            $office?->delete();
            $organization->delete();
        }
    }


    public function test_oso_dashboard_summary_counts_live_transaction_tables(): void
    {
        $response = $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk')
            ->assertOk();
        $overview = $response->viewData('osoOverview');
        $visibleProposalCount = OrgActivity::query()
            ->get()
            ->filter(fn (OrgActivity $activity): bool => app(\App\Services\OrgWorkflowService::class)->canViewActivityDetails(
                'oso',
                $activity->workflow_status ?: 'created',
            ))
            ->count();

        $this->assertSame([
            $visibleProposalCount,
            OrgRenewalSubmission::query()->count(),
            OrgReportStatus::query()->where('report_type', 'fr')->count(),
            OrgReportStatus::query()->where('report_type', 'ar')->count(),
            TosaApplicant::query()->count(),
        ], $overview['typeBreakdown']);
        $this->assertSame(StudentOrganization::active()->count(), $overview['kpis']['totalOrgs']);
        $this->assertArrayHasKey('typeRows', $overview);
        $this->assertCount($visibleProposalCount, collect($overview['typeRows'])->where('kind', 'proposal'));
    }

    public function test_office_flash_confirmation_is_rendered_once_across_shared_layout_pages(): void
    {
        foreach (['/office-desk/activities', '/office-desk/activities/create', '/office-desk/budget-utilization', '/office-desk/renewal'] as $uri) {
            $response = $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->withSession(['success' => 'Test confirmation'])
                ->get($uri);

            $response->assertOk();
            $this->assertSame(1, substr_count($response->getContent(), 'role="alert"'), $uri.' rendered a duplicate confirmation.');
        }
    }



    public function test_activity_queue_uses_academic_year_filter_and_view_details_actions(): void
    {
        $suffix = uniqid('', true);
        $current = OrgActivity::query()->create([
            'title' => 'Current Year Queue '.$suffix,
            'status' => 'draft',
            'workflow_status' => 'oso_review',
            'organization_name' => 'Current Year Organization '.$suffix,
            'location' => 'Campus',
            'starts_at' => '2026-09-20 09:00:00',
        ]);
        $previous = OrgActivity::query()->create([
            'title' => 'Previous Year Queue '.$suffix,
            'status' => 'draft',
            'workflow_status' => 'oso_review',
            'organization_name' => 'Previous Year Organization '.$suffix,
            'location' => 'Campus',
            'starts_at' => '2025-09-20 09:00:00',
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/activities?academic_year=2026-2027')
                ->assertOk()
                ->assertSee('name="academic_year"', false)
                ->assertSee('value="2026-2027" selected', false)
                ->assertSee('View Details', false)
                ->assertSee($current->title, false)
                ->assertDontSee($previous->title, false);
        } finally {
            OrgActivity::query()->whereIn('id', [$current->id, $previous->id])->delete();
        }
    }

    public function test_so_dashboard_does_not_show_pending_documents_from_other_organizations(): void
    {
        $suffix = uniqid('', true);
        $otherActivity = OrgActivity::query()->create([
            'title' => 'Other Organization Action '.$suffix,
            'status' => 'draft',
            'workflow_status' => 'oso_review',
            'organization_name' => 'Other Organization '.$suffix,
            'location' => 'Campus',
            'starts_at' => '2026-09-20 09:00:00',
        ]);
        $otherDocument = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $otherActivity->id,
            'doc_key' => 'budget_proposal',
            'title' => 'Unique Budget Proof '.$suffix,
            'status' => 'pending',
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->get('/office-desk')
                ->assertOk()
                ->assertDontSee('Unique Budget Proof '.$suffix, false);
        } finally {
            $otherDocument->delete();
            $otherActivity->delete();
        }
    }

    public function test_sdo_and_ovcaa_do_not_receive_semester_report_navigation_or_dashboard_metrics(): void
    {
        foreach (['sdo', 'ovcaa'] as $role) {
            $response = $this->actingAs($this->ensureOfficeUser($role), 'office')
                ->get('/office-desk')
                ->assertOk();

            $response
                ->assertDontSee('Financial Report', false)
                ->assertDontSee('Accomplishment Report', false)
                ->assertDontSee('Financial (FR)', false)
                ->assertDontSee('Accomplishment (AR)', false);
        }
    }

    public function test_student_department_and_status_filters_are_bound_to_the_portal_query(): void
    {
        $student = $this->ensureActiveStudent('21-00001');

        $this->actingAs($student, 'student')
            ->get('/portal?college=College%20of%20Informatics%20and%20Computing%20Sciences&status=ongoing')
            ->assertOk()
            ->assertSee('name="college"', false)
            ->assertSee('name="status"', false)
            ->assertSee('value="College of Informatics and Computing Sciences" selected', false)
            ->assertSee('value="ongoing" selected', false);
    }

    public function test_students_only_see_fully_approved_activities_and_cannot_rsvp_to_pending_ones(): void
    {
        $student = $this->ensureActiveStudent('21-00001');
        $suffix = uniqid('', true);
        $pending = OrgActivity::query()->create([
            'title' => 'Pending Visibility Check '.$suffix,
            'description' => 'Should remain private during review.',
            'status' => 'upcoming',
            'workflow_status' => 'oso_review',
            'college' => 'CICS',
            'organization_name' => 'CICS Test Organization',
            'location' => 'Test Venue',
            'starts_at' => now()->addDays(5),
        ]);
        $approved = OrgActivity::query()->create([
            'title' => 'Approved Visibility Check '.$suffix,
            'description' => 'Should be visible to students.',
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'college' => 'CICS',
            'organization_name' => 'CICS Test Organization',
            'location' => 'Test Venue',
            'starts_at' => now()->addDays(6),
        ]);

        try {
            $this->actingAs($student, 'student')
                ->get('/portal?college=CICS')
                ->assertOk()
                ->assertSee($approved->title, false)
                ->assertDontSee($pending->title, false);

            $this->actingAs($student, 'student')
                ->postJson('/portal/activities/'.$pending->id.'/rsvp')
                ->assertStatus(422)
                ->assertJsonPath('ok', false);

            $this->actingAs($student, 'student')
                ->postJson('/portal/activities/'.$approved->id.'/rsvp')
                ->assertOk()
                ->assertJsonPath('registered', true);
        } finally {
            ActivityRegistration::query()->whereIn('org_activity_id', [$pending->id, $approved->id])->delete();
            OrgActivity::query()->whereIn('id', [$pending->id, $approved->id])->delete();
        }
    }

    public function test_public_activity_budget_shows_only_approved_summary_fields(): void
    {
        $student = $this->ensureActiveStudent('21-00001');
        $suffix = uniqid('', true);
        $activity = OrgActivity::query()->create([
            'title' => 'Budget Publication Check '.$suffix,
            'description' => 'Approved activity for public ledger testing.',
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'college' => 'CICS',
            'organization_name' => 'CICS Test Organization',
            'location' => 'Test Venue',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(7),
        ]);
        $pending = ExpenseReceiptReview::query()->create([
            'org_activity_id' => $activity->id,
            'activity_title' => $activity->title,
            'item_name' => 'Pending Receipt Item '.$suffix,
            'quantity' => 2,
            'unit_cost' => 125,
            'expense_date' => now()->toDateString(),
            'receipt_path' => 'expense-receipts/pending-'.$suffix.'.jpg',
            'receipt_name' => 'pending-receipt.jpg',
            'receipt_reference' => 'PENDING-'.$suffix,
            'chain_hash' => 'pending-hash-'.$suffix,
            'verification_status' => 'ready_for_review',
        ]);
        $verified = ExpenseReceiptReview::query()->create([
            'org_activity_id' => $activity->id,
            'activity_title' => $activity->title,
            'item_name' => 'Verified Public Item '.$suffix,
            'quantity' => 3,
            'unit_cost' => 250,
            'expense_date' => now()->toDateString(),
            'receipt_path' => 'expense-receipts/verified-'.$suffix.'.jpg',
            'receipt_name' => 'verified-receipt.jpg',
            'receipt_reference' => 'VERIFIED-'.$suffix,
            'chain_hash' => 'verified-hash-'.$suffix,
            'verification_status' => 'verified',
        ]);

        try {
            $this->actingAs($student, 'student')
                ->get('/portal?college=CICS')
                ->assertOk()
                ->assertSee($verified->item_name, false)
                ->assertSee('Verified Public Item', false)
                ->assertDontSee('On-Chain Budget Seals', false)
                ->assertDontSee($pending->item_name, false)
                ->assertDontSee($pending->receipt_name, false);
        } finally {
            ExpenseReceiptReview::query()->whereIn('id', [$pending->id, $verified->id])->delete();
            OrgActivity::query()->whereKey($activity->id)->delete();
        }
    }

    public function test_student_can_verify_a_public_expense_seal_without_receipt_file_access(): void
    {
        $student = $this->ensureActiveStudent('21-00001');
        $suffix = uniqid('', true);
        $activity = OrgActivity::query()->create([
            'title' => 'Public Seal Verification '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'college' => 'CICS',
            'organization_name' => 'CICS Seal Test Organization',
            'starts_at' => now()->addDays(7),
        ]);
        $receiptPath = 'expense-receipts/public-seal-'.$suffix.'.jpg';
        $receiptContents = 'private receipt fixture '.$suffix;
        Storage::fake('local');
        Storage::disk('local')->put($receiptPath, $receiptContents);
        $receipt = ExpenseReceiptReview::query()->create([
            'org_activity_id' => $activity->id,
            'activity_title' => $activity->title,
            'item_name' => 'Verified public item',
            'quantity' => 1,
            'unit_cost' => 250,
            'expense_date' => now()->toDateString(),
            'receipt_path' => $receiptPath,
            'receipt_disk' => 'local',
            'receipt_name' => 'private-receipt.jpg',
            'file_hash' => hash('sha256', $receiptContents),
            'chain_hash' => 'public-seal-'.$suffix,
            'chain_driver' => 'file',
            'nodes_confirmed' => 3,
            'verification_status' => 'verified',
        ]);
        $this->mock(BudgetChainService::class, function ($mock): void {
            $mock->shouldReceive('verifyHash')->once()->andReturn([
                'ok' => true,
                'chain_driver' => 'file',
                'nodes_confirmed' => 3,
                'sealed_at' => '2026-09-22T10:00:00+08:00',
                'message' => 'Budget seal verified across all 3 nodes.',
            ]);
        });

        try {
            $this->actingAs($student, 'student')
                ->getJson('/portal/expenses/'.$receipt->id.'/verify')
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('status', 'Verified')
                ->assertJsonPath('chain.nodes_confirmed', 3)
                ->assertJsonPath('chain.node_total', 3)
                ->assertJsonPath('receipt_file.matches', true)
                ->assertJsonMissingPath('receipt_file.path');
        } finally {
            ExpenseReceiptReview::query()->whereKey($receipt->id)->delete();
            OrgActivity::query()->whereKey($activity->id)->delete();
        }
    }

    public function test_activity_endorsement_controls_follow_the_current_workflow_owner(): void
    {
        $activeForOso = OrgActivity::query()->where('workflow_status', 'oso_review')->firstOrFail();
        $alreadyEndorsed = OrgActivity::query()->where('workflow_status', 'sdo_review')->firstOrFail();
        $oso = $this->ensureOfficeUser('oso');

        $this->actingAs($oso, 'office')
            ->get('/office-desk/activities?activity='.$activeForOso->id)
            ->assertOk()
            ->assertSee('data-advance-form', false)
            ->assertSee('Endorse / Advance', false);

        $this->actingAs($oso, 'office')
            ->get('/office-desk/activities?activity='.$alreadyEndorsed->id)
            ->assertOk()
            ->assertSee('Already endorsed', false)
            ->assertDontSee('data-advance-form', false);

        $this->actingAs($oso, 'office')
            ->post('/office-desk/activities/'.$alreadyEndorsed->id.'/advance')
            ->assertRedirect()
            ->assertSessionHasErrors('workflow');

        $this->assertSame('sdo_review', $alreadyEndorsed->refresh()->workflow_status);
    }

    public function test_calendar_carries_real_off_campus_scope_into_the_filterable_event_payload(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk/calendar?month=2026-09')
            ->assertOk()
            ->assertSee('data-scope="off"', false)
            ->assertSee('data-event-scope="off"', false)
            ->assertSee('Leadership Summit 2026', false)
            ->assertSee('"scope_key":"off"', false)
            ->assertSee('function applyScopeFilter()', false);
    }

}


<?php

namespace Tests\Feature;

use App\Models\OrgActivity;
use Illuminate\Http\UploadedFile;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class BudgetUtilizationTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('oso');
        $this->ensureOfficeUser('so');
    }

    public function test_budget_page_opens_on_the_consolidated_portfolio(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk/budget-utilization')
            ->assertOk()
            ->assertSee('value="all" selected', false)
            ->assertSee('All Colleges / Units', false)
            ->assertSee('const liveBudgetDefault = "all";', false);
    }

    public function test_organization_filter_changes_the_live_budget_portfolio(): void
    {
        $activity = OrgActivity::query()
            ->whereNotNull('organization_name')
            ->where('organization_name', '!=', '')
            ->where('workflow_status', 'oc_approved')
            ->orderBy('id')
            ->first();

        if (! $activity) {
            $this->markTestSkipped('No live organization activity is available in the Laragon database.');
        }

        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk/budget-utilization?organization='.urlencode($activity->organization_name))
            ->assertOk()
            ->assertSee($activity->organization_name, false)
            ->assertSee('Organization-wide', false)
            ->assertSee($activity->title, false);
    }

    public function test_budget_activity_selector_excludes_unapproved_activities(): void
    {
        $suffix = uniqid('', true);
        $pending = OrgActivity::query()->create([
            'title' => 'Pending Budget Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oso_review',
            'organization_name' => 'Budget Visibility Test Organization',
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);
        $approved = OrgActivity::query()->create([
            'title' => 'Approved Budget Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'organization_name' => 'Budget Visibility Test Organization',
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(9),
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/budget-utilization')
                ->assertOk()
                ->assertSee($approved->title.' (', false)
                ->assertDontSee($pending->title, false);
        } finally {
            OrgActivity::query()->whereIn('id', [$pending->id, $approved->id])->delete();
        }
    }

    public function test_receipt_submission_rejects_an_unapproved_activity(): void
    {
        $suffix = uniqid('', true);
        $pending = OrgActivity::query()->create([
            'title' => 'Pending Receipt Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oso_review',
            'organization_name' => 'Budget Visibility Test Organization',
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/budget-utilization/receipt-reviews', [
                    'activity' => $pending->title,
                    'request_key' => (string) \Illuminate\Support\Str::uuid(),
                    'item_name' => 'Unapproved Item',
                    'category' => 'Supplies',
                    'quantity' => 1,
                    'unit_cost' => 100,
                    'expense_date' => now()->toDateString(),
                    'receipt_reference' => 'PENDING-'.$suffix,
                    'organization_name' => $pending->organization_name,
                    'receipt_reviewed' => '1',
                    'receipt_detected' => '1',
                    'ocr_quality' => 'complete',
                    'ocr_confidence' => 95,
                    'receipt_type' => 'paper_receipt', 'payment_method' => 'cash',
                    'receipt' => UploadedFile::fake()->image('pending-receipt.jpg'),
                ])
                ->assertRedirect()
                ->assertSessionHasErrors('activity');

            $this->assertDatabaseMissing('expense_receipt_reviews', [
                'activity_title' => $pending->title,
                'item_name' => 'Unapproved Item',
            ], 'mysql');
        } finally {
            OrgActivity::query()->whereKey($pending->id)->delete();
        }
    }

    public function test_only_student_organizations_can_submit_receipts(): void
    {
        $suffix = uniqid('', true);
        $approved = OrgActivity::query()->create([
            'title' => 'Approved Receipt Upload Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'organization_name' => 'Budget Visibility Test Organization',
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);

        try {
            foreach (['oso', 'sdo', 'ovcaa'] as $role) {
                $this->actingAs($this->ensureOfficeUser($role), 'office')
                    ->post('/office-desk/budget-utilization/receipt-reviews', [
                        'activity' => $approved->title,
                        'item_name' => 'Unauthorized Item',
                        'quantity' => 1,
                        'unit_cost' => 100,
                        'expense_date' => now()->toDateString(),
                        'receipt_reference' => 'UNAUTHORIZED-'.$suffix,
                        'receipt_reviewed' => '1',
                        'receipt_detected' => '1',
                        'ocr_quality' => 'complete',
                        'receipt' => UploadedFile::fake()->image('unauthorized.jpg'),
                    ])
                    ->assertForbidden();
            }
        } finally {
            OrgActivity::query()->whereKey($approved->id)->delete();
        }
    }

    public function test_student_organization_desk_uses_receipt_seal_status_not_an_automatic_financial_audit(): void
    {
        $this->actingAs($this->ensureOfficeUser('so'), 'office')
            ->get('/office-desk/budget-utilization')
            ->assertOk()
            ->assertSee('Receipt History', false)
            ->assertSee('Receipt Seal Details', false)
            ->assertDontSee('Financial Report Status Workflow', false)
            ->assertDontSee('Verified &amp; Audited', false);
    }

    public function test_student_organization_budget_surface_is_operational_not_consolidated(): void
    {
        $this->actingAs($this->ensureOfficeUser('so'), 'office')
            ->get('/office-desk/budget-utilization')
            ->assertOk()
            ->assertSee('Approved activity', false)
            ->assertSee('Recorded fund balance', false)
            ->assertSee('Show budget charts and trend details', false)
            ->assertDontSee('Full Institutional Org Portfolio (Consolidated)', false)
            ->assertDontSee('All Organizations', false);

        $this->actingAs($this->ensureOfficeUser('so'), 'office')
            ->get('/office-desk')
            ->assertOk()
            ->assertDontSee('href="/office-desk/analytics"', false);
    }

    public function test_student_organization_receipt_capture_is_mobile_ready(): void
    {
        $this->actingAs($this->ensureOfficeUser('so'), 'office')
            ->get('/office-desk/budget-utilization')
            ->assertOk()
            ->assertSee('class="so-receipt-file-input"', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('class="so-camera-dialog"', false)
            ->assertSee('@media (max-width: 640px)', false)
            ->assertSee('Upload receipts (up to 3)', false)
            ->assertSee('name="receipts[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('Open Camera', false)
            ->assertSee('Upload shared receipts/supporting documents', false)
            ->assertSee('Enter the information shown on the shared receipts.', false)
            ->assertSee('Add item', false)
            ->assertDontSee('receipt-scanner.js', false)
            ->assertDontSee('soOcrStatus', false)
            ->assertDontSee('Scan ready', false)
            ->assertDontSee('Server OCR', false);
    }
}

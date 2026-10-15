<?php

namespace Tests\Feature;

use App\Models\OrgActivity;
use Illuminate\Http\UploadedFile;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class BudgetUtilizationTest extends TestCase
{
    use UsesLaragonDatabase;

    private string $organizationName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('oso');
        $this->organizationName = $this->ensureOfficeUser('so')->organizationName();
    }

    public function test_budget_page_opens_on_the_consolidated_portfolio(): void
    {
        $this->actingAs($this->ensureOfficeUser('oso'), 'office')
            ->get('/office-desk/budget-utilization')
            ->assertOk()
            ->assertSee('value="all" selected', false);
    }

    public function test_organization_filter_changes_the_live_budget_portfolio(): void
    {
        $activity = OrgActivity::query()->create([
            'title' => 'Filtered Budget Activity '.uniqid('', true),
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'organization_name' => $this->organizationName,
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/budget-utilization?organization='.urlencode($activity->organization_name))
                ->assertOk()
                ->assertSee($activity->organization_name, false)
                ->assertSee($activity->title, false);
        } finally {
            $activity->delete();
        }
    }

    public function test_so_budget_activity_selector_excludes_unapproved_and_other_organization_activities(): void
    {
        $suffix = uniqid('', true);
        $pending = OrgActivity::query()->create([
            'title' => 'Pending Budget Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oso_review',
            'organization_name' => $this->organizationName,
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);
        $approved = OrgActivity::query()->create([
            'title' => 'Approved Budget Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'organization_name' => $this->organizationName,
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(9),
        ]);
        $other = OrgActivity::query()->create([
            'title' => 'Other Organization Budget Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'organization_name' => 'Other Budget Organization '.$suffix,
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(9),
        ]);

        try {
            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->get('/office-desk/budget-utilization')
                ->assertOk()
                ->assertSee($approved->title, false)
                ->assertDontSee($pending->title, false)
                ->assertDontSee($other->title, false);
        } finally {
            OrgActivity::query()->whereIn('id', [$pending->id, $approved->id, $other->id])->delete();
        }
    }

    public function test_receipt_submission_rejects_an_unapproved_activity(): void
    {
        $suffix = uniqid('', true);
        $pending = OrgActivity::query()->create([
            'title' => 'Pending Receipt Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oso_review',
            'organization_name' => $this->organizationName,
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
            'organization_name' => $this->organizationName,
            'college' => 'CICS',
            'approved_budget' => 10000,
            'starts_at' => now()->addDays(8),
        ]);

        try {
            foreach (['oso', 'sdo', 'ovcaa', 'oc'] as $role) {
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
                        'receipt' => UploadedFile::fake()->image('unauthorized.jpg'),
                    ])
                    ->assertForbidden();
            }
        } finally {
            OrgActivity::query()->whereKey($approved->id)->delete();
        }
    }

}

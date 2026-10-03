<?php

namespace Tests\Feature;

use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\StudentOrganization;
use App\Services\ActivityBudgetService;
use App\Services\ActivityRequirements;
use App\Services\BudgetChainService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;
use ZipArchive;

class ActivityBudgetLifecycleTest extends TestCase
{
    use UsesLaragonDatabase;

    private array $offices;
    private StudentOrganization $organization;
    private OrgFundAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Carbon::setTestNow('2026-10-01 09:00:00');
        Storage::fake('public');
        Storage::fake('local');
        foreach (['so', 'oso', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->offices[$role] = OfficeUser::create([
                'name' => 'Lifecycle '.$role, 'email' => Str::uuid().'@example.test',
                'username' => 'test-'.Str::random(15), 'password' => Str::random(30),
                'office_role' => $role, 'office_title' => strtoupper($role).' test desk', 'is_active' => true,
            ]);
        }
        $this->organization = StudentOrganization::create([
            'name' => 'Lifecycle Organization '.Str::uuid(), 'short_name' => 'LCT',
            'college' => 'CICS', 'academic_year' => '2026-2027', 'is_active' => true,
        ]);
        $this->account = OrgFundAccount::create([
            'organization_name' => $this->organization->name, 'fiscal_year' => '2026-2027', 'total_funds' => 10000,
        ]);
        $this->mock(BudgetChainService::class, function ($mock) {
            $mock->shouldReceive('recentBlocks')->andReturn([]);
            $mock->shouldReceive('sealExpense')->andReturn([
                'block_hash' => str_repeat('a', 64), 'previous_hash' => str_repeat('0', 64),
                'nodes_confirmed' => 3, 'chain_driver' => 'file',
            ]);
        });
    }

    protected function tearDown(): void
    {
        while (DB::connection('mysql')->transactionLevel() > 0) DB::connection('mysql')->rollBack();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function activityPayload(array $override = []): array
    {
        $files = [];
        foreach (app(ActivityRequirements::class)->inCampusRequirements() as $req) {
            if ($req['required_on_submit'] && ! $req['condition']) {
                $files[$req['key']] = UploadedFile::fake()->create($req['key'].'.pdf', 1, 'application/pdf');
            }
        }
        return array_merge([
            'submission_action' => 'submit', 'activity_type' => 'in_campus',
            'title' => 'Lifecycle Event '.Str::uuid(), 'organization_name' => $this->organization->name,
            'location' => 'Campus', 'approved_budget' => '5000.00',
            'objectives' => 'Teach practical sustainability skills.',
            'sdg_goals' => ['SDG 4', 'SDG 12'],
            'participants' => 'Forty student volunteers.', 'safety_plan' => 'First-aid station and emergency contacts.',
            'starts_at' => '2026-10-20 09:00:00', 'ends_at' => '2026-10-20 17:00:00', 'attachments' => $files,
        ], $override);
    }

    private function submitted(): OrgActivity
    {
        $data = $this->activityPayload();
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/activities', $data)
            ->assertRedirect()->assertSessionHasNoErrors();
        return OrgActivity::where('title', $data['title'])->firstOrFail();
    }

    private function approved(): OrgActivity
    {
        $a = $this->submitted();
        foreach (['oso', 'sdo', 'ovcaa', 'oc'] as $role) {
            $payload = in_array($role, ['oso', 'sdo'], true) ? ['documents_reviewed' => '1'] : [];
            $this->actingAs($this->offices[$role], 'office')->post('/office-desk/activities/'.$a->id.'/advance', $payload)
                ->assertRedirect()->assertSessionHasNoErrors();
        }
        return $a->refresh();
    }

    private function receiptPayload(OrgActivity $a, array $override = []): array
    {
        $data = array_merge([
            'org_activity_id' => $a->id, 'request_key' => (string) Str::uuid(),
            'item_name' => 'Printed learning kits', 'supplier' => 'Test supplier', 'quantity' => 3, 'unit_cost' => '123.45',
            'expense_date' => '2026-10-01', 'receipt_reference' => 'TEST-'.Str::uuid(),
            'ocr_quality' => 'complete', 'receipt_reviewed' => '1',
            'receipt_type' => 'paper_receipt', 'payment_method' => 'cash',
            'receipt' => UploadedFile::fake()->image('Original Receipt.jpg'),
        ], $override);
        $scan = \App\Models\ReceiptScan::create([
            'uploaded_by' => $this->offices['so']->id, 'file_hash' => hash_file('sha256', $data['receipt']->getRealPath()),
            'engine' => 'test-fixture', 'raw_text' => 'SYNTHETIC TEST RECEIPT', 'confidence' => 90,
            'extracted' => ['quality' => 'complete', 'fields' => []], 'expires_at' => now()->addHour(),
        ]);
        return $data + ['receipt_scan_id' => $scan->id];
    }

    public function test_submission_office_handoffs_and_final_approval_reserve_the_correct_org_budget(): void
    {
        $a = $this->submitted();
        $this->assertSame('oso_review', $a->workflow_status);
        $this->assertFalse(OrgActivity::visibleToStudents()->whereKey($a->id)->exists());
        $this->assertSame(0.0, app(ActivityBudgetService::class)->balance($this->account)['allocated']);
        $this->actingAs($this->offices['sdo'], 'office')->post('/office-desk/activities/'.$a->id.'/advance', [
            'documents_reviewed' => '1',
        ])->assertSessionHasErrors('workflow');
        $this->actingAs($this->offices['oso'], 'office')->post('/office-desk/activities/'.$a->id.'/advance', ['documents_reviewed' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('sdo_review', $a->refresh()->workflow_status);
        $this->actingAs($this->offices['sdo'], 'office')->get('/office-desk/activities?activity='.$a->id)->assertOk()
            ->assertSee('Workflow')->assertSee('Created')->assertSee('OSO Review')->assertSee('SDO Review')->assertSee('OVCAA Review')->assertSee('OC Final Approval')
            ->assertDontSee('Activity SDGs')->assertDontSee('Organization-entered SDGs:')
            ->assertSee('Waste Policy Compliance Form')->assertSee('Teach practical sustainability skills.')
            ->assertSee('First-aid station and emergency contacts.')
            ->assertDontSee('Select the applicable SDGs')
            ->assertDontSee('SDG Alignment Check')
            ->assertSee('no SDG re-entry is required');
        $submission = InCampusActivitySubmission::where('org_activity_id', $a->id)->firstOrFail();
        foreach (['oso', 'sdo'] as $role) {
            $this->actingAs($this->offices[$role], 'office')->get('/office-desk/activities/'.$submission->id.'/attachments/wpcf?download=1')->assertOk();
        }
        foreach (['ovcaa', 'oc'] as $role) {
            $this->actingAs($this->offices[$role], 'office')->get('/office-desk/activities/'.$submission->id.'/attachments/wpcf?download=1')->assertForbidden();
        }
        $this->actingAs($this->offices['sdo'], 'office')->post('/office-desk/activities/'.$a->id.'/advance', ['documents_reviewed' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($this->offices['ovcaa'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasNoErrors();
        $this->assertSame('oc_review', $a->refresh()->workflow_status);
        $this->actingAs($this->offices['ovcaa'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasErrors('workflow');
        $this->actingAs($this->offices['oc'], 'office')->get('/office-desk/activities?activity='.$a->id)->assertOk()->assertSee('OC Final Approval');
        $this->actingAs($this->offices['oc'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasNoErrors();
        $this->assertSame('oc_approved', $a->refresh()->workflow_status);
        $this->assertSame(['SDG 4', 'SDG 12'], $a->sdg_goals);
        $this->assertSame('SDO checked the submitted DOCX files and Waste Policy Compliance Form.', $a->sdo_review_notes);
        $balance = app(ActivityBudgetService::class)->balance($this->account);
        $this->assertSame(5000.0, $balance['allocated']);
        $this->assertSame(5000.0, $balance['available']);
        $this->assertSame(10000.0, $balance['cash']);
        $this->assertSame(1, BudgetItem::where('org_activity_id', $a->id)->count());
        $this->assertSame(5, DB::table('activity_workflow_events')->where('org_activity_id', $a->id)->count());
        $this->actingAs($this->offices['ovcaa'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasErrors('workflow');
        $this->actingAs($this->offices['oc'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasErrors('workflow');
        $this->assertSame(1, BudgetItem::where('org_activity_id', $a->id)->count());
    }

    public function test_oc_has_a_separate_final_approval_dashboard_and_queue(): void
    {
        OrgActivity::create([
            'title' => 'OC Queue Activity '.Str::uuid(),
            'organization_name' => $this->organization->name,
            'workflow_status' => 'oc_review',
            'status' => 'draft',
            'starts_at' => '2026-10-20 09:00:00',
            'ends_at' => '2026-10-20 17:00:00',
            'approved_budget' => 2500,
        ]);

        $this->actingAs($this->offices['oc'], 'office')
            ->get('/office-desk')
            ->assertOk()
            ->assertSee('OC Final Approval Queue', false)
            ->assertSee('Awaiting OC Approval', false);
    }

    public function test_receipt_debits_once_preserves_cents_and_feeds_history_download_and_semester_compilation(): void
    {
        $a = $this->approved();
        $data = $this->receiptPayload($a);
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $data)->assertSessionHasNoErrors();
        $receipt = ExpenseReceiptReview::where('org_activity_id', $a->id)->firstOrFail();
        $this->assertSame('370.35', (string) $a->refresh()->implemented_budget);
        $balance = app(ActivityBudgetService::class)->balance($this->account);
        $this->assertSame(9629.65, $balance['cash']);
        $this->assertSame(4629.65, $balance['reserved']);
        $this->assertSame(5000.0, $balance['available']);
        $this->assertSame('370.35', (string) BudgetItem::where('org_activity_id', $a->id)->value('utilized'));
        $this->post('/office-desk/budget-utilization/receipt-reviews', $data)->assertSessionHasNoErrors();
        $this->assertSame(1, ExpenseReceiptReview::where('org_activity_id', $a->id)->count());
        $this->assertSame('370.35', (string) $a->refresh()->implemented_budget);
        $this->get('/office-desk/budget-utilization/receipts/'.$receipt->id.'/view?download=1')->assertOk()->assertHeader('content-disposition', 'attachment; filename="Original Receipt.jpg"');
        $query = http_build_query(['organization' => $a->organization_name, 'academic_year' => '2026-2027', 'semester' => '1st Semester']);
        $this->actingAs($this->offices['oso'], 'office')->get('/office-desk/financial-report?'.$query)->assertOk()
            ->assertViewHas('periodExpenseTotal', 370.35)->assertSee('Printed learning kits')->assertDontSee('Tech Titan Corp');
        $this->get('/office-desk/financial-report/print?'.$query)->assertOk()->assertSee('370.35')->assertSee('Original Receipt.jpg')->assertDontSee('org-sidebar');
        $this->get('/office-desk/budget-utilization?'.$query)->assertOk()->assertSee('Receipt History')->assertSee('Original Receipt.jpg');
        $response = $this->get('/office-desk/budget-utilization/receipt-package?'.$query)->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive(); $zip->open($path);
            $this->assertSame(3, $zip->numFiles);
            $this->assertStringContainsString('370.35', $zip->getFromName('expense-register.csv'));
            $zip->close();
        } finally { unlink($path); }
        $empty = http_build_query(['organization' => $a->organization_name, 'academic_year' => '2025-2026', 'semester' => '1st Semester']);
        $this->get('/office-desk/financial-report?'.$empty)->assertViewHas('periodExpenseTotal', 0.0)->assertDontSee('Printed learning kits');
    }

    public function test_one_expense_item_can_store_multiple_receipt_photos_without_double_debiting(): void
    {
        $a = $this->approved();
        $requestKey = (string) Str::uuid();
        $data = [
            'org_activity_id' => $a->id,
            'receipt_reviewed' => '1',
            'expenses' => [[
                'request_key' => $requestKey,
                'item_name' => 'Venue supplies with supporting pages',
                'category' => 'Supplies',
                'quantity' => 1,
                'unit_cost' => '250.00',
                'expense_date' => '2026-10-01',
                'supplier' => 'Multi-photo supplier',
                'receipt_reference' => 'MULTI-'.Str::uuid(),
                'receipt_type' => 'paper_receipt',
                'payment_method' => 'cash',
                'receipts' => [
                    UploadedFile::fake()->image('Receipt front.jpg', 120, 120),
                    UploadedFile::fake()->image('Receipt details.jpg', 180, 120),
                ],
            ]],
        ];

        $this->actingAs($this->offices['so'], 'office')
            ->post('/office-desk/budget-utilization/receipt-reviews', $data)
            ->assertSessionHasNoErrors();

        $receipt = ExpenseReceiptReview::where('org_activity_id', $a->id)->firstOrFail();
        $this->assertCount(2, $receipt->receiptFiles());
        $this->assertSame('250.00', (string) $a->refresh()->implemented_budget);
        $this->get('/office-desk/budget-utilization/receipts/'.$receipt->id.'/view?attachment=1')->assertOk();

        $response = $this->actingAs($this->offices['so'], 'office')
            ->get('/office-desk/budget-utilization/receipt-package?activity_id='.$a->id)
            ->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive();
            $zip->open($path);
            $this->assertSame(4, $zip->numFiles);
            $this->assertStringContainsString('Receipt front.jpg', $zip->getFromName('expense-register.csv'));
            $this->assertStringContainsString('Receipt details.jpg', $zip->getFromName('expense-register.csv'));
            $zip->close();
            unset($zip);
        } finally {
            unset($response, $zip);
            gc_collect_cycles();
            @unlink($path);
        }
    }

    public function test_one_shared_receipt_set_supports_many_item_rows_without_reuploading(): void
    {
        $a = $this->approved();
        $sharedFiles = [
            UploadedFile::fake()->image('Shared receipt front.jpg', 120, 120),
            UploadedFile::fake()->image('Shared receipt details.jpg', 180, 120),
        ];
        $row = fn (string $item, string $cost, string $reference, string $requestKey): array => [
            'request_key' => $requestKey,
            'item_name' => $item,
            'category' => 'Supplies',
            'quantity' => 1,
            'unit_cost' => $cost,
            'expense_date' => '2026-10-01',
            'supplier' => 'Shared receipt supplier',
            'receipt_reference' => $reference,
            'receipt_type' => 'paper_receipt',
            'payment_method' => 'cash',
        ];
        $data = [
            'org_activity_id' => $a->id,
            'receipt_reviewed' => '1',
            'receipts' => $sharedFiles,
            'expenses' => [
                $row('Printed name cards', '100.00', 'SHARED-001', (string) Str::uuid()),
                $row('Event signage', '200.00', 'SHARED-001', (string) Str::uuid()),
            ],
        ];

        $this->actingAs($this->offices['so'], 'office')
            ->post('/office-desk/budget-utilization/receipt-reviews', $data)
            ->assertSessionHasNoErrors();

        $receipts = ExpenseReceiptReview::where('org_activity_id', $a->id)->orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertSame($receipts[0]->receipt_path, $receipts[1]->receipt_path);
        $this->assertCount(2, $receipts[0]->receiptFiles());
        $this->assertSame('300.00', (string) $a->refresh()->implemented_budget);

        $response = $this->actingAs($this->offices['so'], 'office')
            ->get('/office-desk/budget-utilization/receipt-package?activity_id='.$a->id)
            ->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive();
            $zip->open($path);
            $this->assertSame(4, $zip->numFiles);
            $this->assertStringContainsString('Printed name cards', $zip->getFromName('expense-register.csv'));
            $this->assertStringContainsString('Event signage', $zip->getFromName('expense-register.csv'));
            $this->assertStringContainsString('shared:', $zip->getFromName('expense-register.csv'));
            $zip->close();
            unset($zip);
        } finally {
            unset($response, $zip);
            gc_collect_cycles();
            @unlink($path);
        }
    }

    public function test_over_budget_expense_and_excess_allocation_are_rejected_without_debit(): void
    {
        $a = $this->approved();
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $this->receiptPayload($a, ['quantity' => 1, 'unit_cost' => '5000.01']))->assertSessionHasErrors('unit_cost');
        $this->assertSame('0.00', (string) $a->refresh()->implemented_budget);
        $this->assertSame(0, ExpenseReceiptReview::where('org_activity_id', $a->id)->count());
        $other = OrgActivity::create(['title' => 'Excess', 'organization_name' => $a->organization_name, 'workflow_status' => 'ovcaa_review', 'starts_at' => '2026-10-22', 'approved_budget' => 6000]);
        $this->actingAs($this->offices['ovcaa'], 'office')->post('/office-desk/activities/'.$other->id.'/advance')->assertSessionHasNoErrors();
        $this->assertSame('oc_review', $other->refresh()->workflow_status);
        $this->actingAs($this->offices['oc'], 'office')->post('/office-desk/activities/'.$other->id.'/advance')->assertSessionHasErrors('workflow');
        $this->assertSame('oc_review', $other->refresh()->workflow_status);
        $this->assertFalse(BudgetItem::where('org_activity_id', $other->id)->exists());
    }

    public function test_pending_blockchain_seal_can_retry_without_a_second_debit(): void
    {
        $a = $this->approved();
        $this->mock(BudgetChainService::class)->shouldReceive('sealExpense')->once()->andThrow(new \RuntimeException('Network unavailable'));
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $this->receiptPayload($a))->assertSessionHasNoErrors();
        $r = ExpenseReceiptReview::where('org_activity_id', $a->id)->firstOrFail();
        $this->assertSame('pending_seal', $r->verification_status);
        $this->assertTrue(Storage::disk('local')->exists($r->receipt_path));
        $this->assertFalse(Storage::disk('public')->exists($r->receipt_path));
        $this->mock(BudgetChainService::class)->shouldReceive('sealExpense')->once()->andReturn(['block_hash' => str_repeat('b', 64), 'previous_hash' => str_repeat('a', 64), 'nodes_confirmed' => 3, 'chain_driver' => 'file']);
        $this->post('/office-desk/budget-utilization/receipts/'.$r->id.'/retry')->assertSessionHasNoErrors();
        $this->assertSame('verified', $r->refresh()->verification_status);
        $this->assertSame('370.35', (string) $a->refresh()->implemented_budget);
    }

    public function test_ended_activity_cannot_be_submitted_but_post_event_liquidation_remains_available(): void
    {
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/activities', $this->activityPayload(['starts_at' => '2026-09-01', 'ends_at' => '2026-09-02']))->assertSessionHasErrors('ends_at');
        $a = $this->approved();
        Carbon::setTestNow('2026-10-22 10:00:00');
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $this->receiptPayload($a))->assertSessionHasNoErrors();
        $this->assertSame('370.35', (string) $a->refresh()->implemented_budget);
        $submission = InCampusActivitySubmission::where('org_activity_id', $a->id)->firstOrFail();
        $this->put('/office-desk/activities/'.$submission->id, $this->activityPayload())->assertSessionHasErrors('activity');
        $this->delete('/office-desk/activities/'.$submission->id.'/attachments/wpcf')->assertForbidden();
    }

    public function test_missing_fund_account_blocks_final_approval_until_funds_are_configured(): void
    {
        $a = $this->submitted();
        $this->actingAs($this->offices['oso'], 'office')->post('/office-desk/activities/'.$a->id.'/advance', ['documents_reviewed' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($this->offices['sdo'], 'office')->post('/office-desk/activities/'.$a->id.'/advance', ['documents_reviewed' => '1'])->assertSessionHasNoErrors();
        $this->account->delete(); // Only the transaction-local test fixture.
        $this->actingAs($this->offices['ovcaa'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasNoErrors();
        $this->assertSame('oc_review', $a->refresh()->workflow_status);
        $this->actingAs($this->offices['oc'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasErrors('workflow');
        $this->assertSame('oc_review', $a->refresh()->workflow_status);
        $funds = ['organization_name' => $this->organization->name, 'academic_year' => '2026-2027', 'total_funds' => 10000];
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/accounts', $funds)->assertSessionHasNoErrors();
        $this->actingAs($this->offices['oc'], 'office')->post('/office-desk/activities/'.$a->id.'/advance')->assertSessionHasNoErrors();
        $this->assertSame('oc_approved', $a->refresh()->workflow_status);
        $funds['total_funds'] = 4999;
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/accounts', $funds)->assertSessionHasErrors('total_funds');
        $this->assertSame(10000, (int) OrgFundAccount::where('organization_name', $this->organization->name)->value('total_funds'));
    }

    public function test_only_so_can_configure_fund_accounts_and_oso_is_read_only(): void
    {
        $funds = [
            'organization_name' => $this->organization->name,
            'academic_year' => '2026-2027',
            'total_funds' => 12000,
        ];

        $this->actingAs($this->offices['oso'], 'office')
            ->get('/office-desk/budget-utilization?organization='.urlencode($this->organization->name).'&academic_year=2026-2027')
            ->assertOk()
            ->assertDontSee('Set organization funds', false)
            ->assertDontSee('Organization funds', false)
            ->assertDontSee('Recorded fund balance', false);

        $this->actingAs($this->offices['oso'], 'office')
            ->post('/office-desk/budget-utilization/accounts', $funds)
            ->assertForbidden();

        $this->actingAs($this->offices['oso'], 'office')
            ->post('/office-desk/funds/'.$this->account->id, [
                'total_funds' => 12000,
                'beginning_balance' => 0,
                'total_funds_received' => 12000,
            ])
            ->assertForbidden();

        $this->actingAs($this->offices['so'], 'office')
            ->get('/office-desk/budget-utilization?organization='.urlencode($this->organization->name).'&academic_year=2026-2027')
            ->assertOk()
            ->assertSee('Set organization funds', false);
    }

    public function test_missing_required_upload_is_rejected_and_returned_submission_retains_its_files(): void
    {
        $data = $this->activityPayload();
        unset($data['attachments']['wpcf']);
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/activities', $data)->assertSessionHasErrors();
        $this->assertFalse(OrgActivity::where('title', $data['title'])->exists());
        $a = $this->submitted();
        $submission = InCampusActivitySubmission::where('org_activity_id', $a->id)->firstOrFail();
        $original = $submission->attachments['wpcf']['path'];
        $this->actingAs($this->offices['oso'], 'office')->post('/office-desk/activities/'.$a->id.'/return', ['returned_to' => 'so', 'remarks' => 'Clarify the objectives.'])->assertSessionHasNoErrors();
        $this->actingAs($this->offices['so'], 'office')->put('/office-desk/activities/'.$submission->id, $this->activityPayload(['title' => $a->title, 'attachments' => []]))->assertSessionHasNoErrors();
        $this->assertSame('oso_review', $a->refresh()->workflow_status);
        $this->assertSame($original, $submission->refresh()->attachments['wpcf']['path']);
        Storage::disk('public')->assertExists($original);
    }

    public function test_same_title_activities_do_not_share_receipts_and_offices_cannot_upload_or_return_out_of_turn(): void
    {
        $a = $this->approved();
        $other = OrgActivity::create(['title' => $a->title, 'organization_name' => 'Other organization', 'workflow_status' => 'oc_approved', 'starts_at' => '2026-10-20', 'approved_budget' => 5000]);
        $this->actingAs($this->offices['so'], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $this->receiptPayload($a, ['organization_name' => 'Other organization']))->assertSessionHasNoErrors();
        $r = ExpenseReceiptReview::where('org_activity_id', $a->id)->firstOrFail();
        $this->assertSame($a->organization_name, $r->organization_name);
        $this->assertFalse(ExpenseReceiptReview::where('org_activity_id', $other->id)->exists());
        foreach (['oso', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->offices[$role], 'office')->post('/office-desk/budget-utilization/receipt-reviews', $this->receiptPayload($a))->assertForbidden();
            $this->post('/office-desk/activities/'.$a->id.'/return', ['returned_to' => 'so', 'remarks' => 'Attempt to reopen approval'])->assertSessionHasErrors('workflow');
        }
    }
}

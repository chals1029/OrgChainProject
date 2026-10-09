<?php

namespace Tests\Feature;

use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgCashIncome;
use App\Models\OrgFundAccount;
use App\Models\OrgFundSource;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\ActivityBudgetService;
use App\Services\BudgetChainService;
use App\Services\OrganizationCashLedger;
use App\Services\OrgWorkflowService;
use Carbon\Carbon;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OrganizationCashLedgerTest extends TestCase
{
    use UsesLaragonDatabase;

    private const YEAR = '2026-2027';

    private StudentOrganization $organization;

    private StudentOrganization $foreignOrganization;

    private OfficeUser $so;

    private OfficeUser $foreignSo;

    private OfficeUser $oso;

    private OfficeUser $oc;

    private OrganizationCashLedger $ledger;

    private ActivityBudgetService $budgets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Carbon::setTestNow('2026-10-15 10:00:00');
        Storage::fake('local');
        Storage::fake('public');

        $this->organization = $this->organizationFixture('Cash Ledger Fixture');
        $this->foreignOrganization = $this->organizationFixture('Foreign Cash Ledger Fixture');
        $this->so = $this->office('so', $this->organization);
        $this->foreignSo = $this->office('so', $this->foreignOrganization);
        $this->oso = $this->office('oso');
        $this->oc = $this->office('oc');
        $this->ledger = app(OrganizationCashLedger::class);
        $this->budgets = app(ActivityBudgetService::class);
    }

    protected function tearDown(): void
    {
        try {
            foreach (['mysql', 'orgchain'] as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
            Carbon::setTestNow();
        } finally {
            parent::tearDown();
        }
    }

    public function test_saved_opening_income_and_one_receipt_produce_exact_shared_cash(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '10000.10');
        $this->ledger->recordIncome($this->so, $this->income(['amount' => '0.20']));
        $this->ledger->recordIncome($this->so, $this->income(['amount' => '1234.56', 'transaction_date' => '2026-09-30']));
        $activity = $this->approvedActivity('5000.00');
        $this->receipt($activity, ['quantity' => 3, 'unit_cost' => '123.45']);

        $balance = $this->ledger->balance($account);
        $this->assertSame(10000.10, $balance['opening_balance']);
        $this->assertSame(1234.76, $balance['income']);
        $this->assertSame(11234.86, $balance['total']);
        $this->assertSame(370.35, $balance['spent']);
        $this->assertSame(5000.0, $balance['allocated']);
        $this->assertSame(4629.65, $balance['reserved']);
        $this->assertSame(10864.51, $balance['cash']);
        $this->assertSame(6234.86, $balance['available']);
        $this->assertSame('370.35', (string) $activity->refresh()->implemented_budget);
        $this->assertSame($balance, $this->budgets->balance($account));

        $this->assertSame([
            'academic_year' => self::YEAR,
            'has_account' => true,
            'opening_balance' => 10000.10,
            'income' => 1234.76,
            'spent' => 370.35,
            'allocated' => 5000.0,
            'reserved' => 4629.65,
            'cash' => 10864.51,
            'available' => 6234.86,
        ], $this->ledger->funding($this->organization->name, self::YEAR));
    }

    public function test_final_approval_reserves_only_unspent_cash_and_rejections_change_nothing(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '1000.00');
        $spentActivity = $this->approvedActivity('600.00');
        $this->receipt($spentActivity, ['quantity' => 2, 'unit_cost' => '300.00']);
        $balance = $this->ledger->balance($account);
        $this->assertSame(400.0, $balance['cash']);
        $this->assertSame(0.0, $balance['reserved']);
        $this->assertSame(400.0, $balance['available']);

        $this->approvedActivity('400.00');
        $before = $this->ledger->balance($account);
        $this->assertSame(1000.0, $before['allocated']);
        $this->assertSame(400.0, $before['reserved']);
        $this->assertSame(0.0, $before['available']);

        $this->assertApprovalRejected($this->pendingActivity('0.01'), 'available cash');
        $this->assertApprovalRejected($this->pendingActivity('0.00'), 'approved budget');
        $this->assertSame($before, $this->ledger->balance($account));
    }

    public function test_income_requests_are_idempotent_and_business_references_credit_once(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '100.00');
        $payload = $this->income(['amount' => '250.00', 'reference' => 'CR-0001']);

        $first = $this->ledger->recordIncome($this->so, $payload);
        $retry = $this->ledger->recordIncome($this->so, $payload);
        $this->assertSame($first->id, $retry->id);

        $this->assertRejected('request_key', fn () => $this->ledger->recordIncome($this->so, ['amount' => '251.00'] + $payload));
        $this->assertRejected('reference', fn () => $this->ledger->recordIncome($this->so, [
            'request_key' => (string) Str::uuid(),
            'reference' => '  cr-0001 ',
        ] + $payload));

        $this->assertSame(1, OrgCashIncome::where('org_fund_account_id', $account->id)->count());
        $this->assertSame(350.0, $this->ledger->balance($account)['cash']);

        foreach ([fn () => $first->update(['amount' => '1.00']), fn () => $first->delete()] as $mutation) {
            try {
                $mutation();
                $this->fail('Recorded cash inflows must be append-only.');
            } catch (LogicException) {
            }
        }
        $this->assertSame('250.00', (string) $first->fresh()->amount);
    }

    public function test_cash_writes_are_limited_to_the_actors_own_organization(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '500.00');
        $foreignAccount = $this->ledger->saveOpening($this->foreignSo, self::YEAR, '50.00');
        $foreignActivity = $this->approvedActivity('20.00', $this->foreignOrganization);
        $ownPending = $this->pendingActivity('10.00');

        $foreignIncome = $this->ledger->recordIncome($this->foreignSo, $this->income(['amount' => '10.00']));
        $this->assertSame($foreignAccount->id, (int) $foreignIncome->org_fund_account_id);
        $this->assertSame($this->foreignOrganization->name, $foreignIncome->organization_name);

        $this->assertRejected('org_activity_id', fn () => $this->ledger->recordIncome($this->so, $this->income(['org_activity_id' => $foreignActivity->id])));
        $this->assertRejected('org_activity_id', fn () => $this->ledger->recordIncome($this->so, $this->income(['org_activity_id' => $ownPending->id])));

        foreach ([$this->oso, $this->office('so')] as $actor) {
            foreach ([
                fn () => $this->ledger->saveOpening($actor, self::YEAR, '1.00'),
                fn () => $this->ledger->recordIncome($actor, $this->income()),
            ] as $write) {
                try {
                    $write();
                    $this->fail('Only the assigned SO may write organization cash.');
                } catch (AuthorizationException) {
                }
            }
        }

        $this->assertSame(500.0, $this->ledger->balance($account)['cash']);
        $this->assertSame(0.0, $this->ledger->balance($account)['income']);
        $this->assertSame(60.0, $this->ledger->balance($foreignAccount)['cash']);
        $this->assertSame(1, OrgFundAccount::where('organization_name', $this->organization->name)->count());
    }

    public function test_opening_cannot_drop_below_committed_funds_and_preserves_legacy_fields(): void
    {
        $account = OrgFundAccount::create([
            'organization_name' => $this->organization->name, 'college' => 'Legacy College', 'fiscal_year' => self::YEAR,
            'total_funds' => 1000, 'beginning_balance' => 250, 'total_funds_received' => 750, 'cash_opening_balance' => '1000.00',
        ]);
        $source = OrgFundSource::create(['org_fund_account_id' => $account->id, 'category' => 'ssc_fee', 'label' => 'Legacy credit', 'amount' => 750]);
        $activity = $this->approvedActivity('600.00');
        $this->receipt($activity, ['quantity' => 1, 'unit_cost' => '100.00']);
        $this->ledger->recordIncome($this->so, $this->income(['amount' => '50.00']));

        $this->assertRejected('opening_balance', fn () => $this->ledger->saveOpening($this->so, self::YEAR, '549.99'));
        foreach (['-1', '1.234', 'abc', ''] as $invalid) {
            $this->assertRejected('opening_balance', fn () => $this->ledger->saveOpening($this->so, self::YEAR, $invalid));
        }
        $this->assertRejected('academic_year', fn () => $this->ledger->saveOpening($this->so, '2026-2028', '1.00'));
        $this->assertSame('1000.00', (string) $account->fresh()->cash_opening_balance);

        $saved = $this->ledger->saveOpening($this->so, self::YEAR, '550.00');
        $this->assertSame($account->id, $saved->id);
        $fresh = $account->fresh();
        $this->assertSame('550.00', (string) $fresh->cash_opening_balance);
        $this->assertSame(1000, (int) $fresh->total_funds);
        $this->assertSame(250, (int) $fresh->beginning_balance);
        $this->assertSame(750, (int) $fresh->total_funds_received);
        $this->assertSame('Legacy College', $fresh->college);
        $this->assertSame(750, (int) $source->fresh()->amount);
        $this->assertSame(1, OrgFundSource::where('org_fund_account_id', $account->id)->count());

        $balance = $this->ledger->balance($fresh);
        $this->assertSame(500.0, $balance['cash']);
        $this->assertSame(500.0, $balance['reserved']);
        $this->assertSame(0.0, $balance['available']);
    }

    public function test_existing_duplicate_accounts_are_preserved_and_the_oldest_is_canonical(): void
    {
        $oldest = OrgFundAccount::create(['organization_name' => $this->organization->name, 'fiscal_year' => self::YEAR, 'total_funds' => 300, 'cash_opening_balance' => '300.00']);
        $duplicate = OrgFundAccount::create(['organization_name' => $this->organization->name, 'fiscal_year' => self::YEAR, 'total_funds' => 900, 'cash_opening_balance' => '900.00']);

        $saved = $this->ledger->saveOpening($this->so, self::YEAR, '320.00');
        $income = $this->ledger->recordIncome($this->so, $this->income(['amount' => '5.00']));

        $this->assertSame($oldest->id, $saved->id);
        $this->assertSame($oldest->id, (int) $income->org_fund_account_id);
        $this->assertSame(2, OrgFundAccount::where('organization_name', $this->organization->name)->count());
        $this->assertSame('900.00', (string) $duplicate->fresh()->cash_opening_balance);
        $this->assertSame(325.0, $this->ledger->funding($this->organization->name, self::YEAR)['cash']);
        $this->assertSame(325.0, $this->ledger->cashFlow($this->organization->name, ['academic_year' => self::YEAR, 'semester' => 'all'])['ending_balance']);
    }

    public function test_receipt_seal_and_uploaded_report_retries_never_debit_again(): void
    {
        $this->mock(BudgetChainService::class, function ($mock): void {
            $mock->shouldReceive('sealExpense')->once()->andReturn([
                'block_hash' => str_repeat('c', 64), 'previous_hash' => str_repeat('0', 64),
                'nodes_confirmed' => BudgetChainService::NODE_COUNT, 'chain_driver' => 'file',
            ]);
        });
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '1000.00');
        $activity = $this->approvedActivity('500.00');
        $data = $this->receiptData($activity);

        $first = $this->budgets->record($activity, $this->so, $data, UploadedFile::fake()->image('Receipt.jpg'));
        $retry = $this->budgets->record($activity, $this->so, $data, UploadedFile::fake()->image('Receipt again.jpg', 40, 40));
        $this->assertSame($first->id, $retry->id);
        $this->assertSame(1, ExpenseReceiptReview::where('org_activity_id', $activity->id)->count());
        $this->assertSame(876.55, $this->ledger->balance($account)['cash']);

        $this->assertTrue($this->budgets->seal($first));
        $this->assertTrue($this->budgets->seal($first));
        $this->assertSame('verified', $first->fresh()->verification_status);
        $this->assertSame('123.45', (string) $activity->refresh()->implemented_budget);

        $before = $this->snapshotReads($account);
        $status = OrgReportStatus::create([
            'report_type' => 'fr', 'organization_name' => $this->organization->name, 'college' => 'Cash Ledger Test College',
            'semester' => '1st Semester', 'academic_year' => self::YEAR, 'status' => 'draft', 'batch_key' => (string) Str::uuid(),
        ]);
        OrgReportDocument::create([
            'org_report_status_id' => $status->id, 'report_type' => 'fr', 'organization_name' => $this->organization->name,
            'semester' => '1st Semester', 'academic_year' => self::YEAR, 'name' => 'Uploaded copy', 'original_name' => 'copy.xlsx',
            'file_path' => 'semester-reports/fr/copy.xlsx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size' => 1024, 'uploaded_by' => $this->so->name,
            'financial_summary' => [
                'beginning_balance' => 99999.0, 'cash_inflow' => 5000.0, 'cash_outflow' => 123.45, 'ending_balance' => 104875.55,
                'inflow_source' => 'cash_receipts', 'outflow_source' => 'cash_disbursements',
                'activities' => [['name' => $activity->title, 'inflow' => 5000.0, 'outflow' => 123.45]],
                'receipts' => [['date' => '2026-10-01', 'event' => $activity->title, 'reference' => 'CR-X', 'payee' => 'X', 'account' => 'Cash', 'description' => 'Bogus', 'quantity' => 1, 'unit_cost' => 5000, 'amount' => 5000.0, 'ref' => '']],
                'disbursements' => [['date' => '2026-10-01', 'event' => $activity->title, 'reference' => $data['receipt_reference'], 'payee' => 'Test supplier', 'account' => 'Supplies', 'description' => 'Copy', 'quantity' => 1, 'unit_cost' => 123.45, 'amount' => 123.45, 'ref' => '']],
            ],
        ]);

        $this->assertEquals($before, $this->snapshotReads($account));
        $this->assertSame(876.55, $this->ledger->balance($account)['cash']);
    }

    public function test_fiscal_year_boundaries_for_income_and_receipts(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '1000.00');
        foreach (['2026-07-31', '2026-10-16', '2027-08-01', '2026-02-30', '15/10/2026'] as $date) {
            $this->assertRejected('transaction_date', fn () => $this->ledger->recordIncome($this->so, $this->income(['transaction_date' => $date])));
        }
        $this->ledger->recordIncome($this->so, $this->income(['transaction_date' => '2026-08-01', 'amount' => '10.00']));
        $this->ledger->recordIncome($this->so, $this->income(['transaction_date' => '2026-10-15', 'amount' => '20.00']));
        $this->assertRejected('academic_year', fn () => $this->ledger->recordIncome($this->so, $this->income(['academic_year' => '2024-2025', 'transaction_date' => '2024-09-01'])));
        foreach (['0', '0.00', '-5.00', '1.005'] as $amount) {
            $this->assertRejected('amount', fn () => $this->ledger->recordIncome($this->so, $this->income(['amount' => $amount])));
        }

        $activity = $this->approvedActivity('500.00');
        foreach (['2026-07-31', '2026-10-16'] as $date) {
            $this->assertRejected('expense_date', fn () => $this->receipt($activity, ['expense_date' => $date]));
        }
        $this->receipt($activity, ['expense_date' => '2026-08-01', 'quantity' => 1, 'unit_cost' => '40.00']);

        $balance = $this->ledger->balance($account);
        $this->assertSame(30.0, $balance['income']);
        $this->assertSame(40.0, $balance['spent']);
        $this->assertSame(990.0, $balance['cash']);
        $this->assertSame(2, OrgCashIncome::where('org_fund_account_id', $account->id)->count());
        $this->assertSame(1, ExpenseReceiptReview::where('org_activity_id', $activity->id)->count());
    }

    public function test_legacy_spending_gap_is_preserved_and_receipts_count_once(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '2000.00');
        $legacy = OrgActivity::create([
            'title' => 'Legacy Outreach '.Str::uuid(), 'organization_name' => $this->organization->name,
            'workflow_status' => 'oc_approved', 'status' => 'upcoming', 'starts_at' => '2026-09-05 09:00:00',
            'approved_budget' => '800.00', 'implemented_budget' => '500.00',
        ]);
        $balance = $this->ledger->balance($account);
        $this->assertSame(500.0, $balance['spent']);
        $this->assertSame(300.0, $balance['reserved']);
        $this->assertSame(1500.0, $balance['cash']);

        $receipt = $this->receipt($legacy, ['quantity' => 1, 'unit_cost' => '120.00', 'category' => 'Supplies']);
        $this->assertSame('620.00', (string) $legacy->refresh()->implemented_budget);
        $balance = $this->ledger->balance($account);
        $this->assertSame(620.0, $balance['spent']);
        $this->assertSame(180.0, $balance['reserved']);
        $this->assertSame(1380.0, $balance['cash']);

        $flow = $this->ledger->cashFlow($this->organization->name, ['academic_year' => self::YEAR, 'semester' => 'all']);
        $this->assertSame(2000.0, $flow['beginning_balance']);
        $this->assertSame(620.0, $flow['cash_outflow']);
        $this->assertSame(1380.0, $flow['ending_balance']);
        $this->assertSame(['1st Semester', '2nd Semester', 'Midyear'], $flow['selected']->pluck('semester')->all());
        $rows = collect($flow['selected']->first()['summary']['disbursements'])->keyBy('ref');
        $this->assertCount(2, $rows);
        $this->assertSame(120.0, $rows['EXP-'.$receipt->id]['amount']);
        $this->assertSame('Supplies', $rows['EXP-'.$receipt->id]['account']);
        $this->assertSame(500.0, $rows['LEGACY-'.$legacy->id]['amount']);
        $this->assertSame(OrganizationCashLedger::LEGACY_SPENDING, $rows['LEGACY-'.$legacy->id]['account']);
        $this->assertSame('2026-09-05', $rows['LEGACY-'.$legacy->id]['date']);
        $this->assertSame([['name' => $legacy->title, 'inflow' => 0.0, 'outflow' => 620.0]], $flow['activities']);
    }

    public function test_stale_activity_cache_cannot_let_new_receipts_overspend_the_activity(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '1000.00');
        $activity = $this->approvedActivity('500.00');
        ExpenseReceiptReview::create([
            'org_activity_id' => $activity->id, 'activity_title' => $activity->title, 'organization_name' => $activity->organization_name,
            'item_name' => 'Earlier posted venue fee', 'quantity' => 1, 'unit_cost' => '300.00', 'expense_date' => '2026-09-10',
            'receipt_reference' => 'OR-'.Str::uuid(), 'receipt_path' => 'expense-receipts/legacy.jpg', 'receipt_name' => 'legacy.jpg',
            'student_confirmed' => true, 'verification_status' => 'verified',
        ]);
        $this->assertSame('0.00', (string) $activity->refresh()->implemented_budget);
        $this->assertSame(300.0, $this->ledger->balance($account)['spent']);

        $this->assertRejected('unit_cost', fn () => $this->receipt($activity, ['quantity' => 1, 'unit_cost' => '200.01']));
        $this->assertSame(1, ExpenseReceiptReview::where('org_activity_id', $activity->id)->count());

        $this->receipt($activity, ['quantity' => 1, 'unit_cost' => '200.00']);
        $this->assertSame('500.00', (string) $activity->refresh()->implemented_budget);
        $this->assertSame('500.00', (string) BudgetItem::where('org_activity_id', $activity->id)->value('utilized'));
        $balance = $this->ledger->balance($account);
        $this->assertSame(500.0, $balance['spent']);
        $this->assertSame(0.0, $balance['reserved']);
        $this->assertSame(500.0, $balance['cash']);
        $this->assertRejected('unit_cost', fn () => $this->receipt($activity, ['quantity' => 1, 'unit_cost' => '0.01']));
    }

    public function test_cash_flow_periods_are_per_account_and_isolated_by_organization(): void
    {
        $this->ledger->saveOpening($this->so, '2025-2026', '300.00');
        $this->ledger->saveOpening($this->so, self::YEAR, '1000.00');
        $this->ledger->saveOpening($this->foreignSo, self::YEAR, '9000.00');
        $this->ledger->recordIncome($this->foreignSo, $this->income(['amount' => '700.00']));
        $this->ledger->recordIncome($this->so, $this->income([
            'academic_year' => '2025-2026', 'transaction_date' => '2026-02-10', 'amount' => '45.50', 'reference' => 'CR-OLD',
        ]));
        $activity = $this->approvedActivity('400.00');
        $this->ledger->recordIncome($this->so, $this->income([
            'transaction_date' => '2026-09-01', 'amount' => '200.00', 'purpose' => 'Ticket sales', 'org_activity_id' => $activity->id,
        ]));
        $this->receipt($activity, ['quantity' => 1, 'unit_cost' => '150.25', 'expense_date' => '2026-10-05']);

        $all = $this->ledger->cashFlow($this->organization->name, ['academic_year' => 'all', 'semester' => 'all']);
        $this->assertSame([self::YEAR, '2025-2026'], $all['years']);
        $this->assertSame(1300.0, $all['beginning_balance']);
        $this->assertSame(245.5, $all['cash_inflow']);
        $this->assertSame(150.25, $all['cash_outflow']);
        $this->assertSame(1395.25, $all['ending_balance']);
        $this->assertCount(6, $all['selected']);
        $activities = collect($all['activities'])->keyBy('name');
        $this->assertSame(45.5, $activities[OrganizationCashLedger::GENERAL_FUND]['inflow']);
        $this->assertSame(200.0, $activities[$activity->title]['inflow']);
        $this->assertSame(150.25, $activities[$activity->title]['outflow']);
        $this->assertSame(24550, (int) round(collect($all['activities'])->sum('inflow') * 100));
        $this->assertSame(24550, (int) round($all['selected']->sum(fn (array $entry): float => $entry['summary']['cash_inflow']) * 100));
        $this->assertSame(15025, (int) round($all['selected']->sum(fn (array $entry): float => $entry['summary']['cash_outflow']) * 100));

        $second = $this->ledger->cashFlow($this->organization->name, ['academic_year' => '2025-2026', 'semester' => '2nd Semester']);
        $this->assertSame(300.0, $second['beginning_balance']);
        $this->assertSame(45.5, $second['cash_inflow']);
        $this->assertSame(345.5, $second['ending_balance']);
        $this->assertSame('CR-OLD', $second['selected']->sole()['summary']['receipts'][0]['reference']);

        $midyear = $this->ledger->cashFlow($this->organization->name, ['academic_year' => '2025-2026', 'semester' => 'Midyear']);
        $this->assertSame(345.5, $midyear['beginning_balance']);
        $this->assertSame(0.0, $midyear['cash_inflow']);
        $this->assertSame(345.5, $midyear['ending_balance']);

        $firstSemesters = $this->ledger->cashFlow($this->organization->name, ['academic_year' => 'all', 'semester' => '1st Semester']);
        $this->assertSame(1300.0, $firstSemesters['beginning_balance']);
        $this->assertSame(200.0, $firstSemesters['cash_inflow']);
        $this->assertSame(150.25, $firstSemesters['cash_outflow']);
        $this->assertSame(1349.75, $firstSemesters['ending_balance']);
        $this->assertSame(['2025-2026', self::YEAR], $firstSemesters['selected']->pluck('academic_year')->all());

        $this->assertSame(345.5, $this->ledger->funding($this->organization->name, '2025-2026')['cash']);
        $current = $this->ledger->funding($this->organization->name, self::YEAR);
        $this->assertSame(1049.75, $current['cash']);
        $this->assertSame(249.75, $current['reserved']);
        $this->assertSame(800.0, $current['available']);
        $this->assertSame([
            'academic_year' => '2027-2028', 'has_account' => false, 'opening_balance' => 0.0, 'income' => 0.0,
            'spent' => 0.0, 'allocated' => 0.0, 'reserved' => 0.0, 'cash' => 0.0, 'available' => 0.0,
        ], $this->ledger->funding($this->organization->name, '2027-2028'));
        $this->assertSame(9700.0, $this->ledger->funding($this->foreignOrganization->name, self::YEAR)['cash']);
        $this->assertSame([$activity->id], $this->ledger->incomeActivities($this->organization->name, self::YEAR)->pluck('id')->all());

        $empty = $this->ledger->cashFlow($this->organizationFixture('Empty Cash Ledger Fixture')->name, ['academic_year' => 'all', 'semester' => 'all']);
        $this->assertSame([], $empty['years']);
        $this->assertTrue($empty['selected']->isEmpty());
        $this->assertSame(0.0, $empty['ending_balance']);
    }

    public function test_ledger_reads_execute_no_writes(): void
    {
        $account = $this->ledger->saveOpening($this->so, self::YEAR, '700.00');
        $activity = $this->approvedActivity('300.00');
        $this->ledger->recordIncome($this->so, $this->income(['amount' => '12.34', 'org_activity_id' => $activity->id]));
        $this->receipt($activity, ['quantity' => 2, 'unit_cost' => '10.01']);
        $updatedAt = (string) $account->fresh()->updated_at;

        $statements = [];
        $recording = true;
        DB::listen(function (QueryExecuted $query) use (&$statements, &$recording): void {
            if ($recording) {
                $statements[] = $query->sql;
            }
        });
        $this->snapshotReads($account);
        $this->ledger->incomeActivities($this->organization->name, self::YEAR);
        $this->budgets->financial($this->organization->name, self::YEAR, '1st Semester');
        $recording = false;

        $this->assertNotEmpty($statements);
        $this->assertSame([], array_values(array_filter($statements, fn (string $sql): bool => ! preg_match('/^\s*select\b/i', $sql))));
        $this->assertSame($updatedAt, (string) $account->fresh()->updated_at);
    }

    private function organizationFixture(string $prefix): StudentOrganization
    {
        return StudentOrganization::create([
            'name' => $prefix.' '.Str::uuid(),
            'college' => 'Cash Ledger Test College',
            'is_active' => true,
        ]);
    }

    private function office(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        return OfficeUser::create([
            'name' => 'Cash Ledger Fixture '.strtoupper($role),
            'email' => Str::uuid().'@example.test',
            'username' => 'cash_ledger_'.Str::random(16),
            'password' => Str::random(32),
            'office_title' => 'Cash ledger fixture desk',
            'office_role' => $role,
            'student_organization_id' => $organization?->id,
            'is_active' => true,
        ]);
    }

    private function pendingActivity(string $budget, ?StudentOrganization $organization = null): OrgActivity
    {
        return OrgActivity::create([
            'title' => 'Cash Ledger Activity '.Str::uuid(),
            'organization_name' => ($organization ?? $this->organization)->name,
            'workflow_status' => 'oc_review',
            'status' => 'draft',
            'starts_at' => '2026-11-10 09:00:00',
            'ends_at' => '2026-11-10 17:00:00',
            'approved_budget' => $budget,
        ]);
    }

    private function approvedActivity(string $budget, ?StudentOrganization $organization = null): OrgActivity
    {
        $activity = $this->pendingActivity($budget, $organization);
        $this->actingAs($this->oc, 'office');
        app(OrgWorkflowService::class)->advance($activity, 'oc');

        return $activity->refresh();
    }

    private function assertApprovalRejected(OrgActivity $activity, string $message): void
    {
        $this->actingAs($this->oc, 'office');
        try {
            app(OrgWorkflowService::class)->advance($activity, 'oc');
            $this->fail('Final approval should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($message, $exception->errors()['budget'][0]);
        }
        $activity->refresh();
        $this->assertSame('oc_review', $activity->workflow_status);
        $this->assertNull($activity->org_fund_account_id);
        $this->assertNull($activity->approved_at);
        $this->assertFalse(BudgetItem::where('org_activity_id', $activity->id)->exists());
        $this->assertSame(0, DB::connection('mysql')->table('activity_workflow_events')->where('org_activity_id', $activity->id)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function income(array $override = []): array
    {
        return array_merge([
            'academic_year' => self::YEAR,
            'transaction_date' => '2026-10-01',
            'amount' => '100.00',
            'purpose' => 'Membership fees',
            'received_from' => 'Organization members',
            'reference' => 'CR-'.Str::uuid(),
            'request_key' => (string) Str::uuid(),
        ], $override);
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(OrgActivity $activity, array $override = []): array
    {
        return array_merge([
            'org_activity_id' => $activity->id, 'request_key' => (string) Str::uuid(),
            'item_name' => 'Printed learning kits', 'category' => 'Supplies', 'supplier' => 'Test supplier',
            'quantity' => 1, 'unit_cost' => '123.45', 'expense_date' => '2026-10-01',
            'receipt_reference' => 'OR-'.Str::uuid(), 'receipt_type' => 'paper_receipt', 'payment_method' => 'cash',
        ], $override);
    }

    private function receipt(OrgActivity $activity, array $override = []): ExpenseReceiptReview
    {
        return $this->budgets->record(
            $activity,
            $this->so,
            $this->receiptData($activity, $override),
            UploadedFile::fake()->image('Receipt '.Str::random(8).'.jpg')
        );
    }

    private function assertRejected(string $field, Closure $write): void
    {
        try {
            $write();
            $this->fail('Expected a validation error for '.$field.'.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotReads(OrgFundAccount $account): array
    {
        $reads = ['balance' => $this->ledger->balance($account), 'funding' => $this->ledger->funding($this->organization->name, self::YEAR)];
        foreach ([['all', 'all'], [self::YEAR, 'all'], [self::YEAR, '1st Semester'], ['all', 'Midyear']] as [$year, $semester]) {
            $flow = $this->ledger->cashFlow($this->organization->name, ['academic_year' => $year, 'semester' => $semester]);
            $reads[$year.'|'.$semester] = ['selected' => $flow['selected']->all()] + $flow;
        }

        return $reads;
    }
}

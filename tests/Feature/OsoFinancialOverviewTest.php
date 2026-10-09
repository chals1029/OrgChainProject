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
use App\Services\OsoFinancialOverviewService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OsoFinancialOverviewTest extends TestCase
{
    use UsesLaragonDatabase;

    private const YEAR = '2030-2031';

    private const PREVIOUS_YEAR = '2029-2030';

    private StudentOrganization $first;

    private StudentOrganization $second;

    private OsoFinancialOverviewService $overview;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Carbon::setTestNow('2030-10-15 10:00:00');

        $suffix = (string) Str::uuid();
        $this->first = $this->organization('Overview First '.$suffix, 'Overview College A '.$suffix);
        $this->second = $this->organization('Overview Second '.$suffix, 'Overview College B '.$suffix);
        $this->overview = app(OsoFinancialOverviewService::class);
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

    public function test_annual_overview_uses_real_cash_and_separate_final_approved_budgets(): void
    {
        $fixtures = $this->moneyFixtures();
        $result = $this->scopedOverview();
        $rows = collect($result['rows'])->keyBy('organization');

        $this->assertSame(self::YEAR, $result['academic_year']);
        $this->assertSame('Annual', $result['semester']);
        $this->assertCount(2, $rows);
        $this->assertSame($this->first->college, $rows[$this->first->name]['college']);
        $this->assertTrue($rows[$this->first->name]['has_account']);
        $this->assertTrue($rows[$this->second->name]['has_account']);
        $this->assertMoney([1000.10, 123.65, 250.15, 873.60, 600.30], $rows[$this->first->name]);
        $this->assertMoney([500.20, 0.30, 50.10, 450.40, 200.40], $rows[$this->second->name]);
        $this->assertTotals([1500.30, 123.95, 300.25, 1324.0, 800.70], 2, $result);
        $this->assertSame([], $result['transactions']);
        $this->assertContains(self::PREVIOUS_YEAR, $result['years']);
        $this->assertContains(self::YEAR, $result['years']);

        // Reading does not reconcile receipt caches or rewrite duplicate accounts.
        $this->assertSame('250.15', (string) $fixtures['first_activity']->fresh()->implemented_budget);
        $this->assertSame('0.00', (string) $fixtures['second_activity']->fresh()->implemented_budget);
        $this->assertSame('9000.00', (string) $fixtures['duplicate']->fresh()->cash_opening_balance);
    }

    public function test_semester_rows_carry_prior_cash_but_not_prior_activity_approvals(): void
    {
        $account = $this->account($this->first, '100.10');
        $this->account($this->second, '50.20');
        $this->income($account, '10.20', '2030-08-01');
        $this->income($account, '20.30', '2031-01-01');
        $this->income($account, '30.40', '2031-06-01');

        $firstStart = $this->activity($this->first, '2030-08-01 00:00:00', '11.01');
        $firstEnd = $this->activity($this->first, '2030-12-31 23:59:59', '12.02');
        $secondStart = $this->activity($this->first, '2031-01-01 00:00:00', '21.03');
        $secondEnd = $this->activity($this->first, '2031-05-31 23:59:59', '22.04');
        $midyearStart = $this->activity($this->first, '2031-06-01 00:00:00', '31.05');
        $midyearEnd = $this->activity($this->first, '2031-07-31 23:59:59', '32.06');
        $this->activity($this->first, '2030-07-31 23:59:59', '7000.00');
        $this->activity($this->first, '2031-08-01 00:00:00', '8000.00');
        $this->activity($this->second, '2031-06-01 00:00:00', '7.07');

        $this->receipt($firstStart, '1.01', '2030-08-01');
        $this->receipt($firstEnd, '2.02', '2030-12-31');
        // Cash follows expense_date, not the activity's approval/start semester.
        $this->receipt($firstEnd, '3.03', '2031-01-01');
        $this->receipt($secondStart, '4.04', '2031-01-01');
        $this->receipt($secondEnd, '5.05', '2031-05-31');
        $this->receipt($midyearStart, '6.06', '2031-06-01');
        $this->receipt($midyearEnd, '7.07', '2031-07-31');

        foreach ([
            ['1st Semester', [100.10, 10.20, 3.03, 107.27, 23.03], [150.30, 10.20, 3.03, 157.47, 23.03]],
            ['2nd Semester', [107.27, 20.30, 12.12, 115.45, 43.07], [157.47, 20.30, 12.12, 165.65, 43.07]],
            ['Midyear', [115.45, 30.40, 13.13, 132.72, 63.11], [165.65, 30.40, 13.13, 182.92, 70.18]],
        ] as [$semester, $firstMoney, $totals]) {
            $result = $this->scopedOverview($semester);
            $rows = collect($result['rows'])->keyBy('organization');
            $this->assertSame($semester, $result['semester']);
            $this->assertCount(2, $rows);
            $this->assertMoney($firstMoney, $rows[$this->first->name]);
            $this->assertMoney([50.20, 0.0, 0.0, 50.20, $semester === 'Midyear' ? 7.07 : 0.0], $rows[$this->second->name]);
            $this->assertTotals($totals, 2, $result);
        }
    }

    public function test_directory_includes_zero_missing_and_historical_organizations_without_example_money(): void
    {
        $this->activity($this->first, '2030-09-01 09:00:00', '42.25');
        $this->account($this->second, '0.00');
        $inactive = $this->organization('Overview Inactive '.Str::uuid(), $this->first->college, false);
        $this->activity($inactive, '2030-01-01 09:00:00', '99.00');
        $this->account($inactive, '90.00', self::PREVIOUS_YEAR);
        $accountOnly = 'Overview Account Only '.Str::uuid();
        OrgFundAccount::create([
            'organization_name' => $accountOnly, 'college' => $this->first->college,
            'fiscal_year' => self::YEAR, 'cash_opening_balance' => '17.30', 'total_funds' => 17,
        ]);
        OrgFundAccount::create([
            'organization_name' => $accountOnly, 'college' => $this->first->college,
            'fiscal_year' => '2028-2030', 'cash_opening_balance' => '99999.00', 'total_funds' => 99999,
        ]);
        $activityOnly = 'Overview Activity Only '.Str::uuid();
        OrgActivity::create([
            'title' => 'Overview Historical Activity '.Str::uuid(), 'organization_name' => $activityOnly,
            'college' => $this->first->college, 'workflow_status' => 'oc_approved', 'status' => 'completed',
            'starts_at' => '2030-10-01 09:00:00', 'approved_budget' => '9.75', 'implemented_budget' => '0.00',
        ]);
        $pendingOnly = 'Overview Pending Only '.Str::uuid();
        OrgActivity::create([
            'title' => 'Overview Pending Activity '.Str::uuid(), 'organization_name' => $pendingOnly,
            'college' => $this->first->college, 'workflow_status' => 'oc_review', 'status' => 'draft',
            'starts_at' => '2030-10-01 09:00:00', 'approved_budget' => '99999.00',
        ]);

        $result = $this->scopedOverview();
        $rows = collect($result['rows'])->keyBy('organization');
        $this->assertCount(5, $rows);
        $this->assertFalse($rows[$this->first->name]['has_account']);
        $this->assertMoney([0.0, 0.0, 0.0, 0.0, 42.25], $rows[$this->first->name]);
        $this->assertTrue($rows[$this->second->name]['has_account']);
        $this->assertMoney([0.0, 0.0, 0.0, 0.0, 0.0], $rows[$this->second->name]);
        $this->assertFalse($rows[$inactive->name]['has_account']);
        $this->assertMoney([0.0, 0.0, 0.0, 0.0, 0.0], $rows[$inactive->name]);
        $this->assertTrue($rows[$accountOnly]['has_account']);
        $this->assertMoney([17.30, 0.0, 0.0, 17.30, 0.0], $rows[$accountOnly]);
        $this->assertFalse($rows[$activityOnly]['has_account']);
        $this->assertMoney([0.0, 0.0, 0.0, 0.0, 9.75], $rows[$activityOnly]);
        $this->assertTotals([17.30, 0.0, 0.0, 17.30, 52.0], 5, $result);
        $this->assertNotContains($pendingOnly, $result['organizations']);
        $this->assertNotContains('2028-2030', $result['years']);
        $this->assertContains(self::PREVIOUS_YEAR, $result['years']);
        foreach ([$this->first->name, $this->second->name, $inactive->name, $accountOnly, $activityOnly] as $name) {
            $this->assertSame(1, count(array_keys($result['organizations'], $name, true)));
        }

        // A requested year with no accounts still exists as a choice, with zero cash.
        $emptyYear = $this->overview->overview('2032-2033', 'Annual', $this->first->name);
        $this->assertContains('2032-2033', $emptyYear['years']);
        $this->assertFalse($emptyYear['rows'][0]['has_account']);
        $this->assertTotals([0.0, 0.0, 0.0, 0.0, 0.0], 1, $emptyYear);
    }

    public function test_organization_and_department_filters_isolate_every_total_and_keep_choices_available(): void
    {
        $this->moneyFixtures();
        $all = $this->scopedOverview();
        $organization = $this->scopedOverview('Annual', $this->first->name);
        $department = $this->overview->overview(self::YEAR, 'Annual', null, [$this->second->college]);
        $intersection = $this->overview->overview(self::YEAR, 'Annual', $this->first->name, [$this->second->college]);
        $none = $this->overview->overview(self::YEAR, 'Annual', null, []);

        $this->assertSame([$this->first->name], array_column($organization['rows'], 'organization'));
        $this->assertTotals([1000.10, 123.65, 250.15, 873.60, 600.30], 1, $organization);
        $this->assertSame([$this->second->name], array_column($department['rows'], 'organization'));
        $this->assertTotals([500.20, 0.30, 50.10, 450.40, 200.40], 1, $department);
        foreach ([$intersection, $none] as $empty) {
            $this->assertSame([], $empty['rows']);
            $this->assertSame([], $empty['transactions']);
            $this->assertTotals([0.0, 0.0, 0.0, 0.0, 0.0], 0, $empty);
        }
        foreach ([$organization, $department, $intersection, $none] as $filtered) {
            $this->assertSame($all['organizations'], $filtered['organizations']);
            $this->assertSame($all['years'], $filtered['years']);
        }
        $blank = $this->scopedOverview('Annual', '   ');
        $this->assertSame($all['rows'], $blank['rows']);
        $this->assertSame($all['totals'], $blank['totals']);
        $this->assertSame([], $blank['transactions']);

        // Null department scope permits registered rows from both private colleges.
        $unrestricted = $this->overview->overview(self::YEAR, 'Annual', null, null);
        $this->assertContains($this->first->name, array_column($unrestricted['rows'], 'organization'));
        $this->assertContains($this->second->name, array_column($unrestricted['rows'], 'organization'));
    }

    public function test_selected_organization_details_show_only_actual_selected_period_postings(): void
    {
        $fixtures = $this->moneyFixtures();
        $result = $this->scopedOverview('1st Semester', $this->first->name);
        $transactions = $result['transactions'];

        $this->assertSame([
            'INC-'.$fixtures['large_income']->id,
            'EXP-'.$fixtures['approved_receipt']->id,
            'EXP-'.$fixtures['verified_receipt']->id,
            'EXP-'.$fixtures['pending_seal_receipt']->id,
            'LEGACY-'.$fixtures['first_activity']->id,
            'INC-'.$fixtures['small_income']->id,
        ], array_column($transactions, 'ref'));
        $this->assertSame(['inflow', 'outflow', 'outflow', 'outflow', 'outflow', 'inflow'], array_column($transactions, 'direction'));
        $this->assertSame([123.45, 50.04, 40.03, 60.02, 100.06, 0.20], array_column($transactions, 'amount'));
        $this->assertSame('2030-09-01', $transactions[0]['date']);
        $this->assertSame($fixtures['large_income']->reference, $transactions[0]['reference']);
        $this->assertSame('Overview members', $transactions[0]['payee']);
        $this->assertSame($fixtures['first_activity']->title, $transactions[1]['event']);
        $this->assertSame('Supplies', $transactions[1]['account']);
        $this->assertSame('Overview item', $transactions[1]['description']);
        $this->assertSame(1.0, $transactions[1]['quantity']);
        $this->assertSame(50.04, $transactions[1]['unit_cost']);

        $second = $this->scopedOverview('2nd Semester', $this->first->name);
        $this->assertSame([], $second['transactions']);
        $this->assertTotals([873.60, 0.0, 0.0, 873.60, 0.0], 1, $second);
    }

    public function test_overview_and_details_reads_execute_no_database_writes(): void
    {
        $this->moneyFixtures();
        $statements = [];
        $recording = true;
        DB::listen(function (QueryExecuted $query) use (&$statements, &$recording): void {
            if ($recording) {
                $statements[] = $query->sql;
            }
        });
        try {
            $this->scopedOverview();
            $this->scopedOverview('Midyear', $this->first->name);
            $this->scopedOverview('Annual', $this->first->name);
        } finally {
            $recording = false;
        }

        $this->assertNotEmpty($statements);
        $this->assertSame([], array_values(array_filter($statements, fn (string $sql): bool => ! preg_match('/^\s*select\b/i', $sql))));
    }

    public function test_budget_get_exposes_overview_only_to_oso_and_preserves_the_so_organization_boundary(): void
    {
        $fixtures = $this->moneyFixtures();
        $oso = $this->office('oso');
        $so = $this->office('so', $this->first);
        $query = http_build_query([
            'academic_year' => self::YEAR, 'semester' => '1st Semester',
            'organization' => $this->first->name, 'department' => $this->first->college,
        ]);
        $response = $this->actingAs($oso, 'office')->get('/office-desk/budget-utilization?'.$query)->assertOk();
        $overview = $response->viewData('osoFinancialOverview');
        $this->assertIsArray($overview);
        $this->assertSame(self::YEAR, $overview['academic_year']);
        $this->assertSame('1st Semester', $overview['semester']);
        $this->assertSame([$this->first->name], array_column($overview['rows'], 'organization'));
        $this->assertTotals([1000.10, 123.65, 250.15, 873.60, 600.30], 1, $overview);
        $this->assertCount(6, $overview['transactions']);

        $response = $this->actingAs($so, 'office')->get('/office-desk/budget-utilization?'.http_build_query([
            'academic_year' => self::YEAR, 'organization' => $this->second->name,
        ]))->assertOk();
        $this->assertNull($response->viewData('osoFinancialOverview'));
        $this->assertSame($this->first->name, $response->viewData('selectedOrganization'));
        $choices = collect($response->viewData('approvedActivityChoices'));
        $this->assertContains($fixtures['first_activity']->title, $choices->pluck('actName')->all());
        $this->assertNotContains($fixtures['second_activity']->title, $choices->pluck('actName')->all());

        $response = $this->actingAs($this->office('sdo'), 'office')
            ->get('/office-desk/budget-utilization?'.$query)->assertOk();
        $this->assertNull($response->viewData('osoFinancialOverview'));
    }

    public function test_print_statement_renders_the_selected_organization_and_semester_cash(): void
    {
        $this->moneyFixtures();
        $query = http_build_query([
            'academic_year' => self::YEAR,
            'semester' => '2nd Semester',
            'organization' => $this->first->name,
            'department' => $this->first->college,
        ]);
        $response = $this->actingAs($this->office('oso'), 'office')
            ->get('/office-desk/budget-utilization/print?'.$query)->assertOk();

        $document = new \DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new \DOMXPath($document);
        $amounts = [];
        foreach ($xpath->query('//section[@aria-label="Organization cash summary"]//strong') as $node) {
            $amounts[] = (float) preg_replace('/[^\d.-]/', '', $node->textContent);
        }
        $this->assertSame([873.60, 0.0, 0.0, 873.60], $amounts);
        $rows = $xpath->query('//table[@aria-label="Organization cash breakdown"]/tbody/tr');
        $this->assertSame(1, $rows->length);
        $this->assertStringContainsString($this->first->name, $rows->item(0)->textContent);
        $this->assertStringNotContainsString($this->second->name, $rows->item(0)->textContent);
    }

    private function organization(string $name, string $college, bool $active = true): StudentOrganization
    {
        return StudentOrganization::create(['name' => $name, 'college' => $college, 'is_active' => $active]);
    }

    private function office(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        return OfficeUser::create([
            'name' => 'Overview Fixture '.strtoupper($role), 'email' => Str::uuid().'@example.test',
            'username' => 'overview_'.Str::random(16), 'password' => Str::random(32),
            'office_title' => 'Overview fixture desk', 'office_role' => $role,
            'student_organization_id' => $organization?->id, 'is_active' => true,
        ]);
    }

    private function account(StudentOrganization $organization, string $opening, string $year = self::YEAR): OrgFundAccount
    {
        return OrgFundAccount::create([
            'organization_name' => $organization->name, 'college' => $organization->college,
            'fiscal_year' => $year, 'cash_opening_balance' => $opening, 'total_funds' => 999999,
            'beginning_balance' => 888888, 'total_funds_received' => 777777,
        ]);
    }

    private function income(OrgFundAccount $account, string $amount, string $date): OrgCashIncome
    {
        return OrgCashIncome::create([
            'org_fund_account_id' => $account->id, 'organization_name' => $account->organization_name,
            'transaction_date' => $date, 'amount' => $amount, 'purpose' => 'Overview membership fees',
            'received_from' => 'Overview members', 'reference' => 'CR-'.Str::uuid(), 'request_key' => (string) Str::uuid(),
        ]);
    }

    private function activity(StudentOrganization $organization, string $date, string $budget, string $implemented = '0.00', string $workflow = 'oc_approved'): OrgActivity
    {
        return OrgActivity::create([
            'title' => 'Overview Activity '.Str::uuid(), 'organization_name' => $organization->name,
            'college' => $organization->college, 'workflow_status' => $workflow, 'status' => 'upcoming',
            'starts_at' => $date, 'approved_budget' => $budget, 'implemented_budget' => $implemented,
        ]);
    }

    private function receipt(OrgActivity $activity, string $cost, string $date, string $status = 'verified', int $quantity = 1): ExpenseReceiptReview
    {
        return ExpenseReceiptReview::create([
            'org_activity_id' => $activity->id, 'activity_title' => $activity->title,
            'organization_name' => $activity->organization_name, 'item_name' => 'Overview item',
            'category' => 'Supplies', 'supplier' => 'Overview supplier', 'quantity' => $quantity,
            'unit_cost' => $cost, 'expense_date' => $date, 'receipt_reference' => 'OR-'.Str::uuid(),
            'request_key' => (string) Str::uuid(), 'receipt_path' => 'expense-receipts/overview-fixture.jpg',
            'receipt_name' => 'overview-fixture.jpg', 'student_confirmed' => true, 'verification_status' => $status,
        ]);
    }

    private function moneyFixtures(): array
    {
        $firstAccount = $this->account($this->first, '1000.10');
        $secondAccount = $this->account($this->second, '500.20');
        $smallIncome = $this->income($firstAccount, '0.20', '2030-08-01');
        $largeIncome = $this->income($firstAccount, '123.45', '2030-09-01');
        $this->income($secondAccount, '0.30', '2030-09-01');
        $firstActivity = $this->activity($this->first, '2030-08-02 09:00:00', '600.30', '250.15');
        $secondActivity = $this->activity($this->second, '2030-08-02 09:00:00', '200.40');
        $pendingSealReceipt = $this->receipt($firstActivity, '30.01', '2030-08-03', 'pending_seal', 2);
        $verifiedReceipt = $this->receipt($firstActivity, '40.03', '2030-08-04');
        $approvedReceipt = $this->receipt($firstActivity, '50.04', '2030-08-05', 'approved');
        $this->receipt($secondActivity, '25.05', '2030-08-03', 'verified', 2);
        $this->receipt($firstActivity, '8888.00', '2030-08-06', 'rejected');
        $this->receipt($firstActivity, '7777.00', '2030-08-07', 'ready_for_review');
        $pending = $this->activity($this->first, '2030-09-01 09:00:00', '9999.00', '0.00', 'oc_review');
        $this->receipt($pending, '6666.00', '2030-09-01');
        $this->activity($this->first, '2030-09-02 09:00:00', '5555.00', '0.00', 'returned');

        $prior = $this->account($this->first, '3333.00', self::PREVIOUS_YEAR);
        $this->income($prior, '2222.00', '2030-01-01');
        $priorActivity = $this->activity($this->first, '2030-01-02 09:00:00', '1111.00', '111.00');
        $this->receipt($priorActivity, '111.00', '2030-01-03');
        $duplicate = $this->account($this->first, '9000.00');
        $this->income($duplicate, '8000.00', '2030-09-01');

        OrgFundSource::create(['org_fund_account_id' => $firstAccount->id, 'category' => 'ssc_fee', 'label' => 'Legacy already included', 'amount' => 750000]);
        BudgetItem::create([
            'org_activity_id' => $firstActivity->id, 'title' => $firstActivity->title, 'category' => 'Activity',
            'college' => $this->first->college, 'organization_name' => $this->first->name,
            'allocated' => '600.30', 'utilized' => '250.15', 'fiscal_year' => self::YEAR, 'is_approved' => true,
        ]);
        $report = OrgReportStatus::create([
            'report_type' => 'fr', 'organization_name' => $this->first->name, 'college' => $this->first->college,
            'semester' => '1st Semester', 'academic_year' => self::YEAR, 'status' => 'draft', 'batch_key' => (string) Str::uuid(),
        ]);
        OrgReportDocument::create([
            'org_report_status_id' => $report->id, 'report_type' => 'fr', 'organization_name' => $this->first->name,
            'semester' => '1st Semester', 'academic_year' => self::YEAR, 'name' => 'Overview unrelated workbook',
            'original_name' => 'overview.xlsx', 'file_path' => 'semester-reports/fr/overview-fixture.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'file_size' => 1024,
            'uploaded_by' => 'Overview fixture', 'financial_summary' => [
                'beginning_balance' => 99999.0, 'cash_inflow' => 5000.0, 'cash_outflow' => 1234.0, 'ending_balance' => 103765.0,
            ],
        ]);

        return [
            'first_activity' => $firstActivity, 'second_activity' => $secondActivity, 'duplicate' => $duplicate,
            'small_income' => $smallIncome, 'large_income' => $largeIncome,
            'pending_seal_receipt' => $pendingSealReceipt, 'verified_receipt' => $verifiedReceipt, 'approved_receipt' => $approvedReceipt,
        ];
    }

    private function scopedOverview(string $semester = 'Annual', ?string $organization = null): array
    {
        return $this->overview->overview(self::YEAR, $semester, $organization, [$this->first->college, $this->second->college]);
    }

    private function assertMoney(array $expected, array $actual): void
    {
        foreach (['beginning_balance', 'cash_inflow', 'cash_outflow', 'ending_balance', 'approved_budget'] as $index => $key) {
            $this->assertSame($expected[$index], $actual[$key], $key);
        }
    }

    private function assertTotals(array $money, int $count, array $result): void
    {
        $this->assertMoney($money, $result['totals']);
        $this->assertSame($count, $result['totals']['organization_count']);
    }
}

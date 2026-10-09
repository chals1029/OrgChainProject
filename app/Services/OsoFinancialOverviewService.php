<?php

namespace App\Services;

use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\StudentOrganization;
use Carbon\Carbon;

/** Read-only institutional cash statements with independent approved budgets. */
class OsoFinancialOverviewService
{
    private const MONEY_FIELDS = [
        'beginning_balance', 'cash_inflow', 'cash_outflow', 'ending_balance', 'approved_budget',
    ];

    public function __construct(
        private readonly ActivityBudgetService $budgets,
        private readonly OrganizationCashLedger $ledger,
    ) {}

    /**
     * Choices include historical data regardless of the current row filters.
     * All aggregation stays in cents until the presentation boundary.
     *
     * @param  list<string>|null  $departmentValues  null selects all; [] selects none
     * @return array{academic_year: string, semester: string, years: list<string>, organizations: list<string>, rows: list<array>, totals: array, transactions: list<array>}
     */
    public function overview(
        string $academicYear,
        string $semester = 'Annual',
        ?string $organization = null,
        ?array $departmentValues = null,
    ): array {
        [$from, $to] = $this->budgets->dates($academicYear, $semester);
        $organization = trim((string) $organization);
        $directory = [];
        $years = [$academicYear => true];
        $accountYears = [];

        // Registered metadata takes precedence, including inactive organizations.
        foreach (StudentOrganization::on('mysql')->orderBy('id')->get(['name', 'college']) as $registered) {
            $name = (string) $registered->name;
            if (trim($name) !== '') {
                $directory[$name] = trim((string) $registered->college);
            }
        }

        // Match the ledger: invalid years are ignored and the oldest duplicate
        // org/year account is canonical. Merely listing an account never writes it.
        foreach (OrgFundAccount::query()->orderBy('id')->get(['organization_name', 'college', 'fiscal_year']) as $account) {
            $name = (string) $account->organization_name;
            $year = (string) $account->fiscal_year;
            if (trim($name) === '' || ! $this->isAcademicYear($year) || isset($accountYears[$name][$year])) {
                continue;
            }
            $accountYears[$name][$year] = true;
            $years[$year] = true;
            if (! isset($directory[$name]) || $directory[$name] === '') {
                $directory[$name] = trim((string) $account->college);
            }
        }

        // Collapse historical activities in the database instead of loading every
        // activity and its budget merely to discover names and available years.
        $activityYear = 'YEAR(starts_at) - CASE WHEN MONTH(starts_at) < 8 THEN 1 ELSE 0 END';
        $history = OrgActivity::query()->where('workflow_status', 'oc_approved')
            ->select(['organization_name', 'college'])
            ->selectRaw($activityYear.' AS start_year')
            ->groupBy('organization_name', 'college', 'start_year')
            ->orderByRaw('MIN(id)')->get();
        foreach ($history as $activity) {
            $name = (string) $activity->organization_name;
            if (trim($name) === '') {
                continue;
            }
            if (! isset($directory[$name]) || $directory[$name] === '') {
                $directory[$name] = trim((string) $activity->college);
            }
            if ($activity->start_year !== null) {
                $start = (int) $activity->start_year;
                $year = $start.'-'.($start + 1);
                if ($this->isAcademicYear($year)) {
                    $years[$year] = true;
                }
            }
        }

        $organizations = array_map('strval', array_keys($directory));
        usort($organizations, fn (string $a, string $b): int => strcasecmp($a, $b) ?: strcmp($a, $b));
        $years = array_keys($years);
        rsort($years, SORT_STRING);
        $scopedNames = array_values(array_filter($organizations, fn (string $name): bool =>
            ($organization === '' || $name === $organization)
            && ($departmentValues === null || in_array($directory[$name], $departmentValues, true))
        ));

        // These allocations are not cash: no BudgetItem mirrors, reservations,
        // workbook amounts, or account linkage affect this independent sum.
        $approvedBudgets = $scopedNames === [] ? [] : OrgActivity::query()
            ->where('workflow_status', 'oc_approved')
            ->whereIn('organization_name', $scopedNames)
            ->where('starts_at', '>=', $from.' 00:00:00')
            ->where('starts_at', '<', Carbon::parse($to)->addDay()->toDateString().' 00:00:00')
            ->groupBy('organization_name')
            ->selectRaw('organization_name, SUM(approved_budget) AS approved_total')
            ->pluck('approved_total', 'organization_name')->all();
        $statements = $this->ledger->cashFlows($scopedNames, [
            'academic_year' => $academicYear,
            'semester' => $semester === 'Annual' ? 'all' : $semester,
        ]);

        $rows = [];
        $totals = array_fill_keys(self::MONEY_FIELDS, 0);
        foreach ($scopedNames as $name) {
            $statement = $statements[$name];
            $row = [
                'organization' => $name,
                'college' => $directory[$name],
                'has_account' => isset($accountYears[$name][$academicYear]),
            ];
            foreach (self::MONEY_FIELDS as $field) {
                $cents = OrganizationCashLedger::cents($field === 'approved_budget'
                    ? ($approvedBudgets[$name] ?? 0)
                    : $statement[$field]);
                $totals[$field] += $cents;
                $row[$field] = OrganizationCashLedger::money($cents);
            }
            $rows[] = $row;
        }
        foreach (self::MONEY_FIELDS as $field) {
            $totals[$field] = OrganizationCashLedger::money($totals[$field]);
        }
        $totals['organization_count'] = count($rows);

        $transactions = [];
        if ($organization !== '' && isset($statements[$organization])) {
            foreach ($statements[$organization]['selected'] as $period) {
                foreach (['receipts' => 'inflow', 'disbursements' => 'outflow'] as $key => $direction) {
                    foreach ($period['summary'][$key] as $transaction) {
                        $transactions[] = $transaction + ['direction' => $direction];
                    }
                }
            }
            usort($transactions, fn (array $a, array $b): int =>
                strcmp((string) $b['date'], (string) $a['date'])
                ?: strcmp((string) $a['ref'], (string) $b['ref'])
            );
        }

        return [
            'academic_year' => $academicYear,
            'semester' => $semester,
            'years' => $years,
            'organizations' => $organizations,
            'rows' => $rows,
            'totals' => $totals,
            'transactions' => $transactions,
        ];
    }

    private function isAcademicYear(string $year): bool
    {
        return preg_match('/^(\d{4})-(\d{4})$/', $year, $match) === 1
            && (int) $match[2] === (int) $match[1] + 1;
    }
}

<?php

namespace Database\Seeders;

use App\Models\ActivityComplianceDoc;
use App\Models\BudgetItem;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OrgFundSource;
use App\Models\StudentOrganization;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrgSampleDataSeeder extends Seeder
{
    /**
     * Gives every recognized org (AY 2025-2026) sample backend data:
     * fund account + sources, one activity, matching budget items,
     * and compliance docs. Idempotent — orgs that already have
     * activities are left untouched.
     */
    public function run(): void
    {
        $this->unifyOrgNames();

        $orgs = StudentOrganization::active()->orderBy('id')->get();
        $statuses = ['oc_approved', 'oso_review', 'sdo_review', 'college_review', 'ovcaa_review', 'created'];
        $secondTitles = ['Leadership Skills Workshop', 'Community Outreach Day', 'General Assembly 2026'];
        $itemCats = ['Programs', 'Supplies & Materials', 'Food & Catering', 'Equipment & Logistics'];

        foreach ($orgs as $index => $org) {
            if (OrgActivity::where('organization_name', $org->name)->exists()) {
                continue;
            }

            $isOff = in_array($org->short_name, ['Red Cross Youth', 'CYC', 'PCG'], true);
            $scope = $isOff ? 'local_off_campus' : 'in_campus';
            $status = $statuses[$index % count($statuses)];
            $allocated = 12000 + (($index * 3700) % 28000);
            $utilized = (int) round($allocated * [1.0, 0.57, 0.59, 0.36, 0.67, 0.25][$index % 6]);

            $title = $org->short_name.' '.($secondTitles[$index % 3]);
            $activity = OrgActivity::firstOrCreate(
                ['title' => $title],
                [
                    'description' => 'Sample encoded activity for '.$org->name.' (AY 2025-2026).',
                    'status' => $status === 'oc_approved' ? 'completed' : 'upcoming',
                    'location' => $isOff ? 'Off-campus venue (CHED cleared)' : 'University Gymnasium',
                    'college' => $org->college,
                    'organization_name' => $org->name,
                    'workflow_status' => $status,
                    'activity_scope' => $scope,
                    'approved_budget' => $allocated,
                    'implemented_budget' => $utilized,
                    'starts_at' => Carbon::create(2026, 3 + ($index % 7), 5 + ($index % 20), 9),
                    'ends_at' => Carbon::create(2026, 3 + ($index % 7), 5 + ($index % 20), 16),
                ]
            );

            $half = (int) round($allocated / 2);
            BudgetItem::firstOrCreate(
                ['title' => $title.' Budget A', 'organization_name' => $org->name],
                [
                    'category' => $itemCats[$index % 4],
                    'college' => $org->college,
                    'scope' => $scope,
                    'allocated' => $half,
                    'utilized' => min($half, $utilized),
                    'fiscal_year' => '2025-2026',
                    'is_approved' => true,
                ]
            );
            BudgetItem::firstOrCreate(
                ['title' => $title.' Budget B', 'organization_name' => $org->name],
                [
                    'category' => $itemCats[($index + 2) % 4],
                    'college' => $org->college,
                    'scope' => $scope,
                    'allocated' => $allocated - $half,
                    'utilized' => max(0, $utilized - min($half, $utilized)),
                    'fiscal_year' => '2025-2026',
                    'is_approved' => true,
                ]
            );

            $fund = OrgFundAccount::firstOrCreate(
                ['organization_name' => $org->name],
                [
                    'college' => $org->college,
                    'total_funds' => 85000,
                    'cash_opening_balance' => 85000,
                    'beginning_balance' => 25000,
                    'total_funds_received' => 60000,
                    'fiscal_year' => '2025-2026',
                ]
            );
            OrgFundSource::firstOrCreate(
                ['org_fund_account_id' => $fund->id, 'category' => 'ssc_fee'],
                ['label' => 'SSC Fee Allocation', 'amount' => 40000]
            );
            OrgFundSource::firstOrCreate(
                ['org_fund_account_id' => $fund->id, 'category' => 'fundraising'],
                ['label' => 'Fundraising Collections', 'amount' => 20000]
            );

            ActivityComplianceDoc::firstOrCreate(
                ['org_activity_id' => $activity->id, 'doc_key' => 'project_proposal'],
                ['title' => 'Project Proposal', 'status' => 'approved']
            );
            ActivityComplianceDoc::firstOrCreate(
                ['org_activity_id' => $activity->id, 'doc_key' => 'budget_proposal'],
                ['title' => 'Budget Proposal', 'status' => $status === 'oc_approved' ? 'approved' : 'pending']
            );
        }
    }

    /**
     * Renames legacy free-text org names to the canonical registry names
     * so sample data and dropdowns share one source of truth.
     */
    private function unifyOrgNames(): void
    {
        $aliases = [
            'CICS Student Council' => 'College of Informatics and Computing Sciences Student Council (CICS-SC)',
            'CAS Organization' => 'College of Arts and Sciences Student Council (CAS SC)',
            'CHS Student Org' => 'College of Health Sciences Student Council (CHS)',
            'CCJE Society' => 'Criminology Student Organization (CSO)',
            'CTE League' => 'College of Teacher Education Council (CTEC)',
            'CABEIHM Org' => 'College of Accountancy, Business, Economics, and International Hospitality Management Student Council (CABEIHM-SC)',
            'Lab School Council' => 'Student Body Organization High School (SBO-High School)',
            'SSC Sports Committee' => 'Supreme Student Council (SSC)',
        ];

        foreach ($aliases as $old => $canonical) {
            OrgActivity::where('organization_name', $old)->update(['organization_name' => $canonical]);
            BudgetItem::where('organization_name', $old)->update(['organization_name' => $canonical]);
            OrgFundAccount::where('organization_name', $old)->update(['organization_name' => $canonical]);
            \App\Models\ExpenseReceiptReview::where('organization_name', $old)->update(['organization_name' => $canonical]);
        }

        // Legacy fund accounts use "<COLLEGE> Student Organization" names —
        // fold them into the canonical registry names (sources preserved).
        $fundAliases = [
            'CABEIHM Student Organization' => 'College of Accountancy, Business, Economics, and International Hospitality Management Student Council (CABEIHM-SC)',
            'CAS Student Organization' => 'College of Arts and Sciences Student Council (CAS SC)',
            'CCJE Student Organization' => 'Criminology Student Organization (CSO)',
            'CHS Student Organization' => 'College of Health Sciences Student Council (CHS)',
            'CICS Student Organization' => 'College of Informatics and Computing Sciences Student Council (CICS-SC)',
            'CTE Student Organization' => 'College of Teacher Education Council (CTEC)',
            'LAB Student Organization' => 'Student Body Organization High School (SBO-High School)',
        ];
        foreach ($fundAliases as $old => $canonical) {
            OrgFundAccount::where('organization_name', $old)->update(['organization_name' => $canonical]);
        }

        // Backfill fund accounts + sources for any registry org still missing one.
        foreach (StudentOrganization::active()->get() as $org) {
            $fund = OrgFundAccount::firstOrCreate(
                ['organization_name' => $org->name],
                [
                    'college' => $org->college,
                    'total_funds' => 85000,
                    'cash_opening_balance' => 85000,
                    'beginning_balance' => 25000,
                    'total_funds_received' => 60000,
                    'fiscal_year' => '2025-2026',
                ]
            );
            OrgFundSource::firstOrCreate(
                ['org_fund_account_id' => $fund->id, 'category' => 'ssc_fee'],
                ['label' => 'SSC Fee Allocation', 'amount' => 40000]
            );
            OrgFundSource::firstOrCreate(
                ['org_fund_account_id' => $fund->id, 'category' => 'fundraising'],
                ['label' => 'Fundraising Collections', 'amount' => 20000]
            );
        }
    }
}

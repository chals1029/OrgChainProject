<?php

namespace Database\Seeders;

use App\Models\ExpenseReceiptReview;
use App\Models\OrgActivity;
use App\Services\BudgetChainService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BudgetUtilizationDemoSeeder extends Seeder
{
    /**
     * Seeds receipt-review rows for the budget page without duplicating them
     * when the demo database is refreshed or the seeder is run again.
     */
    public function run(): void
    {
        $receipts = [
            ['activity' => 'Innovation Fair Booth Series', 'item' => 'Stage audio and speaker rental', 'supplier' => 'BatStateU Events & Technical Services', 'category' => 'Equipment & Logistics', 'quantity' => 1, 'unit_cost' => 8000, 'date' => '2026-07-03', 'reference' => 'OR-CICS-0703-001', 'file' => 'CICS_Audio_OR0703.pdf'],
            ['activity' => 'Innovation Fair Booth Series', 'item' => 'Printed exhibition backdrops', 'supplier' => 'Campus Print Hub', 'category' => 'Supplies & Materials', 'quantity' => 5, 'unit_cost' => 900, 'date' => '2026-07-02', 'reference' => 'OR-CICS-0702-002', 'file' => 'CICS_Backdrops_OR0702.pdf'],
            ['activity' => 'Innovation Fair Booth Series', 'item' => 'Facilitator meals and bottled water', 'supplier' => 'University Cooperative Canteen', 'category' => 'Food & Catering', 'quantity' => 50, 'unit_cost' => 46, 'date' => '2026-07-04', 'reference' => 'OR-CICS-0704-003', 'file' => 'CICS_Catering_OR0704.pdf'],
            ['activity' => 'Leadership Summit 2026', 'item' => 'Coaster bus charter with insurance', 'supplier' => 'Batangas Transit Cooperative', 'category' => 'Transportation', 'quantity' => 2, 'unit_cost' => 9000, 'date' => '2026-08-12', 'reference' => 'OR-CAS-0812-001', 'file' => 'CAS_Transport_OR0812.pdf'],
            ['activity' => 'Leadership Summit 2026', 'item' => 'Full-board meals and lodging', 'supplier' => 'Camp Benjamin Conference Center', 'category' => 'Food & Lodging', 'quantity' => 45, 'unit_cost' => 350, 'date' => '2026-08-13', 'reference' => 'OR-CAS-0813-002', 'file' => 'CAS_Lodging_OR0813.pdf'],
            ['activity' => 'Leadership Summit 2026', 'item' => 'Leadership workbooks and delegate kits', 'supplier' => 'South Luzon Learning Supplies', 'category' => 'Supplies & Kits', 'quantity' => 45, 'unit_cost' => 133.33, 'date' => '2026-08-10', 'reference' => 'OR-CAS-0810-003', 'file' => 'CAS_Kits_OR0810.pdf'],
            ['activity' => 'Campus Wellness Week', 'item' => 'Stage sound and clinic tent setup', 'supplier' => 'Health Events Production', 'category' => 'Equipment & Audio', 'quantity' => 1, 'unit_cost' => 14500, 'date' => '2026-09-08', 'reference' => 'OR-CHS-0908-001', 'file' => 'CHS_Setup_OR0908.pdf'],
            ['activity' => 'Campus Wellness Week', 'item' => 'Medical diagnostic consumables', 'supplier' => 'BatStateU Health Supply Office', 'category' => 'Supplies & First Aid', 'quantity' => 120, 'unit_cost' => 53.33, 'date' => '2026-09-09', 'reference' => 'OR-CHS-0909-002', 'file' => 'CHS_Medical_OR0909.pdf'],
            ['activity' => 'Criminology Outreach Caravan', 'item' => 'Community safety training materials', 'supplier' => 'Campus General Services', 'category' => 'Supplies & Materials', 'quantity' => 80, 'unit_cost' => 100, 'date' => '2026-09-15', 'reference' => 'OR-CSO-0915-001', 'file' => 'CSO_Materials_OR0915.pdf'],
            ['activity' => 'Teacher Education Literacy Drive', 'item' => 'Reading kits and classroom materials', 'supplier' => 'BatStateU Bookstore', 'category' => 'Learning Materials', 'quantity' => 120, 'unit_cost' => 100, 'date' => '2026-09-30', 'reference' => 'OR-CTEC-0930-001', 'file' => 'CTEC_ReadingKits_OR0930.pdf'],
        ];

        $chainService = app(BudgetChainService::class);
        $ledgerReady = $chainService->recentBlocks(1) !== [];

        foreach ($receipts as $receipt) {
            $activity = OrgActivity::query()->where('title', $receipt['activity'])->first();
            if (! $activity) {
                continue;
            }

            $existing = ExpenseReceiptReview::query()
                ->where('activity_title', $receipt['activity'])
                ->where('receipt_reference', $receipt['reference'])
                ->first();

            $path = 'expense-receipts/demo/'.$receipt['file'];
            Storage::disk('public')->put($path, $this->receiptPdf($receipt));
            $total = (float) $receipt['quantity'] * (float) $receipt['unit_cost'];
            $sealPayload = [
                'activity_title' => $receipt['activity'],
                'item_name' => $receipt['item'],
                'supplier' => $receipt['supplier'],
                'organization_name' => $activity->organization_name,
                'receipt_reference' => $receipt['reference'],
                'quantity' => $receipt['quantity'],
                'unit_cost' => $receipt['unit_cost'],
                'total' => $total,
                'expense_date' => $receipt['date'],
            ];

            if ($existing) {
                if (! $ledgerReady) {
                    $seal = $chainService->sealExpense($sealPayload);
                    $existing->forceFill([
                        'chain_hash' => $seal['block_hash'],
                        'previous_hash' => $seal['previous_hash'],
                        'nodes_confirmed' => $seal['nodes_confirmed'],
                    ])->save();
                }
                continue;
            }

            $seal = $chainService->sealExpense($sealPayload);

            ExpenseReceiptReview::query()->create([
                'activity_title' => $receipt['activity'],
                'item_name' => $receipt['item'],
                'supplier' => $receipt['supplier'],
                'organization_name' => $activity->organization_name,
                'category' => $receipt['category'],
                'quantity' => $receipt['quantity'],
                'unit_cost' => $receipt['unit_cost'],
                'expense_date' => $receipt['date'],
                'receipt_path' => $path,
                'receipt_name' => $receipt['file'],
                'receipt_reference' => $receipt['reference'],
                'chain_hash' => $seal['block_hash'],
                'previous_hash' => $seal['previous_hash'],
                'nodes_confirmed' => $seal['nodes_confirmed'],
                'student_confirmed' => true,
                'verification_status' => 'verified',
            ]);
        }
    }

    /**
     * Creates a small valid PDF so seeded receipt links can be opened during
     * a demo without requiring real student financial documents.
     */
    private function receiptPdf(array $receipt): string
    {
        $escape = static fn (string $value): string => str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value
        );

        $lines = [
            'OrgChain Demo Receipt',
            'Activity: '.$receipt['activity'],
            'Item: '.$receipt['item'],
            'Reference: '.$receipt['reference'],
            'Total: PHP '.number_format((float) $receipt['quantity'] * (float) $receipt['unit_cost'], 2),
        ];
        $stream = "BT\n/F1 16 Tf\n72 720 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -28 Td\n/F1 11 Tf\n";
            }
            $stream .= '('.$escape($line).") Tj\n";
        }
        $stream .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }
}

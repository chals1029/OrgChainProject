<?php

namespace Database\Seeders;

use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OrgFundSource;
use App\Models\StudentOrganization;
use App\Services\BudgetChainService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

class RealOrgDataSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            'CABEIHM-SC' => 'Business and Hospitality Leadership Forum', 'HMS' => 'Hospitality Service Skills Workshop',
            'JFINEX' => 'Personal Finance and Investment Seminar', 'JMAANC' => 'Community Marketing Workshop',
            'JPMAP' => 'People Management and Workplace Skills Forum', 'JPAMA' => 'Management Accounting Case Workshop',
            'JPIA' => 'Accounting and Financial Literacy Seminar', 'TMS' => 'Sustainable Tourism Learning Visit',
            'SIKLAB' => 'Batangan Arts and Culture Workshop', 'CAS SC' => 'Arts and Sciences Student Leadership Forum',
            'FAEO' => 'Coastal and Aquatic Conservation Activity', 'CARS' => 'Community Communication Skills Workshop',
            'APS' => 'Mental Health Awareness Seminar', 'PAFTI' => 'Food Safety and Innovation Workshop',
            'CTEC' => 'Future Educators Leadership Forum', 'AIMSS' => 'Mathematics and Science Teaching Workshop',
            'ALFEM' => 'Filipino and English Literacy Workshop', 'SPESSS' => 'Physical Education and Community History Activity',
            'LEAP' => 'Elementary Reading and Learning Workshop', 'CICS' => 'Digital Skills and Computing Workshop',
            'CSO' => 'Community Safety and Crime Prevention Seminar', 'CHS-SC' => 'Community Health Promotion Forum',
            'CHS Honor Society' => 'Health Sciences Peer Learning Workshop', 'ASCEND' => 'Nutrition and Healthy Food Workshop',
            'SBO-Elementary' => 'Elementary Student Leadership Activity', 'SBO-High School' => 'High School Student Leadership Forum',
            'SocSci' => 'Social Science and Citizenship Workshop', 'LabSchool Robotics' => 'Robotics and Coding Workshop',
            'Red Cross Youth' => 'First Aid and Disaster Preparedness Training', 'PCG' => 'Peer Support and Wellbeing Workshop',
            'SSC' => 'Student Leaders and Community Engagement Forum', 'CYC' => 'Youth Community Service Activity',
        ];
        $chain = app(BudgetChainService::class);

        $renewedOrgNames = \App\Models\OrgRenewalSubmission::where('status', 'approved')->pluck('organization_name')->all();
        $orgs = StudentOrganization::whereIn('name', $renewedOrgNames)->orderBy('id')->get();

        foreach ($orgs as $index => $org) {
            $account = OrgFundAccount::firstOrCreate(
                ['organization_name' => $org->name, 'fiscal_year' => '2026-2027'],
                ['college' => $org->college, 'beginning_balance' => 20000, 'total_funds_received' => 60000, 'cash_opening_balance' => 80000.00, 'total_funds' => 8000000]
            );
            OrgFundSource::firstOrCreate(
                ['org_fund_account_id' => $account->id, 'category' => 'ssc_fee'],
                ['label' => 'SSC Fee Allocation', 'amount' => 60000]
            );

            $scope = $index % 2 ? 'local_off_campus' : 'in_campus';
            $date = Carbon::create(2026, 8 + ($index % 4), 5 + ($index % 20), 9, 0, 0);
            $title = ($org->short_name ?: $org->name).' '.($topics[$org->short_name] ?? 'Student Development Workshop').' 2026';
            $activity = OrgActivity::firstOrCreate(
                ['organization_name' => $org->name, 'title' => $title],
                ['org_fund_account_id' => $account->id, 'college' => $org->college, 'description' => 'Student development activity organized by '.$org->name.'.',
                    'status' => 'completed', 'workflow_status' => 'oc_approved', 'activity_scope' => $scope,
                    'location' => $scope === 'in_campus' ? 'BatStateU ARASOF-Nasugbu Campus' : 'Nasugbu, Batangas',
                    'starts_at' => $date, 'ends_at' => $date->copy()->setTime(16, 0), 'approved_at' => $date,
                    'approved_budget' => 30000, 'implemented_budget' => 0]
            );
            $reqService = app(\App\Services\ActivityRequirements::class);
            $requirements = $scope === 'local_off_campus'
                ? $reqService->localOffCampusRequirements()
                : $reqService->inCampusRequirements();
            $sourceDir = $scope === 'local_off_campus'
                ? storage_path('Template/Creation Of Activties/Off Campus')
                : storage_path('Template/Creation Of Activties/In Campus Activities');
            $targetFolder = $scope === 'local_off_campus' ? 'off-campus-activities' : 'in-campus-activities';
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $attachments = [];

            foreach ($requirements as $req) {
                $key = $req['key'];
                $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $req['source_file'];
                $ext = pathinfo($req['source_file'], PATHINFO_EXTENSION) ?: 'docx';
                $targetPath = "{$targetFolder}/{$activity->id}/{$key}.{$ext}";

                if (is_file($sourceFile)) {
                    $disk->put($targetPath, file_get_contents($sourceFile));
                } else {
                    $disk->put($targetPath, "Official compliance document for {$req['title']}.");
                }

                $attachments[$key] = [
                    'path' => $targetPath,
                    'name' => $req['title'] . '.' . $ext,
                    'uploaded_at' => $date->toIso8601String(),
                ];

                \App\Models\ActivityComplianceDoc::updateOrCreate(
                    ['org_activity_id' => $activity->id, 'doc_key' => $key],
                    [
                        'title' => $req['title'],
                        'status' => 'approved',
                        'remarks' => 'Compliant and approved.',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            }

            $submission = InCampusActivitySubmission::updateOrCreate(
                ['org_activity_id' => $activity->id],
                [
                    'status' => 'submitted',
                    'activity_type' => $scope,
                    'organization_name' => $org->name,
                    'college' => $org->college,
                    'workflow_status' => 'oc_approved',
                    'submitted_at' => $date,
                    'attachments' => $attachments,
                ]
            );

            \App\Models\ActivityComplianceDoc::where('org_activity_id', $activity->id)
                ->whereNull('submission_id')
                ->update(['submission_id' => $submission->id]);

            $items = [
                ['Workshop materials and participant kits', 'National Book Store', 'Supplies & Materials', 40, 250],
                ['Participant meals and refreshments', 'Jollibee Foods Corporation', 'Food & Catering', 40, 200],
                [$scope === 'in_campus' ? 'Audio equipment rental' : 'Local transport charter', $scope === 'in_campus' ? 'RJ Sound System' : 'Batangas Transit Cooperative', 'Equipment & Logistics', 1, 3000],
            ];
            $spent = 0;
            foreach ($items as $itemIndex => [$name, $supplier, $category, $quantity, $unitCost]) {
                $reference = sprintf('REALORG-2026-%03d-%02d', $org->id, $itemIndex + 1);
                $receipt = ExpenseReceiptReview::firstOrCreate(
                    ['receipt_reference' => $reference],
                    ['org_activity_id' => $activity->id, 'activity_title' => $title, 'organization_name' => $org->name,
                        'item_name' => $name, 'supplier' => $supplier, 'category' => $category, 'quantity' => $quantity,
                        'unit_cost' => $unitCost, 'expense_date' => $date->toDateString(), 'receipt_path' => '', 'receipt_name' => '',
                        'student_confirmed' => true, 'verification_status' => 'pending_seal']
                );
                if (! $receipt->chain_hash) {
                    $payload = $receipt->toArray();
                    $payload['expense_date'] = $receipt->expense_date->toDateString();
                    $payload['total'] = round((float) $receipt->unit_cost * $receipt->quantity, 2);
                    try {
                        $seal = $chain->sealExpense($payload);
                    } catch (\Throwable $e) {
                        $previousHash = BudgetChainService::GENESIS_HASH;
                        $blockHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
                        $seal = [
                            'block_hash' => $blockHash,
                            'previous_hash' => $previousHash,
                            'nodes_confirmed' => 3,
                            'chain_driver' => 'file',
                            'chain_tx_hash' => null,
                            'chain_block_number' => null,
                            'chain_contract_address' => null,
                        ];
                    }
                    $expected = ($seal['chain_driver'] ?? 'file') === 'besu' ? (int) config('besu.validator_count', 4) : BudgetChainService::NODE_COUNT;
                    $receipt->fill([
                        'chain_hash' => $seal['block_hash'], 'previous_hash' => $seal['previous_hash'],
                        'nodes_confirmed' => $seal['nodes_confirmed'], 'chain_driver' => $seal['chain_driver'] ?? 'file',
                        'chain_tx_hash' => $seal['chain_tx_hash'] ?? null, 'chain_block_number' => $seal['chain_block_number'] ?? null,
                        'chain_contract_address' => $seal['chain_contract_address'] ?? null,
                        'verification_status' => 'verified',
                    ])->save();
                }
                if ($receipt->verification_status !== 'verified') {
                    throw new RuntimeException('Receipt is not verified: '.$reference);
                }
                $spent += (float) $receipt->unit_cost * $receipt->quantity;
            }
            $activity->update(['implemented_budget' => $spent]);
            BudgetItem::updateOrCreate(
                ['org_activity_id' => $activity->id],
                ['title' => $title, 'category' => 'Programs', 'college' => $org->college, 'organization_name' => $org->name,
                    'scope' => $scope, 'allocated' => $activity->approved_budget, 'utilized' => $spent,
                    'fiscal_year' => '2026-2027', 'is_approved' => true]
            );
        }
    }
}

<?php

use App\Models\ActivityComplianceDoc;
use App\Models\BudgetItem;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgFundAccount;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\AccomplishmentDocumentService;
use App\Services\AccomplishmentReportService;
use App\Services\ActivityRequirements;
use App\Services\FinancialWorkbookService;
use App\Services\OrganizationCashLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$orgName = 'College of Arts and Sciences Student Council (CAS SC)';
$college = 'College of Arts and Sciences';
$academicYear = '2026-2027';
$semester = '1st Semester';

echo "=== 1. Checking Organization and Accounts ===\n";
$org = StudentOrganization::where('name', $orgName)->firstOrFail();
$soUser = OfficeUser::where('student_organization_id', $org->id)->first();
$actorId = $soUser ? (int) $soUser->id : 6362;
$actorName = $soUser ? $soUser->name : 'CAS SC';

// Ensure Fund Account has sufficient balance
$fund = OrgFundAccount::firstOrCreate(
    ['organization_name' => $orgName],
    [
        'college' => $college,
        'fiscal_year' => $academicYear,
        'cash_opening_balance' => 80000.00,
        'total_funds' => 8000000,
        'beginning_balance' => 80000,
        'total_funds_received' => 80000,
    ]
);
echo "Fund account ID: {$fund->id} (Balance: ₱" . number_format($fund->cash_opening_balance, 2) . ")\n";

$disk = Storage::disk('local');
$sourceDir = is_dir(base_path('In Campus')) ? base_path('In Campus') : storage_path('Template/Creation Of Activties/In Campus Activities');
$reqService = app(ActivityRequirements::class);
$requirements = $reqService->inCampusRequirements();

echo "=== 2. Creating and Proposing 2 Activities for CAS SC ===\n";

$activityData = [
    [
        'title' => 'CAS Synergia 2026: Academic Festival and Research Colloquium',
        'description' => 'Annual college-wide multidisciplinary research colloquium, student research presentation, academic debate championship, and academic symposium for all CAS departments (Psychology, Fisheries, Communication, and Food Technology).',
        'location' => 'CAS Amphitheater & University Gymnasium',
        'starts_at' => Carbon::parse('2026-10-18 08:00:00'),
        'ends_at' => Carbon::parse('2026-10-19 17:00:00'),
        'approved_budget' => 25000.00,
        'implemented_budget' => 25000.00,
        'male_participants' => 120,
        'female_participants' => 165,
        'capacity' => 300,
        'receipts' => [
            ['item' => 'Research Colloquium Kits, Certificates & Plaques for Judges', 'amount' => 10000.00, 'supplier' => 'Batangas Academic Print & Awards', 'ref' => 'OR-CAS-2026-001'],
            ['item' => 'Audio-Visual & Stage Logistics Support', 'amount' => 7000.00, 'supplier' => 'Nasugbu Stage & Sounds Co.', 'ref' => 'OR-CAS-2026-002'],
            ['item' => 'Participant, Presenter & Speaker Refreshments', 'amount' => 8000.00, 'supplier' => 'BatStateU Catering Services', 'ref' => 'OR-CAS-2026-003'],
        ],
    ],
    [
        'title' => 'Sinag Agham: Environmental Science Community Workshop',
        'description' => 'Community extension, mangrove rehabilitation orientation, coastal ecology survey, and environmental science seminar organized by CAS student leaders and faculty mentors.',
        'location' => 'BatStateU ARASOF Audio-Visual Hall & Coastal Study Grounds',
        'starts_at' => Carbon::parse('2026-10-25 08:30:00'),
        'ends_at' => Carbon::parse('2026-10-25 16:30:00'),
        'approved_budget' => 15000.00,
        'implemented_budget' => 15000.00,
        'male_participants' => 75,
        'female_participants' => 95,
        'capacity' => 200,
        'receipts' => [
            ['item' => 'Workshop Field Modules & Environmental Sampling Kits', 'amount' => 7000.00, 'supplier' => 'Green Earth Scientific Supplies', 'ref' => 'OR-CAS-2026-004'],
            ['item' => 'Field Transportation & Safety Equipment Rental', 'amount' => 4000.00, 'supplier' => 'Nasugbu Eco-Transport Services', 'ref' => 'OR-CAS-2026-005'],
            ['item' => 'Volunteer & Community Participant Meals', 'amount' => 4000.00, 'supplier' => 'Lokal Kitchenette & Catering', 'ref' => 'OR-CAS-2026-006'],
        ],
    ],
];

$createdActivities = [];
$sampleImage = public_path('images/bsuarasof.jpg');

// Ensure evidence directory exists
$evidenceDir = "accomplishment-evidence/{$org->id}";
$disk->makeDirectory($evidenceDir);

foreach ($activityData as $idx => $actInfo) {
    // Check if activity already exists
    $activity = OrgActivity::where('organization_name', $orgName)
        ->where('title', $actInfo['title'])
        ->first();

    if (!$activity) {
        $activity = OrgActivity::create([
            'title' => $actInfo['title'],
            'description' => $actInfo['description'],
            'status' => 'completed',
            'workflow_status' => 'oc_approved',
            'activity_scope' => 'in_campus',
            'location' => $actInfo['location'],
            'starts_at' => $actInfo['starts_at'],
            'ends_at' => $actInfo['ends_at'],
            'approved_budget' => $actInfo['approved_budget'],
            'implemented_budget' => $actInfo['implemented_budget'],
            'college' => $college,
            'organization_name' => $orgName,
            'capacity' => $actInfo['capacity'],
            'male_participants' => $actInfo['male_participants'],
            'female_participants' => $actInfo['female_participants'],
            'sdg_goals' => ['Quality Education', 'Decent Work and Economic Growth', 'Climate Action'],
            'core_values' => ['Patriotism', 'Integrity', 'Excellence', 'Service'],
            'org_fund_account_id' => $fund->id,
            'approved_at' => $actInfo['starts_at']->copy()->subDays(10),
        ]);
        echo "Created activity #{$activity->id}: {$activity->title}\n";
    } else {
        $activity->update([
            'status' => 'completed',
            'workflow_status' => 'oc_approved',
            'approved_budget' => $actInfo['approved_budget'],
            'implemented_budget' => $actInfo['implemented_budget'],
        ]);
        echo "Found existing activity #{$activity->id}: {$activity->title}\n";
    }

    $createdActivities[] = $activity;

    // Set up storage directory for submitted activity proposal documents
    $actStorageDir = "in-campus-activities/{$activity->id}";
    $disk->makeDirectory($actStorageDir);

    $attachments = [
        'plan_reference' => "Attachment I — {$academicYear} · {$semester} · Activity " . ($idx + 1) . " — {$activity->title}",
        'plan_activity_key' => hash('sha256', $activity->title . $activity->id),
        'plan_source_version' => hash('sha256', $activity->title . now()->timestamp),
        'plan_of_activities_verified' => true,
        'plan_of_activities_verified_at' => now()->toIso8601String(),
    ];

    foreach ($requirements as $req) {
        $key = $req['key'];
        $srcFile = $sourceDir . DIRECTORY_SEPARATOR . $req['source_file'];
        $uuid = Str::uuid()->toString();
        $targetFilename = "{$key}-{$uuid}.docx";
        $targetPath = "{$actStorageDir}/{$targetFilename}";

        if (file_exists($srcFile)) {
            $disk->put($targetPath, file_get_contents($srcFile));
        } else {
            $disk->put($targetPath, 'Official document content for ' . $req['title']);
        }

        $attachments[$key] = [
            'name' => $req['title'] . '.docx',
            'path' => $targetPath,
            'uploaded_at' => now()->toIso8601String(),
            'size' => $disk->exists($targetPath) ? $disk->size($targetPath) : 1024,
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        // Create or update ActivityComplianceDoc
        ActivityComplianceDoc::updateOrCreate(
            [
                'org_activity_id' => $activity->id,
                'doc_key' => $key,
            ],
            [
                'title' => $req['title'],
                'status' => 'approved',
                'returned_to' => null,
                'remarks' => null,
            ]
        );
    }

    // Create or update InCampusActivitySubmission
    $submission = InCampusActivitySubmission::updateOrCreate(
        ['org_activity_id' => $activity->id],
        [
            'status' => 'submitted',
            'workflow_status' => 'oc_approved',
            'activity_type' => 'in_campus',
            'organization_name' => $orgName,
            'college' => $college,
            'objectives' => 'To enhance student academic competence, research excellence, and community involvement aligned with the curriculum and university goals.',
            'participants' => "Officers, faculty members, and student delegates of the {$college}.",
            'safety_plan' => '1. First Aid & Health Station: In coordination with the University Health Services and designated Red Cross student volunteers on standby.',
            'attachments' => $attachments,
            'submitted_at' => $activity->starts_at->copy()->subDays(14),
        ]
    );

    // Record workflow approval events
    $steps = [
        ['role' => 'so', 'from' => 'draft', 'to' => 'so_submitted', 'notes' => 'Proposal and 10 required in-campus documents submitted.'],
        ['role' => 'sdo', 'from' => 'so_submitted', 'to' => 'sdo_endorsed', 'notes' => 'SDO reviewed and verified compliance documents.'],
        ['role' => 'ovcaa', 'from' => 'sdo_endorsed', 'to' => 'ovcaa_endorsed', 'notes' => 'OVCAA endorsed activity.'],
        ['role' => 'oc', 'from' => 'ovcaa_endorsed', 'to' => 'oc_approved', 'notes' => 'Final approval granted by the Office of the Chancellor.'],
    ];

    foreach ($steps as $step) {
        $exists = DB::table('activity_workflow_events')
            ->where('org_activity_id', $activity->id)
            ->where('to_status', $step['to'])
            ->exists();
        if (!$exists) {
            DB::table('activity_workflow_events')->insert([
                'org_activity_id' => $activity->id,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'role' => $step['role'],
                'from_status' => $step['from'],
                'to_status' => $step['to'],
                'notes' => $step['notes'],
                'created_at' => $activity->starts_at->copy()->subDays(10),
            ]);
        }
    }

    echo "=== 3. Setting Up Budget Utilization and Receipts for Activity #{$activity->id} ===\n";

    // 1 overall BudgetItem per activity
    $budgetItem = BudgetItem::updateOrCreate(
        [
            'org_activity_id' => $activity->id,
        ],
        [
            'title' => $activity->title,
            'organization_name' => $orgName,
            'college' => $college,
            'category' => 'Activity allocation',
            'allocated' => $actInfo['approved_budget'],
            'utilized' => $actInfo['implemented_budget'],
            'fiscal_year' => $academicYear,
            'is_approved' => true,
            'scope' => 'in_campus',
            'notes' => 'Approved budget allocation for ' . $activity->title,
        ]
    );
    echo "  - BudgetItem created: {$activity->title} (Allocated: ₱" . number_format($actInfo['approved_budget'], 2) . ")\n";

    $receiptDir = "expense-receipts/{$activity->id}";
    $disk->makeDirectory($receiptDir);

    $expensesList = [];
    $receiptAttachments = [];

    foreach ($actInfo['receipts'] as $rItem) {
        $ref = $rItem['ref'];
        $receiptFilename = Str::uuid()->toString() . '.jpg';
        $receiptRelPath = "{$receiptDir}/{$receiptFilename}";
        if (file_exists($sampleImage)) {
            $disk->put($receiptRelPath, file_get_contents($sampleImage));
        }

        // Also put a copy in accomplishment-evidence/{org_id} so it satisfies AccomplishmentDocumentService regex
        $evidenceReceiptUuid = Str::uuid()->toString();
        $evidenceReceiptPath = "accomplishment-evidence/{$org->id}/{$evidenceReceiptUuid}.jpg";
        if (file_exists($sampleImage)) {
            $disk->put($evidenceReceiptPath, file_get_contents($sampleImage));
        }

        // Create ExpenseReceiptReview
        $receipt = ExpenseReceiptReview::updateOrCreate(
            [
                'org_activity_id' => $activity->id,
                'item_name' => $rItem['item'],
            ],
            [
                'activity_title' => $activity->title,
                'supplier' => $rItem['supplier'],
                'organization_name' => $orgName,
                'quantity' => 1,
                'unit_cost' => $rItem['amount'],
                'expense_date' => $activity->starts_at->copy()->addHours(4),
                'receipt_path' => $receiptRelPath,
                'receipt_name' => "Receipt_{$ref}.jpg",
                'receipt_reference' => $ref,
                'receipt_disk' => 'local',
                'receipt_type' => 'Official Receipt',
                'payment_method' => 'Cash',
                'receipt_attachments' => [
                    [
                        'disk' => 'local',
                        'path' => $receiptRelPath,
                        'name' => "Receipt_{$ref}.jpg",
                        'mime' => 'image/jpeg',
                        'size' => $disk->exists($receiptRelPath) ? $disk->size($receiptRelPath) : 45000,
                        'hash' => hash('sha256', $ref . $rItem['item']),
                    ]
                ],
                'chain_driver' => 'besu',
                'chain_hash' => hash('sha256', $ref . $activity->id),
                'previous_hash' => hash('sha256', $ref . 'prev'),
                'nodes_confirmed' => 4,
                'student_confirmed' => true,
                'verification_status' => 'verified',
                'uploaded_by' => $actorId,
                'created_at' => $activity->starts_at->copy()->addHours(6),
                'updated_at' => $activity->starts_at->copy()->addHours(6),
            ]
        );
        echo "  - Receipt created: {$rItem['item']} (₱" . number_format($rItem['amount'], 2) . ") Ref: {$ref}\n";

        $expensesList[] = [
            'date' => $activity->starts_at->toDateString(),
            'particulars' => $rItem['item'] . ' — ' . $rItem['supplier'],
            'quantity' => 1,
            'unit_cost' => number_format($rItem['amount'], 2, '.', ''),
            'total' => number_format($rItem['amount'], 2, '.', ''),
            'reference' => $ref,
            'posting_reference' => 'EXP-' . $receipt->id,
        ];

        $receiptAttachments[] = [
            'id' => $evidenceReceiptUuid,
            'path' => $evidenceReceiptPath,
            'name' => "Receipt_{$ref}.jpg",
            'caption' => "Receipt {$ref} — {$rItem['item']}",
            'reference' => $ref,
            'posting_reference' => 'EXP-' . $receipt->id,
            'mime' => 'image/jpeg',
        ];
    }

    echo "=== 4. Generating Accomplishment Entry for Activity #{$activity->id} ===\n";

    // Set up photo evidence for the accomplishment report matching regex accomplishment-evidence/{id}/{uuid}.jpg
    $evidencePhotos = [];
    foreach (['Opening Plenary & Ceremonial Program', 'Participant Workshop & Presentation Showcase'] as $pIdx => $caption) {
        $photoUuid = Str::uuid()->toString();
        $photoRelPath = "accomplishment-evidence/{$org->id}/{$photoUuid}.jpg";
        if (file_exists($sampleImage)) {
            $disk->put($photoRelPath, file_get_contents($sampleImage));
        }
        $evidencePhotos[] = [
            'id' => $photoUuid,
            'path' => $photoRelPath,
            'name' => "Documentation_Photo_" . ($pIdx + 1) . ".jpg",
            'caption' => $caption . ' (' . $activity->title . ')',
            'mime' => 'image/jpeg',
        ];
    }

    $brief = $idx === 0
        ? 'The CAS Synergia 2026 Academic Festival was successfully conducted with high student engagement across all CAS programs. Research presentations and academic competitions demonstrated students\' analytical rigor.'
        : 'The Sinag Agham Community Workshop successfully engaged local community members and students in coastal science education, mangrove conservation principles, and ecological stewardship.';

    $obj = $idx === 0
        ? "1. To foster interdisciplinary academic dialogue among CAS students.\n2. To provide an intellectual platform for undergraduate research colloquium presentations.\n3. To recognize academic excellence through scholarly debate and symposiums."
        : "1. To raise environmental awareness and coastal conservation consciousness.\n2. To train student volunteers in ecological survey methods and mangrove planting.\n3. To strengthen university-community partnerships in sustainable development.";

    $narrative = $idx === 0
        ? "The activity commenced at 8:00 AM at the CAS Amphitheater with opening remarks from the College Dean and faculty mentors. Plenary sessions covered undergraduate research advancements, followed by afternoon parallel sessions in psychology, communications, and food technology. On Day 2, inter-departmental debate rounds and the awarding ceremony culminated in the University Gymnasium with overwhelming participation."
        : "Beginning at 8:30 AM at the ARASOF Audio-Visual Hall, the workshop featured lecture sessions from marine science educators. In the afternoon, delegates proceeded to the Coastal Study Grounds for hands-on orientation on mangrove nursery management and water sampling techniques, successfully achieving all scheduled milestones.";

    $entry = OrgActivityAccomplishment::updateOrCreate(
        [
            'org_activity_id' => $activity->id,
            'organization_name' => $orgName,
        ],
        [
            'academic_year' => $academicYear,
            'semester' => $semester,
            'sponsor' => 'CAS Student Council & College Academic Departments',
            'brief_description' => $brief,
            'objectives' => $obj,
            'narrative' => $narrative,
            'people_involved' => "CAS Faculty Members, Student Organization Officers, Student Presenters, and Registered Delegates",
            'problems_encountered' => 'Minor audio-visual feedback during plenary transition, immediately resolved by the technical committee.',
            'recommendations' => 'Maintain multi-department collaborative planning and expand auditorium seating capacity for future iterations.',
            'male_participants' => $activity->male_participants,
            'female_participants' => $activity->female_participants,
            'signatories' => [
                'secretary' => 'Camille P. Garcia',
                'auditor' => 'Daniel B. Mendoza',
                'president' => 'Charles D. Samontañez',
                'adviser' => 'Dr. Aris M. Hernandez',
                'coordinator' => 'Prof. Elena T. Ramos',
                'head' => 'Dean of College of Arts and Sciences',
            ],
            'activity_snapshot' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'starts_at' => $activity->starts_at->toIso8601String(),
                'ends_at' => $activity->ends_at->toIso8601String(),
                'location' => $activity->location,
                'venue' => $activity->location,
                'budget' => $activity->approved_budget,
                'date_label' => $activity->starts_at->format('F j, Y (l)'),
                'time_label' => $activity->starts_at->format('g:i A') . ' – ' . $activity->ends_at->format('g:i A'),
                'classification' => 'College-wide Academic & Extension',
                'sdg_goals' => $activity->sdg_goals,
                'objectives' => $obj,
                'people_involved' => 'Faculty mentors and student participants of CAS',
            ],
            'financial_snapshot' => [
                'expenses' => $expensesList,
                'collections' => [],
                'total_collection' => '0.00',
                'total_expenses' => number_format($activity->implemented_budget, 2, '.', ''),
                'net_collection' => '-' . number_format($activity->implemented_budget, 2, '.', ''),
                'recorded_at' => now()->toIso8601String(),
                'source_note' => 'Actual activity-linked income and posted expense receipts.',
                'receipt_attachments' => $receiptAttachments,
            ],
            'evidence' => $evidencePhotos,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]
    );
    echo "Accomplishment entry saved for activity #{$activity->id}\n";
}

echo "=== 5. Rendering Combined Accomplishment Report (.docx) ===\n";

$arStatus = OrgReportStatus::firstOrCreate(
    [
        'organization_name' => $orgName,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'report_type' => 'ar',
    ],
    [
        'status' => 'submitted',
        'is_locked' => false,
        'submitted_at' => now(),
    ]
);

$arService = app(AccomplishmentReportService::class);
$docService = app(AccomplishmentDocumentService::class);
$allEntries = OrgActivityAccomplishment::where('organization_name', $orgName)
    ->where('academic_year', $academicYear)
    ->where('semester', $semester)
    ->orderBy('id')
    ->get();

$reportsArray = $allEntries->map(fn ($row) => $arService->report($row, false))->all();
$packet = $arService->packet($orgName, $semester, $academicYear, $reportsArray);

$arFileName = "CAS-SC-Accomplishment-Report-{$semester}-AY-{$academicYear}.docx";
$arFinalRelPath = "reports/{$org->id}/" . $arFileName;
$arFinalAbsPath = Storage::disk('local')->path($arFinalRelPath);
File::ensureDirectoryExists(dirname($arFinalAbsPath));
$docService->generate($packet, $arFinalAbsPath);

// Save OrgReportDocument for AR
$arDocument = OrgReportDocument::updateOrCreate(
    [
        'organization_name' => $orgName,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'report_type' => 'ar',
    ],
    [
        'org_report_status_id' => $arStatus->id,
        'name' => "{$orgName} – {$semester} AY {$academicYear} Accomplishment Report",
        'original_name' => $arFileName,
        'file_path' => $arFinalRelPath,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'file_size' => $disk->size($arFinalRelPath),
        'uploaded_by' => $actorName,
        'accomplishment_summary' => [
            'entries_count' => $allEntries->count(),
            'activities' => $allEntries->pluck('activity_snapshot.title')->all(),
            'total_participants' => $allEntries->sum('male_participants') + $allEntries->sum('female_participants'),
            'total_spent' => $allEntries->sum('financial_snapshot.total_expenses'),
        ],
    ]
);
echo "Combined Accomplishment Report generated: {$arFinalRelPath} (" . number_format($disk->size($arFinalRelPath)) . " bytes)\n";

echo "=== 6. Rendering Combined Financial Report (.xlsx) ===\n";

$frStatus = OrgReportStatus::firstOrCreate(
    [
        'organization_name' => $orgName,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'report_type' => 'fr',
    ],
    [
        'status' => 'submitted',
        'is_locked' => false,
        'submitted_at' => now(),
    ]
);

$frService = app(FinancialWorkbookService::class);
$receiptsList = ExpenseReceiptReview::where('organization_name', $orgName)
    ->whereIn('org_activity_id', $createdActivities ? array_column($createdActivities, 'id') : [])
    ->orderBy('expense_date')
    ->get();

$disbursements = [];
$totalOutflow = 0;
foreach ($receiptsList as $r) {
    $cost = (float) $r->unit_cost * (int) $r->quantity;
    $totalOutflow += $cost;
    $disbursements[] = [
        'date' => $r->expense_date ? $r->expense_date->format('Y-m-d') : '2026-10-18',
        'particulars' => $r->item_name . ' (' . $r->activity_title . ')',
        'amount' => $cost,
        'reference' => $r->receipt_reference,
    ];
}

$beginningBalance = (float) $fund->cash_opening_balance;
$endingBalance = $beginningBalance - $totalOutflow;

$frReportsData = [
    [
        'period_label' => "{$semester} AY {$academicYear}",
        'academic_year' => $academicYear,
        'semester' => $semester,
        'summary' => [
            'beginning_balance' => $beginningBalance,
            'cash_inflow' => 0.00,
            'cash_outflow' => $totalOutflow,
            'ending_balance' => $endingBalance,
            'inflow_source' => 'statement',
            'outflow_source' => 'statement',
            'activities' => [
                [
                    'name' => 'CAS Synergia 2026: Academic Festival and Research Colloquium',
                    'inflow' => 0.00,
                    'outflow' => 25000.00,
                ],
                [
                    'name' => 'Sinag Agham: Environmental Science Community Workshop',
                    'inflow' => 0.00,
                    'outflow' => 15000.00,
                ],
            ],
            'receipts' => [],
            'disbursements' => $disbursements,
        ],
    ]
];

$frTempFile = $frService->export($frReportsData, $beginningBalance, $orgName);
$frFileName = "CAS-SC-Financial-Report-{$semester}-AY-{$academicYear}.xlsx";
$frFinalRelPath = "reports/{$org->id}/" . $frFileName;
$disk->put($frFinalRelPath, file_get_contents($frTempFile));
@unlink($frTempFile);

// Save OrgReportDocument for FR
$frDocument = OrgReportDocument::updateOrCreate(
    [
        'organization_name' => $orgName,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'report_type' => 'fr',
    ],
    [
        'org_report_status_id' => $frStatus->id,
        'name' => "{$orgName} – {$semester} AY {$academicYear} Financial Report",
        'original_name' => $frFileName,
        'file_path' => $frFinalRelPath,
        'mime_type' => FinancialWorkbookService::MIME_TYPE,
        'file_size' => $disk->size($frFinalRelPath),
        'uploaded_by' => $actorName,
        'financial_summary' => [
            'beginning_balance' => $beginningBalance,
            'total_inflow' => 0.00,
            'total_outflow' => $totalOutflow,
            'ending_balance' => $endingBalance,
            'activities_count' => count($createdActivities),
        ],
    ]
);
echo "Combined Financial Report generated: {$frFinalRelPath} (" . number_format($disk->size($frFinalRelPath)) . " bytes)\n";

echo "=== All Steps Completed Successfully! ===\n";

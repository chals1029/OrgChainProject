<?php

namespace Tests\Feature;

use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgCashIncome;
use App\Models\OrgFundAccount;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\AccomplishmentDocumentService;
use App\Services\AccomplishmentReportService;
use App\Services\ActivityBudgetService;
use App\Services\FinancialReportPreviewGate;
use App\Services\OrganizationCashLedger;
use App\Services\SemesterReportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;
use ZipArchive;

class AccomplishmentReportTest extends TestCase
{
    use UsesLaragonDatabase;

    private StudentOrganization $organization;

    private StudentOrganization $foreignOrganization;

    private OfficeUser $so;

    private OfficeUser $foreignSo;

    private OfficeUser $oso;

    private OfficeUser $sdo;

    private OfficeUser $ovcaa;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();

        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }

        Storage::fake('public');
        Storage::fake('local');

        $this->organization = StudentOrganization::query()->create([
            'name' => 'Accomplishment Fixture Org '.Str::uuid(),
            'college' => 'College of Engineering',
            'is_active' => true,
        ]);

        $this->foreignOrganization = StudentOrganization::query()->create([
            'name' => 'Foreign Accomplishment Org '.Str::uuid(),
            'college' => 'College of Sciences',
            'is_active' => true,
        ]);

        $this->so = $this->createOfficeUser('so', $this->organization);
        $this->foreignSo = $this->createOfficeUser('so', $this->foreignOrganization);
        $this->oso = $this->createOfficeUser('oso');
        $this->sdo = $this->createOfficeUser('sdo');
        $this->ovcaa = $this->createOfficeUser('ovcaa');
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->temporaryFiles as $path) {
                @unlink($path);
            }
            foreach (['mysql', 'orgchain'] as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_so_creates_complete_accomplishment_report_with_photo_caption_and_stages_valid_docx(): void
    {
        $activity = $this->createEndedApprovedActivity('Robotics and IoT Hands-On Workshop');
        $payload = $this->validReportPayload($activity->id, [
            'sponsor' => 'College of Engineering & Computer Studies',
            'classification' => 'College-Based Academic',
            'male_participants' => 14,
            'female_participants' => 21,
            'narrative' => 'The robotics workshop successfully concluded with 35 student participants completing the hardware lab exercise.',
            'brief_description' => 'A practical seminar introducing microcontrollers and embedded firmware programming.',
            'problems_encountered' => 'Initial delays with USB cable driver installations on older lab computers.',
            'recommendations' => 'Provide a pre-bundled driver package on flash drives before future workshops.',
        ]);

        $response = $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $payload);

        $response->assertRedirect(route('office.accomplishment', [
            'academic_year' => '2025-2026',
            'semester' => '1st Semester',
        ]));
        $response->assertSessionHas('success');

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $this->assertSame($this->organization->name, $report->organization_name);
        $this->assertSame('1st Semester', $report->semester);
        $this->assertSame('2025-2026', $report->academic_year);
        $this->assertSame(14, $report->male_participants);
        $this->assertSame(21, $report->female_participants);
        $this->assertSame('College-Based Academic', $report->activity_snapshot['classification'] ?? null);

        $this->assertCount(1, $report->evidence);
        $evidence = $report->evidence[0];
        $this->assertSame('Hands-on hardware lab session', $evidence['caption']);
        $this->assertSame('evidence_1.png', $evidence['name']);
        $this->assertTrue(Storage::disk('local')->exists($evidence['path']));

        $document = OrgReportDocument::query()
            ->where('organization_name', $this->organization->name)
            ->where('report_type', 'ar')
            ->where('semester', '1st Semester')
            ->where('academic_year', '2025-2026')
            ->whereNotNull('accomplishment_summary')
            ->sole();

        $this->assertTrue($document->hasStoredFile());
        $this->assertTrue(Storage::disk('public')->exists($document->file_path));

        $fullDocxPath = Storage::disk('public')->path($document->file_path);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($fullDocxPath), 'The generated Accomplishment Report must open as a valid Zip/DOCX archive.');

        $xml = $zip->getFromName('word/document.xml');
        $this->assertNotFalse($xml, 'The DOCX archive must contain word/document.xml.');
        $zip->close();

        $this->assertStringContainsString($this->organization->name, $xml);
        $this->assertStringContainsString('1ST SEMESTER, ACADEMIC YEAR 2025-2026', $xml);
        $this->assertStringNotContainsString('SECOND SEMESTER, ACADEMIC YEAR 2025-2026', $xml);
        $this->assertStringNotContainsString('Juan Cruz', $xml);
        $this->assertStringNotContainsString('Juana Cruz', $xml);
        $this->assertStringContainsString('PARTICULARS OF THE ACCOMPLISHMENTS', $xml);
        $this->assertStringContainsString('The robotics workshop successfully concluded with 35 student participants', $xml);
        $this->assertStringContainsString('Hands-on hardware lab session', $xml);

        foreach (AccomplishmentDocumentService::PARTICULARS as $column) {
            $this->assertStringContainsString($column, $xml);
        }
    }

    public function test_exact_money_sources_bound_by_activity_id_and_ledger_never_mutated(): void
    {
        $targetActivity = $this->createEndedApprovedActivity('Annual Hackathon 2025');
        $otherActivity = $this->createEndedApprovedActivity('Unrelated Seminar 2025');

        $account = OrgFundAccount::query()->create([
            'organization_name' => $this->organization->name,
            'college' => $this->organization->college,
            'fiscal_year' => '2025-2026',
            'total_funds' => 5000.00,
            'beginning_balance' => 5000.00,
            'total_funds_received' => 0.00,
            'cash_opening_balance' => 5000.00,
        ]);

        $targetIncome = OrgCashIncome::query()->create([
            'org_fund_account_id' => $account->id,
            'organization_name' => $this->organization->name,
            'org_activity_id' => $targetActivity->id,
            'transaction_date' => '2025-09-10',
            'amount' => 450.00,
            'purpose' => 'Hackathon participant registration',
            'received_from' => 'Participant teams',
            'reference' => 'CR-HACK-001',
            'request_key' => (string) Str::uuid(),
        ]);

        $generalIncome = OrgCashIncome::query()->create([
            'org_fund_account_id' => $account->id,
            'organization_name' => $this->organization->name,
            'org_activity_id' => null,
            'transaction_date' => '2025-09-12',
            'amount' => 2000.00,
            'purpose' => 'General alumni donation',
            'received_from' => 'Alumni association',
            'reference' => 'CR-GEN-002',
            'request_key' => (string) Str::uuid(),
        ]);

        $receiptImagePath = 'receipts/hackathon_boards.png';
        Storage::disk('public')->put($receiptImagePath, $this->validPngBytes(150, 150));

        $targetReceipt = ExpenseReceiptReview::query()->create([
            'org_activity_id' => $targetActivity->id,
            'organization_name' => $this->organization->name,
            'activity_title' => $targetActivity->title,
            'expense_date' => '2025-09-14',
            'item_name' => 'Development boards',
            'supplier' => 'Hardware Depot',
            'quantity' => 2,
            'unit_cost' => 65.50,
            'receipt_reference' => 'SI-9941',
            'receipt_path' => $receiptImagePath,
            'receipt_name' => 'hackathon_boards.png',
            'receipt_disk' => 'public',
            'verification_status' => 'approved',
        ]);

        $otherReceipt = ExpenseReceiptReview::query()->create([
            'org_activity_id' => $otherActivity->id,
            'organization_name' => $this->organization->name,
            'activity_title' => $otherActivity->title,
            'expense_date' => '2025-09-15',
            'item_name' => 'Snacks for Seminar',
            'supplier' => 'Campus Bakery',
            'quantity' => 1,
            'unit_cost' => 500.00,
            'receipt_reference' => 'SI-OTHER-10',
            'receipt_path' => 'receipts/unrelated_seminar_snacks_missing.png',
            'receipt_name' => 'unrelated_seminar_snacks_missing.png',
            'receipt_disk' => 'public',
            'verification_status' => 'approved',
        ]);

        $initialIncomeCount = OrgCashIncome::query()->count();
        $initialReceiptCount = ExpenseReceiptReview::query()->count();
        $initialAccountState = $account->fresh()->toArray();

        $payload = $this->validReportPayload($targetActivity->id);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $targetActivity->id)->sole();
        $financial = $report->financial_snapshot;

        $this->assertCount(1, $financial['collections']);
        $this->assertSame('450.00', $financial['collections'][0]['total']);
        $this->assertSame('CR-HACK-001', $financial['collections'][0]['reference']);
        $this->assertStringContainsString('Hackathon participant registration', $financial['collections'][0]['particulars']);

        $this->assertCount(1, $financial['expenses']);
        $this->assertSame('131.00', $financial['expenses'][0]['total']);
        $this->assertSame('SI-9941', $financial['expenses'][0]['reference']);

        $this->assertSame('450.00', $financial['total_collection']);
        $this->assertSame('131.00', $financial['total_expenses']);
        $this->assertSame('319.00', $financial['net_collection']);

        $this->assertNotEmpty($financial['receipt_attachments']);
        $attached = $financial['receipt_attachments'][0];
        $this->assertTrue(Storage::disk('local')->exists($attached['path']));

        $this->assertSame($initialIncomeCount, OrgCashIncome::query()->count(), 'Accomplishment report saving must NEVER write income rows.');
        $this->assertSame($initialReceiptCount, ExpenseReceiptReview::query()->count(), 'Accomplishment report saving must NEVER write receipt rows.');
        $this->assertEquals($initialAccountState, $account->fresh()->toArray(), 'Accomplishment report saving must NEVER modify fund accounts.');
        $this->assertTrue(Storage::disk('public')->exists($receiptImagePath), 'Source receipt image must remain untouched in its original storage.');
    }

    public function test_actual_participant_counts_entered_by_so_never_copy_proposed_attendance(): void
    {
        $activity = $this->createEndedApprovedActivity('Cybersecurity Colloquium');

        InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'title' => $activity->title,
            'participants' => '250 targeted student attendees from across colleges',
            'objectives' => 'Understand modern cyber threats.',
            'status' => 'approved',
        ]);

        $payload = $this->validReportPayload($activity->id, [
            'male_participants' => 38,
            'female_participants' => 47,
        ]);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $this->assertSame(38, $report->male_participants);
        $this->assertSame(47, $report->female_participants);

        $dto = app(AccomplishmentReportService::class)->report($report);
        $this->assertSame(85, $dto['participants']);
        $this->assertNotSame(250, $dto['participants']);

        $negativePayload = $this->validReportPayload($activity->id, [
            'male_participants' => -5,
            'female_participants' => 20,
        ]);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $negativePayload)
            ->assertSessionHasErrors(['male_participants']);
    }

    public function test_foreign_pending_and_future_activities_are_denied(): void
    {
        $initialAccomplishments = OrgActivityAccomplishment::query()->count();

        $foreignActivity = $this->createEndedApprovedActivity(
            'Foreign Organization Workshop',
            ['organization_name' => $this->foreignOrganization->name]
        );

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($foreignActivity->id))
            ->assertSessionHasErrors(['org_activity_id']);

        $pendingActivity = $this->createEndedApprovedActivity(
            'Unapproved Activity Still in Review',
            ['workflow_status' => 'submitted']
        );

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($pendingActivity->id))
            ->assertSessionHasErrors(['org_activity_id']);

        $futureActivity = $this->createEndedApprovedActivity(
            'Future Planned Symposium',
            [
                'starts_at' => now()->addDays(7)->setTime(9, 0),
                'ends_at' => now()->addDays(7)->setTime(16, 0),
            ]
        );

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($futureActivity->id))
            ->assertSessionHasErrors(['org_activity_id']);

        $this->assertSame($initialAccomplishments, OrgActivityAccomplishment::query()->count());
        $this->assertSame(0, OrgActivityAccomplishment::query()->where('organization_name', $this->organization->name)->count());
        $this->assertSame(0, OrgActivityAccomplishment::query()->where('organization_name', $this->foreignOrganization->name)->count());
    }

    public function test_no_writes_from_incomplete_input_or_missing_caption_or_invalid_image(): void
    {
        $initialAccomplishments = OrgActivityAccomplishment::query()->count();
        $initialDocuments = OrgReportDocument::query()->count();

        $activity = $this->createEndedApprovedActivity('Clean Code Seminar');

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'narrative' => '   ',
            ]))
            ->assertSessionHasErrors(['narrative']);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'photos' => [
                    ['file' => $this->validPngFile('missing_caption.png'), 'caption' => '   '],
                ],
            ]))
            ->assertSessionHasErrors(['photos.0.caption']);

        $fakeCorruptFile = UploadedFile::fake()->createWithContent('fake.png', 'plain text disguised as png bytes');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'photos' => [
                    ['file' => $fakeCorruptFile, 'caption' => 'Valid caption for bad file'],
                ],
            ]))
            ->assertSessionHasErrors(['photos.0.file']);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'photos' => [],
            ]))
            ->assertSessionHasErrors(['photos']);

        $this->assertSame($initialAccomplishments, OrgActivityAccomplishment::query()->count());
        $this->assertSame($initialDocuments, OrgReportDocument::query()->count());
        $this->assertSame(0, OrgActivityAccomplishment::query()->where('organization_name', $this->organization->name)->count());
        $this->assertSame(0, OrgReportDocument::query()->where('organization_name', $this->organization->name)->count());
        $this->assertEmpty(Storage::disk('local')->allFiles('accomplishment-evidence/'.$this->organization->id));
    }

    public function test_two_same_title_activities_keep_distinct_financial_and_evidence(): void
    {
        $activityA = $this->createEndedApprovedActivity('Leadership Bootcamp');
        $activityB = $this->createEndedApprovedActivity('Leadership Bootcamp');
        $this->assertNotSame($activityA->id, $activityB->id);

        $account = OrgFundAccount::query()->create([
            'organization_name' => $this->organization->name,
            'college' => $this->organization->college,
            'fiscal_year' => '2025-2026',
            'total_funds' => 1000.00,
            'beginning_balance' => 1000.00,
            'total_funds_received' => 0.00,
            'cash_opening_balance' => 1000.00,
        ]);

        OrgCashIncome::query()->create([
            'org_fund_account_id' => $account->id,
            'organization_name' => $this->organization->name,
            'org_activity_id' => $activityA->id,
            'transaction_date' => '2025-09-01',
            'amount' => 150.00,
            'purpose' => 'Bootcamp A entry fee',
            'received_from' => 'Cohort A',
            'reference' => 'CR-A-01',
            'request_key' => (string) Str::uuid(),
        ]);

        $receiptPathA = 'receipts/cohort_a_badges_'.Str::uuid().'.png';
        Storage::disk('public')->put($receiptPathA, $this->validPngBytes(100, 100));

        ExpenseReceiptReview::query()->create([
            'org_activity_id' => $activityA->id,
            'organization_name' => $this->organization->name,
            'activity_title' => $activityA->title,
            'expense_date' => '2025-09-02',
            'item_name' => 'Badges for Cohort A',
            'supplier' => 'Print Shop',
            'quantity' => 1,
            'unit_cost' => 50.00,
            'receipt_reference' => 'SI-A-01',
            'receipt_path' => $receiptPathA,
            'receipt_name' => basename($receiptPathA),
            'receipt_disk' => 'public',
            'verification_status' => 'approved',
        ]);

        OrgCashIncome::query()->create([
            'org_fund_account_id' => $account->id,
            'organization_name' => $this->organization->name,
            'org_activity_id' => $activityB->id,
            'transaction_date' => '2025-09-03',
            'amount' => 300.00,
            'purpose' => 'Bootcamp B entry fee',
            'received_from' => 'Cohort B',
            'reference' => 'CR-B-01',
            'request_key' => (string) Str::uuid(),
        ]);

        $receiptPathB = 'receipts/cohort_b_badges_'.Str::uuid().'.png';
        Storage::disk('public')->put($receiptPathB, $this->validPngBytes(100, 100));

        ExpenseReceiptReview::query()->create([
            'org_activity_id' => $activityB->id,
            'organization_name' => $this->organization->name,
            'activity_title' => $activityB->title,
            'expense_date' => '2025-09-04',
            'item_name' => 'Badges for Cohort B',
            'supplier' => 'Print Shop',
            'quantity' => 2,
            'unit_cost' => 60.00,
            'receipt_reference' => 'SI-B-02',
            'receipt_path' => $receiptPathB,
            'receipt_name' => basename($receiptPathB),
            'receipt_disk' => 'public',
            'verification_status' => 'approved',
        ]);

        $payloadA = $this->validReportPayload($activityA->id, [
            'brief_description' => 'First cohort of the bootcamp',
            'photos' => [
                ['file' => $this->validPngFile('cohort_a.png'), 'caption' => 'Cohort A Workshop Session'],
            ],
        ]);
        $this->actingAs($this->so, 'office')->post(route('office.accomplishment.reports.store'), $payloadA)->assertRedirect();

        $payloadB = $this->validReportPayload($activityB->id, [
            'brief_description' => 'Second cohort of the bootcamp',
            'photos' => [
                ['file' => $this->validPngFile('cohort_b.png'), 'caption' => 'Cohort B Presentation Session'],
            ],
        ]);
        $this->actingAs($this->so, 'office')->post(route('office.accomplishment.reports.store'), $payloadB)->assertRedirect();

        $reportA = OrgActivityAccomplishment::query()->where('org_activity_id', $activityA->id)->sole();
        $reportB = OrgActivityAccomplishment::query()->where('org_activity_id', $activityB->id)->sole();

        $this->assertSame('150.00', $reportA->financial_snapshot['total_collection']);
        $this->assertSame('50.00', $reportA->financial_snapshot['total_expenses']);
        $this->assertSame('300.00', $reportB->financial_snapshot['total_collection']);
        $this->assertSame('120.00', $reportB->financial_snapshot['total_expenses']);

        $this->assertSame('Cohort A Workshop Session', $reportA->evidence[0]['caption']);
        $this->assertSame('Cohort B Presentation Session', $reportB->evidence[0]['caption']);
        $this->assertNotSame($reportA->evidence[0]['id'], $reportB->evidence[0]['id']);

        $document = OrgReportDocument::query()
            ->where('organization_name', $this->organization->name)
            ->where('report_type', 'ar')
            ->sole();

        $zip = new ZipArchive();
        $zip->open(Storage::disk('public')->path($document->file_path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('PHP 50.00', $xml);
        $this->assertStringContainsString('PHP 120.00', $xml);
        $this->assertStringContainsString('Cohort A Workshop Session', $xml);
        $this->assertStringContainsString('Cohort B Presentation Session', $xml);
    }

    public function test_duplicate_activity_across_periods_is_denied(): void
    {
        $activity = $this->createEndedApprovedActivity('Single Reporting Activity');

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
            ]))
            ->assertSessionHasErrors(['org_activity_id']);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id, [
                'semester' => '1st Semester',
                'academic_year' => '2026-2027',
            ]))
            ->assertSessionHasErrors(['org_activity_id']);

        $this->assertSame(1, OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->count());
    }

    public function test_evidence_retain_and_edit_semantics(): void
    {
        $activity = $this->createEndedApprovedActivity('Multi-Photo Workshop');

        $initialPayload = $this->validReportPayload($activity->id, [
            'photos' => [
                ['file' => $this->validPngFile('initial_1.png'), 'caption' => 'Original Photo 1'],
                ['file' => $this->validPngFile('initial_2.png'), 'caption' => 'Original Photo 2 to be dropped'],
            ],
        ]);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $initialPayload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $this->assertCount(2, $report->evidence);
        $photo1Id = $report->evidence[0]['id'];
        $photo2Id = $report->evidence[1]['id'];

        $updatePayload = [
            'org_activity_id' => $activity->id,
            'academic_year' => '2025-2026',
            'semester' => '1st Semester',
            'classification' => 'Updated Classification',
            'sponsor' => $report->sponsor,
            'brief_description' => $report->brief_description,
            'objectives' => $report->objectives,
            'narrative' => 'Updated implementation narrative reflecting latest outcomes.',
            'people_involved' => $report->people_involved,
            'male_participants' => 15,
            'female_participants' => 25,
            'problems_encountered' => $report->problems_encountered,
            'recommendations' => $report->recommendations,
            'signatories' => $report->signatories,
            'keep_evidence' => [$photo1Id],
            'evidence_captions' => [
                $photo1Id => 'Refined Caption for Photo 1',
            ],
            'photos' => [
                ['file' => $this->validPngFile('replacement_3.png'), 'caption' => 'Brand New Photo 3'],
            ],
        ];

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $updatePayload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $refreshed = $report->fresh();
        $this->assertCount(2, $refreshed->evidence);
        $this->assertSame($photo1Id, $refreshed->evidence[0]['id']);
        $this->assertSame('Refined Caption for Photo 1', $refreshed->evidence[0]['caption']);
        $this->assertSame('Brand New Photo 3', $refreshed->evidence[1]['caption']);

        $this->actingAs($this->so, 'office')
            ->get(route('office.accomplishment.evidence', ['report' => $refreshed, 'evidence' => $photo1Id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($this->so, 'office')
            ->get(route('office.accomplishment.evidence', ['report' => $refreshed, 'evidence' => $photo2Id]))
            ->assertNotFound();

        $invalidKeepPayload = $updatePayload;
        $invalidKeepPayload['keep_evidence'] = [(string) Str::uuid()];
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $refreshed), $invalidKeepPayload)
            ->assertSessionHasErrors(['keep_evidence']);
    }

    public function test_ar_status_locking_rejects_modification_while_peer_fr_lock_does_not_block_edit(): void
    {
        $activity = $this->createEndedApprovedActivity('Locked Period Activity');
        $payload = $this->validReportPayload($activity->id);

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $payload)
            ->assertRedirect();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();

        $arStatus = OrgReportStatus::query()
            ->where('organization_name', $this->organization->name)
            ->where('report_type', 'ar')
            ->sole();
        $frStatus = OrgReportStatus::query()->firstOrCreate([
            'report_type' => 'fr',
            'organization_name' => $this->organization->name,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ], [
            'status' => 'draft',
        ]);

        $arStatus->update(['status' => 'oso_review']);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasErrors(['report']);

        $arStatus->update(['status' => 'rejected', 'notes' => 'Terminal rejection']);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasErrors(['report']);

        $arStatus->update(['status' => 'draft', 'notes' => null]);
        $frStatus->update(['status' => 'verified']);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $frStatus->update(['status' => 'archived']);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $secondActivity = $this->createEndedApprovedActivity('Second Attempt in Peer Locked Period');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($secondActivity->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $arStatus->update([
            'status' => 'returned',
            'returned_to' => 'so',
            'notes' => 'Please revise implementation highlights.',
        ]);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $arStatus->refresh();
        $this->assertSame('returned', $arStatus->status);
        $this->assertSame('Please revise implementation highlights.', $arStatus->notes);

        $duplicateLockedAr = OrgReportStatus::query()->create([
            'report_type' => 'ar',
            'organization_name' => $this->organization->name,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
            'status' => 'verified',
        ]);
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $payload)
            ->assertSessionHasErrors(['report']);
        $duplicateLockedAr->delete();
    }

    public function test_oso_privacy_cannot_view_unsubmitted_and_read_only_cannot_write(): void
    {
        $activity = $this->createEndedApprovedActivity('Privacy Protected Activity');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertRedirect();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $evidenceId = $report->evidence[0]['id'];
        $document = OrgReportDocument::query()->where('organization_name', $this->organization->name)->where('report_type', 'ar')->sole();

        $this->actingAs($this->oso, 'office')
            ->get(route('office.accomplishment.evidence', ['report' => $report, 'evidence' => $evidenceId]))
            ->assertForbidden();

        $this->actingAs($this->oso, 'office')
            ->get(route('office.accomplishment.reports.preview', $report))
            ->assertForbidden();

        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', $document))
            ->assertForbidden();

        $this->actingAs($this->oso, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertForbidden();

        $this->actingAs($this->oso, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $this->validReportPayload($activity->id))
            ->assertForbidden();

        $this->actingAs($this->so, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->oso, 'office')
            ->get(route('office.accomplishment.evidence', ['report' => $report, 'evidence' => $evidenceId]))
            ->assertOk();

        $this->actingAs($this->oso, 'office')
            ->get(route('office.accomplishment.reports.preview', $report))
            ->assertOk()
            ->assertViewIs('org.accomplishment-print');

        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', $document))
            ->assertOk();

        $this->actingAs($this->oso, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertForbidden();
    }

    public function test_sdo_and_ovcaa_roles_are_forbidden_from_native_accomplishment_endpoints(): void
    {
        $activity = $this->createEndedApprovedActivity('Institutional Boundary Activity');

        foreach ([$this->sdo, $this->ovcaa] as $officer) {
            $this->actingAs($officer, 'office')
                ->get(route('office.accomplishment'))
                ->assertForbidden();

            $this->actingAs($officer, 'office')
                ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
                ->assertForbidden();

            $this->actingAs($officer, 'office')
                ->get(route('office.accomplishment.template'))
                ->assertForbidden();
        }
    }

    public function test_ar_submit_requires_actual_evidence_files_and_succeeds_independently(): void
    {
        $activity = $this->createEndedApprovedActivity('Independent AR Submission Activity');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertRedirect();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $evidencePath = $report->evidence[0]['path'];

        Storage::disk('local')->delete($evidencePath);

        $submitPayload = [
            'organization_name' => $this->organization->name,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        $this->actingAs($this->so, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), $submitPayload)
            ->assertSessionHasErrors(['report']);

        $this->assertSame('draft', OrgReportStatus::query()->where('organization_name', $this->organization->name)->where('report_type', 'ar')->value('status'));

        Storage::disk('local')->put($evidencePath, $this->validPngBytes(120, 120));

        $this->actingAs($this->so, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), $submitPayload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('oso_review', OrgReportStatus::query()->where('organization_name', $this->organization->name)->where('report_type', 'ar')->value('status'));
        $this->assertNull(OrgReportStatus::query()->where('organization_name', $this->organization->name)->where('report_type', 'fr')->value('status'));

        $legacyOrg = StudentOrganization::query()->create([
            'name' => 'Legacy Upload Org '.Str::uuid(),
            'college' => 'College of Arts',
            'is_active' => true,
        ]);
        $legacySo = $this->createOfficeUser('so', $legacyOrg);

        $this->stageValidFrDocument($legacyOrg->name, 'ar', 'legacy_ar.pdf');

        $this->actingAs($legacySo, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), [
                'organization_name' => $legacyOrg->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('oso_review', OrgReportStatus::query()->where('organization_name', $legacyOrg->name)->where('report_type', 'ar')->value('status'));
        $this->assertNull(OrgReportStatus::query()->where('organization_name', $legacyOrg->name)->where('report_type', 'fr')->value('status'));
    }

    public function test_snapshot_preview_after_submission_and_return_archive_paths(): void
    {
        $activity = $this->createEndedApprovedActivity('Lifecycle Activity');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertRedirect();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $document = OrgReportDocument::query()->where('organization_name', $this->organization->name)->where('report_type', 'ar')->sole();

        $this->actingAs($this->so, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ])
            ->assertSessionHas('success');

        $viewSubmitted = $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', $document))
            ->assertOk();
        $this->assertStringContainsString($activity->title, $viewSubmitted->getContent());

        $this->actingAs($this->oso, 'office')
            ->post(route('office.reports.review', ['reportType' => 'ar']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
                'decision' => 'return',
                'notes' => 'Please revise narrative details.',
            ])
            ->assertRedirect();

        $arStatus = OrgReportStatus::query()
            ->where('organization_name', $this->organization->name)
            ->where('report_type', 'ar')
            ->sole();
        $this->assertSame('returned', $arStatus->status);
        $this->assertSame('Please revise narrative details.', $arStatus->notes);

        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', $document))
            ->assertForbidden();

        $photo1Id = $report->evidence[0]['id'];
        $updatePayload = [
            'org_activity_id' => $activity->id,
            'academic_year' => '2025-2026',
            'semester' => '1st Semester',
            'classification' => 'Returned and Revised',
            'sponsor' => $report->sponsor,
            'brief_description' => $report->brief_description,
            'objectives' => $report->objectives,
            'narrative' => 'Revised implementation narrative after OSO requested more details.',
            'people_involved' => $report->people_involved,
            'male_participants' => $report->male_participants,
            'female_participants' => $report->female_participants,
            'problems_encountered' => $report->problems_encountered,
            'recommendations' => $report->recommendations,
            'signatories' => $report->signatories,
            'keep_evidence' => [$photo1Id],
            'evidence_captions' => [
                $photo1Id => 'Retained documentation photo',
            ],
        ];

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $updatePayload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $arStatus->refresh();
        $this->assertSame('returned', $arStatus->status);
        $this->assertSame('Please revise narrative details.', $arStatus->notes);

        $this->actingAs($this->so, 'office')
            ->post(route('office.reports.submit', ['reportType' => 'ar']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('oso_review', $arStatus->refresh()->status);

        $this->actingAs($this->oso, 'office')
            ->post(route('office.reports.review', ['reportType' => 'ar']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
                'decision' => 'accept',
                'notes' => 'Verified and archived by OSO.',
            ])
            ->assertRedirect();

        $this->assertSame('archived', $arStatus->refresh()->status);

        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', $document->fresh()))
            ->assertOk();

        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.update', $report), $updatePayload)
            ->assertSessionHasErrors(['report']);
    }

    public function test_native_report_survives_source_activity_deletion(): void
    {
        $activity = $this->createEndedApprovedActivity('Activity Destined For Deletion');
        $this->actingAs($this->so, 'office')
            ->post(route('office.accomplishment.reports.store'), $this->validReportPayload($activity->id))
            ->assertRedirect();

        $report = OrgActivityAccomplishment::query()->where('org_activity_id', $activity->id)->sole();
        $this->assertSame('Activity Destined For Deletion', $report->activity_snapshot['title']);

        $activity->delete();
        $this->assertNull(OrgActivity::query()->find($activity->id));

        $refreshed = OrgActivityAccomplishment::query()->find($report->id);
        $this->assertNotNull($refreshed, 'The native accomplishment report must NOT be cascaded away on source activity deletion.');

        $dto = app(AccomplishmentReportService::class)->report($refreshed);
        $this->assertSame('Activity Destined For Deletion', $dto['title']);

        $preview = $this->actingAs($this->so, 'office')
            ->get(route('office.accomplishment.reports.preview', $refreshed))
            ->assertOk()
            ->assertViewIs('org.accomplishment-print');

        $this->assertStringContainsString('Activity Destined For Deletion', $preview->getContent());
    }

    public function test_official_template_download_endpoints(): void
    {
        $narrativeResponse = $this->actingAs($this->so, 'office')
            ->get(route('office.accomplishment.template', ['kind' => 'narrative']));
        $narrativeResponse->assertOk();
        $this->assertSame(AccomplishmentDocumentService::MIME_TYPE, $narrativeResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('Accomplishment Report Template.docx', (string) $narrativeResponse->headers->get('Content-Disposition'));

        $particularsResponse = $this->actingAs($this->so, 'office')
            ->get(route('office.accomplishment.template', ['kind' => 'particulars']));
        $particularsResponse->assertOk();
        $this->assertSame(AccomplishmentDocumentService::MIME_TYPE, $particularsResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('Particulars of the Accomplishments.docx', (string) $particularsResponse->headers->get('Content-Disposition'));

        $this->actingAs($this->oso, 'office')
            ->get(route('office.accomplishment.template', ['kind' => 'narrative']))
            ->assertOk();
    }

    private function createOfficeUser(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'Accomplishment Test '.strtoupper($role).' '.Str::random(6),
            'email' => Str::uuid().'@test.example',
            'username' => 'user_'.Str::random(12),
            'password' => bcrypt(Str::random(32)),
            'office_title' => 'Accomplishment Test Desk',
            'office_role' => $role,
            'student_organization_id' => $organization?->id,
            'is_active' => true,
        ]);
    }

    private function createEndedApprovedActivity(string $title, array $overrides = []): OrgActivity
    {
        return OrgActivity::query()->create(array_merge([
            'title' => $title,
            'organization_name' => $this->organization->name,
            'workflow_status' => 'oc_approved',
            'status' => 'draft',
            'starts_at' => now()->subDays(5)->setTime(8, 30),
            'ends_at' => now()->subDays(5)->setTime(16, 30),
            'location' => 'Amphitheater, Main Campus',
            'approved_budget' => 500.00,
            'sdg_goals' => [4, 9],
        ], $overrides));
    }

    private function validReportPayload(int $activityId, array $overrides = []): array
    {
        return array_merge([
            'org_activity_id' => $activityId,
            'academic_year' => '2025-2026',
            'semester' => '1st Semester',
            'classification' => 'College-Based',
            'sponsor' => 'College Student Council',
            'brief_description' => 'A comprehensive student skills training activity with interactive sessions.',
            'objectives' => 'Enhance practical competencies and teamwork among student participants.',
            'narrative' => 'The program was executed according to schedule with active student engagement.',
            'people_involved' => 'Faculty advisers, student organization officers, and enrolled students',
            'male_participants' => 12,
            'female_participants' => 18,
            'problems_encountered' => 'Minor audio feedback during introductory remarks; swiftly resolved.',
            'recommendations' => 'Ensure complete sound check thirty minutes prior to event commencement.',
            'signatories' => [
                'secretary' => 'Alice Secretary',
                'auditor' => 'Bob Auditor',
                'president' => 'Charlie President',
                'adviser' => 'Dr. Adviser',
                'coordinator' => 'Prof. Coordinator',
                'head' => 'Dean Head',
            ],
            'photos' => [
                [
                    'file' => $this->validPngFile('evidence_1.png'),
                    'caption' => 'Hands-on hardware lab session',
                ],
            ],
        ], $overrides);
    }

    private function validPngBytes(int $width = 100, int $height = 100): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 230, 230, 230);
        imagefill($image, 0, 0, $background);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function validPngFile(string $name = 'photo.png', int $width = 100, int $height = 100): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_img_');
        $this->temporaryFiles[] = $path;
        file_put_contents($path, $this->validPngBytes($width, $height));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function validPdfBytes(): string
    {
        $stream = 'BT /F1 12 Tf 40 120 Td (Manual semester report fixture) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }

    private function stageValidFrDocument(
        ?string $organization = null,
        string $reportType = 'fr',
        string $filename = 'financial_report.pdf',
    ): OrgReportDocument {
        $orgName = $organization ?? $this->organization->name;
        $status = OrgReportStatus::query()->firstOrCreate([
            'organization_name' => $orgName,
            'report_type' => $reportType,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ], [
            'college' => 'College of Engineering',
            'status' => 'draft',
            'batch_key' => (string) Str::uuid(),
        ]);

        $pdfBytes = $this->validPdfBytes();
        $filePath = 'semester-reports/'.$reportType.'/'.Str::slug($orgName).'/2025_2026/1st-semester/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($filePath, $pdfBytes);

        return OrgReportDocument::query()->create([
            'org_report_status_id' => $status->id,
            'report_type' => $reportType,
            'organization_name' => $orgName,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
            'name' => $reportType === 'fr' ? 'Financial Report' : 'Accomplishment Report',
            'original_name' => $filename,
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size' => strlen($pdfBytes),
            'uploaded_by' => 'Fixture Setup',
        ]);
    }
}

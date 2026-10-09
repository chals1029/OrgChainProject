<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgCashIncome;
use App\Models\OrgFundAccount;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\ActivityBudgetService;
use App\Services\FinancialWorkbookService;
use App\Services\OrganizationCashLedger;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;
use ZipArchive;

class SoFinancialReportTest extends TestCase
{
    use UsesLaragonDatabase;

    private const SHEETS = [
        'Form 1 - SNW',
        'Form 2 - SOA',
        'Form 3 - SCF',
        'Form 4 - Cash Receipt',
        'Form 5 - Cash Disbursement',
        'Sheet1',
        'Re',
    ];

    private const PERIOD = ['academic_year' => '2025-2026', 'semester' => '1st Semester'];

    private const LEDGER_YEAR = '2025-2026';

    private const PDF_BYTES = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

    private const INJECTED_TEXT = '=HYPERLINK("https://attacker.example/","Click")';

    private StudentOrganization $organization;

    private StudentOrganization $foreignOrganization;

    private OfficeUser $so;

    private OfficeUser $oso;

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

        $this->organization = StudentOrganization::create([
            'name' => 'Financial Fixture '.Str::uuid(),
            'college' => 'Financial Test College',
            'is_active' => true,
        ]);
        $this->foreignOrganization = StudentOrganization::create([
            'name' => 'Foreign Financial Fixture '.Str::uuid(),
            'college' => 'Financial Test College',
            'is_active' => true,
        ]);
        $this->so = $this->office('so', $this->organization);
        $this->oso = $this->office('oso');
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

    public function test_created_report_counts_every_cash_row_and_previews_all_seven_sheets(): void
    {
        $templateHash = hash_file('sha256', $this->templatePath());

        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'First Semester Financial Report',
                'document' => $this->workbook($this->receipts(30), $this->disbursements(120), 500),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $document = OrgReportDocument::where('organization_name', $this->organization->name)->sole();
        $summary = $document->financial_summary;
        $this->assertTrue($document->hasStoredFile());
        $this->assertCount(30, $summary['receipts']);
        $this->assertCount(120, $summary['disbursements']);
        $this->assertEqualsWithDelta(3000.0, $summary['cash_inflow'], 0.001);
        $this->assertEqualsWithDelta(1260.0, $summary['cash_outflow'], 0.001);
        $this->assertEqualsWithDelta(500.0, $summary['beginning_balance'], 0.001);
        $this->assertEqualsWithDelta(2240.0, $summary['ending_balance'], 0.001);

        $preview = $this->getJson(route('office.financial.reports.preview', $document))
            ->assertOk()
            ->assertJsonPath('title', 'First Semester Financial Report')
            ->json();
        $this->assertSame(self::SHEETS, array_column($preview['sheets'], 'name'));
        $this->assertEqualsWithDelta(3000.0, $preview['cash_inflow'], 0.001);
        $this->assertEqualsWithDelta(1260.0, $preview['cash_outflow'], 0.001);
        $this->assertEqualsWithDelta(2240.0, $preview['balance'], 0.001);
        $disbursementSheet = $preview['sheets'][4];
        $this->assertGreaterThan(FinancialWorkbookService::PREVIEW_ROWS, $disbursementSheet['total_rows']);
        $this->assertCount(FinancialWorkbookService::PREVIEW_ROWS, $disbursementSheet['rows']);
        $this->assertSame('Date', $disbursementSheet['rows'][8][1]['text']);
        $this->assertSame('10.50', str_replace('₱', '', $disbursementSheet['rows'][9][9]['text']));

        $this->assertSame($templateHash, hash_file('sha256', $this->templatePath()));
    }

    public function test_invalid_blank_or_misdated_uploads_save_nothing(): void
    {
        $this->actingAs($this->so, 'office');
        $attempts = [
            UploadedFile::fake()->createWithContent('report.xlsx', 'this is not a workbook'),
            $this->workbook([], [], 0),
            UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
        ];
        foreach ($attempts as $file) {
            $this->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'Rejected report',
                'document' => $file,
            ])->assertSessionHasErrors('document');
        }
        $this->post(route('office.financial.reports.store'), [
            'name' => 'Rejected report',
            'academic_year' => '2025-2027',
            'semester' => '1st Semester',
            'document' => $this->workbook($this->receipts(1), [], 0),
        ])->assertSessionHasErrors('academic_year');

        $this->assertSame(0, OrgReportDocument::where('organization_name', $this->organization->name)->count());
        $this->assertSame(0, OrgReportStatus::where('organization_name', $this->organization->name)->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_history_uses_submission_date_and_uploaded_workbook_money_never_reaches_cash(): void
    {
        $archived = $this->reportStatusFixture('fr', '2024-2025', '1st Semester', 'archived', now()->subYears(2));
        $old = $this->document($archived, $this->summary(100, 1000, 400), 'Archived report', now()->subYears(2)->subDay());
        $draft = $this->reportStatusFixture('fr', '2025-2026', '1st Semester', 'draft');
        $revision = $this->document($draft, $this->summary(0, 5000, 5000), 'Superseded revision', now()->subYears(3));
        $newest = $this->document($draft, $this->summary(700, 800, 300, 'org  fair'), 'Newest revision');
        $foreign = $this->reportStatusFixture('fr', '2025-2026', '1st Semester', 'draft', null, $this->foreignOrganization->name);
        $this->document($foreign, $this->summary(0, 99999, 0), 'Foreign report');
        $currentYear = app(ActivityBudgetService::class)->period()['academic_year'];

        $this->actingAs($this->so, 'office')
            ->get(route('office.financial'))
            ->assertOk()
            ->assertViewHas('financialDashboard', function (array $dashboard) use ($old, $revision, $newest, $currentYear): bool {
                $this->assertSame($this->organization->name, $dashboard['organization']);
                $this->assertSame($currentYear, $dashboard['year_filter']);
                $this->assertSame($currentYear, $dashboard['management_year']);
                $this->assertFalse($dashboard['funding']['has_account']);
                foreach (['opening_balance', 'income', 'spent', 'reserved', 'cash', 'available'] as $key) {
                    $this->assertEqualsWithDelta(0.0, (float) $dashboard['funding'][$key], 0.001, $key);
                }
                foreach (['beginning_balance', 'cash_inflow', 'cash_outflow', 'ending_balance'] as $key) {
                    $this->assertEqualsWithDelta(0.0, (float) $dashboard[$key], 0.001, $key);
                }
                $this->assertSame([], $dashboard['activities']);
                $this->assertSame([$old->id], array_column($dashboard['history_reports'], 'id'));
                $this->assertEqualsCanonicalizing([$revision->id, $newest->id], array_column($dashboard['current_reports'], 'id'));
                $current = collect($dashboard['current_reports'])->keyBy('id');
                $this->assertNull($current[$revision->id]['submitted_at']);
                $this->assertFalse($current[$revision->id]['can_submit']);
                $this->assertEqualsWithDelta(1200.0, $current[$newest->id]['balance'], 0.001);
                foreach ([$currentYear, '2025-2026', '2024-2025'] as $year) {
                    $this->assertContains($year, $dashboard['years']);
                }

                return true;
            });

        $this->ledgerFixture();
        $before = app(OrganizationCashLedger::class)->funding($this->organization->name, self::LEDGER_YEAR);
        $this->post(route('office.financial.reports.store'), self::PERIOD + [
            'name' => 'Bogus upload',
            'document' => $this->workbook($this->receipts(30), $this->disbursements(3), 99999),
        ])->assertSessionHasNoErrors();
        $this->assertEquals($before, app(OrganizationCashLedger::class)->funding($this->organization->name, self::LEDGER_YEAR));
        $this->assertSame(1, OrgCashIncome::query()->where('org_fund_account_id', $this->account()->id)->count());

        $assertFlow = function (array $filters, float $beginning, float $inflow, float $outflow): void {
            $this->get(route('office.financial', $filters))
                ->assertOk()
                ->assertViewHas('financialDashboard', function (array $dashboard) use ($beginning, $inflow, $outflow): bool {
                    $this->assertEqualsWithDelta($beginning, $dashboard['beginning_balance'], 0.001);
                    $this->assertEqualsWithDelta($inflow, $dashboard['cash_inflow'], 0.001);
                    $this->assertEqualsWithDelta($outflow, $dashboard['cash_outflow'], 0.001);
                    $this->assertEqualsWithDelta($beginning + $inflow - $outflow, $dashboard['ending_balance'], 0.001);
                    $this->assertEqualsWithDelta($inflow, array_sum(array_column($dashboard['activities'], 'inflow')), 0.001);
                    $this->assertEqualsWithDelta($outflow, array_sum(array_column($dashboard['activities'], 'outflow')), 0.001);
                    $this->assertSame(self::LEDGER_YEAR, $dashboard['management_year']);
                    $this->assertTrue($dashboard['funding']['has_account']);
                    $this->assertEqualsWithDelta(1000.0, $dashboard['funding']['opening_balance'], 0.001);
                    $this->assertEqualsWithDelta(250.0, $dashboard['funding']['income'], 0.001);
                    $this->assertEqualsWithDelta(31.5, $dashboard['funding']['spent'], 0.001);
                    $this->assertEqualsWithDelta(1218.5, $dashboard['funding']['cash'], 0.001);
                    $this->assertEqualsWithDelta(568.5, $dashboard['funding']['reserved'], 0.001);
                    $this->assertEqualsWithDelta(650.0, $dashboard['funding']['available'], 0.001);

                    return true;
                });
        };
        $assertFlow(self::PERIOD, 1000.0, 250.0, 31.5);
        $assertFlow(['academic_year' => self::LEDGER_YEAR, 'semester' => '2nd Semester'], 1218.5, 0.0, 0.0);
        $assertFlow(['academic_year' => self::LEDGER_YEAR, 'semester' => 'all'], 1000.0, 250.0, 31.5);

        $foreignView = $this->actingAs($this->office('so', $this->foreignOrganization), 'office')
            ->get(route('office.financial', self::PERIOD))
            ->assertOk();
        $foreignView->assertViewHas('financialDashboard', function (array $dashboard): bool {
            $this->assertFalse($dashboard['funding']['has_account']);
            $this->assertEqualsWithDelta(0.0, $dashboard['cash_inflow'], 0.001);

            return true;
        });
    }

    public function test_saved_opening_and_income_forms_bind_the_assigned_organization(): void
    {
        $opening = ['_form' => 'financial-opening', 'academic_year' => self::LEDGER_YEAR, 'opening_balance' => '1000.00'];

        $this->actingAs($this->oso, 'office')->post(route('office.financial.opening'), $opening)->assertForbidden();
        $this->actingAs($this->oso, 'office')->post(route('office.financial.income'), $this->incomePayload())->assertForbidden();
        $this->assertFalse(OrgFundAccount::query()->where('organization_name', $this->organization->name)->exists());

        $this->actingAs($this->so, 'office');
        foreach ([['opening_balance' => '-1'], ['opening_balance' => '10.005'], ['academic_year' => '2025-2027']] as $invalid) {
            $this->from(route('office.financial'))
                ->post(route('office.financial.opening'), array_merge($opening, $invalid))
                ->assertRedirect(route('office.financial'))
                ->assertSessionHasErrors(array_keys($invalid))
                ->assertSessionHasInput('_form', 'financial-opening');
        }

        $this->post(route('office.financial.income'), $this->incomePayload())->assertSessionHasErrors();
        $this->assertFalse(OrgFundAccount::query()->where('organization_name', $this->organization->name)->exists());

        $this->post(route('office.financial.opening'), $opening + ['organization_name' => $this->foreignOrganization->name])
            ->assertRedirect(route('office.financial', ['academic_year' => self::LEDGER_YEAR]))
            ->assertSessionHasNoErrors();
        $this->assertFalse(OrgFundAccount::query()->where('organization_name', $this->foreignOrganization->name)->exists());
        $ledger = app(OrganizationCashLedger::class);
        $this->assertEqualsWithDelta(1000.0, $ledger->funding($this->organization->name, self::LEDGER_YEAR)['available'], 0.001);

        $this->from(route('office.financial', ['academic_year' => self::LEDGER_YEAR]))
            ->post(route('office.financial.income'), $this->incomePayload(['reference' => '']))
            ->assertSessionHasErrors('reference')
            ->assertSessionHasInput('_form', 'financial-income');
        foreach ([['amount' => '0'], ['amount' => '5.555'], ['transaction_date' => now()->addDay()->toDateString()], ['request_key' => 'not-a-uuid']] as $invalid) {
            $this->post(route('office.financial.income'), $this->incomePayload($invalid))->assertSessionHasErrors(array_keys($invalid));
        }

        $payload = $this->incomePayload();
        $this->post(route('office.financial.income'), $payload + ['organization_name' => $this->foreignOrganization->name])->assertSessionHasNoErrors();
        $this->post(route('office.financial.income'), $payload)->assertSessionHasNoErrors();
        $this->post(route('office.financial.income'), array_merge($payload, ['amount' => '999.00']))->assertSessionHasErrors();
        $this->post(route('office.financial.income'), $this->incomePayload())->assertSessionHasErrors();
        $account = $this->account();
        $this->assertSame(1, OrgCashIncome::query()->where('org_fund_account_id', $account->id)->count());
        $funding = $ledger->funding($this->organization->name, self::LEDGER_YEAR);
        $this->assertEqualsWithDelta(250.0, $funding['income'], 0.001);
        $this->assertEqualsWithDelta(1250.0, $funding['cash'], 0.001);
        $this->assertEqualsWithDelta(1250.0, $funding['available'], 0.001);

        $this->approvedActivity('Capacity check', 900);
        $this->post(route('office.financial.opening'), array_merge($opening, ['opening_balance' => '100.00']))->assertSessionHasErrors();
        $this->assertEqualsWithDelta(1000.0, $ledger->funding($this->organization->name, self::LEDGER_YEAR)['opening_balance'], 0.001);
        $this->post(route('office.financial.opening'), array_merge($opening, ['opening_balance' => '2000.00']))->assertSessionHasNoErrors();
        $funding = $ledger->funding($this->organization->name, self::LEDGER_YEAR);
        $this->assertEqualsWithDelta(900.0, $funding['reserved'], 0.001);
        $this->assertEqualsWithDelta(2250.0, $funding['cash'], 0.001);
        $this->assertEqualsWithDelta(1350.0, $funding['available'], 0.001);
        $this->assertSame($account->id, $this->account()->id);
    }

    public function test_foreign_documents_are_forbidden_to_the_so_desk(): void
    {
        $foreignStatus = $this->reportStatusFixture('fr', '2025-2026', '1st Semester', 'draft', null, $this->foreignOrganization->name);
        $foreign = $this->document($foreignStatus, $this->summary(0, 10, 0), 'Foreign report');
        $ownStatus = $this->reportStatusFixture('fr', '2025-2026', '1st Semester', 'draft');
        $own = $this->document($ownStatus, $this->summary(0, 10, 0), 'Own report');

        $this->actingAs($this->so, 'office');
        $this->getJson(route('office.financial.reports.preview', $foreign))->assertForbidden();
        $this->post(route('office.financial.reports.submit', $foreign))->assertForbidden();
        $this->get(route('office.reports.documents.view', ['document' => $foreign, 'download' => 1]))->assertForbidden();
        $this->post(route('office.reports.submit', ['reportType' => 'fr']), [
            'organization_name' => $this->foreignOrganization->name,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ])->assertForbidden();
        $this->get(route('office.reports.documents.view', ['document' => $own, 'download' => 1]))->assertOk();
        $this->assertSame('draft', $foreignStatus->refresh()->status);

        // While in draft, even OSO cannot view/download foreign document (draft privacy)
        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', ['document' => $foreign, 'download' => 1]))
            ->assertForbidden();

        // Once submitted to OSO review with submitted_at timestamp, OSO can view/download
        $foreignStatus->update([
            'status' => 'oso_review',
            'submitted_at' => now(),
        ]);
        $this->actingAs($this->oso, 'office')
            ->get(route('office.reports.documents.view', ['document' => $foreign, 'download' => 1]))
            ->assertOk();
    }

    public function test_fr_submit_requires_actual_fr_file_and_succeeds_without_ar(): void
    {
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'Submitted report',
                'document' => $this->workbook($this->receipts(2), $this->disbursements(2), 0),
            ])
            ->assertSessionHasNoErrors();
        $fr = OrgReportDocument::where('organization_name', $this->organization->name)->sole();

        // An absent or incomplete AR does NOT block FR submission under independent reports.
        $arStatus = $this->reportStatusFixture('ar', '2025-2026', '1st Semester', 'draft');
        $this->document($arStatus, null, 'Accomplishment Report', null, false);

        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        $disk = Storage::disk('public');
        $frFilePath = $fr->file_path;
        $frContent = $disk->get($frFilePath);

        // Missing or empty FR file blocks FR submission
        $disk->put($frFilePath, '');
        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasErrors('report');
        $this->post(route('office.reports.submit', ['reportType' => 'fr']), [
            'organization_name' => $this->organization->name,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ])->assertSessionHasErrors('report');

        // Restoring the FR file allows submission; AR missing evidence does not block FR!
        $disk->put($frFilePath, $frContent);
        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();

        $this->post(route('office.financial.reports.submit', $fr))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        // Only FR transitions to oso_review; AR remains draft!
        $this->assertSame('oso_review', OrgReportStatus::where('organization_name', $this->organization->name)->where('report_type', 'fr')->value('status'));
        $this->assertSame('draft', OrgReportStatus::where('organization_name', $this->organization->name)->where('report_type', 'ar')->value('status'));

        // Resubmission of locked FR is blocked
        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasErrors('report');

        $missingStatus = $this->reportStatusFixture('fr', '2025-2026', '2nd Semester', 'draft');
        $missing = $this->document($missingStatus, $this->summary(0, 10, 0), 'Missing file', null, false);
        $this->document($this->reportStatusFixture('ar', '2025-2026', '2nd Semester', 'draft'), null, 'AR with file');
        $this->getJson(route('office.financial.reports.preview', $missing))->assertNotFound();
        $this->post(route('office.financial.reports.submit', $missing))->assertSessionHasErrors('report');
        $this->assertSame('draft', $missingStatus->refresh()->status);
    }

    public function test_submit_requires_a_successful_view_of_the_current_file_version(): void
    {
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'Gated report',
                'document' => $this->workbook($this->receipts(2), $this->disbursements(2), 0),
            ])
            ->assertSessionHasNoErrors();
        $fr = OrgReportDocument::where('organization_name', $this->organization->name)->sole();
        $this->document($this->reportStatusFixture('ar', '2025-2026', '1st Semester', 'draft'), null, 'Accomplishment Report');
        $assertDraft = fn () => $this->assertSame(
            ['draft'],
            OrgReportStatus::where('organization_name', $this->organization->name)->pluck('status')->unique()->values()->all()
        );

        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasErrors('report');
        $assertDraft();

        $this->get(route('office.financial.template'))->assertOk();
        $this->get(route('office.reports.documents.view', ['document' => $fr, 'download' => 1]))->assertOk();
        $this->get(route('office.reports.documents.view', $fr))->assertOk();
        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasErrors('report');
        $assertDraft();

        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        $replacement = $this->workbook($this->receipts(3), $this->disbursements(1), 0);
        Storage::disk('public')->put($fr->file_path, (string) file_get_contents($replacement->getRealPath()));
        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasErrors('report');
        $assertDraft();

        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        $this->post(route('office.financial.reports.submit', $fr))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('oso_review', OrgReportStatus::where('organization_name', $this->organization->name)->where('report_type', 'fr')->value('status'));
        $this->assertSame('draft', OrgReportStatus::where('organization_name', $this->organization->name)->where('report_type', 'ar')->value('status'));

        $legacyStatus = $this->reportStatusFixture('fr', '2025-2026', '2nd Semester', 'draft');
        $this->document($this->reportStatusFixture('ar', '2025-2026', '2nd Semester', 'draft'), null, 'Second AR');
        $docx = $this->document($legacyStatus, null, 'Legacy docx');
        $docx->update(['original_name' => 'legacy.docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        $this->getJson(route('office.financial.reports.preview', $docx))->assertStatus(422);
        $this->get(route('office.reports.documents.view', $docx))->assertOk();
        $this->post(route('office.financial.reports.submit', $docx))->assertSessionHasErrors('report');
        $this->assertSame('draft', $legacyStatus->refresh()->status);

        $fakePdf = $this->document($legacyStatus, null, 'Fake pdf');
        $this->get(route('office.reports.documents.view', $fakePdf))->assertOk();
        $this->post(route('office.financial.reports.submit', $fakePdf))->assertSessionHasErrors('report');
        $this->assertSame('draft', $legacyStatus->refresh()->status);

        $pdf = $this->document($legacyStatus, null, 'Legacy pdf', null, true, self::PDF_BYTES);
        $this->post(route('office.financial.reports.submit', $pdf))->assertSessionHasErrors('report');
        $this->get(route('office.reports.documents.view', ['document' => $pdf, 'download' => 1]))->assertOk();
        $this->post(route('office.financial.reports.submit', $pdf))->assertSessionHasErrors('report');
        $this->assertSame('draft', $legacyStatus->refresh()->status);
        $this->get(route('office.reports.documents.view', $pdf))->assertOk();
        $this->post(route('office.financial.reports.submit', $pdf))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertSame('oso_review', $legacyStatus->refresh()->status);
    }

    public function test_peer_ar_locked_state_does_not_block_fr_upload_or_submission_while_duplicate_locked_fr_does_block(): void
    {
        $this->actingAs($this->so, 'office');

        // Lock the AR peer to verified and archived
        $arStatus = $this->reportStatusFixture('ar', '2025-2026', '1st Semester', 'archived');
        $this->document($arStatus, null, 'Archived AR');

        // SO can still store and preview new FR
        $this->post(route('office.financial.reports.store'), self::PERIOD + [
            'name' => 'Peer unblocked FR',
            'document' => $this->workbook($this->receipts(2), $this->disbursements(1), 0),
        ])->assertSessionHasNoErrors();

        $fr = OrgReportDocument::where('organization_name', $this->organization->name)
            ->where('report_type', 'fr')->latest('id')->firstOrFail();

        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();

        // Submitting FR succeeds despite peer AR being archived
        $this->post(route('office.financial.reports.submit', $fr))
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('oso_review', OrgReportStatus::where('organization_name', $this->organization->name)->where('report_type', 'fr')->value('status'));
        $this->assertSame('archived', $arStatus->refresh()->status);

        // Same-type locked duplicate rows DO block FR submission
        $duplicatePeriod = [
            'organization_name' => $this->organization->name,
            'semester' => '2nd Semester',
            'academic_year' => '2025-2026',
        ];
        $this->post(route('office.financial.reports.store'), $duplicatePeriod + [
            'name' => 'Duplicate test FR',
            'document' => $this->workbook($this->receipts(1), $this->disbursements(1), 0),
        ])->assertSessionHasNoErrors();
        $fr2 = OrgReportDocument::where('organization_name', $this->organization->name)
            ->where('report_type', 'fr')->latest('id')->firstOrFail();
        $this->getJson(route('office.financial.reports.preview', $fr2))->assertOk();

        $duplicateLockedFr = OrgReportStatus::query()->create([
            'report_type' => 'fr',
            'organization_name' => $this->organization->name,
            'semester' => '2nd Semester',
            'academic_year' => '2025-2026',
            'status' => 'verified',
        ]);

        $this->post(route('office.financial.reports.submit', $fr2))
            ->assertSessionHasErrors('report');
        $duplicateLockedFr->delete();
    }

    public function test_fr_returned_state_and_notes_persist_until_resubmit_and_rejected_is_terminal(): void
    {
        $this->actingAs($this->so, 'office');
        $this->post(route('office.financial.reports.store'), self::PERIOD + [
            'name' => 'Initial FR',
            'document' => $this->workbook($this->receipts(2), $this->disbursements(1), 0),
        ])->assertSessionHasNoErrors();
        $fr = OrgReportDocument::where('organization_name', $this->organization->name)
            ->where('report_type', 'fr')->latest('id')->firstOrFail();
        $this->getJson(route('office.financial.reports.preview', $fr))->assertOk();
        $this->post(route('office.financial.reports.submit', $fr))->assertSessionHasNoErrors();

        // OSO returns the FR with notes
        $this->actingAs($this->oso, 'office')
            ->post(route('office.reports.review', ['reportType' => 'fr']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
                'decision' => 'return',
                'notes' => 'Please fix cash receipt references.',
            ])
            ->assertRedirect();

        $frStatus = OrgReportStatus::query()
            ->where('organization_name', $this->organization->name)
            ->where('report_type', 'fr')
            ->sole();
        $this->assertSame('returned', $frStatus->status);
        $this->assertSame('Please fix cash receipt references.', $frStatus->notes);

        // SO uploads revised FR workbook: returned state + reason persist!
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'Revised FR',
                'document' => $this->workbook($this->receipts(3), $this->disbursements(1), 0),
            ])
            ->assertSessionHasNoErrors();

        $frStatus->refresh();
        $this->assertSame('returned', $frStatus->status);
        $this->assertSame('Please fix cash receipt references.', $frStatus->notes);

        // View revised FR and resubmit
        $revisedFr = OrgReportDocument::where('organization_name', $this->organization->name)
            ->where('report_type', 'fr')->latest('id')->firstOrFail();
        $this->getJson(route('office.financial.reports.preview', $revisedFr))->assertOk();
        $this->post(route('office.financial.reports.submit', $revisedFr))->assertSessionHasNoErrors();
        $this->assertSame('oso_review', $frStatus->refresh()->status);

        // OSO rejects with required reason
        $this->actingAs($this->oso, 'office')
            ->post(route('office.reports.review', ['reportType' => 'fr']), [
                'organization_name' => $this->organization->name,
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
                'decision' => 'reject',
                'notes' => 'Discrepancies found with physical receipts.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $frStatus->refresh()->status);

        // Rejected is terminal: SO cannot resubmit
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.submit', $revisedFr))
            ->assertSessionHasErrors('report');
        $this->assertSame('rejected', $frStatus->refresh()->status);
    }

    public function test_literal_formula_text_from_an_upload_stays_text_in_preview_and_export(): void
    {
        $literal = [
            'Form 4 - Cash Receipt' => ['C10' => self::INJECTED_TEXT, 'E10' => self::INJECTED_TEXT, 'G10' => self::INJECTED_TEXT, 'K10' => self::INJECTED_TEXT],
            'Form 5 - Cash Disbursement' => ['D10' => self::INJECTED_TEXT],
        ];
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.reports.store'), self::PERIOD + [
                'name' => 'Injection fixture',
                'document' => $this->workbook($this->receipts(2), $this->disbursements(1), 0, $literal),
            ])
            ->assertSessionHasNoErrors();
        $document = OrgReportDocument::where('organization_name', $this->organization->name)->sole();
        $this->assertSame(self::INJECTED_TEXT, $document->financial_summary['receipts'][0]['event']);
        $this->assertSame(self::INJECTED_TEXT, $document->financial_summary['disbursements'][0]['reference']);

        $preview = $this->getJson(route('office.financial.reports.preview', $document))->assertOk()->json();
        $this->assertSame(self::INJECTED_TEXT, $preview['sheets'][3]['rows'][9][2]['text']);

        $this->ledgerFixture(self::INJECTED_TEXT, [
            'purpose' => self::INJECTED_TEXT,
            'received_from' => self::INJECTED_TEXT,
            'reference' => self::INJECTED_TEXT,
        ], [
            'item_name' => self::INJECTED_TEXT,
            'supplier' => self::INJECTED_TEXT,
            'receipt_reference' => self::INJECTED_TEXT,
        ]);
        $response = $this->get(route('office.financial.export', self::PERIOD))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;

        $spreadsheet = (new XlsxReader())->load($path);
        $textCells = [];
        foreach (['Form 4 - Cash Receipt', 'Form 5 - Cash Disbursement'] as $title) {
            $sheet = $spreadsheet->getSheetByName($title);
            $matches = array_values(array_filter(
                ['C', 'D', 'E', 'F', 'G', 'K'],
                fn (string $column): bool => $sheet->getCell($column.'10')->getValue() === self::INJECTED_TEXT
            ));
            $this->assertNotSame([], $matches, $title.' should list the recorded ledger text.');
            foreach ($matches as $column) {
                $textCells[] = [$title, $column.'10'];
            }
        }
        foreach (['Form 3 - SCF', 'Form 2 - SOA'] as $title) {
            $sheet = $spreadsheet->getSheetByName($title);
            $match = collect(range(12, 45))->first(fn (int $row): bool => $sheet->getCell('C'.$row)->getValue() === self::INJECTED_TEXT);
            $this->assertNotNull($match, $title.' should list the actual event label as text.');
            $textCells[] = [$title, 'C'.$match];
        }
        foreach ($textCells as [$title, $coordinate]) {
            $cell = $spreadsheet->getSheetByName($title)->getCell($coordinate);
            $this->assertFalse($cell->isFormula(), $title.'!'.$coordinate.' must not be a formula.');
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), $title.'!'.$coordinate);
            $this->assertSame(self::INJECTED_TEXT, $cell->getValue());
        }
        $spreadsheet->disconnectWorksheets();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if (str_starts_with($name, 'xl/worksheets/sheet')) {
                $this->assertDoesNotMatchRegularExpression('/<f[^>]*>[^<]*HYPERLINK/i', (string) $zip->getFromIndex($index), $name);
            }
        }
        $zip->close();
    }

    public function test_export_fills_a_copy_of_the_template_with_saved_ledger_rows_only(): void
    {
        $templateHash = hash_file('sha256', $this->templatePath());
        $this->actingAs($this->so, 'office');
        $noAccount = $this->get(route('office.financial.export', self::PERIOD))->assertOk();
        $this->assertStringContainsString('blank template', (string) $noAccount->headers->get('Content-Disposition'));

        $this->ledgerFixture();
        $this->post(route('office.financial.reports.store'), self::PERIOD + [
            'name' => 'Bogus export source',
            'document' => $this->workbook($this->receipts(30), $this->disbursements(3), 99999),
        ])->assertSessionHasNoErrors();

        foreach ([400, -5] as $override) {
            $this->get(route('office.financial.export', self::PERIOD + ['beginning_balance' => $override]))
                ->assertSessionHasErrors('beginning_balance');
        }

        $response = $this->get(route('office.financial.export', self::PERIOD))->assertOk();
        $this->assertSame(FinancialWorkbookService::MIME_TYPE, $response->headers->get('Content-Type'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;

        $spreadsheet = (new XlsxReader())->load($path);
        $this->assertSame(self::SHEETS, $spreadsheet->getSheetNames());
        $rowText = fn (string $sheet, int $row): string => implode(' | ', array_map(
            fn (string $column): string => (string) $spreadsheet->getSheetByName($sheet)->getCell($column.$row)->getValue(),
            ['C', 'D', 'E', 'F', 'G', 'K']
        ));
        $amount = fn (string $sheet, int $row): mixed => $spreadsheet->getSheetByName($sheet)->getCell('J'.$row)->getValue();
        $this->assertEqualsWithDelta(250.0, (float) $amount('Form 4 - Cash Receipt', 10), 0.001);
        $this->assertStringContainsString('OR-INFLOW-1', $rowText('Form 4 - Cash Receipt', 10));
        $this->assertSame('', trim(str_replace('|', '', $rowText('Form 4 - Cash Receipt', 11))));
        $this->assertEqualsWithDelta(31.5, (float) $amount('Form 5 - Cash Disbursement', 10), 0.001);
        $this->assertStringContainsString('SI-1001', $rowText('Form 5 - Cash Disbursement', 10));
        $this->assertSame('', trim(str_replace('|', '', $rowText('Form 5 - Cash Disbursement', 11))));
        $this->assertStringNotContainsString('CR-30', implode(' ', array_map('strval', array_merge(...$spreadsheet->getSheetByName('Form 4 - Cash Receipt')->toArray(null, false, false)))));

        $statementSheet = $spreadsheet->getSheetByName('Form 3 - SCF');
        $labels = array_filter(array_map(fn (int $row): mixed => $statementSheet->getCell('C'.$row)->getValue(), range(12, 45)));
        $this->assertContains('Org Fair', $labels);
        $this->assertNotContains('Membership Fees (Second Semester)', $labels);
        $this->assertNotContains('Expenses for Bihis Spartan', $labels);
        $this->assertNotContains('CONAHS Student Council Contribution (Sandugan)', $labels);
        $this->assertSame('Cash Balance, beginning of 1st Semester AY 2025-2026', $statementSheet->getCell('B46')->getValue());
        $this->assertEquals(1000, $statementSheet->getCell('F46')->getValue());

        $netWorthSheet = $spreadsheet->getSheetByName('Form 1 - SNW');
        $this->assertSame('Net Worth, end of 1st Semester AY 2025-2026', $netWorthSheet->getCell('C27')->getValue());
        $this->assertEquals(1000, $netWorthSheet->getCell('F25')->getValue());
        $this->assertEqualsWithDelta(1218.5, (float) $netWorthSheet->getCell('F12')->getValue(), 0.001);

        $sample = $spreadsheet->getSheetByName('Sheet1');
        $this->assertSame('hidden', $sample->getSheetState());
        $this->assertSame('Particulars', $sample->getCell('B2')->getValue());
        foreach (['B3', 'C3', 'E3', 'E8', 'F8'] as $coordinate) {
            $this->assertNull($sample->getCell($coordinate)->getValue());
        }
        $this->assertStringNotContainsString('Water Gun', implode(' ', array_map('strval', array_merge(...$sample->toArray(null, false, false)))));

        foreach (['F13', 'F14', 'F19', 'F20', 'F21'] as $coordinate) {
            $this->assertNull($netWorthSheet->getCell($coordinate)->getValue());
        }
        $notes = $spreadsheet->getSheetByName('Re');
        foreach (['D13', 'D14', 'D15'] as $coordinate) {
            $this->assertNull($notes->getCell($coordinate)->getValue());
        }

        $cached = fn (string $sheet, string $coordinate): float => (float) $spreadsheet->getSheetByName($sheet)->getCell($coordinate)->getOldCalculatedValue();
        $this->assertEqualsWithDelta(1218.5, $cached('Form 1 - SNW', 'F16'), 0.001);
        $this->assertEqualsWithDelta(1218.5, $cached('Form 1 - SNW', 'F27'), 0.001);
        $this->assertEqualsWithDelta(1218.5, $cached('Form 1 - SNW', 'F29'), 0.001);
        $this->assertEqualsWithDelta(1218.5, (float) $notes->getCell('D16')->getValue(), 0.001);
        $spreadsheet->disconnectWorksheets();

        $drawingText = '';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        for ($index = 0; $index < $zip->numFiles; $index++) {
            if (str_starts_with((string) $zip->getNameIndex($index), 'xl/drawings/drawing')) {
                $drawingText .= (string) $zip->getFromIndex($index);
            }
        }
        $zip->close();
        $this->assertStringContainsString('BATANGAS STATE UNIVERSITY', $drawingText);
        $this->assertStringContainsString(e(mb_strtoupper($this->organization->name)), $drawingText);
        $this->assertStringContainsString('For 1st Semester AY 2025-2026', $drawingText);
        $this->assertStringNotContainsString('SUPREME STUDENT COUNCIL', $drawingText);
        $this->assertStringNotContainsString('May 31', $drawingText);

        $roundTrip = app(FinancialWorkbookService::class)->summarize($path);
        $this->assertEqualsWithDelta(250.0, $roundTrip['cash_inflow'], 0.001);
        $this->assertEqualsWithDelta(31.5, $roundTrip['cash_outflow'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $roundTrip['beginning_balance'], 0.001);
        $this->assertEqualsWithDelta(1218.5, $roundTrip['ending_balance'], 0.001);
        $this->assertCount(1, $roundTrip['receipts']);
        $this->assertCount(1, $roundTrip['disbursements']);

        $blank = $this->get(route('office.financial.export', ['academic_year' => '2030-2031', 'semester' => 'Midyear']))->assertOk();
        $this->assertStringContainsString('blank template', (string) $blank->headers->get('Content-Disposition'));

        $this->assertSame($templateHash, hash_file('sha256', $this->templatePath()));
    }

    private function office(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        return OfficeUser::create([
            'name' => 'Financial Fixture '.strtoupper($role),
            'email' => Str::uuid().'@example.test',
            'username' => 'financial_'.Str::random(16),
            'password' => Str::random(32),
            'office_title' => 'Financial fixture desk',
            'office_role' => $role,
            'student_organization_id' => $organization?->id,
            'is_active' => true,
        ]);
    }

    private function templatePath(): string
    {
        return app(FinancialWorkbookService::class)->templatePath();
    }

    /**
     * Real ledger inputs through the SO forms and the receipt service:
     * ₱1,000 opening, ₱250 inflow and a ₱31.50 receipt for a ₱600 activity.
     *
     * @param  array<string, mixed>  $income
     * @param  array<string, mixed>  $receipt
     */
    private function ledgerFixture(string $activityTitle = 'Org Fair', array $income = [], array $receipt = []): OrgActivity
    {
        $this->actingAs($this->so, 'office')
            ->post(route('office.financial.opening'), [
                '_form' => 'financial-opening',
                'academic_year' => self::LEDGER_YEAR,
                'opening_balance' => '1000.00',
            ])
            ->assertSessionHasNoErrors();
        $activity = $this->approvedActivity($activityTitle, 600);
        $this->post(route('office.financial.income'), $this->incomePayload($income + ['org_activity_id' => $activity->id]))
            ->assertSessionHasNoErrors();
        app(ActivityBudgetService::class)->record($activity, $this->so, $receipt + [
            'request_key' => (string) Str::uuid(),
            'item_name' => 'Booth supplies',
            'category' => 'Supplies',
            'supplier' => 'Campus Supplier',
            'quantity' => 2,
            'unit_cost' => '15.75',
            'expense_date' => '2025-09-16',
            'receipt_reference' => 'SI-1001',
        ], UploadedFile::fake()->createWithContent('receipt.jpg', 'receipt photo '.Str::uuid()));

        return $activity->refresh();
    }

    private function approvedActivity(string $title, float $approvedBudget): OrgActivity
    {
        return OrgActivity::create([
            'title' => $title,
            'organization_name' => $this->organization->name,
            'workflow_status' => 'oc_approved',
            'status' => 'draft',
            'starts_at' => '2025-09-15 09:00:00',
            'ends_at' => '2025-09-15 17:00:00',
            'approved_budget' => $approvedBudget,
            'org_fund_account_id' => $this->account()->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function incomePayload(array $overrides = []): array
    {
        return array_merge([
            '_form' => 'financial-income',
            'academic_year' => self::LEDGER_YEAR,
            'transaction_date' => '2025-09-20',
            'amount' => '250.00',
            'purpose' => 'Org Fair ticket sales',
            'received_from' => 'Org Fair attendees',
            'reference' => 'OR-INFLOW-1',
            'request_key' => (string) Str::uuid(),
        ], $overrides);
    }

    private function account(): OrgFundAccount
    {
        return OrgFundAccount::query()
            ->where('organization_name', $this->organization->name)
            ->where('fiscal_year', self::LEDGER_YEAR)
            ->sole();
    }

    private function reportStatusFixture(
        string $type,
        string $academicYear,
        string $semester,
        string $state,
        mixed $submittedAt = null,
        ?string $organization = null,
    ): OrgReportStatus {
        return OrgReportStatus::create([
            'report_type' => $type,
            'organization_name' => $organization ?? $this->organization->name,
            'college' => 'Financial Test College',
            'semester' => $semester,
            'academic_year' => $academicYear,
            'status' => $state,
            'batch_key' => (string) Str::uuid(),
            'submitted_at' => $submittedAt,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $summary
     */
    private function document(
        OrgReportStatus $status,
        ?array $summary,
        string $name,
        mixed $createdAt = null,
        bool $withFile = true,
        string $content = 'fixture',
    ): OrgReportDocument {
        $path = 'semester-reports/'.$status->report_type.'/fixture/'.Str::uuid().($summary !== null ? '.xlsx' : '.pdf');
        if ($withFile) {
            Storage::disk('public')->put($path, $content);
        }
        $document = OrgReportDocument::create([
            'org_report_status_id' => $status->id,
            'report_type' => $status->report_type,
            'organization_name' => $status->organization_name,
            'semester' => $status->semester,
            'academic_year' => $status->academic_year,
            'name' => $name,
            'original_name' => basename($path),
            'file_path' => $path,
            'mime_type' => $summary !== null ? FinancialWorkbookService::MIME_TYPE : 'application/pdf',
            'file_size' => strlen($content),
            'financial_summary' => $summary,
            'uploaded_by' => 'Financial Fixture',
        ]);
        if ($createdAt !== null) {
            $document->created_at = $createdAt;
            $document->save();
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(float $beginning, float $inflow, float $outflow, string $event = 'Org Fair'): array
    {
        return [
            'beginning_balance' => $beginning,
            'cash_inflow' => $inflow,
            'cash_outflow' => $outflow,
            'ending_balance' => round($beginning + $inflow - $outflow, 2),
            'inflow_source' => 'cash_receipts',
            'outflow_source' => 'cash_disbursements',
            'activities' => [['name' => $event, 'inflow' => $inflow, 'outflow' => $outflow]],
            'receipts' => [],
            'disbursements' => [],
        ];
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function receipts(int $count): array
    {
        $date = ExcelDate::PHPToExcel(new DateTimeImmutable('2025-09-01'));

        return array_map(fn (int $index): array => [
            $date + $index, 'Org Fair', 'CR-'.$index, 'Member '.$index, 'Collections', 'Membership fee', 1, 100, 100, null,
        ], range(1, $count));
    }

    /**
     * Amounts are left as uncached H*I formulas so totals must come from the
     * quantity and unit cost columns.
     *
     * @return list<array<int, mixed>>
     */
    private function disbursements(int $count): array
    {
        $date = ExcelDate::PHPToExcel(new DateTimeImmutable('2025-09-02'));

        return array_map(fn (int $index): array => [
            $date + $index, 'Org Fair', 'OR-'.$index, 'Campus Supplier', 'Supplies', 'Item '.$index, 2, 5.25, null, null,
        ], range(1, $count));
    }

    /**
     * Build an upload from the supplied template the way an organization
     * would fill it in, inserting rows above the totals when needed.
     *
     * @param  list<array<int, mixed>>  $receipts
     * @param  list<array<int, mixed>>  $disbursements
     */
    private function workbook(array $receipts, array $disbursements, float $beginning, array $literalText = []): UploadedFile
    {
        $reader = new XlsxReader();
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($this->templatePath());

        foreach ([
            ['Form 4 - Cash Receipt', $receipts, 22],
            ['Form 5 - Cash Disbursement', $disbursements, 250],
        ] as [$title, $rows, $capacity]) {
            $sheet = $spreadsheet->getSheetByName($title);
            if (count($rows) > $capacity) {
                $sheet->insertNewRowBefore(9 + $capacity, count($rows) - $capacity);
            }
            foreach ($rows as $index => $values) {
                $row = 10 + $index;
                $values[8] ??= '=H'.$row.'*I'.$row;
                $sheet->fromArray([$values], null, 'B'.$row);
            }
        }
        $spreadsheet->getSheetByName('Form 3 - SCF')->setCellValue('F46', $beginning);
        foreach ($literalText as $title => $cells) {
            foreach ($cells as $coordinate => $text) {
                $spreadsheet->getSheetByName($title)->setCellValueExplicit($coordinate, $text, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'orgchain-fr-test-');
        $this->temporaryFiles[] = $path;
        $writer = new XlsxWriter($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, 'Financial Report_New Format.xlsx', FinancialWorkbookService::MIME_TYPE, null, true);
    }
}

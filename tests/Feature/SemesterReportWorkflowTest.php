<?php

namespace Tests\Feature;

use App\Models\ArchiveFolder;
use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\OrgActivity;
use App\Services\OrgWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\FinancialWorkbookService;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class SemesterReportWorkflowTest extends TestCase
{
    use UsesLaragonDatabase;

    private StudentOrganization $organization;

    /** @var array<string, OfficeUser> */
    private array $offices = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        $this->organization = StudentOrganization::create([
            'name' => 'Report Workflow Fixture '.Str::uuid(),
            'college' => 'Workflow Test College',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->temporaryFiles as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
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

    private function office(string $role): OfficeUser
    {
        return $this->offices[$role] ??= OfficeUser::create([
            'name' => strtoupper($role).' Workflow Fixture',
            'email' => 'workflow-'.$role.'-'.Str::uuid().'@g.batstate-u.edu.ph',
            'username' => 'workflow_'.$role.'_'.Str::random(12),
            'password' => 'FixtureOnly@2026!',
            'office_role' => $role,
            'office_title' => strtoupper($role).' Desk',
            'student_organization_id' => $role === 'so' ? $this->organization->id : null,
            'is_active' => true,
        ]);
    }

    private function reportPdf(string $name): UploadedFile
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

        return UploadedFile::fake()->createWithContent($name, $pdf);
    }

    private function reportXlsx(string $name): UploadedFile
    {
        $templatePath = app(FinancialWorkbookService::class)->templatePath();
        $reader = new XlsxReader();
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($templatePath);

        // Set meaningful beginning balance on Form 3 - SCF so FinancialWorkbookService::summarize validates
        $spreadsheet->getSheetByName('Form 3 - SCF')->setCellValue('F46', 10000.0);

        $path = tempnam(sys_get_temp_dir(), 'orgchain-wf-fr-');
        $this->temporaryFiles[] = $path;
        $writer = new XlsxWriter($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, $name, FinancialWorkbookService::MIME_TYPE, null, true);
    }

    public function test_new_so_activity_submission_starts_at_oso_and_resubmission_skips_college_review(): void
    {
        $activity = OrgActivity::query()->create([
            'title' => 'Direct OSO Workflow '.uniqid(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'status' => 'draft',
            'workflow_status' => 'created',
            'location' => 'Campus',
            'approved_budget' => 1000,
        ]);

        try {
            $workflow = app(OrgWorkflowService::class);

            $workflow->advance($activity, 'so');
            $this->assertSame('oso_review', $activity->refresh()->workflow_status);
            $this->assertSame('oso_review', $workflow->nextStatus('returned', 'so'));
        } finally {
            $activity->delete();
        }
    }

    public function test_so_submits_ar_independently_and_oso_archives_it_while_fr_remains_unaffected(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        // SO stages AR document
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/documents', $period + [
                'name' => 'Accomplishment Report',
                'document' => $this->reportPdf('accomplishment.pdf'),
            ])
            ->assertRedirect();

        // SO submits AR independently to OSO (no FR exists)
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('org_report_statuses', [
            'report_type' => 'ar',
            'organization_name' => $organization,
            'status' => 'oso_review',
        ]);
        // FR is completely untouched
        $this->assertDatabaseMissing('org_report_statuses', [
            'report_type' => 'fr',
            'organization_name' => $organization,
        ]);

        $stagedDocument = OrgReportDocument::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'ar')
            ->firstOrFail();

        // Unauthorized offices are forbidden
        foreach (['sdo', 'ovcaa'] as $restrictedRole) {
            $this->actingAs($this->office($restrictedRole), 'office')
                ->get('/office-desk/reports/documents/'.$stagedDocument->id.'/view')
                ->assertForbidden();
            $this->actingAs($this->office($restrictedRole), 'office')
                ->post('/office-desk/reports/ar/review', $period + [
                    'decision' => 'accept',
                    'notes' => 'Unauthorized review',
                ])
                ->assertForbidden();
        }

        // OSO navigates report browser and views submitted AR document
        $this->actingAs($this->office('oso'), 'office')
            ->get(route('office.reports.index', ['organization' => $organization, 'semester' => '1st Semester', 'academic_year' => '2025-2026', 'tab' => 'ar']))
            ->assertOk();

        $this->actingAs($this->office('oso'), 'office')
            ->get('/office-desk/reports/documents/'.$stagedDocument->id.'/view')
            ->assertOk();

        // AR marked opened by OSO; peer FR still absent
        $this->assertSame($this->office('oso')->id, OrgReportStatus::query()
            ->where('report_type', 'ar')
            ->where('organization_name', $organization)
            ->value('opened_by'));

        // OSO reviews and archives AR only
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/ar/review', $period + [
                'decision' => 'accept',
                'notes' => 'Accepted and verified for semester archive.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, OrgReportStatus::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'ar')
            ->where('status', 'archived')
            ->count());
        $this->assertSame(0, OrgReportStatus::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'fr')
            ->count());
        $this->assertSame(1, ArchiveFolder::query()
            ->where('organization_name', $organization)
            ->where('semester', '1st Semester')
            ->count());
    }

    public function test_so_submits_fr_independently_after_exact_latest_viewed_while_ar_remains_unaffected(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        // SO stages FR document
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/documents', $period + [
                'name' => 'Financial Report',
                'document' => $this->reportXlsx('financial.xlsx'),
            ])
            ->assertRedirect();

        $frDocument = OrgReportDocument::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'fr')
            ->sole();

        // FR submission requires viewing exact latest file first
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');

        // Raw download/binary view of XLSX does NOT mark the workbook preview gate
        $this->actingAs($this->office('so'), 'office')
            ->get(route('office.reports.documents.view', $frDocument))
            ->assertOk();
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');

        // SO views actual JSON workbook preview endpoint, which marks the exact session-view gate
        $this->actingAs($this->office('so'), 'office')
            ->getJson(route('office.financial.reports.preview', $frDocument))
            ->assertOk();

        // Now FR submission succeeds independently without AR
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHas('success');

        $frStatus = OrgReportStatus::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'fr')
            ->sole();
        $this->assertSame('oso_review', $frStatus->status);
        $this->assertDatabaseMissing('org_report_statuses', [
            'report_type' => 'ar',
            'organization_name' => $organization,
        ]);

        // OSO reviews and returns FR for revision
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/fr/review', $period + [
                'decision' => 'return',
                'notes' => 'Please reconcile beginning balance with bank statement.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $frStatus->refresh();
        $this->assertSame('returned', $frStatus->status);
        $this->assertSame('so', $frStatus->returned_to);
        $this->assertSame('Please reconcile beginning balance with bank statement.', $frStatus->notes);

        // SO re-stages FR: returned state + reason persist through edit until resubmit
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/documents', $period + [
                'name' => 'Revised Financial Report',
                'document' => $this->reportXlsx('financial_v2.xlsx'),
            ])
            ->assertRedirect();

        $frStatus->refresh();
        $this->assertSame('returned', $frStatus->status);
        $this->assertSame('Please reconcile beginning balance with bank statement.', $frStatus->notes);

        // Must view new revision before resubmitting
        $revisedDoc = OrgReportDocument::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'fr')
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');

        // Raw binary view of new revision does NOT satisfy view gate
        $this->actingAs($this->office('so'), 'office')
            ->get(route('office.reports.documents.view', $revisedDoc))
            ->assertOk();
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');

        // Viewing the new workbook revision via preview JSON marks the gate
        $this->actingAs($this->office('so'), 'office')
            ->getJson(route('office.financial.reports.preview', $revisedDoc))
            ->assertOk();

        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('oso_review', $frStatus->refresh()->status);

        // OSO approves and archives FR
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/fr/review', $period + [
                'decision' => 'accept',
                'notes' => 'Verified and accepted by OSO.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('archived', $frStatus->refresh()->status);
        $this->assertSame(0, OrgReportStatus::query()->where('organization_name', $organization)->where('report_type', 'ar')->count());
    }

    public function test_peer_locked_state_does_not_block_edit_and_submission(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => '2nd Semester',
            'academic_year' => '2025-2026',
        ];

        // 1. Lock AR to archived
        $arStatus = OrgReportStatus::query()->create([
            'report_type' => 'ar',
            'organization_name' => $organization,
            'semester' => '2nd Semester',
            'academic_year' => '2025-2026',
            'status' => 'archived',
        ]);

        // SO can still stage, view, and submit FR independently
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/documents', $period + [
                'name' => 'Peer Unblocked FR',
                'document' => $this->reportXlsx('peer_fr.xlsx'),
            ])
            ->assertRedirect();

        $frDoc = OrgReportDocument::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'fr')
            ->sole();

        // SO views actual workbook preview JSON to satisfy preview gate
        $this->actingAs($this->office('so'), 'office')
            ->getJson(route('office.financial.reports.preview', $frDoc))
            ->assertOk();
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/fr/submit', $period)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('org_report_statuses', [
            'report_type' => 'fr',
            'organization_name' => $organization,
            'status' => 'oso_review',
        ]);
        $this->assertSame('archived', $arStatus->refresh()->status);
    }

    public function test_same_type_locked_duplicates_block_submission(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => 'Midyear',
            'academic_year' => '2025-2026',
        ];

        // Stage AR document
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/documents', $period + [
                'name' => 'AR Document',
                'document' => $this->reportPdf('ar.pdf'),
            ])
            ->assertRedirect();

        // An existing locked duplicate AR row exists for the same period
        OrgReportStatus::query()->create([
            'report_type' => 'ar',
            'organization_name' => $organization,
            'semester' => 'Midyear',
            'academic_year' => '2025-2026',
            'status' => 'verified',
        ]);

        // Submitting AR is blocked due to the locked same-type duplicate row
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');
    }

    public function test_rejected_decision_is_terminal_and_requires_reason(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/documents', $period + [
                'name' => 'AR For Rejection Test',
                'document' => $this->reportPdf('rejection.pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertRedirect()
            ->assertSessionHas('success');

        // OSO attempts to reject without notes: validation fails (notes required)
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/ar/review', $period + [
                'decision' => 'reject',
                'notes' => '',
            ])
            ->assertSessionHasErrors('notes');

        // OSO rejects with required reason
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/ar/review', $period + [
                'decision' => 'reject',
                'notes' => 'Terminal rejection: invalid attendance logs.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $arStatus = OrgReportStatus::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'ar')
            ->sole();
        $this->assertSame('rejected', $arStatus->status);
        $this->assertSame('Terminal rejection: invalid attendance logs.', $arStatus->notes);

        // SO cannot resubmit rejected report; rejection is terminal
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertRedirect()
            ->assertSessionHasErrors('report');

        $this->assertSame('rejected', $arStatus->refresh()->status);
    }

    public function test_oso_publication_privacy_draft_and_returned_documents_hidden_from_oso(): void
    {
        Storage::fake('public');
        $organization = $this->organization->name;
        $period = [
            'organization_name' => $organization,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/documents', $period + [
                'name' => 'Draft AR',
                'document' => $this->reportPdf('draft_ar.pdf'),
            ])
            ->assertRedirect();

        $doc = OrgReportDocument::query()
            ->where('organization_name', $organization)
            ->where('report_type', 'ar')
            ->sole();

        // 1. While status is draft, OSO cannot view document
        $this->actingAs($this->office('oso'), 'office')
            ->get('/office-desk/reports/documents/'.$doc->id.'/view')
            ->assertForbidden();

        // 2. Submit AR to oso_review: OSO can view
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertRedirect();

        $this->actingAs($this->office('oso'), 'office')
            ->get('/office-desk/reports/documents/'.$doc->id.'/view')
            ->assertOk();

        // 3. Return report: OSO cannot view returned document bytes
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/ar/review', $period + [
                'decision' => 'return',
                'notes' => 'Please revise for accuracy.',
            ])
            ->assertRedirect();

        $this->actingAs($this->office('oso'), 'office')
            ->get('/office-desk/reports/documents/'.$doc->id.'/view')
            ->assertForbidden();

        // 4. Role boundaries: SDO and OVCAA cannot view, submit, or review
        foreach (['sdo', 'ovcaa'] as $role) {
            $this->actingAs($this->office($role), 'office')
                ->get('/office-desk/reports/documents/'.$doc->id.'/view')
                ->assertForbidden();
            $this->actingAs($this->office($role), 'office')
                ->post('/office-desk/reports/ar/submit', $period)
                ->assertForbidden();
            $this->actingAs($this->office($role), 'office')
                ->post('/office-desk/reports/ar/review', $period + ['decision' => 'accept'])
                ->assertForbidden();
        }

        // 5. SO cannot review, OSO cannot submit
        $this->actingAs($this->office('so'), 'office')
            ->post('/office-desk/reports/ar/review', $period + ['decision' => 'accept'])
            ->assertForbidden();
        $this->actingAs($this->office('oso'), 'office')
            ->post('/office-desk/reports/ar/submit', $period)
            ->assertForbidden();
    }

    public function test_directory_only_opens_available_submitted_reports_and_selects_the_available_type(): void
    {
        Storage::fake('public');
        $period = ['academic_year' => '2037-2038', 'semester' => '1st Semester'];
        $cases = [
            'none' => [],
            'draft' => [['ar', 'draft', true]],
            'returned' => [['ar', 'returned', true]],
            'missing' => [['ar', 'oso_review', false]],
            'fr-only' => [['fr', 'oso_review', true]],
            'ar-only' => [['ar', 'archived', true]],
            'both' => [['ar', 'oso_review', true], ['fr', 'oso_review', true]],
        ];
        $names = [];
        foreach ($cases as $case => $reports) {
            $organization = StudentOrganization::create([
                'name' => 'Directory '.$case.' '.Str::uuid(),
                'college' => 'Directory test college', 'is_active' => true,
            ]);
            $names[$case] = $organization->name;
            foreach ($reports as [$type, $state, $hasFile]) {
                $status = OrgReportStatus::create($period + [
                    'organization_name' => $organization->name, 'report_type' => $type,
                    'status' => $state, 'batch_key' => (string) Str::uuid(),
                    'submitted_at' => $state === 'draft' ? null : now()->addSecond(),
                ]);
                $path = 'semester-reports/directory-'.Str::uuid().'.pdf';
                if ($hasFile) {
                    $upload = $this->reportPdf('directory.pdf');
                    Storage::disk('public')->put($path, file_get_contents($upload->getRealPath()));
                }
                OrgReportDocument::create($period + [
                    'org_report_status_id' => $status->id, 'report_type' => $type,
                    'organization_name' => $organization->name, 'name' => 'Directory report',
                    'original_name' => 'directory.pdf', 'file_path' => $path,
                    'mime_type' => 'application/pdf', 'file_size' => 603, 'uploaded_by' => 'Fixture',
                ]);
            }
        }
        foreach (['ar', 'fr'] as $preferredType) {
            $response = $this->actingAs($this->office('oso'), 'office')
                ->get(route('office.reports.index', $period + ['tab' => $preferredType]))
                ->assertOk();
            $directory = collect($response->viewData('reportDirectory'))->keyBy('organization');
            foreach (['none', 'draft', 'returned', 'missing'] as $case) {
                $this->assertFalse($directory[$names[$case]]['can_view_submitted']);
            }
            foreach (['ar-only' => 'ar', 'fr-only' => 'fr', 'both' => $preferredType] as $case => $expectedType) {
                $entry = $directory[$names[$case]];
                $this->assertTrue($entry['can_view_submitted']);
                parse_str(parse_url($entry['view_url'], PHP_URL_QUERY), $query);
                $this->assertSame($expectedType, $query['tab']);
                $this->assertSame($names[$case], $query['organization']);
                $this->assertSame($period['academic_year'], $query['academic_year']);
                $this->assertSame($period['semester'], $query['semester']);
            }
        }
    }
}

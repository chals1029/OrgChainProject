<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\UserAccount;
use App\Services\TosaScoringService;
use App\Services\TosaWorkbookService;
use DOMDocument;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;
use ZipArchive;

class TosaWorkbookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->text('sr_code');
            $table->text('full_name');
            $table->text('account_status');
            $table->text('role');
        });
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'academic_year', 'status', 'subsection', 'score_evaluation'] as $field) {
                $table->text($field)->nullable();
            }
            $table->float('gwa')->nullable();
            $table->boolean('gwa_verified')->default(false);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_users', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'email', 'office_role', 'tosa_clearance'] as $field) {
                $table->text($field)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->integer('auth_version')->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_templates', function (Blueprint $table): void {
            $table->id();
            $table->text('category');
            $table->text('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function applicant(string $srCode = 'own-code', string $label = 'Actual own award'): TosaApplicant
    {
        $input = ['gwa' => 1.34, 'gwa_verified' => true, 'eligible_verified' => true,
            'academic_awards' => [['label' => $label, 'evidence' => 'E page 2', 'level' => 'local', 'place' => 4]],
            'leadership_seminars' => [['label' => 'Actual three-day seminar', 'evidence' => 'F page 3', 'level' => 'local', 'days' => 3]],
            'leadership_positions' => [['label' => 'Actual local president', 'evidence' => 'F page 4', 'level' => 'local', 'role' => 'president']],
            'leadership_awards' => [], 'social_activities' => []];
        $applicant = TosaApplicant::query()->create(['sr_code' => $srCode, 'full_name' => 'Applicant '.$srCode,
            'academic_year' => '2026-2027', 'status' => 'submitted', 'subsection' => 'accepted', 'gwa' => 1.34, 'gwa_verified' => true]);
        $applicant->score_evaluation = ['rubric_version' => TosaScoringService::RUBRIC_VERSION,
            'status' => 'finalized', 'input' => $input, ...app(TosaScoringService::class)->calculate(1.34, $input),
            'verified_at' => '2026-10-01T10:00:00+00:00', 'updated_at' => '2026-10-01T10:00:00+00:00',
            'verified_by' => ['id' => 1, 'name' => 'Private evaluator identity']];
        $applicant->save();

        return $applicant;
    }

    private function student(string $srCode = 'own-code'): UserAccount
    {
        return UserAccount::query()->create(['sr_code' => $srCode, 'full_name' => 'Synthetic workbook student', 'account_status' => 'active', 'role' => 'student']);
    }

    private function withWorkbook(string $bytes, callable $assertions): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tosa-test-');
        file_put_contents($path, $bytes);
        $workbook = null;
        try {
            $workbook = (new XlsxReader())->load($path);
            $assertions($workbook);
        } finally {
            $workbook?->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_reference_preview_preserves_original_download_bytes(): void
    {
        $this->actingAs($this->student(), 'student');
        $path = base_path('TOSA/TOSA COMPUTATION.xlsx');
        $hash = hash_file('sha256', $path);
        $this->get(route('portal.tosa.workbook.reference'))->assertOk();
        $download = $this->get(route('portal.tosa.templates.show', ['key' => 'computation']));
        $download->assertOk()->assertDownload();
        $this->assertSame($hash, hash_file('sha256', $download->baseResponse->getFile()->getPathname()));
        $this->assertSame($hash, hash_file('sha256', $path));
    }

    public function test_student_query_ids_never_select_another_applicant_or_a_later_duplicate(): void
    {
        $own = $this->applicant();
        $other = $this->applicant('other-code', 'OTHER PRIVATE ACHIEVEMENT');
        $this->applicant('own-code', 'LATER DUPLICATE ACHIEVEMENT');
        $this->actingAs($this->student(), 'student');
        $response = $this->get(route('portal.tosa.workbook.show', ['applicant' => $other->id, 'applicant_id' => $other->id]));
        $response->assertOk()->assertSee('Actual own award')->assertDontSee('OTHER PRIVATE ACHIEVEMENT')->assertDontSee('LATER DUPLICATE ACHIEVEMENT');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('own-code', $own->sr_code);
        Auth::guard('student')->forgetUser();
        $this->actingAs($this->student('no-application'), 'student');
        $this->get(route('portal.tosa.workbook.show', ['applicant' => $own->id]))->assertNotFound();
        $this->get(route('portal.tosa.workbook.download', ['applicant' => $own->id]))->assertNotFound();
    }

    public function test_seven_sheet_export_contains_actual_inputs_correct_calculations_and_no_demo_or_evaluator_data(): void
    {
        $this->applicant();
        $this->applicant('foreign-code', 'FOREIGN PRIVATE DATA');
        $this->actingAs($this->student(), 'student');
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'tosa-score-*');
        $response = $this->get(route('portal.tosa.workbook.download', ['applicant_id' => 2]));
        $response->assertOk()->assertDownload('TOSA-system-computed.xlsx')->assertHeader('Content-Type', TosaWorkbookService::MIME_TYPE);
        $bytes = $response->streamedContent();
        $this->assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'tosa-score-*'));
        $this->withWorkbook($bytes, function (Spreadsheet $workbook): void {
            $this->assertSame(['SUMMARY', 'Acad', 'L1', 'L2', 'L3', 'SC1', 'SC2'], $workbook->getSheetNames());
            $summary = $workbook->getSheetByName('SUMMARY');
            $this->assertEquals(26, $summary->getCell('D11')->getValue());
            $this->assertEquals(0.125, $summary->getCell('D12')->getValue());
            $this->assertEquals(1.1, $summary->getCell('D16')->getValue());
            $this->assertEquals(0, $summary->getCell('D18')->getValue());
            $this->assertEquals(27.225, $summary->getCell('D21')->getValue());
            $this->assertEquals(0.6, $workbook->getSheetByName('L3')->getCell('I4')->getValue());
            $this->assertEquals(0.5, $workbook->getSheetByName('L3')->getCell('I5')->getValue());
            $values = [];
            foreach ($workbook->getWorksheetIterator() as $sheet) {
                foreach ($sheet->getCellCollection()->getCoordinates() as $address) {
                    $cell = $sheet->getCell($address);
                    $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
                    $values[] = (string) $cell->getValue();
                }
            }
            $text = implode('\n', $values);
            $this->assertStringContainsString('Actual own award', $text);
            $this->assertStringNotContainsString('FOREIGN PRIVATE DATA', $text);
            $this->assertStringNotContainsString('Private evaluator identity', $text);
            $this->assertNotContains('sf', $values);
        });
    }

    public function test_generated_workbook_reports_raw_points_and_applies_every_relevant_category_cap(): void
    {
        $applicant = $this->applicant();
        $evaluation = $applicant->score_evaluation;
        $input = $evaluation['input'];
        $input['academic_awards'] = array_fill(0, 14, ['label' => 'Verified international award', 'evidence' => 'E page 5', 'level' => 'international', 'place' => 1]);
        $input['leadership_positions'] = array_fill(0, 30, ['label' => 'Verified local president', 'evidence' => 'F page 6', 'level' => 'local', 'role' => 'president']);
        $input['social_activities'] = array_fill(0, 40, ['label' => 'Verified social organizer', 'evidence' => 'G page 7', 'level' => 'international', 'scope' => 'non_school', 'role' => 'organizer']);
        $applicant->score_evaluation = [...$evaluation, 'input' => $input, ...app(TosaScoringService::class)->calculate(1.34, $input)];
        $applicant->save();
        $this->actingAs($this->student(), 'student');
        $response = $this->get(route('portal.tosa.workbook.download'));
        $this->withWorkbook($response->streamedContent(), function (Spreadsheet $workbook): void {
            $summary = $workbook->getSheetByName('SUMMARY');
            $this->assertEquals(42, $summary->getCell('B12')->getValue());
            $this->assertEquals(10, $summary->getCell('D12')->getValue());
            $this->assertEquals(15.6, $summary->getCell('B16')->getValue());
            $this->assertEquals(12, $summary->getCell('D16')->getValue());
            $this->assertEquals(120, $summary->getCell('B18')->getValue());
            $this->assertEquals(18, $summary->getCell('D18')->getValue());
            $this->assertEquals(66, $summary->getCell('D21')->getValue());
        });
    }

    public function test_payload_strings_are_escaped_in_html_and_explicit_strings_in_real_xlsx(): void
    {
        $payload = '=WEBSERVICE("https://example.invalid")<script>alert(1)</script>';
        $this->applicant('own-code', $payload);
        $this->actingAs($this->student(), 'student');
        $this->get(route('portal.tosa.workbook.show'))->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        $response = $this->get(route('portal.tosa.workbook.download'));
        $this->withWorkbook($response->streamedContent(), function (Spreadsheet $workbook) use ($payload): void {
            $cell = $workbook->getSheetByName('Acad')->getCell('B4');
            $this->assertSame($payload, $cell->getValue());
            $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        });
    }

    public function test_readonly_ovcaa_and_oso_can_view_but_foreign_roles_and_no_clearance_cannot(): void
    {
        $applicant = $this->applicant();
        foreach ([['oso', null, 200], ['ovcaa', 'Level 1 Read-only', 200], ['ovcaa', 'No Access', 403], ['sdo', null, 403], ['oc', null, 403]] as [$role, $clearance, $status]) {
            $user = OfficeUser::query()->create(['name' => 'Synthetic workbook office', 'email' => uniqid().'@example.invalid',
                'office_role' => $role, 'tosa_clearance' => $clearance, 'is_active' => true, 'auth_version' => 0]);
            $this->actingAs($user, 'office');
            $this->get(route('office.tosa.workbook.show', $applicant))->assertStatus($status);
            $reference = $this->get(route('office.tosa.workbook.reference'));
            $reference->assertStatus($status);
            $response = $this->get(route('office.tosa.workbook.download', $applicant));
            $response->assertStatus($status);
            if ($status === 200) {
                $this->assertStringStartsWith('PK', $response->streamedContent());
            }
        }
        $inactive = OfficeUser::query()->create(['name' => 'Inactive fixture', 'office_role' => 'oso', 'is_active' => false]);
        $this->actingAs($inactive, 'office');
        $this->get(route('office.tosa.workbook.show', $applicant))->assertRedirect(route('office.login'));
    }

    public function test_no_evaluation_cannot_be_downloaded_and_draft_is_visibly_provisional(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->student(), 'student');
        $evaluation = $applicant->score_evaluation;
        $evaluation['status'] = 'draft';
        $evaluation['verified_by'] = null;
        $evaluation['verified_at'] = null;
        $applicant->score_evaluation = $evaluation;
        $applicant->save();
        $this->get(route('portal.tosa.workbook.show'))->assertOk();
        $download = $this->get(route('portal.tosa.workbook.download'))->assertOk();
        $this->withWorkbook($download->streamedContent(), function (Spreadsheet $workbook): void {
            $this->assertSame('draft', $workbook->getSheetByName('SUMMARY')->getCell('B5')->getValue());
        });
        $applicant->score_evaluation = null;
        $applicant->save();
        $this->get(route('portal.tosa.workbook.show'))->assertNotFound();
        $this->get(route('portal.tosa.workbook.download'))->assertNotFound();
    }

    public function test_reference_formulas_are_not_executed_and_preview_dimensions_are_bounded(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tosa-test-');
        $workbook = new Spreadsheet();
        $sheet = $workbook->getActiveSheet();
        $sheet->setCellValue('A1', '=WEBSERVICE("https://example.invalid/private")');
        $sheet->setCellValue('A'.(TosaWorkbookService::PREVIEW_ROWS + 1), 'OUTSIDE ROW BOUND');
        $sheet->setCellValue('AG1', 'OUTSIDE COLUMN BOUND');
        $writer = new XlsxWriter($workbook);
        $writer->setPreCalculateFormulas(false);
        try {
            $writer->save($path);
            // PhpSpreadsheet deliberately omits <v> caches when pre-calculation
            // is disabled, even after setCalculatedValue(). Supply a genuine
            // saved Excel string cache in the fixture XML without evaluating it.
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path));
            try {
                $xml = new DOMDocument();
                $this->assertTrue($xml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'), LIBXML_NONET));
                foreach ($xml->getElementsByTagName('c') as $node) {
                    if ($node->getAttribute('r') === 'A1') {
                        $node->setAttribute('t', 'str');
                        $cache = $xml->createElementNS($xml->documentElement->namespaceURI, 'v');
                        $cache->appendChild($xml->createTextNode('cached only'));
                        $node->appendChild($cache);
                        break;
                    }
                }
                $this->assertTrue($zip->addFromString('xl/worksheets/sheet1.xml', $xml->saveXML()));
            } finally {
                $zip->close();
            }
            $preview = app(TosaWorkbookService::class)->referencePreview($path);
            $cell = $preview['sheets'][0]['rows'][0][0];
            $this->assertSame('=WEBSERVICE("https://example.invalid/private")', $cell['formula']);
            $this->assertSame('cached only', $cell['value']);
            $this->assertLessThanOrEqual(TosaWorkbookService::PREVIEW_ROWS, count($preview['sheets'][0]['rows']));
            $this->assertLessThanOrEqual(TosaWorkbookService::PREVIEW_COLUMNS, count($preview['sheets'][0]['columns']));
            $this->assertStringNotContainsString('OUTSIDE', json_encode($preview));
        } finally {
            $workbook->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_external_relationships_entities_and_expanding_zip_parts_are_rejected_before_loading(): void
    {
        foreach ([
            ['xl/_rels/workbook.xml.rels', '<Relationships><Relationship TargetMode="External" Target="https://example.invalid/private"/></Relationships>'],
            ['xl/worksheets/sheet1.xml', '<!DOCTYPE sheet [<!ENTITY x SYSTEM "file:///private">]><sheet>&x;</sheet>'],
            ['xl/worksheets/sheet1.xml', str_repeat('x', 8 * 1024 * 1024 + 1)],
        ] as [$name, $content]) {
            $path = tempnam(sys_get_temp_dir(), 'tosa-test-');
            $zip = new ZipArchive();
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<Types/>');
            $zip->addFromString('xl/workbook.xml', '<workbook/>');
            $zip->addFromString($name, $content);
            $zip->close();
            try {
                app(TosaWorkbookService::class)->referencePreview($path);
                $this->fail('Unsafe reference package was loaded.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            } finally {
                unlink($path);
            }
        }
    }
}

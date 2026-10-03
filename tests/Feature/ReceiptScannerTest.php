<?php

namespace Tests\Feature;

use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\ReceiptScan;
use App\Services\BudgetChainService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReceiptScannerTest extends TestCase
{
    use UsesLaragonDatabase;
    private OfficeUser $actor;
    private OrgActivity $activity;
    private const SCAN = '/office-desk/budget-utilization/receipts/scan';
    private const VALIDATE_DOCUMENT = '/office-desk/budget-utilization/receipts/validate-document';
    private const STORE = '/office-desk/budget-utilization/receipt-reviews';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Storage::fake('local');
        $this->actor = OfficeUser::create(['name' => 'Scanner Test', 'email' => Str::uuid().'@example.test', 'username' => Str::random(18), 'password' => Str::random(30), 'office_role' => 'so', 'office_title' => 'Scanner test desk', 'is_active' => true]);
        $org = 'Scanner Test '.Str::uuid();
        OrgFundAccount::create(['organization_name' => $org, 'fiscal_year' => '2026-2027', 'total_funds' => 5000]);
        $this->activity = OrgActivity::create(['title' => 'SYNTHETIC OCR QA', 'organization_name' => $org, 'workflow_status' => 'oc_approved', 'approved_budget' => 2000, 'implemented_budget' => 0, 'starts_at' => '2026-09-20']);
        $this->actingAs($this->actor, 'office');
        $this->mock(BudgetChainService::class)->shouldReceive('sealExpense')->andReturn(['block_hash' => str_repeat('e', 64), 'previous_hash' => str_repeat('0', 64), 'nodes_confirmed' => 3, 'chain_driver' => 'file']);
    }

    protected function tearDown(): void
    {
        while (DB::connection('mysql')->transactionLevel() > 0) DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function fakeOcr(): void
    {
        $text = "GCash\nPaid to SAVEMORE MARKET\nAmount PHP 300.00\nReference No. 1234567890123\nSep 20, 2026";
        Http::fake(['*/scan' => Http::response(['text' => $text, 'confidence' => 91, 'engine' => 'test-tesseract', 'lines' => array_map(fn ($l) => ['text' => $l, 'confidence' => 91], explode("\n", $text))])]);
    }

    private function fakeOcrSequence(): void
    {
        $response = static function (string $text): array {
            return ['text' => $text, 'confidence' => 91, 'engine' => 'test-tesseract', 'lines' => array_map(fn ($line) => ['text' => $line, 'confidence' => 91], explode("\n", $text))];
        };
        Http::fake(['*/scan' => Http::sequence()
            ->push($response("Cash\nPaid to SAVEMORE MARKET\nAmount PHP 100.00\nReference No. BATCH-1\nSep 20, 2026"))
            ->push($response("GCash\nPaid to WATER STATION\nAmount PHP 250.00\nReference No. BATCH-2\nSep 20, 2026"))]);
    }

    private function docx(string $text, string $name = 'receipt.docx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'orgchain-feature-docx-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $safe = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.$safe.'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    private function payload(UploadedFile $file, ?string $scan = null): array
    {
        return ['org_activity_id' => $this->activity->id, 'request_key' => (string) Str::uuid(), 'item_name' => 'Test supplies', 'quantity' => 1, 'unit_cost' => '300.00', 'expense_date' => '2026-09-20', 'supplier' => 'Savemore corrected', 'receipt_reference' => '1234567890123', 'receipt_reviewed' => 1, 'receipt_type' => 'ewallet_receipt', 'payment_method' => 'gcash', 'receipt_scan_id' => $scan, 'receipt' => $file, 'ocr_confidence' => 100, 'ocr_quality' => 'complete'];
    }

    public function test_scan_does_not_debit_and_saved_confidence_comes_from_server_with_corrections(): void
    {
        $this->fakeOcr();
        $file = UploadedFile::fake()->image('synthetic.png');
        $result = $this->postJson(self::SCAN, ['receipt' => $file])->assertOk()->assertJsonPath('fields.payment_method.value', 'gcash')->assertJsonMissingPath('raw_text');
        $this->assertSame('0.00', (string)$this->activity->refresh()->implemented_budget);
        $payload = $this->payload($file, $result->json('scan_id'));
        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();
        $receipt = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->firstOrFail();
        $this->assertSame(91, (int)$receipt->ocr_confidence);
        $this->assertSame('gcash', $receipt->payment_method);
        $this->assertSame('SAVEMORE MARKET', $receipt->ocr_corrections['supplier']['detected']);
        $this->assertSame('Savemore corrected', $receipt->ocr_corrections['supplier']['reviewed']);
        $this->assertSame('300.00', (string)$this->activity->refresh()->implemented_budget);
        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();
        $this->assertSame('300.00', (string)$this->activity->refresh()->implemented_budget);
    }

    public function test_docx_preflight_rejects_activity_templates_and_accepts_receipt_like_documents(): void
    {
        $this->postJson(self::VALIDATE_DOCUMENT, ['receipt' => $this->docx('Activity Proposal Programme Schedule of Activities Objectives Venue Participants Budget Proposal', 'activity.docx')])
            ->assertUnprocessable()->assertJsonPath('valid', false);
        $this->postJson(self::VALIDATE_DOCUMENT, ['receipt' => $this->docx('Official Receipt Sales Invoice Total PHP 300.00 Reference No. 123456 Date 09/20/2026 Paid to Savemore', 'receipt.docx')])
            ->assertOk()->assertJsonPath('valid', true)->assertJsonPath('kind', 'docx');
    }

    public function test_store_rechecks_docx_classifier_before_persisting(): void
    {
        $this->post(self::STORE, $this->payload($this->docx('Activity Proposal Programme Schedule of Activities Objectives Venue Participants Budget Proposal', 'activity.docx')))
            ->assertSessionHasErrors('receipt');
        $this->assertSame(0, ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->count());
        $this->assertSame('0.00', (string) $this->activity->refresh()->implemented_budget);
    }

    public function test_batch_records_one_receipt_per_item_and_updates_activity_and_budget_once(): void
    {
        $this->fakeOcrSequence();
        $firstFile = new UploadedFile(base_path('tests/Fixtures/receipts/savemore.png'), 'savemore.png', 'image/png', null, true);
        $secondFile = new UploadedFile(base_path('tests/Fixtures/receipts/gcash.png'), 'gcash.png', 'image/png', null, true);
        $firstScan = $this->postJson(self::SCAN, ['receipt' => $firstFile])->assertOk()->json('scan_id');
        $secondScan = $this->postJson(self::SCAN, ['receipt' => $secondFile])->assertOk()->json('scan_id');

        $payload = [
            'org_activity_id' => $this->activity->id,
            'receipt_reviewed' => 1,
            'expenses' => [
                [
                    'request_key' => (string) Str::uuid(), 'item_name' => 'Printed kits', 'category' => 'Printing',
                    'quantity' => 2, 'unit_cost' => '100.00', 'expense_date' => '2026-09-20', 'supplier' => 'Savemore',
                    'receipt_reference' => 'BATCH-1', 'receipt_type' => 'paper_receipt', 'payment_method' => 'cash',
                    'receipt_scan_id' => $firstScan, 'receipt' => $firstFile,
                ],
                [
                    'request_key' => (string) Str::uuid(), 'item_name' => 'Drinking water', 'category' => 'Food & Refreshments',
                    'quantity' => 1, 'unit_cost' => '250.00', 'expense_date' => '2026-09-20', 'supplier' => 'Water Station',
                    'receipt_reference' => 'BATCH-2', 'receipt_type' => 'ewallet_receipt', 'payment_method' => 'gcash',
                    'receipt_scan_id' => $secondScan, 'receipt' => $secondFile,
                ],
            ],
        ];

        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();
        $receipts = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertSame(['Printed kits', 'Drinking water'], $receipts->pluck('item_name')->all());
        $this->assertSame('450.00', (string) $this->activity->refresh()->implemented_budget);
        $this->assertSame('450.00', (string) \App\Models\BudgetItem::where('org_activity_id', $this->activity->id)->value('utilized'));
    }

    public function test_scan_token_cannot_be_forged_reused_by_another_actor_or_for_another_file_or_after_expiry(): void
    {
        $this->fakeOcr();
        $file = UploadedFile::fake()->image('receipt.png', 50, 50);
        $scanId = $this->postJson(self::SCAN, ['receipt' => $file])->assertOk()->json('scan_id');
        $this->post(self::STORE, $this->payload($file))->assertSessionHasErrors('receipt');
        $this->post(self::STORE, $this->payload($file, (string)Str::uuid()))->assertSessionHasErrors('receipt');
        $this->post(self::STORE, $this->payload(UploadedFile::fake()->image('other.png', 70, 80), $scanId))->assertSessionHasErrors('receipt');
        $scan = ReceiptScan::findOrFail($scanId);
        $scan->update(['uploaded_by' => $this->actor->id + 100000]);
        $this->post(self::STORE, $this->payload($file, $scanId))->assertSessionHasErrors('receipt');
        $scan->update(['uploaded_by' => $this->actor->id, 'expires_at' => now()->subMinute()]);
        $this->post(self::STORE, $this->payload($file, $scanId))->assertSessionHasErrors('receipt');
        $this->assertSame('0.00', (string)$this->activity->refresh()->implemented_budget);
    }

    public function test_review_confirmation_and_approved_activity_remain_required(): void
    {
        $this->fakeOcr();
        $file = UploadedFile::fake()->image('receipt.png');
        $id = $this->postJson(self::SCAN, ['receipt' => $file])->json('scan_id');
        $data = $this->payload($file, $id); $data['receipt_reviewed'] = 0;
        $this->post(self::STORE, $data)->assertSessionHasErrors('receipt_reviewed');
        $this->activity->update(['workflow_status' => 'oso_review']);
        $this->post(self::STORE, $this->payload($file, $id))->assertSessionHasErrors('activity');
    }

    public function test_only_so_can_scan_and_file_types_are_checked(): void
    {
        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable();
        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('huge.png')->size(11000)])->assertUnprocessable();
        foreach (['oso','sdo','ovcaa'] as $role) {
            $this->actor->update(['office_role' => $role]);
            $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('r.png')])->assertForbidden();
        }
    }

    public function test_expired_session_returns_json_instead_of_login_html(): void
    {
        \Illuminate\Support\Facades\Auth::guard('office')->logout();
        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('r.png')])->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_unreadable_photos_and_unavailable_service_do_not_issue_scan_tokens(): void
    {
        Http::fake(['*/scan' => Http::sequence()->push(['text' => 'blur', 'lines' => [], 'confidence' => 10])->push([], 503)]);
        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('blur.png')])->assertUnprocessable();
        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('receipt.png')])->assertStatus(503);
        $this->assertSame(0, ReceiptScan::where('uploaded_by', $this->actor->id)->count());
    }

    public function test_clear_but_incomplete_images_are_rejected_before_a_scan_token_is_issued(): void
    {
        $text = "Screenshot of a phone gallery\nSep 20, 2026\nSome unrelated text";
        Http::fake(['*/scan' => Http::response([
            'text' => $text,
            'confidence' => 91,
            'engine' => 'test-tesseract',
            'lines' => array_map(fn ($line) => ['text' => $line, 'confidence' => 91], explode("\n", $text)),
        ])]);

        $this->postJson(self::SCAN, ['receipt' => UploadedFile::fake()->image('gallery-screenshot.png')])
            ->assertUnprocessable()
            ->assertJsonPath('errors.receipt.0', 'Receipt is blurry or incomplete. Upload a clear, uncropped photo showing the merchant, total amount, transaction date, and receipt or reference number.');
        $this->assertSame(0, ReceiptScan::where('uploaded_by', $this->actor->id)->count());
    }

    public function test_pdf_is_manual_and_has_no_invented_ocr_score(): void
    {
        $file = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
        $this->post(self::STORE, $this->payload($file))->assertSessionHasNoErrors();
        $receipt = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->firstOrFail();
        $this->assertNull($receipt->ocr_confidence);
        $this->assertNull($receipt->receipt_scan_id);
        $this->assertSame('partial', $receipt->ocr_quality);
    }

    public function test_manual_photo_upload_does_not_require_or_call_ocr(): void
    {
        $file = UploadedFile::fake()->image('manual-receipt.png');
        $payload = $this->payload($file);
        unset($payload['receipt_scan_id'], $payload['ocr_confidence'], $payload['ocr_quality']);

        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();
        $receipt = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->firstOrFail();
        $this->assertNull($receipt->receipt_scan_id);
        $this->assertNull($receipt->ocr_confidence);
        $this->assertNull($receipt->ocr_quality);
        $this->assertNull($receipt->ocr_corrections);
    }

    public function test_live_local_ocr_with_synthetic_photos(): void
    {
        if (!getenv('RECEIPT_OCR_INTEGRATION')) $this->markTestSkipped('Set RECEIPT_OCR_INTEGRATION=1 with the local Docker scanner running.');
        foreach (['gcash' => ['1234.50', 'gcash', '1234567890123'], 'savemore' => ['300.00', 'cash', '87654321']] as $name => [$amount, $method, $reference]) {
            $file = new UploadedFile(base_path('tests/Fixtures/receipts/'.$name.'.png'), $name.'.png', 'image/png', null, true);
            $this->postJson(self::SCAN, ['receipt' => $file])->assertOk()
                ->assertJsonPath('fields.unit_cost.value', $amount)->assertJsonPath('fields.payment_method.value', $method)
                ->assertJsonPath('fields.receipt_reference.value', $reference)->assertJsonPath('fields.expense_date.value', '2026-09-20');
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\ExpenseReceiptReview;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Services\BudgetChainService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReceiptScannerTest extends TestCase
{
    use UsesLaragonDatabase;

    private OfficeUser $actor;
    private OrgActivity $activity;
    private const VALIDATE_DOCUMENT = '/office-desk/budget-utilization/receipts/validate-document';
    private const STORE = '/office-desk/budget-utilization/receipt-reviews';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Storage::fake('local');
        $this->actor = OfficeUser::create([
            'name' => 'Scanner Test',
            'email' => Str::uuid().'@example.test',
            'username' => Str::random(18),
            'password' => Str::random(30),
            'office_role' => 'so',
            'office_title' => 'Scanner test desk',
            'is_active' => true,
        ]);
        $org = 'Scanner Test '.Str::uuid();
        OrgFundAccount::create(['organization_name' => $org, 'fiscal_year' => '2026-2027', 'cash_opening_balance' => 5000]);
        $this->activity = OrgActivity::create([
            'title' => 'ACTIVITY RECEIPT QA',
            'organization_name' => $org,
            'workflow_status' => 'oc_approved',
            'approved_budget' => 2000,
            'implemented_budget' => 0,
            'starts_at' => '2026-09-20',
        ]);
        $this->actingAs($this->actor, 'office');
        $this->mock(BudgetChainService::class)->shouldReceive('sealExpense')->andReturn([
            'block_hash' => str_repeat('e', 64),
            'previous_hash' => str_repeat('0', 64),
            'nodes_confirmed' => 3,
            'chain_driver' => 'file',
        ]);
    }

    protected function tearDown(): void
    {
        while (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        parent::tearDown();
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

    private function payload(UploadedFile $file): array
    {
        return [
            'org_activity_id' => $this->activity->id,
            'request_key' => (string) Str::uuid(),
            'item_name' => 'Test supplies',
            'quantity' => 1,
            'unit_cost' => '300.00',
            'expense_date' => '2026-09-20',
            'supplier' => 'Savemore corrected',
            'receipt_reference' => '1234567890123',
            'receipt_reviewed' => 1,
            'receipt_type' => 'ewallet_receipt',
            'payment_method' => 'gcash',
            'receipt' => $file,
        ];
    }

    public function test_manual_receipt_upload_debits_activity_and_budget_cleanly(): void
    {
        $file = UploadedFile::fake()->image('receipt.png');
        $payload = $this->payload($file);

        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();

        $receipt = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->firstOrFail();
        $this->assertSame('gcash', $receipt->payment_method);
        $this->assertSame('Savemore corrected', $receipt->supplier);
        $this->assertSame('300.00', (string) $this->activity->refresh()->implemented_budget);

        // Submitting duplicate request key does not debit twice
        $this->post(self::STORE, $payload)->assertSessionHasNoErrors();
        $this->assertSame('300.00', (string) $this->activity->refresh()->implemented_budget);
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
        $firstFile = UploadedFile::fake()->image('savemore.png');
        $secondFile = UploadedFile::fake()->image('gcash.png');

        $payload = [
            'org_activity_id' => $this->activity->id,
            'receipt_reviewed' => 1,
            'expenses' => [
                [
                    'request_key' => (string) Str::uuid(),
                    'item_name' => 'Printed kits',
                    'category' => 'Printing',
                    'quantity' => 2,
                    'unit_cost' => '100.00',
                    'expense_date' => '2026-09-20',
                    'supplier' => 'Savemore',
                    'receipt_reference' => 'BATCH-1',
                    'receipt_type' => 'paper_receipt',
                    'payment_method' => 'cash',
                    'receipt' => $firstFile,
                ],
                [
                    'request_key' => (string) Str::uuid(),
                    'item_name' => 'Drinking water',
                    'category' => 'Food & Refreshments',
                    'quantity' => 1,
                    'unit_cost' => '250.00',
                    'expense_date' => '2026-09-20',
                    'supplier' => 'Water Station',
                    'receipt_reference' => 'BATCH-2',
                    'receipt_type' => 'ewallet_receipt',
                    'payment_method' => 'gcash',
                    'receipt' => $secondFile,
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

    public function test_review_confirmation_and_approved_activity_remain_required(): void
    {
        $file = UploadedFile::fake()->image('receipt.png');
        $data = $this->payload($file);
        $data['receipt_reviewed'] = 0;
        $this->post(self::STORE, $data)->assertSessionHasErrors('receipt_reviewed');

        $this->activity->update(['workflow_status' => 'oso_review']);
        $this->post(self::STORE, $this->payload($file))->assertSessionHasErrors('activity');
    }

    public function test_only_so_can_validate_documents(): void
    {
        $this->postJson(self::VALIDATE_DOCUMENT, ['receipt' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable();

        foreach (['oso', 'sdo', 'ovcaa'] as $role) {
            $this->actor->update(['office_role' => $role]);
            $this->postJson(self::VALIDATE_DOCUMENT, ['receipt' => $this->docx('Receipt', 'receipt.docx')])->assertForbidden();
        }
    }

    public function test_pdf_upload_is_accepted_as_attachment(): void
    {
        $file = UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF");
        $this->post(self::STORE, $this->payload($file))->assertSessionHasNoErrors();

        $receipt = ExpenseReceiptReview::where('org_activity_id', $this->activity->id)->firstOrFail();
        $this->assertSame('300.00', (string) $this->activity->refresh()->implemented_budget);
    }
}

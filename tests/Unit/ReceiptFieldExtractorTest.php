<?php

namespace Tests\Unit;

use App\Services\ReceiptFieldExtractor;
use Carbon\Carbon;
use Tests\TestCase;

class ReceiptFieldExtractorTest extends TestCase
{
    private function parse(string $text, int $confidence = 96): array
    {
        Carbon::setTestNow('2026-09-21');
        try {
            return app(ReceiptFieldExtractor::class)->extract([
                'text' => $text, 'confidence' => $confidence,
                'lines' => array_map(fn ($line) => ['text' => $line, 'confidence' => $confidence], explode("\n", $text)),
            ]);
        } finally { Carbon::setTestNow(); }
    }

    public function test_gcash_is_payment_channel_and_not_merchant(): void
    {
        $result = $this->parse("GCash\nPaid to\nSAVEMORE MARKET\nAmount PHP 1,234.50\nReference No. 1234567890123\nSep 20, 2026");
        $fields = array_map(fn ($f) => $f['value'], $result['fields']);
        $this->assertSame('SAVEMORE MARKET', $fields['supplier']);
        $this->assertSame('gcash', $fields['payment_method']);
        $this->assertSame('ewallet_receipt', $fields['receipt_type']);
        $this->assertSame('1234.50', $fields['unit_cost']);
        $this->assertSame('1234567890123', $fields['receipt_reference']);
        $this->assertSame('2026-09-20', $fields['expense_date']);
    }

    public function test_total_is_not_cash_change_subtotal_or_tax(): void
    {
        $fields = $this->parse("SAVEMORE MARKET\nSALES INVOICE\nInvoice No. 87654321\n09/20/2026\nSUBTOTAL 250.00\nTOTAL PHP 300.00\nCASH PHP 500.00\nCHANGE PHP 200.00\nVAT 32.14")['fields'];
        $this->assertSame('300.00', $fields['unit_cost']['value']);
        $this->assertSame('87654321', $fields['receipt_reference']['value']);
        $this->assertSame('cash', $fields['payment_method']['value']);
        $this->assertSame('paper_receipt', $fields['receipt_type']['value']);
    }

    public function test_split_labels_and_low_confidence_are_reviewed_not_guessed(): void
    {
        $result = $this->parse("TOTAL\nPHP 150.25\nRef No.\nABC-123456\n2026-09-19", 55);
        $this->assertSame('150.25', $result['fields']['unit_cost']['value']);
        $this->assertSame('ABC-123456', $result['fields']['receipt_reference']['value']);
        $this->assertSame('partial', $result['quality']);
        $this->assertLessThan(80, $result['fields']['unit_cost']['confidence']);
    }

    public function test_unknown_values_stay_empty_instead_of_using_last_number_or_first_line(): void
    {
        $result = $this->parse("WELCOME CUSTOMER\nINVOICE\n500.00\nCHANGE 250.00\nTIN 123456789\n08/32/2026");
        foreach (['supplier', 'unit_cost', 'expense_date', 'receipt_reference'] as $key) $this->assertNull($result['fields'][$key]['value'], $key);
    }

    public function test_ambiguous_dates_are_flagged_and_invalid_or_future_dates_are_not_filled(): void
    {
        $result = $this->parse("Date 09/08/2026");
        $this->assertSame('2026-09-08', $result['fields']['expense_date']['value']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertNull($this->parse('Date 02/30/2026')['fields']['expense_date']['value']);
        $this->assertNull($this->parse('Date 2027-01-01')['fields']['expense_date']['value']);
        $this->assertSame('2026-09-20', $this->parse('Date 20/09/2026')['fields']['expense_date']['value']);
    }

    public function test_conflicting_totals_and_references_require_manual_review(): void
    {
        $result = $this->parse("TOTAL 200.00\nTOTAL 300.00\nRef No. 123456\nRef No. 987654");
        $this->assertNull($result['fields']['unit_cost']['value']);
        $this->assertNull($result['fields']['receipt_reference']['value']);
        $this->assertCount(2, $result['warnings']);
    }

    public function test_receipt_date_does_not_use_footer_registration_date(): void
    {
        $fields = $this->parse("Date 2026-09-20\nBIR Registration 2024-01-01")['fields'];
        $this->assertSame('2026-09-20', $fields['expense_date']['value']);
    }

    public function test_dotted_gcash_reference_and_total_amount_sent_labels(): void
    {
        $fields = $this->parse("GCash\nSent to Student Vendor\nTotal Amount Sent PHP 250.00\nRef. No. 1234 567 890123\nSep. 20, 2026")['fields'];
        $this->assertSame('1234567890123', $fields['receipt_reference']['value']);
        $this->assertSame('250.00', $fields['unit_cost']['value']);
    }
}

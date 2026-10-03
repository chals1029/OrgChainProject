<?php

namespace App\Services;

/** Conservative, label-based extraction. Scores are review hints, not authenticity checks. */
class ReceiptFieldExtractor
{
    public function extract(array $ocr): array
    {
        $lines = array_values(array_filter($ocr['lines'] ?? [], fn ($l) => filled($l['text'] ?? null)));
        $text = implode("\n", array_column($lines, 'text'));
        $warnings = [];
        $candidates = [];
        $add = function ($key, $value, $score, $rank = 1) use (&$candidates) {
            if ($value !== null && $value !== '') $candidates[$key][] = compact('value', 'score', 'rank');
        };
        $method = preg_match('/\bGCASH\b/i', $text) ? 'gcash' : (preg_match('/\b(?:PAYMAYA|MAYA)\b/i', $text) ? 'maya' : 'unknown');
        $wallet = in_array($method, ['gcash', 'maya']);
        $type = $wallet && preg_match('/(?:sent to|paid to|send money|payment successful|amount sent)/i', $text) ? 'ewallet_receipt' : (preg_match('/(?:sales invoice|official receipt|subtotal|vatable|cashier)/i', $text) ? 'paper_receipt' : 'unknown');
        if (!$wallet && preg_match('/^\s*CASH(?: TENDERED)?\s*[:₱P\d]/im', $text)) $method = 'cash';
        if (!$wallet && preg_match('/\b(?:VISA|MASTERCARD|CREDIT CARD|DEBIT CARD)\b/i', $text)) $method = 'card';
        foreach ($lines as $i => $line) {
            $raw = trim($line['text']);
            $score = max(0, min(100, (int) round($line['confidence'] ?? 0)));
            $next = trim($lines[$i + 1]['text'] ?? '');
            // Label/value may be on separate lines; low-quality value text lowers the score too.
            if (preg_match('/(?:TO|NAME|TOTAL|AMOUNT|DUE|PAID|NO\.?|NUMBER|#|ID)\s*[:.-]?$/i', $raw)) $score = min($score, (int) ($lines[$i + 1]['confidence'] ?? 0));
            // Prefer explicit totals; never interpret CASH, CHANGE, balance, fee or VAT as the total.
            if (preg_match('/^(?:GRAND\s+TOTAL|TOTAL\s+(?:AMOUNT(?:\s+(?:PAID|SENT))?|DUE|PAYMENT)|AMOUNT(?:\s+(?:DUE|PAID|SENT))?|TOTAL)\b\s*[:=.-]?\s*(.*)$/i', $raw, $m)) {
                $value = $this->money($m[1] ?: $next);
                $add('unit_cost', $value, min($score, 95), 3);
            } elseif ($wallet && preg_match('/^(?:AMOUNT(?:\s+SENT)?|YOU\s+SENT)\b\s*[:=.-]?\s*(.*)$/i', $raw, $m)) {
                $add('unit_cost', $this->money($m[1] ?: $next), min($score, 90), 2);
            }
            if (preg_match('/^(?:(?:OR|O\.?R\.?|SI|S\.?I\.?|INVOICE|INV|REF(?:ERENCE)?\.?|RECEIPT|TRANSACTION)\s*(?:NO\.?|NUMBER|#|ID)?)[\s:#.-]*(.*)$/i', $raw, $m)) {
                $value = trim($m[1] ?: $next);
                if (preg_match('/^[A-Z0-9][A-Z0-9\s\/-]{2,119}$/i', $value) && preg_match('/\d/', $value)) {
                    $add('receipt_reference', preg_replace('/\s+/', '', strtoupper($value)), min($score, 93));
                }
            }
            if (preg_match('/^(?:PAID TO|SENT TO|RECIPIENT|MERCHANT|STORE NAME|BUSINESS NAME)\s*[:.-]?\s*(.*)$/i', $raw, $m)) {
                $value = trim($m[1] ?: $next);
                if (preg_match('/[a-z]{2}/i', $value) && !preg_match('/^(?:AMOUNT|REFERENCE|GCASH|MAYA|DATE)\b/i', $value)) $add('supplier', mb_substr($value, 0, 255), min($score, 92), 3);
            } elseif (preg_match('/\bSAVEMORE(?:\s+MARKET)?\b/i', $raw)) {
                $add('supplier', 'Savemore Market', min($score, 90), 2);
            } elseif ($type !== 'ewallet_receipt' && $i < 3 && preg_match('/\b(?:STORE|MARKET|SUPERMARKET|TRADING|RESTAURANT|ENTERPRISES|PHARMACY|BOOKSTORE)\b/i', $raw) && !preg_match('/(?:RECEIPT|WELCOME|THANK|CASHIER|DATE|INVOICE)/i', $raw)) {
                $add('supplier', mb_substr($raw, 0, 255), min($score, 65));
            }
            // Ignore registration/permit/expiry dates in receipt footers.
            if (preg_match('/\b(?:EXPIR|VALID UNTIL|PERMIT|ISSUED|ACCREDITATION|BIR|REGISTRATION)\b/i', $raw)) continue;
            $date = $this->date($raw, $ambiguous);
            if ($date) {
                $add('expense_date', $date, min($score, $ambiguous ? 55 : 93));
                if ($ambiguous) $warnings[] = 'Numeric date is ambiguous; check month and day (shown as month/day/year).';
            }
        }
        $fields = [];
        foreach (['supplier', 'unit_cost', 'expense_date', 'receipt_reference'] as $key) {
            $choices = $candidates[$key] ?? [];
            usort($choices, fn ($a, $b) => $b['rank'] <=> $a['rank'] ?: $b['score'] <=> $a['score']);
            $top = $choices[0] ?? null;
            $values = $top ? array_unique(array_column(array_filter($choices, fn ($c) => $c['rank'] === $top['rank']), 'value')) : [];
            if (count($values) > 1) {
                $warnings[] = 'Conflicting '.str_replace('_', ' ', $key).' values; enter the correct value from the receipt.';
                $top = null;
            }
            $fields[$key] = ['value' => $top['value'] ?? null, 'confidence' => $top['score'] ?? 0];
        }
        foreach (['receipt_type' => $type, 'payment_method' => $method] as $key => $value) {
            $fields[$key] = ['value' => $value, 'confidence' => $value === 'unknown' ? 0 : min(90, (int) ($ocr['confidence'] ?? 0))];
        }
        if ($fields['expense_date']['value'] && $fields['expense_date']['value'] > now()->toDateString()) {
            $fields['expense_date'] = ['value' => null, 'confidence' => 0];
            $warnings[] = 'A future date was detected; check the transaction date.';
        }
        // These are the minimum details needed to prove that the upload is a
        // complete receipt image. Payment channel and receipt type may not be
        // printed on every paper receipt, so they remain reviewable fields
        // instead of making an otherwise complete receipt fail the scan.
        $required = ['supplier', 'unit_cost', 'expense_date', 'receipt_reference'];
        $missingRequired = array_values(array_filter($required, fn ($key) =>
            $fields[$key]['value'] === null || $fields[$key]['confidence'] < 80
        ));
        return [
            'fields' => $fields,
            'quality' => $missingRequired === [] ? 'complete' : 'partial',
            'missing_required_fields' => $missingRequired,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function money(string $value): ?string
    {
        if (!preg_match('/^(?:PHP|₱|P)?\s*(\d{1,3}(?:,\d{3})+(?:\.\d{2})?|\d+(?:\.\d{2})?)\s*(?:PHP|₱)?\s*$/iu', trim($value), $m)) return null;
        $amount = (float) str_replace(',', '', $m[1]);
        return $amount > 0 && $amount <= 9999999999.99 ? number_format($amount, 2, '.', '') : null;
    }

    private function date(string $value, ?bool &$ambiguous): ?string
    {
        $ambiguous = false;
        if (preg_match('/\b(20\d{2})[-\/.](\d{1,2})[-\/.](\d{1,2})\b/', $value, $m)) {
            [$year, $month, $day] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        } elseif (preg_match('/\b(\d{1,2})[-\/.](\d{1,2})[-\/.](20\d{2})\b/', $value, $m)) {
            [$month, $day, $year] = [(int)$m[1], (int)$m[2], (int)$m[3]];
            if ($month > 12) [$month, $day] = [$day, $month];
            elseif ($day <= 12 && $month !== $day) $ambiguous = true;
        } elseif (preg_match('/\b(Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:t(?:ember)?)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\.?\s+(\d{1,2}),?\s+(20\d{2})\b/i', $value, $m)) {
            $month = array_search(strtolower(substr($m[1], 0, 3)), ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec']) + 1;
            $day = (int)$m[2]; $year = (int)$m[3];
        } else return null;
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }
}

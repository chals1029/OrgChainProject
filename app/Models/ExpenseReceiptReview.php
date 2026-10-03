<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseReceiptReview extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'receipt_scan_id', 'receipt_type', 'payment_method', 'ocr_corrections',
        'org_activity_id', 'uploaded_by', 'request_key', 'file_hash', 'receipt_disk',
        'activity_title',
        'item_name',
        'supplier',
        'organization_name',
        'category',
        'quantity',
        'unit_cost',
        'expense_date',
        'receipt_path',
        'receipt_name',
        'receipt_attachments',
        'receipt_reference',
        'ocr_confidence',
        'ocr_quality',
        'chain_hash',
        'previous_hash',
        'nodes_confirmed',
        'chain_driver',
        'chain_tx_hash',
        'chain_block_number',
        'chain_contract_address',
        'student_confirmed',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'ocr_corrections' => 'array',
            'receipt_attachments' => 'array',
            'expense_date' => 'date',
            'unit_cost' => 'decimal:2',
            'student_confirmed' => 'boolean',
        ];
    }

    /**
     * Return all receipt files for this expense, including legacy records
     * that only have receipt_path and receipt_name.
     *
     * @return list<array{path:string,disk:string,name:string,hash?:string,size?:int,mime?:string}>
     */
    public function receiptFiles(): array
    {
        $attachments = collect($this->receipt_attachments ?? [])
            ->filter(fn ($file) => is_array($file) && filled($file['path'] ?? null))
            ->map(fn (array $file) => [
                'path' => (string) $file['path'],
                'disk' => (string) ($file['disk'] ?? $this->receipt_disk ?: 'public'),
                'name' => (string) ($file['name'] ?? $this->receipt_name),
                'hash' => $file['hash'] ?? null,
                'size' => isset($file['size']) ? (int) $file['size'] : null,
                'mime' => $file['mime'] ?? null,
            ])->values();

        if ($attachments->isNotEmpty()) {
            return $attachments->all();
        }

        return [[
            'path' => (string) $this->receipt_path,
            'disk' => (string) ($this->receipt_disk ?: 'public'),
            'name' => (string) $this->receipt_name,
            'hash' => $this->file_hash,
            'size' => null,
            'mime' => null,
        ]];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseReceiptReview extends Model
{
    protected $fillable = [
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
        'receipt_reference',
        'ocr_confidence',
        'ocr_quality',
        'chain_hash',
        'previous_hash',
        'nodes_confirmed',
        'student_confirmed',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'unit_cost' => 'decimal:2',
            'student_confirmed' => 'boolean',
        ];
    }
}

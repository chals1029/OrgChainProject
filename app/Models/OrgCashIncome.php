<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only organization cash inflow. Recorded credits cannot be edited
 * or deleted through the model.
 */
class OrgCashIncome extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_fund_account_id',
        'organization_name',
        'org_activity_id',
        'transaction_date',
        'amount',
        'purpose',
        'received_from',
        'reference',
        'request_key',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Recorded cash inflows cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Recorded cash inflows cannot be deleted.'));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(OrgFundAccount::class, 'org_fund_account_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OrgActivity::class, 'org_activity_id');
    }
}

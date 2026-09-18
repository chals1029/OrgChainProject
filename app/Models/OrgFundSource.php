<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgFundSource extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_fund_account_id',
        'category',
        'label',
        'amount',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(OrgFundAccount::class, 'org_fund_account_id');
    }
}

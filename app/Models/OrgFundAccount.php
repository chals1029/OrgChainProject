<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgFundAccount extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'organization_name',
        'college',
        'total_funds',
        'beginning_balance',
        'total_funds_received',
        'cash_opening_balance',
        'fiscal_year',
    ];

    protected function casts(): array
    {
        return [
            'cash_opening_balance' => 'decimal:2',
        ];
    }

    public function sources(): HasMany
    {
        return $this->hasMany(OrgFundSource::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(OrgCashIncome::class);
    }
}

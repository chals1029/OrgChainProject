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
        'fiscal_year',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(OrgFundSource::class);
    }
}

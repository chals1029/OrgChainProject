<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistration extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_activity_id',
        'student_id',
        'sr_code',
        'full_name',
        'year_level',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OrgActivity::class, 'org_activity_id');
    }
}

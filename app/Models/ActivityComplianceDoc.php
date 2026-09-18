<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityComplianceDoc extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_activity_id',
        'submission_id',
        'doc_key',
        'title',
        'status',
        'returned_to',
        'remarks',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OrgActivity::class, 'org_activity_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(InCampusActivitySubmission::class, 'submission_id');
    }
}

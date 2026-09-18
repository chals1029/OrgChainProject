<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgRenewalDocument extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'submission_id',
        'doc_key',
        'title',
        'file_path',
        'file_name',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(OrgRenewalSubmission::class, 'submission_id');
    }
}

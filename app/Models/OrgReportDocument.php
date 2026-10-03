<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgReportDocument extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_report_status_id',
        'report_type',
        'organization_name',
        'semester',
        'academic_year',
        'name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function reportStatus(): BelongsTo
    {
        return $this->belongsTo(OrgReportStatus::class, 'org_report_status_id');
    }
}

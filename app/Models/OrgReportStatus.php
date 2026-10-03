<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgReportStatus extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'report_type',
        'organization_name',
        'college',
        'semester',
        'academic_year',
        'status',
        'returned_to',
        'notes',
        'batch_key',
        'submitted_at',
        'opened_at',
        'opened_by',
        'reviewed_at',
        'reviewed_by',
        'archived_at',
        'archive_folder_id',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'opened_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrgReportDocument::class, 'org_report_status_id');
    }
}

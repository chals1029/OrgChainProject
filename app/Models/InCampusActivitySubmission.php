<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InCampusActivitySubmission extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_activity_id',
        'status',
        'activity_type',
        'organization_name',
        'college',
        'workflow_status',
        'returned_to',
        'document_statuses',
        'sla_due_at',
        'reminder_sent_at',
        'rationale',
        'objectives',
        'participants',
        'safety_plan',
        'attachments',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'document_statuses' => 'array',
            'submitted_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OrgActivity::class, 'org_activity_id');
    }

    public function isOffCampus(): bool
    {
        return $this->activity_type === 'local_off_campus';
    }

    public function isInCampus(): bool
    {
        return $this->activity_type === 'in_campus' || empty($this->activity_type);
    }
}

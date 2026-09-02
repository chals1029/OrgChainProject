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
        'rationale',
        'objectives',
        'participants',
        'safety_plan',
        'programme_html',
        'project_proposal_html',
        'budget_proposal_html',
        'faculty_in_charge_html',
        'medical_request_html',
        'insurance_request_html',
        'resolution_html',
        'sample_letter_html',
        'wpcf_html',
        'approved_plan_html',
        'class_schedule_html',
        'meeting_minutes_html',
        'off_campus_req_html',
        'cert_compliance_html',
        'ched_report_html',
        'travel_matrix_html',
        'passenger_matrix_html',
        'course_activities_html',
        'attachments',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'submitted_at' => 'datetime',
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

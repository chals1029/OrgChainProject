<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgActivity extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_fund_account_id', 'opening_spent', 'approved_at', 'sdo_review_notes',
        'title',
        'description',
        'status',
        'location',
        'college',
        'organization_name',
        'program',
        'workflow_status',
        'returned_to',
        'activity_scope',
        'sdg_goals',
        'core_values',
        'male_participants',
        'female_participants',
        'approved_budget',
        'implemented_budget',
        'starts_at',
        'ends_at',
        'cover_image',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sdg_goals' => 'array',
            'core_values' => 'array',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'activity_id');
    }

    public function complianceDocs(): HasMany
    {
        return $this->hasMany(ActivityComplianceDoc::class, 'org_activity_id');
    }

    /**
     * Activities that have completed the full office approval workflow.
     *
     * Student-facing queries should use this scope so a submitted activity
     * cannot become public merely because its display status is upcoming.
     */
    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->where('workflow_status', 'oc_approved');
    }
}

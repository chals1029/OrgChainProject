<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgActivity extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
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
}

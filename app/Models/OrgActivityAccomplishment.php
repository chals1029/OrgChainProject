<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgActivityAccomplishment extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_activity_id', 'organization_name', 'academic_year', 'semester',
        'sponsor', 'brief_description', 'objectives', 'narrative', 'people_involved',
        'male_participants', 'female_participants', 'problems_encountered', 'recommendations',
        'signatories', 'activity_snapshot', 'financial_snapshot', 'evidence', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'signatories' => 'array', 'activity_snapshot' => 'array', 'financial_snapshot' => 'array',
            'evidence' => 'array', 'male_participants' => 'integer', 'female_participants' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OrgActivity::class, 'org_activity_id');
    }
}

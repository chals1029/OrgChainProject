<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgReportSubmissionWindow extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'report_type',
        'academic_year',
        'semester',
        'is_locked',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }
}

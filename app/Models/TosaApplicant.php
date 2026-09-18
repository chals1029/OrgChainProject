<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TosaApplicant extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'full_name',
        'email',
        'sr_code',
        'college',
        'program',
        'year_level',
        'organization_name',
        'subsection',
        'status',
        'requirements',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
        ];
    }
}

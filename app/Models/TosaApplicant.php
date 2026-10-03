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
        'gwa',
        'gwa_verified',
        'academic_year',
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
            'gwa' => 'float',
            'gwa_verified' => 'boolean',
        ];
    }

    public function displayGwa(): string
    {
        return $this->gwa !== null ? number_format((float) $this->gwa, 2) : '—';
    }

    public function honorStanding(): string
    {
        if ($this->gwa === null) {
            return 'Pending Verification';
        }

        $val = (float) $this->gwa;
        if ($val <= 1.20) {
            return "President's Lister · Highest Honors";
        }
        if ($val <= 1.45) {
            return "Dean's Lister · High Honors";
        }
        if ($val <= 1.75) {
            return "Academic Honors · With Honors";
        }

        return 'Good Academic Standing';
    }
}

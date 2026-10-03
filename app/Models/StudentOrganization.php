<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'college',
        'academic_year',
        'is_active',
        'is_qualified_for_renewal',
        'disqualification_reason',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_qualified_for_renewal' => 'boolean',
            'status_updated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeQualified($query)
    {
        return $query->where('is_qualified_for_renewal', true);
    }

    public function scopeNotQualified($query)
    {
        return $query->where('is_qualified_for_renewal', false);
    }

    public function scopeForAcademicYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }
}

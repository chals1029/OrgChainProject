<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'college',
        'academic_year',
        'logo_path',
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

    public function logoUrl(): ?string
    {
        if (filled($this->logo_path) && file_exists(public_path($this->logo_path))) {
            return asset($this->logo_path);
        }

        $shortName = Str::slug($this->short_name ?? '');
        $slugs = [
            $shortName,
            Str::slug($this->name ?? ''),
            $shortName !== '' ? $shortName.'-sc' : '',
            Str::endsWith($shortName, '-sc') ? Str::beforeLast($shortName, '-sc') : $shortName,
        ];

        foreach (array_unique($slugs) as $slug) {
            $path = "images/organizations/{$slug}.png";
            if ($slug !== '' && file_exists(public_path($path))) {
                return asset($path);
            }
        }

        $college = Str::lower($this->college ?? '');
        foreach ([
            'informatics' => 'cics.png',
            'accountancy' => 'cabeihm.png',
            'health' => 'chs.png',
            'teacher' => 'ctec.png',
            'laboratory' => 'sbo.png',
            'campus wide' => 'ssc.png',
        ] as $keyword => $file) {
            if (Str::contains($college, $keyword)) {
                $path = "images/organizations/{$file}";

                return file_exists(public_path($path)) ? asset($path) : null;
            }
        }

        return null;
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

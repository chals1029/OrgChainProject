<?php

namespace App\Models;

use App\Services\TosaRequirements;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
            'score_evaluation' => 'array',
            'gwa' => 'float',
            'gwa_verified' => 'boolean',
        ];
    }

    public function studentSubmissionLocked(): bool
    {
        return $this->status === 'accepted' || $this->subsection === 'accepted'
            || ($this->status === 'submitted' && $this->subsection !== 'returned');
    }

    public function requirementPath(string $key): ?string
    {
        if (! array_key_exists($key, TosaRequirements::SUBMISSIONS)) {
            return null;
        }
        $srCode = trim((string) $this->sr_code);
        $path = data_get($this->requirements, $key.'.path');
        if ($srCode === '' || in_array($srCode, ['.', '..'], true)
            || strpbrk($srCode, "/\\:\0") !== false || ! is_string($path) || str_contains($path, '\\')
            || str_contains($path, ':') || str_contains($path, "\0")
            || in_array('..', explode('/', $path), true)
            || ! str_starts_with($path, 'tosa/'.$srCode.'/')) {
            return null;
        }

        return $path;
    }

    public function ownedRequirementFile(string $key): ?string
    {
        $path = $this->requirementPath($key);
        if ($path === null) {
            return null;
        }

        clearstatcache(true);
        $disk = Storage::disk('local');
        $localRoot = realpath($disk->path(''));
        $ownerRoot = realpath($disk->path('tosa/'.trim((string) $this->sr_code)));
        $absolute = realpath($disk->path($path));
        if (! $localRoot || ! $ownerRoot || ! $absolute
            || $ownerRoot !== $localRoot.DIRECTORY_SEPARATOR.'tosa'.DIRECTORY_SEPARATOR.trim((string) $this->sr_code)
            || ! str_starts_with($absolute, $ownerRoot.DIRECTORY_SEPARATOR)
            || ! is_file($absolute)) {
            return null;
        }

        return $absolute;
    }

    public function requirementFile(string $key): ?string
    {
        $file = $this->ownedRequirementFile($key);

        return $file && TosaRequirements::validSubmissionFile($key, $file) ? $file : null;
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

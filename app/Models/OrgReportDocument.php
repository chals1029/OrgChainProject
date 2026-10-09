<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrgReportDocument extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'org_report_status_id',
        'report_type',
        'organization_name',
        'semester',
        'academic_year',
        'name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'financial_summary',
        'accomplishment_summary',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'financial_summary' => 'array',
            'accomplishment_summary' => 'array',
        ];
    }

    public function reportStatus(): BelongsTo
    {
        return $this->belongsTo(OrgReportStatus::class, 'org_report_status_id');
    }

    /**
     * A staged report counts only when a readable, non-empty regular file
     * exists; directories, zero-byte files, and metadata-only rows do not.
     */
    public function hasStoredFile(): bool
    {
        $path = trim((string) $this->file_path);
        if ($path === '' || str_contains(str_replace('\\', '/', $path), '../')) {
            return false;
        }

        $absolute = Storage::disk('public')->path($path);
        clearstatcache(true, $absolute);

        return is_file($absolute) && is_readable($absolute) && (int) filesize($absolute) > 0;
    }

    public function isWorkbook(): bool
    {
        return strtolower(pathinfo((string) ($this->original_name ?: $this->file_path), PATHINFO_EXTENSION)) === 'xlsx';
    }
}

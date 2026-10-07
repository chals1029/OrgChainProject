<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgRenewalDocument extends Model
{
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_VERIFIED = 'verified';
    public const REVIEW_RETURNED = 'returned';
    public const REVIEW_REJECTED = 'rejected';

    protected $connection = 'mysql';

    protected $attributes = [
        'review_status' => self::REVIEW_PENDING,
    ];

    protected $fillable = [
        'submission_id',
        'doc_key',
        'title',
        'file_path',
        'file_name',
        'review_status',
        'review_remarks',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public static function reviewStatusLabels(): array
    {
        return [
            self::REVIEW_PENDING => 'Pending',
            self::REVIEW_VERIFIED => 'Verified',
            self::REVIEW_RETURNED => 'For Revision',
            self::REVIEW_REJECTED => 'Rejected',
        ];
    }

    public function reviewStatusLabel(): string
    {
        return self::reviewStatusLabels()[$this->review_status ?: self::REVIEW_PENDING] ?? 'Pending';
    }

    public function fileVersion(): string
    {
        return hash('sha256', (string) $this->file_path);
    }

    public function isVerified(): bool
    {
        return $this->review_status === self::REVIEW_VERIFIED;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(OrgRenewalSubmission::class, 'submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(OfficeUser::class, 'reviewed_by');
    }
}

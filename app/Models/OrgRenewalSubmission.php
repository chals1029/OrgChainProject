<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class OrgRenewalSubmission extends Model
{
    public const TERMINAL_STATUSES = ['approved', 'rejected'];

    protected $connection = 'mysql';

    protected $fillable = [
        'renewal_window_id',
        'organization_name',
        'college',
        'submitted_by',
        'adviser_name',
        'dean_name',
        'status',
        'notes',
        'review_remarks',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function window(): BelongsTo
    {
        return $this->belongsTo(OrgRenewalWindow::class, 'renewal_window_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrgRenewalDocument::class, 'submission_id');
    }

    public function uploadedKeys(): array
    {
        return $this->storedDocuments()->keys()->all();
    }

    public function completionPercent(array $requiredDocs): int
    {
        $total = count($requiredDocs);
        if ($total === 0) {
            return 0;
        }
        $uploaded = count(array_intersect(
            collect($requiredDocs)->pluck('key')->all(),
            $this->uploadedKeys()
        ));

        return (int) round(($uploaded / $total) * 100);
    }

    /**
     * Review tally against the given checklist; documents whose key is not on
     * the checklist or whose stored file is absent/empty never count.
     *
     * @return array{required: int, verified: int, missing: int}
     */
    public function requiredDocumentReview(array $requiredDocs): array
    {
        $requiredKeys = collect($requiredDocs)->pluck('key')->filter()->unique()->values();
        $documents = $this->storedDocuments();

        return [
            'required' => $requiredKeys->count(),
            'verified' => $requiredKeys->filter(fn ($key) => $documents->get($key)?->isVerified() === true)->count(),
            'missing' => $requiredKeys->reject(fn ($key) => $documents->has($key))->count(),
        ];
    }

    public function canBeApproved(array $requiredDocs): bool
    {
        $review = $this->requiredDocumentReview($requiredDocs);

        return $this->status === 'submitted'
            && $review['required'] > 0
            && $review['missing'] === 0
            && $review['verified'] === $review['required'];
    }

    /**
     * Documents backed by a real stored file, keyed by doc_key.
     */
    public function storedDocuments(): Collection
    {
        return $this->documents
            ->filter(fn (OrgRenewalDocument $document) => $document->hasStoredFile())
            ->keyBy('doc_key');
    }
}

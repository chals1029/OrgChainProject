<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgRenewalSubmission extends Model
{
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
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
        return $this->documents->pluck('doc_key')->all();
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
}

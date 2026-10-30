<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TosaHistoryEvent extends Model
{
    protected $connection = 'mysql';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(TosaApplicant::class, 'applicant_id');
    }

    public static function record(string $action, string $actorType, ?int $actorId, string $actorName, ?int $applicantId = null, ?array $details = null): void
    {
        static::query()->create([
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'applicant_id' => $applicantId,
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}

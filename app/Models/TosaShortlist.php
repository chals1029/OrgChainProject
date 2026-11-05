<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TosaShortlist extends Model
{
    protected $connection = 'mysql';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'draft_ids' => 'array',
            'published_entries' => 'array',
            'published_decisions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public static function reviewFingerprint(TosaApplicant $applicant): string
    {
        return hash('sha256', json_encode($applicant->document_review['documents'] ?? null, JSON_THROW_ON_ERROR));
    }

    public function decisionFor(TosaApplicant $applicant, bool $reviewed): ?string
    {
        if (! $this->published_at || ! $reviewed) {
            return null;
        }
        $decision = $this->published_decisions[(string) $applicant->id] ?? null;
        if (! is_array($decision) || ! in_array($decision['status'] ?? null, ['qualified', 'not_qualified'], true)
            || ($decision['review'] ?? null) !== self::reviewFingerprint($applicant)) {
            return null;
        }

        return $decision['status'];
    }

    /** @return list<int> */
    public function applicantIdsForDecision(string $status): array
    {
        if (! $this->published_at || ! in_array($status, ['qualified', 'not_qualified'], true)) {
            return [];
        }
        $ids = [];
        foreach ($this->published_decisions ?? [] as $id => $entry) {
            if (is_array($entry) && ($entry['status'] ?? null) === $status && ctype_digit((string) $id)) {
                $ids[] = (int) $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $workflow = app(\App\Services\TosaWorkflowService::class);
        return TosaApplicant::query()->whereKey($ids)->where('status', 'submitted')->get()
            ->filter(fn (TosaApplicant $applicant): bool => $this->decisionFor($applicant, $workflow->isVerified($applicant)) === $status)
            ->map(fn (TosaApplicant $applicant): int => (int) $applicant->id)->values()->all();
    }
}

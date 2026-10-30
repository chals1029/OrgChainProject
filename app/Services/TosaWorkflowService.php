<?php

namespace App\Services;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use Illuminate\Validation\ValidationException;

final class TosaWorkflowService
{
    /** Check the OSO's attestations against the current, readable private originals. */
    public function isVerified(TosaApplicant $applicant): bool
    {
        $review = $applicant->document_review;
        if ($applicant->status !== 'submitted' || $applicant->subsection === 'returned') {
            return false;
        }
        $documents = $review['documents'] ?? null;
        if (! is_array($documents)) {
            return false;
        }
        foreach (TosaRequirements::SUBMISSIONS as $key => $_definition) {
            $attestation = $documents[$key] ?? null;
            $path = $applicant->requirementPath($key);
            if (! is_array($attestation) || ! is_string($path)
                || ($attestation['path'] ?? null) !== $path
                || ! is_int($attestation['verified_by'] ?? null) || $attestation['verified_by'] < 1
                || ! is_string($attestation['verified_at'] ?? null) || $attestation['verified_at'] === ''
                || $applicant->requirementFile($key) === null) {
                return false;
            }
        }

        return true;
    }

    /** The caller holds the applicant and reviewer locks. */
    public function confirmAll(TosaApplicant $applicant, OfficeUser $reviewer): void
    {
        $review = is_array($applicant->document_review) ? $applicant->document_review : [];
        $previous = is_array($review['documents'] ?? null) ? $review['documents'] : [];
        $documents = [];
        $time = now()->toIso8601String();
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $path = $applicant->requirementPath($key);
            if ($path === null || $applicant->requirementFile($key) === null) {
                throw ValidationException::withMessages(['documents' => 'The original '.$definition['title'].' must be available before adding this applicant.']);
            }
            $prior = $previous[$key] ?? null;
            $documents[$key] = is_array($prior) && ($prior['path'] ?? null) === $path
                && is_int($prior['verified_by'] ?? null) && $prior['verified_by'] > 0
                && is_string($prior['verified_at'] ?? null) && $prior['verified_at'] !== ''
                ? $prior : ['path' => $path, 'verified_by' => (int) $reviewer->id, 'verified_at' => $time];
        }
        if ($previous !== $documents) {
            $review['documents'] = $documents;
            $applicant->document_review = $review;
            $applicant->save();
            TosaHistoryEvent::record('reviewed', 'office', (int) $reviewer->id, $reviewer->name, (int) $applicant->id);
        }
    }
}

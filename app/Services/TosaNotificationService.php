<?php

namespace App\Services;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class TosaNotificationService
{
    public const AUTHORIZED_SR_CODES = ['23-73068', '23-70834', '23-73600'];
    public const AUTHORIZED_EMAILS = ['23-73068@g.batstate-u.edu.ph', '23-70834@g.batstate-u.edu.ph', '23-73600@g.batstate-u.edu.ph'];
    public const AUTHORIZED_NAMES = ['SAMONTANEZ', 'VIVA', 'VIVAS', 'CABADIN'];

    public function isWhitelisted(TosaApplicant $applicant, ?string $resolvedEmail): bool
    {
        return in_array(trim((string) $applicant->sr_code), self::AUTHORIZED_SR_CODES, true)
            || in_array(strtolower(trim((string) $resolvedEmail)), self::AUTHORIZED_EMAILS, true);
    }

    public function resolveApplicantEmail(TosaApplicant $applicant): ?string
    {
        $email = trim((string) $applicant->email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = trim((string) UserAccount::query()->where('sr_code', $applicant->sr_code)
                ->where('account_status', 'active')->first()?->email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    public function hasEmailBeenSent(TosaApplicant $applicant, string $status): bool
    {
        return TosaHistoryEvent::query()->where('applicant_id', $applicant->id)
            ->where('action', 'result_email_sent')->where('details->decision', $status)->exists();
    }

    public function sendResultNotifications(TosaShortlist $shortlist, OfficeUser $actor): array
    {
        $results = [];
        foreach (['qualified', 'not_qualified'] as $status) {
            $ids = $shortlist->applicantIdsForDecision($status);
            foreach (TosaApplicant::query()->whereKey($ids)->get() as $applicant) {
                $results[$applicant->id] = $this->sendApplicantNotification($applicant, $status, (int) $actor->id, $actor->name);
            }
        }

        return $results;
    }

    public function sendApplicantNotification(TosaApplicant $applicant, string $status, ?int $actorId, string $actorName): array
    {
        if (! in_array($status, ['qualified', 'not_qualified'], true)) {
            return ['status' => 'pending'];
        }
        if ($this->hasEmailBeenSent($applicant, $status)) {
            return ['status' => 'duplicate'];
        }

        $email = $this->resolveApplicantEmail($applicant);
        if (! $this->isWhitelisted($applicant, $email)) {
            $details = ['decision' => $status, 'recipient' => $email, 'reason' => 'Restricted: Not in authorized recipient whitelist.'];
            TosaHistoryEvent::record('result_email_skipped', 'office', $actorId, $actorName, $applicant->id, $details);
            Log::info('TOSA result email skipped.', ['applicant_id' => $applicant->id] + $details);

            return ['status' => 'skipped'];
        }

        try {
            if (! in_array($email, self::AUTHORIZED_EMAILS, true)) {
                throw new \RuntimeException('The resolved email is not an authorized official address.');
            }
            $docx = app(TosaLetterService::class)->generate($status, [
                'name' => $applicant->full_name,
                'program' => $applicant->program,
            ]);
            $body = "Dear Student,\n\nThank you for submitting your TOSA application.\n\nYour application has been reviewed, and the result is now available. Please see the attached Word document for your official TOSA application result.\n\nThank you for your interest and participation.\n\nBest regards,\nTOSA Administration";
            Mail::raw($body, function ($message) use ($email, $docx): void {
                $message->to($email)->subject('TOSA Application Result')
                    ->attachData($docx, 'TOSA-Application-Result.docx', [
                        'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ]);
            });
        } catch (Throwable $error) {
            TosaHistoryEvent::record('result_email_failed', 'office', $actorId, $actorName, $applicant->id, [
                'decision' => $status, 'recipient' => $email, 'error' => $error->getMessage(),
            ]);
            Log::warning('TOSA result email failed.', ['applicant_id' => $applicant->id, 'error' => $error->getMessage()]);

            return ['status' => 'failed'];
        }

        TosaHistoryEvent::record('result_email_sent', 'office', $actorId, $actorName, $applicant->id, [
            'decision' => $status, 'recipient' => $email, 'sent_at' => now()->toIso8601String(),
        ]);

        return ['status' => 'sent'];
    }
}

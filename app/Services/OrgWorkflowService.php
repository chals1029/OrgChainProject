<?php

namespace App\Services;

use App\Models\ActivityComplianceDoc;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrgWorkflowService
{
    public const FLOW = [
        'created',
        'college_review',
        'oso_review',
        'sdo_review',
        'ovcaa_review',
        'oc_review',
        'oc_approved',
    ];

    public function label(string $status): string
    {
        return match ($status) {
            'created' => 'Created',
            'college_review' => 'College Review',
            'oso_review' => 'OSO Review',
            'sdo_review' => 'SDO Review',
            'ovcaa_review' => 'OVCAA Review',
            'oc_review' => 'OC Final Approval',
            'oc_approved' => 'OC Approved',
            'returned' => 'Returned for Revision',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function nextStatus(string $current, string $role): ?string
    {
        $map = [
            'so' => ['created' => 'oso_review', 'returned' => 'oso_review'],
            'college_reviewer' => ['created' => 'oso_review', 'college_review' => 'oso_review'],
            'oso' => ['college_review' => 'sdo_review', 'oso_review' => 'sdo_review'],
            'sdo' => ['sdo_review' => 'ovcaa_review'],
            'ovcaa' => ['ovcaa_review' => 'oc_review'],
            'oc' => ['oc_review' => 'oc_approved'],
        ];

        // No college_reviewer accounts are seeded, so new SO submissions go
        // directly to OSO. College Review remains available for legacy rows
        // and explicit college_reviewer endorsements.
        return $map[$role][$current] ?? null;
    }

    public function canAct(string $role, string $status): bool
    {
        return $this->nextStatus($status, $role) !== null;
    }

    /**
     * Submitted activity files become readable as the package reaches each
     * review desk. A later desk must not be able to preview or download the
     * package before the preceding desk has endorsed it.
     *
     * Completed packages remain readable for audit purposes. A returned
     * package is locked again until SO resubmits it to OSO.
     */
    public function canViewActivityDocuments(string $role, string $status): bool
    {
        $status = $status ?: 'created';

        return match ($role) {
            'so' => true,
            'oso' => in_array($status, [
                'college_review',
                'oso_review',
                'sdo_review',
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'sdo' => in_array($status, [
                'sdo_review',
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'ovcaa' => in_array($status, [
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'oc' => in_array($status, ['oc_review', 'oc_approved', 'completed'], true),
            default => false,
        };
    }

    /**
     * Review desks only receive the activity package when it reaches their
     * assigned workflow stage. Earlier desks retain access after handoff for
     * audit history; downstream desks must not receive even the activity
     * detail record before their turn.
     */
    public function canViewActivityDetails(string $role, string $status): bool
    {
        $status = $status ?: 'created';

        return match ($role) {
            'so' => true,
            'oso' => in_array($status, [
                'oso_review',
                'sdo_review',
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'sdo' => in_array($status, [
                'sdo_review',
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'ovcaa' => in_array($status, [
                'ovcaa_review',
                'oc_review',
                'oc_approved',
                'completed',
            ], true),
            'oc' => in_array($status, ['oc_review', 'oc_approved', 'completed'], true),
            default => false,
        };
    }

    public function advance(OrgActivity $activity, string $role, array $review = []): OrgActivity
    {
        $updated = \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($activity, $role, $review) {
            $locked = OrgActivity::query()->lockForUpdate()->findOrFail($activity->id);
            $current = $locked->workflow_status ?: 'created';
            $next = $this->nextStatus($current, $role);
            if ($next === null) throw new \RuntimeException('This activity is not awaiting this desk’s review.');
            if (! $locked->starts_at || ($locked->ends_at ?? $locked->starts_at)->isPast()) {
                throw new \RuntimeException('An ended activity cannot be submitted or approved. Return it for a corrected schedule.');
            }
            if (in_array($role, ['oso', 'sdo'], true)) {
                if (empty($review['documents_reviewed'])) throw new \RuntimeException('Confirm that you reviewed the submitted files.');
                $this->checkDocuments($locked);
            }
            if ($role === 'oso') {
                ActivityComplianceDoc::query()->where('org_activity_id', $locked->id)->update(['status' => 'approved', 'returned_to' => null]);
            }
            if ($role === 'sdo') {
                $locked->sdo_review_notes = 'SDO checked the submitted DOCX files and Waste Policy Compliance Form.';
            }
            if ($next === 'oc_approved') app(ActivityBudgetService::class)->reserve($locked);
            $locked->workflow_status = $next;
            $locked->returned_to = null;
            $locked->status = $next === 'oc_approved' ? ($locked->starts_at->isPast() ? 'ongoing' : 'upcoming') : 'draft';
            $locked->save();
            $eventNotes = $role === 'sdo'
                ? $locked->sdo_review_notes
                : ($review['review_notes'] ?? null);
            $this->event($locked, $role, $current, $next, $eventNotes);
            $this->syncSubmission($locked);
            return $locked;
        });
        $activity->setRawAttributes($updated->getAttributes(), true);
        return $activity;
    }

    public function checkDocuments(OrgActivity $activity): void
    {
        $submission = InCampusActivitySubmission::query()->where('org_activity_id', $activity->id)->latest('id')->first();
        if (! $submission) throw new \RuntimeException('No submitted document package is attached to this activity.');
        $files = $submission->attachments ?? [];
        $requirements = app(ActivityRequirements::class);
        $requirements = $activity->activity_scope === 'local_off_campus' ? $requirements->localOffCampusRequirements() : $requirements->inCampusRequirements();
        foreach ($requirements as $requirement) {
            if (empty($requirement['required_on_submit'])) continue;
            $file = $files[$requirement['key']] ?? null;
            if (! is_array($file) || empty($file['path']) || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($file['path'])) {
                throw new \RuntimeException('Missing required upload: '.$requirement['title'].'. Ask SO to upload it to the matching checklist slot.');
            }
        }
    }

    public function event(OrgActivity $activity, string $role, string $from, string $to, ?string $notes): void
    {
        $actor = \Illuminate\Support\Facades\Auth::guard('office')->user();
        \Illuminate\Support\Facades\DB::connection('mysql')->table('activity_workflow_events')->insert([
            'org_activity_id' => $activity->id, 'actor_id' => $actor?->id, 'actor_name' => $actor?->name,
            'role' => $role, 'from_status' => $from, 'to_status' => $to, 'notes' => $notes, 'created_at' => now(),
        ]);
    }

    public function returnForRevision(OrgActivity $activity, string $returnTo, ?string $remarks = null): OrgActivity
    {
        $role = \Illuminate\Support\Facades\Auth::guard('office')->user()?->office_role ?? '';
        return \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($activity, $role, $remarks, $returnTo) {
            $locked = OrgActivity::query()->lockForUpdate()->findOrFail($activity->id);
            if (! in_array($role, ['oso', 'sdo', 'ovcaa', 'oc'], true) || ! $this->canAct($role, $locked->workflow_status)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['workflow' => 'Only the current reviewing desk can return this activity.']);
            }
            if ($returnTo !== 'so') throw \Illuminate\Validation\ValidationException::withMessages(['returned_to' => 'Revisions must return to SO for correction and full review.']);
            $from = $locked->workflow_status;
            $locked->update(['workflow_status' => 'returned', 'returned_to' => 'so', 'status' => 'draft']);
            ActivityComplianceDoc::query()->where('org_activity_id', $locked->id)->update(['status' => 'returned', 'returned_to' => 'so', 'remarks' => $remarks]);
            $this->event($locked, $role, $from, 'returned', $remarks);
            $this->syncSubmission($locked);
            $activity->setRawAttributes($locked->getAttributes(), true);
            return $activity;
        });
    }

    public function syncSubmission(OrgActivity $activity): void
    {
        InCampusActivitySubmission::query()
            ->where('org_activity_id', $activity->id)
            ->update([
                'workflow_status' => $activity->workflow_status,
                'returned_to' => $activity->returned_to,
                'status' => $activity->workflow_status === 'created' ? 'draft' : 'submitted',
            ]);
    }

    public function sendSlaReminder(string $toEmail, string $orgName, string $activityTitle, string $dueLabel): bool
    {
        $subject = 'OrgChain reminder: please submit compliance documents';
        $body = "Hello {$orgName},\n\n"
            ."OSO reminds you to submit required documents for \"{$activityTitle}\" by {$dueLabel}.\n\n"
            ."Please log in to the Student Organization desk and complete the pending requirements.\n\n"
            ."— Office of Student Organizations";

        try {
            Mail::raw($body, function ($message) use ($toEmail, $subject): void {
                $message->to($toEmail)->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('OSO SLA reminder failed: '.$e->getMessage());

            return false;
        }
    }
}

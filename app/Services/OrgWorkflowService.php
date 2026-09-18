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
            'oc_approved' => 'OC Approved',
            'returned' => 'Returned for Revision',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function nextStatus(string $current, string $role): ?string
    {
        $map = [
            'so' => ['created' => 'oso_review', 'returned' => 'college_review'],
            'college_reviewer' => ['created' => 'oso_review', 'college_review' => 'oso_review'],
            'oso' => ['college_review' => 'oso_review', 'oso_review' => 'sdo_review', 'created' => 'oso_review'],
            'sdo' => ['oso_review' => 'sdo_review', 'sdo_review' => 'ovcaa_review'],
            'ovcaa' => ['sdo_review' => 'ovcaa_review', 'ovcaa_review' => 'oc_approved'],
        ];

        // Demo continuity: no college_reviewer accounts seeded → SO created skips to OSO Review.
        // College Review remains available when a college_reviewer advances from created.
        return $map[$role][$current] ?? null;
    }

    public function canAct(string $role, string $status): bool
    {
        return $this->nextStatus($status, $role) !== null
            || in_array($role, ['oso', 'sdo', 'ovcaa', 'college_reviewer'], true);
    }

    public function advance(OrgActivity $activity, string $role): OrgActivity
    {
        $current = $activity->workflow_status ?: 'created';
        $next = $this->nextStatus($current, $role);

        if ($next === null && $role === 'oso' && $current === 'created') {
            $next = 'oso_review';
        }

        if ($next === null) {
            throw new \RuntimeException('This role cannot advance the current workflow step.');
        }

        $activity->workflow_status = $next;
        $activity->returned_to = null;
        if ($next === 'oc_approved') {
            $activity->status = 'completed';
        } elseif ($activity->status === 'draft') {
            $activity->status = 'upcoming';
        }
        $activity->save();

        return $activity;
    }

    public function returnForRevision(OrgActivity $activity, string $returnTo, ?string $remarks = null): OrgActivity
    {
        $activity->workflow_status = 'returned';
        $activity->returned_to = $returnTo;
        $activity->save();

        if ($remarks) {
            ActivityComplianceDoc::query()
                ->where('org_activity_id', $activity->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'returned',
                    'returned_to' => $returnTo,
                    'remarks' => $remarks,
                ]);
        }

        return $activity;
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

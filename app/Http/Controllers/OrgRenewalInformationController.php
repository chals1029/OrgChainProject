<?php

namespace App\Http\Controllers;

use App\Models\OrgRenewalSubmission;
use App\Models\OrgRenewalWindow;
use App\Models\StudentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrgRenewalInformationController extends Controller
{
    public function save(Request $request): JsonResponse
    {
        $office = Auth::guard('office')->user();
        abort_unless($office?->office_role === 'so', 403);
        $organization = $office->studentOrganization;
        abort_unless($organization instanceof StudentOrganization && $organization->is_active
            && trim((string) $organization->name) !== '', 403,
            'This SO account requires an active organization assignment.');

        $validated = $request->validate([
            'window_id' => ['required', 'integer'],
            'organization_name' => ['required', 'string', 'max:255'],
            'adviser_name' => ['required', 'string', 'max:255'],
            'dean_name' => ['required', 'string', 'max:255'],
        ]);
        abort_unless($validated['organization_name'] === $organization->name, 403,
            'This SO account is not assigned to that organization.');

        $submission = DB::connection('mysql')->transaction(function () use ($validated, $office, $organization): OrgRenewalSubmission {
            $window = OrgRenewalWindow::query()->lockForUpdate()->find($validated['window_id']);
            if (! $window
                || (int) OrgRenewalWindow::query()->latest('id')->value('id') !== (int) $window->id
                || ! $window->isAcceptingSubmissions()) {
                throw ValidationException::withMessages(['renewal' => 'The renewal window changed or closed. Reload the page before saving information.']);
            }
            $existing = OrgRenewalSubmission::query()
                ->where('renewal_window_id', $window->id)
                ->where('organization_name', $organization->name)
                ->lockForUpdate()
                ->first();
            if ($existing?->submissionLocked()) {
                throw ValidationException::withMessages(['renewal' => 'This renewal packet is '.$existing->status.' and cannot be edited. OSO must return or reject a submitted packet before changes can be made.']);
            }
            $currentOrganization = StudentOrganization::query()->find($organization->id);
            abort_unless($currentOrganization && $currentOrganization->is_active
                && (int) $office->fresh()->student_organization_id === (int) $currentOrganization->id
                && $currentOrganization->name === $organization->name, 403);
            if (! ($currentOrganization->is_qualified_for_renewal ?? true)) {
                throw ValidationException::withMessages(['renewal' => 'Renewal information blocked: '.($currentOrganization->disqualification_reason ?: 'This organization is Not Qualified to Renew by OSO.')]);
            }

            $submission = $existing ?? new OrgRenewalSubmission([
                'renewal_window_id' => $window->id,
                'organization_name' => $currentOrganization->name,
                'status' => 'draft',
            ]);
            $submission->fill([
                'college' => $currentOrganization->college,
                'submitted_by' => $office->id,
                'adviser_name' => trim($validated['adviser_name']),
                'dean_name' => trim($validated['dean_name']),
            ])->save();

            return $submission;
        });

        return response()->json([
            'message' => 'Organization information saved. Selected files have not been uploaded.',
            'information' => [
                'window_id' => $submission->renewal_window_id,
                'organization_name' => $submission->organization_name,
                'college' => $submission->college,
                'adviser_name' => $submission->adviser_name,
                'dean_name' => $submission->dean_name,
                'status' => $submission->status,
            ],
        ]);
    }
}

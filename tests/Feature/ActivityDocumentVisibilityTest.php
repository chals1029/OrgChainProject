<?php

namespace Tests\Feature;

use App\Models\ActivityComplianceDoc;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ActivityDocumentVisibilityTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_oso_can_preview_a_file_from_an_older_submission_version(): void
    {
        Storage::fake('public');
        $office = $this->ensureOfficeUser('oso');
        $suffix = uniqid('', true);
        $title = 'Document Visibility Activity '.$suffix;
        $organization = 'Document Visibility Organization '.$suffix;
        $path = 'in-campus-activities/legacy-version/signed-proposal.docx';
        Storage::disk('public')->put($path, 'test document');

        $activity = OrgActivity::query()->create([
            'title' => $title,
            'description' => 'Verifies OSO can read files saved before a later edit.',
            'status' => 'draft',
            'workflow_status' => 'oso_review',
            'activity_scope' => 'in_campus',
            'organization_name' => $organization,
            'college' => 'CICS',
            'location' => 'Test venue',
            'approved_budget' => 1000,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $olderSubmission = InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'status' => 'submitted',
            'activity_type' => 'in_campus',
            'organization_name' => $organization,
            'attachments' => [
                'project_proposal' => [
                    'path' => $path,
                    'name' => 'signed-proposal.docx',
                    'uploaded_at' => now()->subMinutes(5)->toIso8601String(),
                ],
            ],
            'submitted_at' => now()->subMinutes(5),
        ]);

        // A later edit row has no file for this key. The OSO page must still
        // follow the compliance row back to the submission that owns the file.
        InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'status' => 'draft',
            'activity_type' => 'in_campus',
            'organization_name' => $organization,
            'attachments' => ['conditions' => []],
        ]);

        $doc = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $activity->id,
            'submission_id' => $olderSubmission->id,
            'doc_key' => 'project_proposal',
            'title' => 'Project Proposal',
            'status' => 'approved',
        ]);

        try {
            $previewUrl = route('office.activities.attachments.file', [
                'submission' => $olderSubmission->id,
                'key' => 'project_proposal',
                'preview' => 1,
            ]);

            $this->actingAs($office, 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee('Project Proposal', false)
                ->assertSee($previewUrl, false)
                ->assertDontSee('No uploaded documents yet', false);

            $this->actingAs($office, 'office')
                ->get($previewUrl)
                ->assertOk();
        } finally {
            $doc->delete();
            InCampusActivitySubmission::query()->where('org_activity_id', $activity->id)->delete();
            $activity->delete();
        }
    }

    public function test_status_only_demo_rows_do_not_appear_as_uploaded_documents(): void
    {
        Storage::fake('public');
        $office = $this->ensureOfficeUser('oso');
        $suffix = uniqid('', true);
        $activity = OrgActivity::query()->create([
            'title' => 'Status Only Activity '.$suffix,
            'status' => 'upcoming',
            'workflow_status' => 'oc_approved',
            'activity_scope' => 'in_campus',
            'organization_name' => 'Status Only Organization '.$suffix,
            'college' => 'CICS',
            'location' => 'Test venue',
            'approved_budget' => 1000,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);
        $doc = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $activity->id,
            'doc_key' => 'project_proposal',
            'title' => 'Project Proposal',
            'status' => 'approved',
        ]);

        try {
            $this->actingAs($office, 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee('No uploaded documents yet', false)
                ->assertDontSee('No file uploaded yet', false);
        } finally {
            $doc->delete();
            $activity->delete();
        }
    }

    public function test_downstream_desks_cannot_see_activity_documents_before_their_review_stage(): void
    {
        Storage::fake('public');
        $suffix = uniqid('', true);
        $organization = 'Stage Locked Organization '.$suffix;
        $path = 'in-campus-activities/stage-locked/signed-proposal.docx';
        Storage::disk('public')->put($path, 'test document');

        $activity = OrgActivity::query()->create([
            'title' => 'Stage Locked Activity '.$suffix,
            'description' => 'Verifies each review desk receives files only at its workflow stage.',
            'status' => 'draft',
            'workflow_status' => 'oso_review',
            'activity_scope' => 'in_campus',
            'organization_name' => $organization,
            'college' => 'CICS',
            'location' => 'Test venue',
            'approved_budget' => 1000,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $submission = InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'status' => 'submitted',
            'activity_type' => 'in_campus',
            'organization_name' => $organization,
            'attachments' => [
                'project_proposal' => [
                    'path' => $path,
                    'name' => 'signed-proposal.docx',
                    'uploaded_at' => now()->subMinutes(5)->toIso8601String(),
                ],
            ],
            'submitted_at' => now()->subMinutes(5),
        ]);

        $doc = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $activity->id,
            'submission_id' => $submission->id,
            'doc_key' => 'project_proposal',
            'title' => 'Project Proposal',
            'status' => 'pending',
        ]);

        $previewUrl = route('office.activities.attachments.file', [
            'submission' => $submission->id,
            'key' => 'project_proposal',
            'preview' => 1,
        ]);

        try {
            foreach (['sdo', 'ovcaa', 'oc'] as $role) {
                $this->actingAs($this->ensureOfficeUser($role), 'office')
                    ->get('/office-desk/activities?activity='.$activity->id)
                    ->assertOk()
                    ->assertDontSee($activity->title, false)
                    ->assertDontSee('Documents locked until your review stage', false)
                    ->assertDontSee('Project Proposal', false)
                    ->assertDontSee($previewUrl, false);

                $this->actingAs($this->ensureOfficeUser($role), 'office')
                    ->get($previewUrl)
                    ->assertForbidden();

                $this->actingAs($this->ensureOfficeUser($role), 'office')
                    ->get('/office-desk/activities/'.$submission->id.'/edit')
                    ->assertForbidden();
            }

            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee('Project Proposal', false)
                ->assertSee($previewUrl, false);

            $activity->update(['workflow_status' => 'sdo_review']);
            $this->actingAs($this->ensureOfficeUser('sdo'), 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee($activity->title, false)
                ->assertSee('Project Proposal', false);
            $this->actingAs($this->ensureOfficeUser('sdo'), 'office')
                ->get($previewUrl)
                ->assertOk();

            $activity->update(['workflow_status' => 'ovcaa_review']);
            $this->actingAs($this->ensureOfficeUser('ovcaa'), 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee($activity->title, false)
                ->assertSee('Project Proposal', false);
            $this->actingAs($this->ensureOfficeUser('ovcaa'), 'office')
                ->get($previewUrl)
                ->assertOk();
            $this->actingAs($this->ensureOfficeUser('oc'), 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertDontSee($activity->title, false);

            $activity->update(['workflow_status' => 'oc_review']);
            $this->actingAs($this->ensureOfficeUser('oc'), 'office')
                ->get('/office-desk/activities?activity='.$activity->id)
                ->assertOk()
                ->assertSee($activity->title, false)
                ->assertSee('Project Proposal', false);
            $this->actingAs($this->ensureOfficeUser('oc'), 'office')
                ->get($previewUrl)
                ->assertOk();
        } finally {
            $doc->delete();
            $submission->delete();
            $activity->delete();
        }
    }

    public function test_activity_edit_form_does_not_nest_delete_method_into_update_form(): void
    {
        Storage::fake('public');
        $office = $this->ensureOfficeUser('so');
        $suffix = uniqid('', true);
        $organization = $office->organizationName() ?: 'Edit Form Organization '.$suffix;
        $path = 'in-campus-activities/edit-form/activity-proposal.pdf';
        Storage::disk('public')->put($path, 'test document');

        $activity = OrgActivity::query()->create([
            'title' => 'Edit Form Activity '.$suffix,
            'description' => 'Verifies the edit form keeps its update method.',
            'status' => 'upcoming',
            'workflow_status' => 'created',
            'activity_scope' => 'in_campus',
            'organization_name' => $organization,
            'college' => 'CICS',
            'location' => 'Test venue',
            'approved_budget' => 1000,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $submission = InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'status' => 'submitted',
            'activity_type' => 'in_campus',
            'organization_name' => $organization,
            'attachments' => [
                'project_proposal' => [
                    'path' => $path,
                    'name' => 'activity-proposal.pdf',
                    'uploaded_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        try {
            $content = $this->actingAs($office, 'office')
                ->get('/office-desk/activities/'.$submission->id.'/edit')
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('name="_method" value="PUT"', $content);
            $this->assertStringNotContainsString('name="_method" value="DELETE"', $content);
            $this->assertStringContainsString('data-attachment-delete', $content);
        } finally {
            $submission->delete();
            $activity->delete();
        }
    }
}

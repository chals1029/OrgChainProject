<?php

namespace Tests\Feature;

use App\Models\ActivityComplianceDoc;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivity;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UsesLaragonDatabase;
use Tests\Support\CreatesApprovedActivityPlan;
use Tests\TestCase;

class ActivityDocumentVisibilityTest extends TestCase
{
    use UsesLaragonDatabase;
    use CreatesApprovedActivityPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_oso_can_preview_a_file_from_an_older_submission_version(): void
    {
        Storage::fake('local');
        $office = $this->ensureOfficeUser('oso');
        $suffix = uniqid('', true);
        $title = 'Document Visibility Activity '.$suffix;
        $organization = 'Document Visibility Organization '.$suffix;
        $path = 'in-campus-activities/legacy-version/signed-proposal.docx';
        Storage::disk('local')->put($path, 'test document');

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
        Storage::fake('local');
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
        Storage::fake('local');
        $suffix = uniqid('', true);
        $organization = 'Stage Locked Organization '.$suffix;
        $path = 'in-campus-activities/stage-locked/signed-proposal.docx';
        Storage::disk('local')->put($path, 'test document');

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
        $downloadUrl = route('office.activities.attachments.file', [
            'submission' => $submission->id,
            'key' => 'project_proposal',
            'download' => 1,
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
                    ->get($downloadUrl)
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

            $this->actingAs($this->ensureOfficeUser('sdo'), 'office')
                ->post(route('office.activities.advance', $activity))
                ->assertSessionHasErrors('documents_reviewed');
            $this->assertSame('sdo_review', $activity->refresh()->workflow_status);
            $this->actingAs($this->ensureOfficeUser('sdo'), 'office')
                ->post(route('office.activities.advance', $activity), ['documents_reviewed' => '0'])
                ->assertSessionHasErrors('documents_reviewed');
            $this->assertSame('sdo_review', $activity->refresh()->workflow_status);

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
            foreach (['sdo', 'ovcaa', 'oc'] as $role) {
                $response = $this->actingAs($this->ensureOfficeUser($role), 'office')
                    ->get($downloadUrl)->assertOk();
                $this->assertSame('test document', $response->baseResponse->getFile()->getContent());
            }

            $activity->update(['workflow_status' => 'returned']);
            foreach (['oso', 'sdo', 'ovcaa', 'oc'] as $role) {
                $this->actingAs($this->ensureOfficeUser($role), 'office')->get($previewUrl)->assertForbidden();
                $this->actingAs($this->ensureOfficeUser($role), 'office')->get($downloadUrl)->assertForbidden();
            }
        } finally {
            $doc->delete();
            $submission->delete();
            $activity->delete();
        }
    }

    #[DataProvider('documentReviewStages')]
    public function test_document_display_reflects_passed_stages_without_mutating_reviews(
        string $workflowStatus,
        string $storedStatus,
        string $expectedStatus,
        string $expectedStyle,
        string $expectedLegacyStatus,
        string $expectedLegacyStyle
    ): void {
        Storage::fake('local');
        $office = $this->ensureOfficeUser('so');
        $organization = $office->organizationName();
        $path = 'in-campus-activities/review-stage/proposal.docx';
        Storage::disk('local')->put($path, 'Original proposal bytes');
        $activity = OrgActivity::query()->create([
            'title' => 'Review Stage Display Activity',
            'status' => 'draft',
            'workflow_status' => $workflowStatus,
            'activity_scope' => 'in_campus',
            'organization_name' => $organization,
            'college' => 'CICS',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'approved_budget' => 0,
        ]);
        $submission = InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id,
            'status' => 'submitted',
            'activity_type' => 'in_campus',
            'organization_name' => $organization,
            'attachments' => [
                'project_proposal' => ['path' => $path, 'name' => 'proposal.docx'],
                'legacy_document' => ['path' => $path, 'name' => 'legacy.docx'],
            ],
        ]);
        $doc = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $activity->id,
            'submission_id' => $submission->id,
            'doc_key' => 'project_proposal',
            'title' => 'Project Proposal',
            'status' => $storedStatus,
        ]);
        try {
            $this->actingAs($office, 'office')
                ->get(route('office.activities', ['activity' => $activity->id]))
                ->assertOk()
                ->assertViewHas('selectedActivity', function (array $selected) use (
                    $expectedStatus, $expectedStyle, $expectedLegacyStatus, $expectedLegacyStyle
                ): bool {
                    $documents = collect($selected['documents'])->keyBy('filename');
                    return $documents->count() === 2
                        && $documents['proposal.docx']['status'] === $expectedStatus
                        && $documents['proposal.docx']['status_style'] === $expectedStyle
                        && $documents['legacy.docx']['status'] === $expectedLegacyStatus
                        && $documents['legacy.docx']['status_style'] === $expectedLegacyStyle;
                });
            $this->assertSame($storedStatus, $doc->refresh()->status);
            $this->assertSame($workflowStatus, $activity->refresh()->workflow_status);
        } finally {
            $doc->delete();
            $submission->delete();
            $activity->delete();
        }
    }

    public static function documentReviewStages(): array
    {
        return [
            'unsubmitted' => ['created', 'approved', 'Not submitted', 'yellow', 'Not submitted', 'yellow'],
            'awaiting OSO' => ['oso_review', 'pending', 'Pending', 'yellow', 'Pending', 'yellow'],
            'individual OSO approval' => ['oso_review', 'approved', 'OSO approved', 'green', 'Pending', 'yellow'],
            'awaiting SDO' => ['sdo_review', 'pending', 'OSO approved', 'green', 'OSO approved', 'green'],
            'awaiting OVCAA' => ['ovcaa_review', 'approved', 'SDO approved', 'green', 'SDO approved', 'green'],
            'awaiting OC' => ['oc_review', 'approved', 'OVCAA approved', 'green', 'OVCAA approved', 'green'],
            'final approval' => ['oc_approved', 'approved', 'OC approved', 'green', 'OC approved', 'green'],
            'completed' => ['completed', 'approved', 'OC approved', 'green', 'OC approved', 'green'],
            'returned package' => ['returned', 'approved', 'Returned', 'red', 'Returned', 'red'],
            'returned document' => ['sdo_review', 'returned', 'Returned', 'red', 'OSO approved', 'green'],
            'rejected document' => ['oc_review', 'rejected', 'Rejected', 'red', 'OVCAA approved', 'green'],
        ];
    }

    public function test_returned_edit_selects_matching_actual_plan_and_does_not_retain_a_missing_entry(): void
    {
        Storage::fake('local');
        $office = $this->ensureOfficeUser('so');
        $organization = $office->organizationName();
        $plan = $this->approvedActivityPlan($organization, ['Current Approved Workshop']);
        $option = $plan['options'][0];
        $selectedValue = static function (string $content): string {
            $dom = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $dom->loadHTML($content, LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new \DOMXPath($dom);
            $select = $xpath->query('//select[@name="plan_activity_key"]')->item(0);
            if (! $select instanceof \DOMElement) {
                throw new \RuntimeException('The activity selection is missing from the returned form.');
            }
            $option = $xpath->query('./option[@selected]', $select)->item(0)
                ?? $xpath->query('./option', $select)->item(0);
            return $option->getAttribute('value');
        };
        $activity = OrgActivity::create([
            'title' => $option['title'], 'organization_name' => $organization,
            'workflow_status' => 'returned', 'status' => 'draft', 'activity_scope' => 'in_campus',
            'starts_at' => now()->addDay(), 'approved_budget' => 0,
        ]);
        $submission = InCampusActivitySubmission::create([
            'org_activity_id' => $activity->id, 'organization_name' => $organization,
            'status' => 'returned', 'activity_type' => 'in_campus',
            'attachments' => ['plan_reference' => $option['reference']],
        ]);
        try {
            $content = $this->actingAs($office, 'office')->get(route('office.activities.edit', $submission))
                ->assertOk()->getContent();
            $this->assertSame($option['key'], $selectedValue($content));
            $activity->update(['title' => 'No longer in current plan']);
            $submission->update(['attachments' => ['plan_reference' => 'Old plan reference', 'plan_activity_key' => 'stale-key']]);
            $content = $this->get(route('office.activities.edit', $submission))->assertOk()->getContent();
            $this->assertSame('', $selectedValue($content));
        } finally {
            $submission->delete();
            $activity->delete();
            $plan['document']->delete();
            $plan['submission']->delete();
            $plan['window']->delete();
        }
    }

}

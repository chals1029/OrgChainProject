<?php

namespace Tests\Feature;

use App\Models\ActivityComplianceDoc;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\StudentOrganization;
use App\Services\ApprovedPlanOfActivitiesService;
use Tests\Support\CreatesApprovedActivityPlan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ActivityChecklistSubmissionTest extends TestCase
{
    use UsesLaragonDatabase;
    use CreatesApprovedActivityPlan;

    private StudentOrganization $organization;
    private array $offices;
    private array $planFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Storage::fake('public');
        Storage::fake('local');
        $this->organization = StudentOrganization::create([
            'name' => 'Checklist Fixture '.Str::uuid(),
            'college' => 'Checklist Test College',
            'is_active' => true,
        ]);
        foreach (['so', 'oso'] as $role) {
            $this->offices[$role] = OfficeUser::create([
                'name' => 'Checklist Fixture '.strtoupper($role),
                'email' => Str::uuid().'@example.test',
                'username' => 'checklist_'.Str::random(16),
                'password' => Str::random(32),
                'office_title' => 'Checklist fixture desk',
                'office_role' => $role,
                'student_organization_id' => $role === 'so' ? $this->organization->id : null,
                'is_active' => true,
            ]);
        }
    }

    protected function tearDown(): void
    {
        try {
            foreach (['mysql', 'orgchain'] as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    #[DataProvider('documentChecklists')]
    public function test_incomplete_checklist_saves_nothing_and_one_complete_submit_reaches_oso_review(string $type, array $documents): void
    {
        $coreKeys = array_keys($documents);
        $files = $this->checklistFiles($coreKeys);
        $title = 'Checklist Submission '.Str::uuid();
        $payload = $this->payload($type, $title);
        $incomplete = $files;
        array_pop($incomplete);

        $this->actingAs($this->offices['so'], 'office')
            ->post(route('office.activities.store'), $payload + ['attachments' => $incomplete])
            ->assertSessionHasErrors('attachments');
        $this->assertNothingSaved($title);

        $this->post(route('office.activities.store'), $payload)
            ->assertSessionHasErrors('attachments');
        $this->assertNothingSaved($title);

        $emptyKey = $coreKeys[0];
        $this->post(route('office.activities.store'), $payload + ['attachments' => [
            $emptyKey => UploadedFile::fake()->create($emptyKey.'.pdf', 0, 'application/pdf'),
        ] + $files])->assertSessionHasErrors('attachments.'.$emptyKey);
        $this->assertNothingSaved($title);

        $this->post(route('office.activities.store'), $payload + ['attachments' => $files])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $activity = OrgActivity::where('title', $title)->sole();
        $this->assertSame('oso_review', $activity->workflow_status);
        $this->assertSame($type, $activity->activity_scope);
        $submission = InCampusActivitySubmission::where('org_activity_id', $activity->id)->sole();
        $this->assertSame('submitted', $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $storedKeys = array_values(array_intersect(array_keys($submission->attachments), $coreKeys));
        $this->assertEqualsCanonicalizing($coreKeys, $storedKeys);
        foreach ($coreKeys as $key) {
            $this->assertStoredDocument($submission->attachments[$key]['path']);
            $this->assertSame($key.'.pdf', $submission->attachments[$key]['name']);
        }
        $this->assertCount(count($coreKeys) + count($this->planFiles), Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertEqualsCanonicalizing(
            $coreKeys,
            ActivityComplianceDoc::where('org_activity_id', $activity->id)->pluck('doc_key')->all(),
        );

        $this->actingAs($this->offices['oso'], 'office')
            ->post(route('office.activities.advance', $activity), ['documents_reviewed' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('sdo_review', $activity->refresh()->workflow_status);
    }

    #[DataProvider('documentChecklists')]
    public function test_draft_or_missing_submission_intent_is_rejected_without_writes(string $type, array $documents): void
    {
        $title = 'Intent Check '.Str::uuid();
        $payload = $this->payload($type, $title);
        $this->actingAs($this->offices['so'], 'office');

        $this->post(route('office.activities.store'), ['submission_action' => 'draft'] + $payload + [
            'attachments' => $this->checklistFiles(array_keys($documents)),
        ])->assertSessionHasErrors('submission_action');
        $this->assertNothingSaved($title);

        unset($payload['submission_action']);
        $this->post(route('office.activities.store'), $payload + [
            'attachments' => $this->checklistFiles(array_keys($documents)),
        ])->assertSessionHasErrors('submission_action');
        $this->assertNothingSaved($title);
    }

    #[DataProvider('documentChecklists')]
    public function test_returned_packet_with_an_unusable_stored_file_cannot_be_resubmitted_until_replaced(string $type, array $documents): void
    {
        $coreKeys = array_keys($documents);
        $title = 'Returned Packet '.Str::uuid();
        $payload = $this->payload($type, $title);
        $this->actingAs($this->offices['so'], 'office')
            ->post(route('office.activities.store'), $payload + ['attachments' => $this->checklistFiles($coreKeys)])
            ->assertSessionHasNoErrors();
        $activity = OrgActivity::where('title', $title)->sole();
        $this->actingAs($this->offices['oso'], 'office')
            ->post(route('office.activities.return', $activity), ['returned_to' => 'so', 'remarks' => 'Revise the packet.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('returned', $activity->refresh()->workflow_status);

        $submission = InCampusActivitySubmission::where('org_activity_id', $activity->id)->sole();
        $lostKey = $coreKeys[0];
        $lostPath = $submission->attachments[$lostKey]['path'];
        $disk = Storage::disk('local');
        $revised = ['location' => 'Revised venue'] + $this->payload($type, $title.' revised');
        $corruptions = [
            'missing' => function () use ($disk, $lostPath): void {
                $disk->delete($lostPath);
            },
            'zero-byte' => function () use ($disk, $lostPath): void {
                $disk->put($lostPath, '');
            },
            'directory' => function () use ($disk, $lostPath): void {
                $disk->delete($lostPath);
                $disk->makeDirectory($lostPath);
            },
        ];
        $this->actingAs($this->offices['so'], 'office');

        foreach ($corruptions as $corrupt) {
            $corrupt();
            $savedActivity = $activity->fresh()->getAttributes();
            $savedSubmission = $submission->fresh()->getAttributes();
            $savedDocs = ActivityComplianceDoc::where('org_activity_id', $activity->id)->orderBy('id')->get()->toArray();
            $savedFiles = $disk->allFiles();

            $this->put(route('office.activities.update', $submission), $revised)
                ->assertSessionHasErrors('attachments');
            $this->put(route('office.activities.update', $submission), $revised + [
                'attachments' => [$lostKey => UploadedFile::fake()->create($lostKey.'.pdf', 0, 'application/pdf')],
            ])->assertSessionHasErrors('attachments.'.$lostKey);
            $this->put(route('office.activities.update', $submission), ['submission_action' => 'draft'] + $revised)
                ->assertSessionHasErrors('submission_action');

            $this->assertSame($savedActivity, $activity->fresh()->getAttributes());
            $this->assertSame($savedSubmission, $submission->fresh()->getAttributes());
            $this->assertSame($savedDocs, ActivityComplianceDoc::where('org_activity_id', $activity->id)->orderBy('id')->get()->toArray());
            $this->assertEqualsCanonicalizing($savedFiles, $disk->allFiles());
        }

        $this->put(route('office.activities.update', $submission), $revised + [
            'attachments' => [$lostKey => $this->pdf($lostKey)],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $activity->refresh();
        $this->assertSame('oso_review', $activity->workflow_status);
        $this->assertNull($activity->returned_to);
        $this->assertSame($title.' revised', $activity->title);
        $submission->refresh();
        $this->assertSame('submitted', $submission->status);
        $this->assertNull($submission->returned_to);
        $this->assertNotSame($lostPath, $submission->attachments[$lostKey]['path']);
        foreach ($coreKeys as $key) {
            $this->assertStoredDocument($submission->attachments[$key]['path']);
        }
    }

    #[DataProvider('documentChecklists')]
    public function test_unlisted_documents_and_bulk_extras_cannot_create_an_activity(string $type, array $documents): void
    {
        $title = 'Unlisted Upload '.Str::uuid();
        $payload = $this->payload($type, $title);
        $files = $this->checklistFiles(array_keys($documents));
        $this->actingAs($this->offices['so'], 'office')
            ->post(route('office.activities.store'), $payload + [
                'attachments' => $files + ['meeting_minutes' => $this->pdf('meeting_minutes')],
            ])->assertSessionHasErrors('attachments');
        $this->assertNothingSaved($title);
        $this->post(route('office.activities.store'), $payload + [
            'attachments' => $files,
            'supporting_documents' => [$this->pdf('extra')],
        ])->assertSessionHasErrors('supporting_documents');
        $this->assertNothingSaved($title);
    }

    #[DataProvider('documentChecklists')]
    public function test_template_pack_contains_only_the_listed_official_documents(string $type, array $documents): void
    {
        $response = $this->actingAs($this->offices['so'], 'office')
            ->get(route('office.activities.templates.download', ['type' => $type]))
            ->assertOk()
            ->assertDownload();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $actual = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $actual[] = $zip->getNameIndex($index);
        }
        $expectedNames = $type === 'in_campus' ? [
            'checklist_template' => 'Checklist Template.docx',
            'programme' => 'Programme.docx',
            'project_proposal' => 'Project Proposal.docx',
            'budget_proposal' => 'Budget Proposal.docx',
            'medical_clearance' => 'Medical Request Sample Letter.docx',
            'insurance' => 'Insurance Request Sample Letter.docx',
            'resolution' => 'Resolution of the Organization.docx',
            'faculty_in_charge' => 'Faculty-in-Charge.docx',
            'sample_letter' => 'Sample Letter.docx',
            'wpcf' => 'Waste Policy Compliance Form (WPCF).docx',
        ] : [
            'course_activities' => 'Course Activities.docx',
            'off_campus_req' => 'Request for Conduct of Local Off-Campus Activities (FO-REQ-09).docx',
            'parents_consent' => 'Parent Consent Form - Waiver (FO-SOA-03).doc',
            'cert_compliance' => 'Certificate of Compliance.docx',
            'checklist_requirements' => 'Checklist of the Requirements.docx',
            'ched_report' => 'CHED Compliance Report.docx',
            'travel_matrix' => 'Matrix of Travel and Tour.docx',
            'faculty_in_charge' => 'Faculty-in-Charge.docx',
            'passenger_matrix' => 'Matrix of Passenger.docx',
        ];
        foreach ($documents as $key => $sourceFile) {
            $bytes = $zip->getFromName($expectedNames[$key]);
            $this->assertIsString($bytes);
            $download = $this->get(route('office.activities.templates.download', [
                'type' => $type, 'file' => $sourceFile,
            ]))->assertOk()->assertDownload($expectedNames[$key]);
            $this->assertSame(
                hash_file('sha256', $download->baseResponse->getFile()->getPathname()),
                hash('sha256', $bytes),
                "{$sourceFile} must retain its original content in the renamed archive.",
            );
        }
        $zip->close();
        $expected = array_values($expectedNames);
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
        ob_start();
        try {
            $response->baseResponse->sendContent();
        } finally {
            ob_end_clean();
        }
        $unlistedTemplate = $type === 'in_campus'
            ? 'Attachment I_ Plan of Activities.docx'
            : 'Format for list of personnel.docx';
        $this->get(route('office.activities.templates.download', [
            'type' => $type, 'file' => $unlistedTemplate,
        ]))->assertNotFound();
    }

    public function test_zero_budget_and_server_derived_title_reference_are_saved(): void
    {
        $title = 'Approved Sustainability Workshop';
        $payload = $this->payload('in_campus', $title);
        $option = app(ApprovedPlanOfActivitiesService::class)->resolve($this->organization->name, $payload['plan_activity_key']);
        $this->actingAs($this->offices['so'], 'office')->post(route('office.activities.store'), [
            'title' => 'Unapproved spoofed title', 'plan_reference' => 'Unapproved spoofed reference',
            'approved_budget' => '0',
        ] + $payload + ['attachments' => $this->checklistFiles(array_keys(self::documentChecklists()['in-campus'][1]))])
            ->assertRedirect()->assertSessionHasNoErrors();
        $activity = OrgActivity::where('organization_name', $this->organization->name)->sole();
        $this->assertSame($title, $activity->title);
        $this->assertEquals(0, $activity->approved_budget);
        $attachments = InCampusActivitySubmission::where('org_activity_id', $activity->id)->sole()->attachments;
        $this->assertSame($option['reference'], $attachments['plan_reference']);
        $this->assertSame($option['key'], $attachments['plan_activity_key']);
        $this->assertSame($option['document_id'], $attachments['plan_source_document_id']);
        $this->assertSame($option['document_version'], $attachments['plan_source_version']);
    }

    public function test_oversized_authoritative_title_returns_validation_without_saving_an_activity(): void
    {
        $title = str_repeat('É', 256);
        $payload = $this->payload('in_campus', $title);
        $this->actingAs($this->offices['so'], 'office')
            ->post(route('office.activities.store'), $payload + [
                'attachments' => $this->checklistFiles(array_keys(self::documentChecklists()['in-campus'][1])),
            ])
            ->assertSessionHasErrors('plan_activity_key');
        $this->assertFalse(OrgActivity::where('organization_name', $this->organization->name)->exists());
        $this->assertFalse(InCampusActivitySubmission::where('organization_name', $this->organization->name)->exists());
    }

    public function test_negative_budget_foreign_stale_and_tampered_plan_keys_save_nothing(): void
    {
        $title = 'Approved Budget Validation Workshop';
        $payload = $this->payload('in_campus', $title);
        $files = $this->checklistFiles(array_keys(self::documentChecklists()['in-campus'][1]));
        $this->actingAs($this->offices['so'], 'office')->post(route('office.activities.store'), ['approved_budget' => '-0.01'] + $payload + ['attachments' => $files])
            ->assertSessionHasErrors('approved_budget');
        $this->assertNothingSaved($title);
        $window = \App\Models\OrgRenewalWindow::latest('id')->firstOrFail();
        $foreign = $this->approvedActivityPlan('Another organization', ['Foreign Workshop'], $window);
        foreach ([$foreign['options'][0]['key'], str_repeat('f', 64), ''] as $key) {
            $this->post(route('office.activities.store'), ['plan_activity_key' => $key] + $payload + ['attachments' => $files])
                ->assertSessionHasErrors('plan_activity_key');
        }
        $document = \App\Models\OrgRenewalDocument::where('doc_key', 'plan_of_activities')
            ->whereHas('submission', fn ($query) => $query->where('organization_name', $this->organization->name))->sole();
        Storage::disk('local')->put($document->file_path, $this->activityPlanDocx([$title.' changed']));
        $this->post(route('office.activities.store'), $payload + ['attachments' => $files])
            ->assertSessionHasErrors('plan_activity_key');
        $this->approvedActivityPlan($this->organization->name, [$title]);
        $this->post(route('office.activities.store'), $payload + ['attachments' => $files])
            ->assertSessionHasErrors('plan_activity_key');
        $this->assertFalse(OrgActivity::where('organization_name', $this->organization->name)->exists());
    }

    public function test_real_table_rows_join_multiline_titles_keep_duplicates_and_reject_blank_templates(): void
    {
        $plan = $this->approvedActivityPlan($this->organization->name, ["Sustainability\nWorkshop", 'Sustainability Workshop']);
        $this->assertSame(['Sustainability Workshop', 'Sustainability Workshop'], array_column($plan['options'], 'title'));
        $this->assertNotSame($plan['options'][0]['key'], $plan['options'][1]['key']);
        Storage::disk('local')->put($plan['document']->file_path, file_get_contents(public_path('templates/renewal/Attachment I_ Plan of Activities.docx')));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ApprovedPlanOfActivitiesService::class)->options($this->organization->name);
    }

    public function test_pdf_plan_uses_actual_table_titles_and_a_textless_pdf_has_no_fallback(): void
    {
        $plan = $this->approvedActivityPlan($this->organization->name, ['Initial DOCX Activity']);
        $path = 'renewal-fixtures/'.$plan['submission']->id.'/Attachment I.pdf';
        Storage::disk('local')->put($path, $this->activityPlanPdf(["Sustainability\nWorkshop", 'Sustainability Workshop']));
        $plan['document']->update(['file_path' => $path, 'file_name' => 'Attachment I.pdf']);
        $options = app(ApprovedPlanOfActivitiesService::class)->options($this->organization->name);
        $this->assertSame(['Sustainability Workshop', 'Sustainability Workshop'], array_column($options, 'title'));
        $this->assertNotSame($options[0]['key'], $options[1]['key']);
        Storage::disk('local')->put($path, $this->activityPlanPdf([], false));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ApprovedPlanOfActivitiesService::class)->options($this->organization->name);
    }

    private function payload(string $type, string $title): array
    {
        $plan = $this->approvedActivityPlan($this->organization->name, [$title]);
        $this->planFiles = Storage::disk('local')->allFiles();
        return [
            'submission_action' => 'submit',
            'activity_type' => $type,
            'plan_activity_key' => $plan['options'][0]['key'],
            'location' => 'Campus',
            'approved_budget' => '1000.00',
            'starts_at' => now()->addWeek()->format('Y-m-d').' 09:00:00',
            'ends_at' => now()->addWeek()->format('Y-m-d').' 17:00:00',
        ];
    }

    /**
     * @param list<string> $keys
     * @return array<string, UploadedFile>
     */
    private function checklistFiles(array $keys): array
    {
        $files = [];
        foreach ($keys as $key) {
            $files[$key] = $this->pdf($key);
        }

        return $files;
    }

    private function pdf(string $key): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($key.'.pdf', "%PDF-1.4\n% {$key}\n");
    }

    private function assertStoredDocument(string $path): void
    {
        $disk = Storage::disk('local');
        $disk->assertExists($path);
        $this->assertTrue($disk->fileExists($path), "{$path} is not a stored file.");
        $this->assertGreaterThan(0, $disk->size($path), "{$path} is empty.");
    }

    private function assertNothingSaved(string $title): void
    {
        $this->assertFalse(OrgActivity::where('title', $title)->exists());
        $this->assertFalse(OrgActivity::where('organization_name', $this->organization->name)->exists());
        $this->assertFalse(InCampusActivitySubmission::where('organization_name', $this->organization->name)->exists());
        $this->assertEqualsCanonicalizing($this->planFiles, Storage::disk('local')->allFiles());
    }

    public static function documentChecklists(): array
    {
        return [
            'in-campus' => ['in_campus', [
                'checklist_template' => '0. Checklist Template.docx',
                'programme' => '2. Programme.docx',
                'project_proposal' => '3. Project Proposal.docx',
                'budget_proposal' => '4. Budget Proposal.docx',
                'medical_clearance' => '5. Medical Request Sample Letter.docx',
                'insurance' => '6. Insurance Request Sample Letter.docx',
                'resolution' => '7. Resolution of the Organization .docx',
                'faculty_in_charge' => '14. Faculty-In-Charge.docx',
                'sample_letter' => 'Sample Letter.docx',
                'wpcf' => 'Waste-Policy-Compliance-Form-2026 (1).docx',
            ]],
            'off-campus' => ['local_off_campus', [
                'course_activities' => '13. Course Activities (1).docx',
                'off_campus_req' => 'BatStateU-FO-REQ-09_Request-for-the-Conduct-of-Local-Off-Campus-Activities-Rev.-02 (1) (1).docx',
                'parents_consent' => 'BatStateU-FO-SOA-03_Parent_s Consent Form (Waiver)_Rev. 01.doc',
                'cert_compliance' => 'Certificate of Compliance (3).docx',
                'checklist_requirements' => 'Checklist of the Requirements.docx',
                'ched_report' => 'CHED Compliance Report (1).docx',
                'travel_matrix' => 'Copy of Matrix of Travel and Tour (1).docx',
                'faculty_in_charge' => 'Faculty-In-Charge.docx',
                'passenger_matrix' => 'FORMAT FOR MATRIX OF PASSENGER.docx',
            ]],
        ];
    }
}

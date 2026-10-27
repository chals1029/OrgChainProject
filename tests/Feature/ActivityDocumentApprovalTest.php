<?php

namespace Tests\Feature;

use App\Models\BudgetItem;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Services\ActivityRequirements;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ActivityDocumentApprovalTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $env = $this->readProjectEnv();
        foreach (['ORGCHAIN_TEST_DATABASE' => 'DB_DATABASE', 'ORGCHAIN_TEST_VOTING_DATABASE' => 'VOTING_DB_DATABASE'] as $test => $project) {
            if (! getenv($test) || getenv($test) === ($env[$project] ?? null)) {
                throw new \RuntimeException('Document approval tests require isolated databases.');
            }
        }
        $this->useLaragonDatabase();
        foreach (['mysql'] as $connection) DB::connection($connection)->beginTransaction();
        Carbon::setTestNow('2030-10-01 09:00:00');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach (['mysql'] as $connection) {
            while (DB::connection($connection)->transactionLevel() > 0) DB::connection($connection)->rollBack();
        }
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function incompletePackets(): array
    {
        $cases = [];
        foreach (['oso', 'sdo', 'ovcaa', 'oc'] as $role) {
            foreach (['no_packet', 'latest_packet_empty', 'file_deleted', 'file_empty', 'draft'] as $failure) {
                $cases[$role.' '.$failure] = [$role, $failure];
            }
        }
        return $cases;
    }

    #[DataProvider('incompletePackets')]
    public function test_every_review_desk_rejects_incomplete_current_evidence_without_side_effects(string $role, string $failure): void
    {
        $office = OfficeUser::create([
            'name' => 'Document Approval Reviewer', 'username' => Str::uuid()->toString(),
            'email' => Str::uuid().'@example.test', 'password' => Str::random(32),
            'office_role' => $role, 'office_title' => 'Document reviewer', 'is_active' => true,
        ]);
        $stage = $role.'_review';
        $activity = OrgActivity::create([
            'title' => 'Approval evidence boundary '.Str::uuid(), 'organization_name' => 'Evidence QA',
            'status' => 'draft', 'workflow_status' => $stage, 'activity_scope' => 'in_campus',
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2), 'approved_budget' => 0,
        ]);
        $submission = null;
        if ($failure !== 'no_packet') {
            $attachments = [];
            foreach (app(ActivityRequirements::class)->inCampusRequirements() as $requirement) {
                $path = 'evidence/'.$activity->id.'/'.$requirement['key'].'.pdf';
                Storage::disk('local')->put($path, "%PDF-1.4\n% Signed activity evidence\n%%EOF\n");
                $attachments[$requirement['key']] = ['path' => $path, 'name' => $requirement['key'].'.pdf'];
            }
            $submission = InCampusActivitySubmission::create([
                'org_activity_id' => $activity->id, 'organization_name' => $activity->organization_name,
                'status' => $failure === 'draft' ? 'draft' : 'submitted', 'workflow_status' => $stage,
                'activity_type' => 'in_campus', 'submitted_at' => $failure === 'draft' ? null : now(),
                'attachments' => $attachments,
            ]);
            if ($failure === 'latest_packet_empty') {
                $submission = InCampusActivitySubmission::create([
                    'org_activity_id' => $activity->id, 'organization_name' => $activity->organization_name,
                    'status' => 'submitted', 'workflow_status' => $stage, 'activity_type' => 'in_campus',
                    'submitted_at' => now(), 'attachments' => [],
                ]);
            } elseif ($failure === 'file_deleted') {
                Storage::disk('local')->delete($attachments['wpcf']['path']);
            } elseif ($failure === 'file_empty') {
                Storage::disk('local')->put($attachments['wpcf']['path'], '');
            }
        }
        $before = $submission?->attachments;
        $this->actingAs($office, 'office')->post('/office-desk/activities/'.$activity->id.'/advance', ['documents_reviewed' => '1'])
            ->assertRedirect()->assertSessionHasErrors('workflow');
        $this->assertSame($stage, $activity->refresh()->workflow_status);
        $this->assertNull($activity->approved_at);
        $this->assertFalse(BudgetItem::where('org_activity_id', $activity->id)->exists());
        $this->assertSame(0, DB::connection('mysql')->table('activity_workflow_events')->where('org_activity_id', $activity->id)->count());
        if ($submission) $this->assertEquals($before, $submission->refresh()->attachments);
    }
}

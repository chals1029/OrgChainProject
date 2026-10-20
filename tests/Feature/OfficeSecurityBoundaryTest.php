<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureOfficeAuthenticated;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeAnnouncement;
use App\Models\OfficeSetting;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;
use ZipArchive;

class OfficeSecurityBoundaryTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::beginTransaction();
        DB::connection('orgchain')->beginTransaction();
        Storage::fake('local');
        Storage::fake('public');
        OfficeSetting::forScope('oso')->update(['values' => OfficeSetting::defaults()]);
    }

    protected function tearDown(): void
    {
        DB::connection('orgchain')->rollBack();
        DB::rollBack();
        parent::tearDown();
    }

    private function organization(): StudentOrganization
    {
        return StudentOrganization::create([
            'name' => 'Security Organization '.Str::uuid(), 'college' => 'Security College',
            'academic_year' => '2026-2027', 'is_active' => true,
        ]);
    }

    private function officer(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        $identifier = 'security-'.Str::uuid();

        return OfficeUser::create([
            'name' => 'Security Officer', 'email' => $identifier.'@g.batstate-u.edu.ph',
            'username' => $identifier, 'password' => 'SecurityFixture@2026!',
            'office_role' => $role, 'student_organization_id' => $organization?->id,
            'office_title' => strtoupper($role).' Security Test Desk',
            'tosa_clearance' => match ($role) {
                'oso' => 'Level 3 Master',
                'ovcaa' => 'Level 1 Read-only',
                default => 'No Access',
            },
            'is_active' => true, 'must_change_password' => false,
        ]);
    }

    private function activity(StudentOrganization $organization): OrgActivity
    {
        return OrgActivity::create([
            'title' => 'Security Activity '.Str::uuid(), 'organization_name' => $organization->name,
            'college' => $organization->college, 'workflow_status' => 'oc_approved',
            'status' => 'upcoming', 'activity_scope' => 'in_campus',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'approved_budget' => 1000,
        ]);
    }

    private function receipt(OrgActivity $activity, string $bytes): ExpenseReceiptReview
    {
        $path = 'expense-receipts/security-'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, $bytes);

        return ExpenseReceiptReview::create([
            'org_activity_id' => $activity->id, 'activity_title' => $activity->title,
            'organization_name' => $activity->organization_name, 'item_name' => 'Security expense',
            'supplier' => 'Security supplier', 'category' => 'supplies', 'quantity' => 1,
            'unit_cost' => 12.34, 'expense_date' => now()->toDateString(),
            'receipt_path' => $path, 'receipt_name' => basename($path), 'receipt_disk' => 'local',
            'verification_status' => 'verified',
        ]);
    }

    private function package(string $url): array
    {
        $response = $this->get($url)->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        try {
            $files = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $files[$name] = $zip->getFromIndex($index);
            }

            return $files;
        } finally {
            $zip->close();
            unlink($path);
        }
    }

    public function test_notifications_follow_assigned_organization_and_review_stage_visibility(): void
    {
        $organization = $this->organization();
        $own = $this->activity($organization);
        $foreign = $this->activity($this->organization());
        $early = $this->activity($organization);
        $early->update(['workflow_status' => 'oso_review']);
        foreach ([$own, $foreign, $early] as $activity) {
            DB::connection('mysql')->table('activity_workflow_events')->insert([
                'org_activity_id' => $activity->id, 'role' => 'oso', 'from_status' => 'oso_review',
                'to_status' => 'sdo_review', 'created_at' => now()->subMinute(),
            ]);
        }
        $future = $this->activity($organization);
        DB::connection('mysql')->table('activity_workflow_events')->insert([
            'org_activity_id' => $future->id, 'role' => 'oc', 'from_status' => 'oc_review',
            'to_status' => 'oc_approved', 'created_at' => now()->addDay(),
        ]);
        $notifications = $this->actingAs($this->officer('so', $organization), 'office')
            ->get('/office-desk/calendar')->assertOk()->viewData('officeNotifications');
        $this->assertTrue($notifications->contains('url', route('office.activities', ['activity' => $own->id])));
        $this->assertFalse($notifications->contains('url', route('office.activities', ['activity' => $foreign->id])));
        $this->assertFalse($notifications->contains('url', route('office.activities', ['activity' => $future->id])));
        $downstream = $this->actingAs($this->officer('oc'), 'office')
            ->get('/office-desk/calendar')->assertOk()->viewData('officeNotifications');
        $this->assertTrue($downstream->contains('url', route('office.activities', ['activity' => $own->id])));
        $this->assertFalse($downstream->contains('url', route('office.activities', ['activity' => $early->id])));
    }

    public function test_unassigned_and_inactive_organization_so_cannot_enter_or_export(): void
    {
        $organization = $this->organization();
        $organization->update(['is_active' => false]);
        foreach ([$this->officer('so'), $this->officer('so', $organization)] as $officer) {
            $this->actingAs($officer, 'office')->get('/office-desk')->assertForbidden();
            $this->get(route('office.budget.receipts.package'))->assertForbidden();
        }
    }

    public function test_scope_helpers_and_export_reject_unassigned_or_inactive_organization_so_without_middleware(): void
    {
        $this->withoutMiddleware(EnsureOfficeAuthenticated::class);
        $organization = $this->organization();
        $organization->update(['is_active' => false]);
        foreach ([$this->officer('so'), $this->officer('so', $organization)] as $officer) {
            $this->actingAs($officer, 'office');
            foreach (['office.analytics.export', 'office.activities', 'office.budget', 'office.renewal', 'office.archive', 'office.budget.receipts.package'] as $route) {
                $this->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_so_receipt_zip_ignores_forged_organization_and_excludes_foreign_csv_and_bytes(): void
    {
        $own = $this->organization();
        $foreign = $this->organization();
        $ownActivity = $this->activity($own);
        $foreignActivity = $this->activity($foreign);
        $ownReceipt = $this->receipt($ownActivity, 'own original receipt');
        $foreignReceipt = $this->receipt($foreignActivity, 'foreign original receipt');
        $this->actingAs($this->officer('so', $own), 'office');
        $files = $this->package(route('office.budget.receipts.package', ['organization' => $foreign->name]));
        $this->assertStringContainsString($own->name, $files['expense-register.csv']);
        $this->assertStringNotContainsString($foreign->name, $files['expense-register.csv']);
        $this->assertContains('own original receipt', $files);
        $this->assertNotContains('foreign original receipt', $files);
        $this->assertStringContainsString($ownReceipt->receipt_name, $files['expense-register.csv']);
        $this->assertStringNotContainsString($foreignReceipt->receipt_name, $files['expense-register.csv']);
        $this->get(route('office.budget.receipts.package', ['activity_id' => $foreignActivity->id]))->assertForbidden();
        $filtered = $this->package(route('office.budget.receipts.package', ['activity_id' => $ownActivity->id]));
        $this->assertContains('own original receipt', $filtered);
        $foreignReceipt->update(['organization_name' => $own->name]);
        $inconsistent = $this->package(route('office.budget.receipts.package'));
        $this->assertNotContains('foreign original receipt', $inconsistent);
        $this->assertStringNotContainsString($foreignReceipt->receipt_name, $inconsistent['expense-register.csv']);
        $foreignReceipt->update(['organization_name' => $foreign->name]);

        $this->actingAs($this->officer('oso'), 'office');
        $global = $this->package(route('office.budget.receipts.package'));
        $this->assertStringContainsString($own->name, $global['expense-register.csv']);
        $this->assertStringContainsString($foreign->name, $global['expense-register.csv']);
        $this->assertContains('own original receipt', $global);
        $this->assertContains('foreign original receipt', $global);
    }

    public function test_private_activity_preview_respects_so_boundary_and_institution_review_scope(): void
    {
        $own = $this->organization();
        $foreign = $this->organization();
        $activity = $this->activity($foreign);
        $activity->update(['workflow_status' => 'sdo_review']);
        $path = 'in-campus-activities/'.$activity->id.'/private.pdf';
        Storage::disk('local')->put($path, 'private proposal original');
        $submission = InCampusActivitySubmission::create([
            'org_activity_id' => $activity->id, 'organization_name' => $foreign->name,
            'activity_type' => 'in_campus', 'status' => 'submitted',
            'attachments' => ['project_proposal' => ['path' => $path, 'name' => 'private.pdf']],
        ]);
        $url = route('office.activities.attachments.file', ['submission' => $submission, 'key' => 'project_proposal']);
        $this->actingAs($this->officer('so', $own), 'office')->get($url)->assertForbidden();
        // An inconsistent submission label must not override the activity's actual owner.
        $submission->update(['organization_name' => $own->name]);
        $this->get($url)->assertForbidden();
        $submission->update(['organization_name' => $foreign->name]);
        $response = $this->actingAs($this->officer('sdo'), 'office')->get($url)->assertOk();
        $this->assertSame('private proposal original', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->actingAs($this->officer('so', $foreign), 'office')->get($url)->assertOk();
        Storage::disk('local')->delete($path);
        Storage::disk('public')->put($path, 'public must not be a fallback');
        $this->get($url)->assertNotFound();
    }

    public function test_only_oso_can_publish_announcements_or_send_reminders(): void
    {
        $organization = $this->organization();
        $activity = $this->activity($organization);
        $count = OfficeAnnouncement::count();
        Mail::shouldReceive('raw')->never();
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->officer($role, $role === 'so' ? $organization : null), 'office');
            $this->post(route('office.updates.announcements.store'), [
                'type' => 'Advisory', 'body' => 'Security publication', 'priority' => 'normal',
            ])->assertForbidden();
            $this->post(route('office.oso.remind', $activity))->assertForbidden();
        }
        $this->assertSame($count, OfficeAnnouncement::count());
    }

    public function test_oso_reminder_targets_current_active_registered_representative(): void
    {
        $organization = $this->organization();
        $activity = $this->activity($organization);
        $outgoing = $this->officer('so', $organization);
        $outgoing->update(['is_active' => false]);
        $incoming = $this->officer('so', $organization);
        Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback) use ($incoming, $activity): bool {
            $message = new Message(new Email());
            $callback($message);

            return str_contains($body, $activity->title)
                && $message->getSymfonyMessage()->getTo()[0]->getAddress() === $incoming->email;
        });
        $this->actingAs($this->officer('oso'), 'office');
        $this->post(route('office.oso.remind', $activity))->assertRedirect()->assertSessionHas('success');
        $incoming->update(['is_active' => false]);
        $this->post(route('office.oso.remind', $activity))->assertRedirect()->assertSessionHas('error');
        $incoming->update(['is_active' => true]);
        $organization->update(['is_active' => false]);
        $this->post(route('office.oso.remind', $activity))->assertRedirect()->assertSessionHas('error');
        $this->post(route('office.updates.announcements.store'), [
            'type' => 'General Announcement', 'body' => 'Security publication', 'priority' => 'normal',
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();
        $this->assertDatabaseHas('office_announcements', ['body' => 'Security publication']);
    }

    public function test_analytics_csv_neutralizes_user_controlled_cells(): void
    {
        $organization = $this->organization();
        $activity = $this->activity($organization);
        $activity->update(['title' => '=SecurityFormula()', 'college' => '@SecurityCollege']);
        $this->actingAs($this->officer('so', $organization), 'office');
        $csv = $this->get(route('office.analytics.export'))->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", $csv)));
        $cells = array_merge(...$rows);
        $this->assertContains("'=SecurityFormula()", $cells);
        $this->assertContains("'@SecurityCollege", $cells);
        $this->assertNotContains('=SecurityFormula()', $cells);
        $this->assertNotContains('@SecurityCollege', $cells);
    }

    public function test_overlapping_template_downloads_use_separate_files_and_clean_up_after_send(): void
    {
        $this->actingAs($this->officer('oso'), 'office');
        $first = $this->get(route('office.activities.templates.download'))->assertOk()->baseResponse;
        $second = $this->get(route('office.activities.templates.download'))->assertOk()->baseResponse;
        $firstPath = $first->getFile()->getPathname();
        $secondPath = $second->getFile()->getPathname();
        try {
            $this->assertNotSame($firstPath, $secondPath);
            foreach ([$firstPath, $secondPath] as $path) {
                $zip = new ZipArchive();
                $this->assertTrue($zip->open($path));
                $this->assertGreaterThan(0, $zip->numFiles);
                $zip->close();
            }
            ob_start();
            try {
                $first->sendContent();
            } finally {
                ob_end_clean();
            }
            $this->assertFileDoesNotExist($firstPath);
            $this->assertFileExists($secondPath);
            ob_start();
            try {
                $second->sendContent();
            } finally {
                ob_end_clean();
            }
            $this->assertFileDoesNotExist($secondPath);
        } finally {
            foreach ([$firstPath, $secondPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_tosa_download_requires_role_clearance_and_applicant_owned_private_path(): void
    {
        $srCode = 'SEC-'.Str::random(12);
        $path = 'tosa/'.$srCode.'/application_form.docx';
        $bytes = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));
        Storage::disk('local')->put($path, $bytes);
        $applicant = TosaApplicant::create([
            'full_name' => 'Synthetic Security Applicant', 'sr_code' => $srCode,
            'email' => 'synthetic-security@g.batstate-u.edu.ph', 'college' => 'Security College',
            'program' => 'Test Program', 'year_level' => '4', 'academic_year' => '2026-2027',
            'status' => 'submitted', 'subsection' => 'pending',
            'requirements' => ['application_form' => ['path' => $path, 'name' => 'Signed Nomination.docx']],
        ]);
        $url = route('office.tosa.documents.download', ['applicant' => $applicant, 'key' => 'application_form']);
        $this->get($url)->assertRedirect(route('office.login'));
        $oso = $this->officer('oso');
        $oso->update(['tosa_clearance' => 'No Access']);
        $this->actingAs($oso, 'office');
        $response = $this->get($url)->assertOk()->assertDownload('Signed Nomination.docx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        foreach (['draft', 'pending'] as $status) {
            $applicant->update(['status' => $status]);
            $this->get($url)->assertNotFound();
            $this->get($url.'?download=1')->assertNotFound();
            $this->postJson(route('office.tosa.subsection', $applicant), [
                'subsection' => 'accepted', 'gwa' => 1.00, 'gwa_verified' => true,
            ])->assertNotFound();
            $this->assertSame($status, $applicant->refresh()->status);
            $this->assertSame('pending', $applicant->subsection);
            $this->assertNull($applicant->gwa);
            $this->assertFalse($applicant->gwa_verified);
        }
        $applicant->update(['status' => 'submitted']);
        Storage::disk('local')->delete($path);
        Storage::disk('public')->put($path, 'public must not be a fallback');
        $this->get($url)->assertNotFound();
        Storage::disk('local')->put($path, $bytes);
        $applicant->update(['requirements' => ['application_form' => ['path' => 'tosa/another-student/application_form.docx', 'name' => 'Signed Nomination.docx']]]);
        Storage::disk('local')->put('tosa/another-student/application_form.docx', $bytes);
        $this->get($url)->assertNotFound();
        $applicant->update(['requirements' => ['application_form' => ['path' => $path, 'name' => 'Signed Nomination.docx']]]);
        $download = $this->get($url.'?download=1')->assertOk()->assertDownload('Signed Nomination.docx');
        $this->assertSame($bytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
        $ovcaa = $this->officer('ovcaa');
        foreach (['Level 1 Read-only', 'Level 2 Evaluator'] as $clearance) {
            $ovcaa->update(['tosa_clearance' => $clearance]);
            $response = $this->actingAs($ovcaa, 'office')->get($url)->assertOk();
            $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        }
        $ovcaa->update(['tosa_clearance' => 'No Access']);
        $this->get($url)->assertForbidden();
        foreach (['so', 'sdo', 'oc'] as $role) {
            $organization = $role === 'so' ? $this->organization() : null;
            $this->actingAs($this->officer($role, $organization), 'office')->get($url)->assertForbidden();
        }
        $oso->update(['is_active' => false]);
        $this->actingAs($oso, 'office')->get($url)->assertRedirect(route('office.login'));
    }
}

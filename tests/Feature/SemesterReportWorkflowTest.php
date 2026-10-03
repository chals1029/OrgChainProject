<?php

namespace Tests\Feature;

use App\Models\ArchiveFolder;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\OrgActivity;
use App\Services\OrgWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class SemesterReportWorkflowTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_new_so_activity_submission_starts_at_oso_and_resubmission_skips_college_review(): void
    {
        $activity = OrgActivity::query()->create([
            'title' => 'Direct OSO Workflow '.uniqid(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'status' => 'draft',
            'workflow_status' => 'created',
            'location' => 'Campus',
            'approved_budget' => 1000,
        ]);

        try {
            $workflow = app(OrgWorkflowService::class);

            $workflow->advance($activity, 'so');
            $this->assertSame('oso_review', $activity->refresh()->workflow_status);
            $this->assertSame('oso_review', $workflow->nextStatus('returned', 'so'));
        } finally {
            $activity->delete();
        }
    }

    public function test_so_submits_ar_and_fr_as_one_package_and_oso_archives_it(): void
    {
        Storage::fake('public');
        $organization = 'Workflow Test Organization '.uniqid();
        $period = [
            'organization_name' => $organization,
            'semester' => '1st Semester',
            'academic_year' => '2025-2026',
        ];

        try {
            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/reports/ar/documents', $period + [
                    'name' => 'Accomplishment Report',
                    'document' => UploadedFile::fake()->create('accomplishment.pdf', 10, 'application/pdf'),
                ])
                ->assertRedirect();

            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/reports/fr/documents', $period + [
                    'name' => 'Financial Report',
                    'document' => UploadedFile::fake()->create('financial.pdf', 10, 'application/pdf'),
                ])
                ->assertRedirect();

            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/reports/semester/submit', $period + ['return_type' => 'ar'])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('org_report_statuses', [
                'report_type' => 'ar',
                'organization_name' => $organization,
                'status' => 'oso_review',
            ]);
            $this->assertDatabaseHas('org_report_statuses', [
                'report_type' => 'fr',
                'organization_name' => $organization,
                'status' => 'oso_review',
            ]);

            $stagedDocument = OrgReportDocument::query()
                ->where('organization_name', $organization)
                ->firstOrFail();
            foreach (['sdo', 'ovcaa'] as $restrictedRole) {
                $this->actingAs($this->ensureOfficeUser($restrictedRole), 'office')
                    ->get('/office-desk/reports/documents/'.$stagedDocument->id.'/view')
                    ->assertForbidden();
            }

            $this->actingAs($this->ensureOfficeUser('sdo'), 'office')
                ->post('/office-desk/reports/semester/review', $period + [
                    'decision' => 'accept',
                ])
                ->assertForbidden();

            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/accomplishment-report?organization='.urlencode($organization).'&semester=1st%20Semester&academic_year=2025-2026')
                ->assertOk()
                ->assertSee('OSO review queue', false)
                ->assertSee($organization, false);

            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->get('/office-desk/accomplishment-report?organization='.urlencode($organization).'&semester=1st%20Semester&academic_year=2025-2026')
                ->assertOk()
                ->assertSee('Package locked while waiting for OSO.', false)
                ->assertSee('OSO has not opened the submitted AR + FR files yet.', false)
                ->assertDontSee('Stage AR document', false);

            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->get('/office-desk/reports/documents/'.$stagedDocument->id.'/view')
                ->assertOk();

            $this->assertDatabaseHas('org_report_statuses', [
                'report_type' => 'ar',
                'organization_name' => $organization,
                'opened_by' => $this->ensureOfficeUser('oso')->id,
            ]);

            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->get('/office-desk/accomplishment-report?organization='.urlencode($organization).'&semester=1st%20Semester&academic_year=2025-2026')
                ->assertOk()
                ->assertSee('OSO opened the submitted AR + FR files on', false)
                ->assertDontSee('Stage AR document', false);

            $this->actingAs($this->ensureOfficeUser('oso'), 'office')
                ->post('/office-desk/reports/semester/review', $period + [
                    'decision' => 'accept',
                    'notes' => 'Accepted for the semester archive.',
                ])
                ->assertRedirect(route('office.archive'))
                ->assertSessionHas('success');

            $this->assertSame(2, OrgReportStatus::query()
                ->where('organization_name', $organization)
                ->where('status', 'archived')
                ->count());
            $this->assertSame(2, OrgReportDocument::query()
                ->where('organization_name', $organization)
                ->count());
            $this->assertSame(1, ArchiveFolder::query()
                ->where('organization_name', $organization)
                ->where('semester', '1st Semester')
                ->count());
        } finally {
            $statusIds = OrgReportStatus::query()
                ->where('organization_name', $organization)
                ->pluck('id');
            OrgReportStatus::query()->whereIn('id', $statusIds)->delete();
            ArchiveFolder::query()->where('organization_name', $organization)->delete();
        }
    }

    public function test_so_cannot_submit_until_both_report_documents_are_staged(): void
    {
        Storage::fake('public');
        $organization = 'Incomplete Report Organization '.uniqid();
        $period = [
            'organization_name' => $organization,
            'semester' => 'Midyear',
            'academic_year' => '2025-2026',
        ];

        try {
            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/reports/ar/documents', $period + [
                    'document' => UploadedFile::fake()->create('accomplishment.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                ])
                ->assertRedirect();

            $this->actingAs($this->ensureOfficeUser('so'), 'office')
                ->post('/office-desk/reports/semester/submit', $period + ['return_type' => 'ar'])
                ->assertRedirect()
                ->assertSessionHasErrors('report');

            $this->assertDatabaseHas('org_report_statuses', [
                'report_type' => 'ar',
                'organization_name' => $organization,
                'status' => 'draft',
            ]);
        } finally {
            OrgReportStatus::query()->where('organization_name', $organization)->delete();
        }
    }
}

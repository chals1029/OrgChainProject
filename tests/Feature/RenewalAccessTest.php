<?php

namespace Tests\Feature;

use App\Models\OrgActivity;
use App\Models\OrgRenewalWindow;
use App\Models\StudentOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class RenewalAccessTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        $this->ensureOfficeUser('so');
        $this->ensureOfficeUser('oso');
        $this->ensureOfficeUser('sdo');
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    public function test_guest_is_redirected_from_renewal(): void
    {
        $this->get('/office-desk/renewal')->assertRedirect();
    }

    public function test_guest_cannot_save_renewal_information(): void
    {
        $this->postJson(route('office.renewal.information.save'), [
            'window_id' => 1,
            'organization_name' => 'Unassigned organization',
            'adviser_name' => 'Adviser',
            'dean_name' => 'Dean',
        ])->assertUnauthorized();
    }

    public function test_only_so_can_save_renewal_information(): void
    {
        foreach (['oso', 'sdo'] as $role) {
            $this->actingAs($this->ensureOfficeUser($role), 'office')
                ->postJson(route('office.renewal.information.save'), [
                    'window_id' => 1,
                    'organization_name' => 'Unassigned organization',
                    'adviser_name' => 'Adviser',
                    'dean_name' => 'Dean',
                ])->assertForbidden();
        }
    }

    public function test_sdo_receives_forbidden_on_renewal(): void
    {
        $user = $this->ensureOfficeUser('sdo');

        $this->actingAs($user, 'office')
            ->get('/office-desk/renewal')
            ->assertForbidden();
    }

    public function test_so_sees_locked_renewal_when_window_closed(): void
    {
        $user = $this->ensureOfficeUser('so');

        OrgRenewalWindow::query()->delete();
        OrgRenewalWindow::query()->create([
            'academic_year' => '2026-2027',
            'semester' => 'Annual',
            'is_open' => false,
            'required_docs' => OrgRenewalWindow::defaultRequiredDocs(),
        ]);

        $response = $this->actingAs($user, 'office')->get('/office-desk/renewal');
        $response->assertOk();
        $response->assertSee('Renewal is locked', false);
    }

    public function test_oso_can_open_renewal_window(): void
    {
        $user = $this->ensureOfficeUser('oso');

        OrgRenewalWindow::query()->delete();

        $this->actingAs($user, 'office')
            ->post('/office-desk/renewal/window', [
                'academic_year' => '2026-2027',
                'semester' => 'Annual',
                'instructions' => 'Automated test open.',
            ])
            ->assertRedirect(route('office.renewal'));

        $window = OrgRenewalWindow::query()->latest('id')->firstOrFail();
        $this->assertFalse($window->is_open);
        $this->post(route('office.renewal.window'), ['toggle_only' => 1, 'window_id' => $window->id, 'is_open' => 1])
            ->assertSessionHasNoErrors()->assertRedirect(route('office.renewal'));
        $this->assertTrue((bool) OrgRenewalWindow::query()->latest('id')->value('is_open'));
    }



    public function test_oso_can_update_organization_qualification_and_status(): void
    {
        $user = $this->ensureOfficeUser('oso');
        $org = \App\Models\StudentOrganization::firstOrCreate(
            ['name' => 'Test Council Alpha'],
            [
                'slug' => 'test-council-alpha',
                'college' => 'College of Informatics and Computing Sciences',
                'is_active' => true,
                'is_qualified_for_renewal' => true,
            ]
        );

        $response = $this->actingAs($user, 'office')
            ->post("/office-desk/renewal/organizations/{$org->id}/status", [
                'is_active' => '0',
                'is_qualified_for_renewal' => '0',
                'disqualification_reason' => 'Unliquidated financial report and dormant roster.',
            ]);

        $response->assertRedirect(route('office.renewal'));

        $org->refresh();
        $this->assertFalse((bool) $org->is_active);
        $this->assertFalse((bool) $org->is_qualified_for_renewal);
        $this->assertSame('Unliquidated financial report and dormant roster.', $org->disqualification_reason);
    }

    public function test_disqualified_organization_is_blocked_from_submitting_renewal_packet(): void
    {
        $soUser = $this->ensureOfficeUser('so');
        $targetOrgName = 'College of Informatics and Computing Sciences Student Council (CICS-SC)';

        $org = \App\Models\StudentOrganization::firstOrCreate(
            ['name' => $targetOrgName],
            ['slug' => 'cics-sc', 'is_active' => true, 'is_qualified_for_renewal' => true]
        );

        // Disqualify the org
        $org->update([
            'is_qualified_for_renewal' => false,
            'disqualification_reason' => 'Unliquidated FR clearance required.',
        ]);

        OrgRenewalWindow::query()->delete();
        $window = OrgRenewalWindow::query()->create([
            'academic_year' => '2026-2027',
            'semester' => 'Annual',
            'is_open' => true,
            'required_docs' => OrgRenewalWindow::defaultRequiredDocs(),
        ]);

        $response = $this->actingAs($soUser, 'office')
            ->post('/office-desk/renewal/submit', [
                'window_id' => $window->id,
                'organization_name' => $targetOrgName,
                'adviser_name' => 'Dr. Test Adviser',
                'dean_name' => 'Dr. Test Dean',
                'action' => 'submit',
            ]);

        $response->assertSessionHasErrors('renewal');

        // Re-qualify the org so future tests stay pristine
        $org->update([
            'is_active' => true,
            'is_qualified_for_renewal' => true,
            'disqualification_reason' => null,
        ]);
    }

    public function test_oso_activity_recommendations_count_only_matching_completed_final_approved_activities(): void
    {
        $suffix = (string) Str::uuid();
        $two = StudentOrganization::create(['name' => 'Two '.$suffix, 'is_qualified_for_renewal' => true]);
        $zero = StudentOrganization::create(['name' => 'Zero '.$suffix, 'is_qualified_for_renewal' => true]);
        $three = StudentOrganization::create(['name' => 'Three '.$suffix, 'is_qualified_for_renewal' => false]);
        OrgRenewalWindow::create(['academic_year' => '2026-2027', 'semester' => 'Annual', 'is_open' => false]);

        $activity = static function (string $organization, string $date, string $status = 'completed', string $workflow = 'oc_approved'): void {
            OrgActivity::create(['title' => 'Recommendation test', 'organization_name' => $organization,
                'starts_at' => $date, 'status' => $status, 'workflow_status' => $workflow]);
        };
        $activity($two->name, '2025-08-01 00:00:00');
        $activity($two->name, '2026-07-31 23:59:59');
        $activity($two->name, '2025-07-31 23:59:59');
        $activity($two->name, '2026-08-01 00:00:00');
        $activity($two->name, '2026-01-01', 'ongoing');
        $activity($two->name, '2026-01-01', 'completed', 'oc_review');
        $activity(strtoupper($two->name), '2026-01-01');
        $activity('Other '.$suffix, '2026-01-01');
        foreach (['2025-09-01', '2026-01-01', '2026-07-01'] as $date) {
            $activity($three->name, $date);
        }

        $response = $this->actingAs($this->ensureOfficeUser('oso'), 'office')->get('/office-desk/renewal')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        foreach ([
            [$two->name, '2 activities', 'Below activity requirement — OSO review recommended'],
            [$three->name, '3 activities', 'Meets activity requirement'],
        ] as [$name, $count, $label]) {
            $matchingRows = $xpath->query('//tr[.//strong[@class="or-name" and text()="'.$name.'"]]');
            $this->assertSame(1, $matchingRows->length);
            $text = $matchingRows->item(0)->textContent;
            $this->assertStringContainsString('AY 2025-2026 · '.$count, $text);
            $this->assertStringContainsString($label, $text);
        }
        $rows = $response->viewData('allOrganizations')->keyBy('id');
        $this->assertSame('2025-2026', $rows[$two->id]['activity_recommendation_year']);
        $this->assertSame(2, $rows[$two->id]['activity_recommendation_count']);
        $this->assertTrue($rows[$two->id]['can_file_renewal']);
        $this->assertSame(3, $rows[$three->id]['activity_recommendation_count']);
        $this->assertFalse($rows[$three->id]['can_file_renewal']);
        $this->assertTrue($two->fresh()->is_qualified_for_renewal);
        $this->assertSame(0, $rows[$zero->id]['activity_recommendation_count']);
        $this->assertFalse($three->fresh()->is_qualified_for_renewal);
    }

    public function test_activity_recommendation_is_unavailable_without_a_valid_latest_window(): void
    {
        $org = StudentOrganization::create(['name' => 'Unavailable '.Str::uuid(), 'is_qualified_for_renewal' => true]);
        OrgRenewalWindow::query()->delete();
        $this->actingAs($this->ensureOfficeUser('oso'), 'office');

        foreach ([null, 'invalid', '2026-2028'] as $year) {
            if ($year !== null) {
                OrgRenewalWindow::create(['academic_year' => $year, 'semester' => 'Annual', 'is_open' => false]);
            }
            $row = $this->get('/office-desk/renewal')->assertOk()->viewData('allOrganizations')->firstWhere('id', $org->id);
            if ($year === null) {
                OrgRenewalWindow::create(['academic_year' => '2026-2027', 'semester' => 'Annual', 'is_open' => false]);
            }
            $this->assertNull($row['activity_recommendation_year']);
            $this->assertNull($row['activity_recommendation_count']);
            $this->assertTrue($row['can_file_renewal']);
        }
    }
}


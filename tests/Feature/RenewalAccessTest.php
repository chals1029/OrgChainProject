<?php

namespace Tests\Feature;

use App\Models\OrgRenewalWindow;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class RenewalAccessTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('so');
        $this->ensureOfficeUser('oso');
        $this->ensureOfficeUser('sdo');
    }

    public function test_guest_is_redirected_from_renewal(): void
    {
        $this->get('/office-desk/renewal')->assertRedirect();
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
                'is_open' => '1',
                'instructions' => 'Automated test open.',
            ])
            ->assertRedirect(route('office.renewal'));

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
}


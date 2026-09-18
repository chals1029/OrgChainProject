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
}

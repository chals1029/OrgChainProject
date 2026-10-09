<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class SystemAdminAccessTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_guest_is_redirected_from_system_maintenance_console(): void
    {
        $this->get('/system-admin')->assertRedirect(route('system-admin.login'));
    }

    public function test_system_admin_login_page_renders(): void
    {
        $this->get(route('system-admin.login'))
            ->assertOk()
            ->assertSee('System Maintenance Admin', false)
            ->assertSee('Open maintenance console', false);
    }

    public function test_system_admin_can_login_and_open_dashboard(): void
    {
        $admin = $this->ensureSystemAdmin();

        $this->post(route('system-admin.login'), [
            'email' => $admin->email,
            'password' => 'FixtureSystemAdminOnly@2026!',
        ])->assertRedirect(route('system-admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'system_admin');

        $this->get(route('system-admin.dashboard'))
            ->assertOk()
            ->assertSee('System overview', false)
            ->assertSee('Platform health', false)
            ->assertSee('Office accounts', false)
            ->assertSee('Recent maintenance activity', false);
    }

    public function test_office_user_cannot_use_system_admin_session(): void
    {
        $office = $this->ensureOfficeUser('oso');

        $this->actingAs($office, 'office')
            ->get('/system-admin')
            ->assertRedirect(route('system-admin.login'));
    }

    public function test_system_admin_can_toggle_an_office_account(): void
    {
        $admin = $this->ensureSystemAdmin();
        $office = $this->ensureOfficeUser('sdo');

        $this->actingAs($admin, 'system_admin')
            ->patch(route('system-admin.office-users.status', $office), ['is_active' => false])
            ->assertRedirect();

        $this->assertFalse((bool) $office->refresh()->is_active);

        $this->actingAs($admin, 'system_admin')
            ->patch(route('system-admin.office-users.status', $office), ['is_active' => true])
            ->assertRedirect();

        $this->assertTrue((bool) $office->refresh()->is_active);
        $this->assertDatabaseHas('system_admin_audit_logs', [
            'event' => 'office_account_status_changed',
            'target' => 'office_user:'.$office->id,
        ]);
    }
}

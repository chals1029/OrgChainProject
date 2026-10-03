<?php

namespace Tests\Feature;

use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OsoSettingsTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('so');
        $this->ensureOfficeUser('oso');
        $this->ensureOfficeUser('sdo');
        $this->ensureOfficeUser('ovcaa');
    }

    public function test_oso_desk_renders_backend_settings_payload(): void
    {
        $user = $this->ensureOfficeUser('oso');

        $this->actingAs($user, 'office')
            ->get('/office-desk')
            ->assertOk()
            ->assertSee('System &amp; Office Settings', false)
            ->assertSee('OrgChain Student Organizations Portal', false);
    }

    public function test_non_oso_can_use_personal_settings_but_cannot_update_institutional_settings(): void
    {
        $user = $this->ensureOfficeUser('sdo');

        $this->actingAs($user, 'office')
            ->get('/office-desk')
            ->assertOk()
            ->assertSee('Account Settings', false)
            ->assertSee('Security &amp; Access Controls', false)
            ->assertDontSee('System &amp; Office Identification', false)
            ->assertDontSee('Two-Factor Authentication', false)
            ->assertDontSee('Strict IP Whitelist', false)
            ->assertDontSee('Biometric / WebAuthn', false);

        $this->actingAs($user, 'office')
            ->postJson('/office-desk/settings', [
                'section' => 'security',
                'values' => [
                    'session_timeout' => 30,
                    'auto_lock_interval' => 10,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('settings.security.session_timeout', 30);

        $this->actingAs($user, 'office')
            ->postJson('/office-desk/settings', [
                'section' => 'general',
                'values' => [
                    'system_name' => 'Not allowed',
                ],
            ])
            ->assertForbidden();
    }

    public function test_oso_settings_validate_the_requested_section(): void
    {
        $user = $this->ensureOfficeUser('oso');

        $this->actingAs($user, 'office')
            ->postJson('/office-desk/settings', [
                'section' => 'not-a-settings-section',
                'values' => [],
            ])
            ->assertUnprocessable();
    }

    public function test_oso_can_download_a_signed_snapshot(): void
    {
        $user = $this->ensureOfficeUser('oso');

        $this->actingAs($user, 'office')
            ->get('/office-desk/settings/snapshot')
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=utf-8');
    }

    public function test_non_oso_cannot_download_oso_snapshot(): void
    {
        $user = $this->ensureOfficeUser('ovcaa');

        $this->actingAs($user, 'office')
            ->get('/office-desk/settings/snapshot')
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\OfficeSetting;
use App\Models\OfficeTemplate;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OsoSettingsTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::beginTransaction();
        DB::connection('orgchain')->beginTransaction();
        OfficeSetting::forScope('oso')->update(['values' => OfficeSetting::defaults()]);
    }

    protected function tearDown(): void
    {
        DB::connection('orgchain')->rollBack();
        DB::rollBack();
        parent::tearDown();
    }

    private function officer(string $role): OfficeUser
    {
        $identifier = 'settings-test-'.Str::uuid();

        return OfficeUser::create([
            'name' => strtoupper($role).' Settings Test',
            'email' => $identifier.'@g.batstate-u.edu.ph',
            'username' => $identifier,
            'password' => 'SettingsTest@2026!',
            'office_role' => $role,
            'office_title' => 'Settings Test Desk',
            'tosa_clearance' => match ($role) {
                'oso' => 'Level 3 Master',
                'ovcaa' => 'Level 1 Read-only',
                default => 'No Access',
            },
            'is_active' => true,
        ]);
    }

    private function configureSecurity(array $security): void
    {
        $values = OfficeSetting::defaults();
        $values['security'] = array_replace($values['security'], $security);
        OfficeSetting::forScope('oso')->update(['values' => $values]);
    }

    private function applicant(): TosaApplicant
    {
        return TosaApplicant::create([
            'full_name' => 'Settings Applicant '.Str::uuid(),
            'sr_code' => 'SET-'.Str::random(12),
            'email' => 'settings-applicant@g.batstate-u.edu.ph',
            'college' => 'Settings Test College',
            'program' => 'Settings Test Program',
            'year_level' => '4',
            'academic_year' => '2026-2027',
            'subsection' => 'pending',
            'status' => 'pending',
            'requirements' => [],
        ]);
    }

    private function userPayload(string $role = 'oso', string $clearance = 'No Access'): array
    {
        return [
            'name' => 'Created Settings Officer',
            'email' => 'settings-created-'.Str::uuid().'@g.batstate-u.edu.ph',
            'office_role' => $role,
            'password' => 'NewOfficer@2026!',
            'password_confirmation' => 'NewOfficer@2026!',
            'tosa_clearance' => $clearance,
        ];
    }

    public function test_non_oso_can_update_own_profile_without_creating_personal_settings(): void
    {
        $user = $this->officer('so');

        $this->actingAs($user, 'office')
            ->postJson('/office-desk/settings/account', [
                'name' => 'Organization Treasurer',
                'email' => $user->email,
                'office_title' => 'Treasurer',
                'employee_id' => 'SO-TEST-1',
            ])
            ->assertOk();

        $user->refresh();
        $this->assertSame('Organization Treasurer', $user->name);
        $this->assertSame('Treasurer', $user->office_title);
        $this->assertSame('SO-TEST-1', $user->employee_id);
        $this->assertDatabaseMissing('office_settings', ['scope' => 'office-user-'.$user->id]);
    }

    public function test_non_oso_cannot_update_institutional_settings(): void
    {
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $user = $this->officer($role);
            foreach (['general', 'security'] as $section) {
                $this->actingAs($user, 'office')
                    ->postJson('/office-desk/settings', [
                        'section' => $section,
                        'values' => ['session_timeout' => 30, 'tosa_gate' => false],
                    ])
                    ->assertForbidden();
            }
        }
    }

    public function test_removed_settings_sections_cannot_be_saved(): void
    {
        $user = $this->officer('oso');

        foreach (['notifications', 'preferences', 'records'] as $section) {
            $this->actingAs($user, 'office')
                ->postJson('/office-desk/settings', ['section' => $section, 'values' => []])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('section');
        }
    }

    public function test_oso_can_save_tosa_gate_and_unlock_duration_without_changing_its_pin(): void
    {
        $user = $this->officer('oso');
        $pinHash = OfficeSetting::valuesFor('oso')['security']['tosa_pin_hash'];

        $this->actingAs($user, 'office')
            ->postJson('/office-desk/settings', [
                'section' => 'security',
                'values' => ['session_timeout' => 30, 'tosa_gate' => false],
            ])
            ->assertOk()
            ->assertJsonPath('settings.security.session_timeout', 30)
            ->assertJsonPath('settings.security.tosa_gate', false)
            ->assertJsonMissingPath('settings.security.tosa_pin_hash');

        $this->assertSame($pinHash, OfficeSetting::valuesFor('oso')['security']['tosa_pin_hash']);
    }

    public function test_oso_settings_validate_the_requested_section(): void
    {
        $this->actingAs($this->officer('oso'), 'office')
            ->postJson('/office-desk/settings', ['section' => 'not-a-settings-section', 'values' => []])
            ->assertUnprocessable();
    }

    public function test_oso_can_download_a_checksum_snapshot_without_sensitive_hashes(): void
    {
        $officer = $this->officer('oso');
        $this->actingAs($officer, 'office')
            ->postJson('/office-desk/settings/pin/tosa', ['pin' => '4826'])
            ->assertOk();

        $response = $this->get('/office-desk/settings/snapshot')
            ->assertOk()
            ->assertHeader('content-type', 'application/json; charset=utf-8');
        $content = $response->streamedContent();
        $snapshot = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $checksum = $snapshot['sha256'];
        unset($snapshot['sha256']);

        $this->assertSame(hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), $checksum);
        $assertNoCredentials = function (array $data) use (&$assertNoCredentials): void {
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $this->assertNotSame('password', $key);
                    $this->assertDoesNotMatchRegularExpression('/(?:^|_)(?:hash|token)(?:_|$)/i', $key);
                }
                if (is_array($value)) {
                    $assertNoCredentials($value);
                } elseif (is_string($value)) {
                    $this->assertFalse(Hash::isHashed($value), 'The snapshot must not contain credential hashes.');
                }
            }
        };
        $assertNoCredentials($snapshot);
        $this->assertStringNotContainsString($officer->password, $content);
        $this->assertTrue($snapshot['settings']['security']['tosa_pin_configured']);
    }

    public function test_non_oso_cannot_download_oso_snapshot(): void
    {
        $this->actingAs($this->officer('ovcaa'), 'office')
            ->get('/office-desk/settings/snapshot')
            ->assertForbidden();
    }

    public function test_general_settings_persist_and_branding_is_shared_with_other_desks(): void
    {
        $values = [
            'system_name' => 'Settings System Fixture',
            'office_name' => 'Settings Office Fixture',
            'university_name' => 'Settings University Fixture',
            'campus_unit' => 'Settings Campus',
            'contact_email' => 'settings-contact@g.batstate-u.edu.ph',
            'contact_phone' => '123456789',
            'contact_location' => 'Settings Location',
        ];
        $this->actingAs($this->officer('oso'), 'office')
            ->postJson('/office-desk/settings', ['section' => 'general', 'values' => $values])
            ->assertOk();
        foreach ($values as $field => $value) {
            $this->assertSame($value, OfficeSetting::valuesFor('oso')['general'][$field]);
        }
        $this->actingAs($this->officer('ovcaa'), 'office')
            ->get('/office-desk/tosa')
            ->assertOk()
            ->assertViewHas('officeSettings', fn ($settings) => $settings['general']['system_name'] === $values['system_name']);
    }

    public function test_invalid_settings_and_account_credentials_leave_records_unchanged(): void
    {
        $officer = $this->officer('oso');
        $settings = OfficeSetting::valuesFor('oso');
        $this->actingAs($officer, 'office')
            ->postJson('/office-desk/settings', [
                'section' => 'security',
                'values' => ['session_timeout' => -1, 'tosa_gate' => false],
            ])
            ->assertUnprocessable();
        $this->assertSame($settings, OfficeSetting::valuesFor('oso'));
        foreach ([
            ['current_password' => 'wrong-password', 'new_password' => 'ChangedPassword@2026!', 'new_password_confirmation' => 'ChangedPassword@2026!'],
            ['current_password' => 'SettingsTest@2026!', 'new_password' => 'ChangedPassword@2026!', 'new_password_confirmation' => 'Mismatch@2026!'],
        ] as $payload) {
            $this->postJson('/office-desk/settings/password', $payload)->assertUnprocessable();
            $this->assertTrue(Hash::check('SettingsTest@2026!', $officer->refresh()->password));
        }
        $this->postJson('/office-desk/settings/account', [
            'name' => 'Must Not Save', 'email' => 'outside@example.com', 'office_title' => 'Must Not Save',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame('OSO Settings Test', $officer->refresh()->name);
    }

    public function test_password_change_is_hashed_and_profile_email_is_normalized(): void
    {
        $officer = $this->officer('oso');
        $this->actingAs($officer, 'office')->postJson('/office-desk/settings/password', [
            'current_password' => 'SettingsTest@2026!',
            'new_password' => 'ChangedPassword@2026!',
            'new_password_confirmation' => 'ChangedPassword@2026!',
        ])->assertOk();
        $this->assertTrue(Hash::check('ChangedPassword@2026!', $officer->refresh()->password));
        $this->postJson('/office-desk/settings/account', [
            'name' => 'Updated Settings Officer', 'email' => strtoupper($officer->email),
            'office_title' => 'Updated Title', 'employee_id' => 'SET-123',
        ])->assertOk()->assertJsonPath('user.email', strtolower($officer->email));
        $this->assertSame('Updated Settings Officer', $officer->refresh()->name);
    }

    public function test_new_officer_can_login_and_invalid_credentials_or_clearance_never_create_users(): void
    {
        $this->actingAs($this->officer('oso'), 'office');
        $before = OfficeUser::count();
        $invalid = $this->userPayload();
        $invalid['password_confirmation'] = 'Mismatch@2026!';
        $this->postJson('/office-desk/settings/users', $invalid)
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $invalid = $this->userPayload();
        $invalid['email'] = 'settings@example.com';
        $this->postJson('/office-desk/settings/users', $invalid)
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        foreach ([['sdo', 'Level 1 Read-only'], ['oc', 'Level 3 Master'], ['ovcaa', 'Level 3 Master']] as [$role, $clearance]) {
            $this->postJson('/office-desk/settings/users', $this->userPayload($role, $clearance))
                ->assertUnprocessable()->assertJsonValidationErrors('tosa_clearance');
        }
        $this->assertSame($before, OfficeUser::count());
        $payload = $this->userPayload('ovcaa', 'Level 2 Evaluator');
        $response = $this->postJson('/office-desk/settings/users', $payload)
            ->assertCreated()->assertJsonPath('user.office_role', 'ovcaa');
        $created = OfficeUser::findOrFail($response->json('user.id'));
        $this->assertTrue(Hash::check($payload['password'], $created->password));
        $this->assertSame(2, $created->tosaClearanceLevel());
        $this->post('/office/logout')->assertRedirect('/');
        $this->post(route('office.login'), ['email' => $created->email, 'password' => $payload['password']])
            ->assertRedirect(route('office.password.change'));
        $this->assertAuthenticatedAs($created, 'office');
    }

    public function test_user_deactivation_blocks_existing_session_and_login_and_cannot_deactivate_self(): void
    {
        $admin = $this->officer('oso');
        $target = $this->officer('ovcaa');
        $this->actingAs($admin, 'office')
            ->patchJson('/office-desk/settings/users/'.$admin->id.'/status', ['is_active' => false])
            ->assertUnprocessable();
        $this->assertTrue($admin->refresh()->is_active);
        $this->patchJson('/office-desk/settings/users/'.$target->id.'/status', ['is_active' => false])
            ->assertOk()->assertJsonPath('is_active', false);
        $this->actingAs($target->fresh(), 'office')->get('/office-desk/tosa')
            ->assertRedirect(route('office.login'));
        $this->assertGuest('office');
        $this->post(route('office.login'), ['email' => $target->email, 'password' => 'SettingsTest@2026!'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('office');
        $this->actingAs($admin, 'office')
            ->patchJson('/office-desk/settings/users/'.$target->id.'/status', ['is_active' => true])
            ->assertOk();
        $this->post('/office/logout');
        $this->post(route('office.login'), ['email' => $target->email, 'password' => 'SettingsTest@2026!'])
            ->assertRedirect(route('office.home'));
    }

    public function test_logo_upload_replacement_reset_and_invalid_files_are_truthful(): void
    {
        Storage::fake('public');
        $this->actingAs($this->officer('oso'), 'office');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->postJson('/office-desk/settings/logo', ['logo' => UploadedFile::fake()->createWithContent('logo.png', $png)])
            ->assertOk();
        $old = OfficeSetting::valuesFor('oso')['general']['logo_path'];
        Storage::disk('public')->assertExists($old);
        $this->postJson('/office-desk/settings/logo', ['logo' => UploadedFile::fake()->createWithContent('new.png', $png)])
            ->assertOk();
        $replacement = OfficeSetting::valuesFor('oso')['general']['logo_path'];
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($replacement);
        $this->postJson('/office-desk/settings/logo', [
            'logo' => UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ])->assertUnprocessable();
        $this->assertSame($replacement, OfficeSetting::valuesFor('oso')['general']['logo_path']);
        $this->deleteJson('/office-desk/settings/logo')->assertOk();
        $this->assertNull(OfficeSetting::valuesFor('oso')['general']['logo_path']);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_unconfigured_and_incorrect_tosa_pins_never_unlock_or_leak_applicants(): void
    {
        $applicant = $this->applicant();
        $this->actingAs($this->officer('oso'), 'office');
        foreach (['1234', '2026'] as $pin) {
            $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => $pin])
                ->assertUnprocessable()->assertJsonPath('ok', false);
        }
        $this->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('tosaUnlocked', false)
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->isEmpty())
            ->assertDontSee($applicant->full_name);
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertForbidden();
        $this->assertSame('pending', $applicant->refresh()->subsection);
        $this->postJson('/office-desk/settings/pin/tosa', ['pin' => '4826'])->assertOk();
        $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '1234'])->assertUnprocessable();
        $this->get('/office-desk/settings/exports/tosa-manifest')->assertForbidden();
        $this->postJson('/office-desk/settings/pin/master', ['pin' => '4826'])->assertNotFound();
    }

    public function test_tosa_unlock_expires_and_configuration_changes_invalidate_prior_unlock(): void
    {
        $applicant = $this->applicant();
        $this->freezeTime();
        $this->configureSecurity(['session_timeout' => 15, 'tosa_pin_hash' => Hash::make('4826')]);
        $this->actingAs($this->officer('oso'), 'office')
            ->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '4826'])->assertOk();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', true)
            ->assertViewHas('tosaUnlockSecondsRemaining', 900)
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id));
        $this->travel(15)->minutes();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', false);
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertForbidden();
        $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '4826'])->assertOk();
        $this->postJson('/office-desk/settings/pin/tosa', ['pin' => '9876'])->assertOk();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', false);
        $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '9876'])->assertOk();
        $this->postJson('/office-desk/settings', [
            'section' => 'security', 'values' => ['session_timeout' => 30, 'tosa_gate' => true],
        ])->assertOk();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', false);
    }

    public function test_tosa_no_timeout_manual_lock_disabled_gate_and_user_session_boundary(): void
    {
        $this->configureSecurity(['session_timeout' => 0, 'tosa_pin_hash' => Hash::make('4826')]);
        $this->actingAs($this->officer('oso'), 'office')
            ->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '4826'])->assertOk();
        $this->travel(1)->days();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', true)
            ->assertViewHas('tosaUnlockSecondsRemaining', 0);
        $this->postJson('/office-desk/settings/tosa-pin/lock')->assertOk();
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', false);
        $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '4826'])->assertOk();
        $this->actingAs($this->officer('ovcaa'), 'office')->get('/office-desk/tosa')
            ->assertOk()->assertViewHas('tosaUnlocked', false);
        $this->configureSecurity(['tosa_gate' => false]);
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaUnlocked', true)
            ->assertViewHas('tosaUnlockSecondsRemaining', 0);
        $this->postJson('/office-desk/settings/tosa-pin/lock')->assertUnprocessable();
    }

    public function test_tosa_clearance_is_enforced_for_view_review_and_template_management(): void
    {
        $this->configureSecurity(['tosa_gate' => false]);
        $applicant = $this->applicant();
        foreach (['so', 'sdo', 'oc'] as $role) {
            $officer = $this->officer($role);
            $officer->update(['tosa_clearance' => 'Level 3 Master']);
            $this->actingAs($officer, 'office')->get('/office-desk/tosa')->assertForbidden();
            $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
                ->assertForbidden();
            $this->postJson('/office-desk/settings/tosa-pin/verify', ['pin' => '4826'])->assertForbidden();
        }
        $officer = $this->officer('oso');
        $officer->update(['tosa_clearance' => 'No Access']);
        $this->actingAs($officer, 'office')->get('/office-desk/tosa')->assertForbidden();
        $officer->update(['tosa_clearance' => 'Level 1 Read-only']);
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaCanReview', false);
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'screening'])
            ->assertForbidden();
        $officer->update(['tosa_clearance' => 'Level 2 Evaluator']);
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'screening'])
            ->assertOk();
        $this->assertSame('screening', $applicant->refresh()->subsection);
        $this->postJson('/office-desk/tosa/requirements/template', [])->assertForbidden();
        $officer->update(['tosa_clearance' => 'Level 3 Master']);
        $this->get('/office-desk/tosa')->assertOk()->assertViewHas('tosaCanManageTemplates', true);
        $this->post('/office-desk/tosa/requirements/template', [])
            ->assertRedirect()
            ->assertSessionHasErrors(['requirement_id', 'requirement_title', 'template_file']);
        $ovcaa = $this->officer('ovcaa');
        $this->assertSame('Level 1 Read-only', $ovcaa->effectiveTosaClearance());
        $ovcaa->update(['tosa_clearance' => 'Level 3 Master']);
        $this->assertSame(2, $ovcaa->tosaClearanceLevel());
        $this->actingAs($ovcaa, 'office')
            ->postJson('/office-desk/tosa/requirements/template', [])->assertForbidden();
    }

    public function test_exports_contain_real_records_preserve_cents_and_escape_spreadsheet_formulas(): void
    {
        $organization = StudentOrganization::create([
            'name' => '=Settings Formula '.Str::uuid(), 'short_name' => 'SET',
            'college' => 'Settings Test College', 'academic_year' => '2026-2027', 'is_active' => true,
        ]);
        $activity = OrgActivity::create([
            'title' => 'Settings Export Activity '.Str::uuid(), 'organization_name' => $organization->name,
            'college' => $organization->college, 'starts_at' => '2026-10-09 10:00:00',
            'workflow_status' => 'oc_approved', 'approved_budget' => '1234.56', 'implemented_budget' => '12.34',
        ]);
        $this->actingAs($this->officer('oso'), 'office');
        $roster = $this->get('/office-desk/settings/exports/organization-roster')->assertOk()->streamedContent();
        $this->assertStringContainsString("'".$organization->name, $roster);
        $dossier = $this->get('/office-desk/settings/exports/accomplishment-dossier')->assertOk()->streamedContent();
        $this->assertStringContainsString($activity->title, $dossier);
        $this->assertStringContainsString('1234.56,12.34', $dossier);
        $this->configureSecurity(['tosa_gate' => false]);
        $applicant = $this->applicant();
        $manifest = $this->get('/office-desk/settings/exports/tosa-manifest')->assertOk()->streamedContent();
        $this->assertStringContainsString($applicant->full_name, $manifest);
        $this->assertStringContainsString('pending,0,0', $manifest);
        $this->get('/office-desk/settings/exports/not-a-package')->assertNotFound();
        $this->actingAs($this->officer('ovcaa'), 'office')
            ->get('/office-desk/settings/exports/organization-roster')->assertForbidden();
    }

    public function test_colliding_long_email_usernames_remain_unique_and_within_column_limit(): void
    {
        $this->actingAs($this->officer('oso'), 'office');
        $local = strtolower(Str::random(64));
        $ids = [];
        foreach (['g.batstate-u.edu.ph', 'batstate-u.edu.ph'] as $domain) {
            $payload = $this->userPayload();
            $payload['email'] = $local.'@'.$domain;
            $response = $this->postJson('/office-desk/settings/users', $payload)->assertCreated();
            $ids[] = $response->json('user.id');
        }
        $first = OfficeUser::findOrFail($ids[0]);
        $second = OfficeUser::findOrFail($ids[1]);
        $this->assertNotSame($first->username, $second->username);
        $this->assertLessThanOrEqual(64, strlen($first->username));
        $this->assertLessThanOrEqual(64, strlen($second->username));
    }

    public function test_master_template_upload_persists_replaces_and_obeys_server_lock(): void
    {
        Storage::fake('public');
        $this->actingAs($this->officer('oso'), 'office');
        $title = 'Settings Template '.Str::uuid();
        $payload = ['requirement_id' => 1, 'requirement_title' => $title];
        $this->postJson('/office-desk/tosa/requirements/template', $payload)->assertForbidden();
        $this->configureSecurity(['tosa_gate' => false]);
        $payload['template_file'] = UploadedFile::fake()->createWithContent('sample.pdf', "%PDF-1.4\nsettings-first-template");
        $this->post('/office-desk/tosa/requirements/template', $payload)->assertRedirect(route('office.tosa'));
        $template = OfficeTemplate::where('category', 'TOSA')->where('name', $title)->firstOrFail();
        $oldPath = $template->file_path;
        $this->assertSame("%PDF-1.4\nsettings-first-template", Storage::disk('public')->get($oldPath));
        $payload['template_file'] = UploadedFile::fake()->createWithContent('replacement.pdf', "%PDF-1.4\nsettings-replacement-template");
        $this->post('/office-desk/tosa/requirements/template', $payload)->assertRedirect(route('office.tosa'));
        $template->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame("%PDF-1.4\nsettings-replacement-template", Storage::disk('public')->get($template->file_path));
        $this->assertSame(1, OfficeTemplate::where('category', 'TOSA')->where('name', $title)->count());
        $this->assertSame('replacement.pdf', $template->original_name);
    }

    public function test_generic_template_upload_cannot_bypass_tosa_clearance_or_office_role(): void
    {
        $this->configureSecurity(['tosa_gate' => false]);
        $this->actingAs($this->officer('sdo'), 'office')
            ->postJson('/office-desk/updates/templates', ['category' => 'TOSA'])->assertForbidden();
        $officer = $this->officer('oso');
        $officer->update(['tosa_clearance' => 'Level 2 Evaluator']);
        $this->actingAs($officer, 'office')
            ->postJson('/office-desk/updates/templates', ['category' => 'TOSA'])->assertForbidden();
        $this->postJson('/office-desk/updates/templates', ['category' => ' tosa '])->assertForbidden();
    }
}

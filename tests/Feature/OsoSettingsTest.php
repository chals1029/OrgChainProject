<?php

namespace Tests\Feature;

use App\Models\OfficeSetting;
use App\Models\OfficeTemplate;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\StudentOrganization;
use App\Models\TosaApplicant;
use App\Models\UserAccount;
use App\Services\TosaRequirements;
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
        OfficeSetting::forScope('oso')->update(['values' => OfficeSetting::defaults()]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function officer(string $role): OfficeUser
    {
        $identifier = 'settings-test-'.Str::uuid();
        $organization = $role === 'so' ? StudentOrganization::create([
            'name' => 'Settings Test Organization '.Str::uuid(),
            'college' => 'Settings Test College',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]) : null;

        return OfficeUser::create([
            'name' => strtoupper($role).' Settings Test',
            'email' => $identifier.'@g.batstate-u.edu.ph',
            'username' => $identifier,
            'password' => 'SettingsTest@2026!',
            'office_role' => $role,
            'office_title' => 'Settings Test Desk',
            'student_organization_id' => $organization?->id,
            'tosa_clearance' => match ($role) {
                'oso' => 'Level 3 Master',
                'ovcaa' => 'Level 1 Read-only',
                default => 'No Access',
            },
            'is_active' => true,
        ]);
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
            'status' => 'submitted',
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
            $this->actingAs($user, 'office')
                ->postJson('/office-desk/settings', [
                    'section' => 'general',
                    'values' => ['system_name' => 'Unauthorized change'],
                ])
                ->assertForbidden();
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

    public function test_oso_settings_validate_the_requested_section(): void
    {
        $this->actingAs($this->officer('oso'), 'office')
            ->postJson('/office-desk/settings', ['section' => 'not-a-settings-section', 'values' => []])
            ->assertUnprocessable();
    }

    public function test_oso_can_download_a_checksum_snapshot_without_sensitive_hashes(): void
    {
        $officer = $this->officer('oso');
        $pinHash = Hash::make('4826');
        $values = OfficeSetting::defaults();
        $values['security'] = ['tosa_gate' => true, 'session_timeout' => 15, 'tosa_pin_hash' => $pinHash];
        $values['general']['password_hash'] = $pinHash;
        $values['legacy_secret'] = $pinHash;
        OfficeSetting::forScope('oso')->update(['values' => $values]);
        $this->actingAs($officer, 'office');

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
        $this->assertArrayNotHasKey('security', $snapshot['settings']);
        $this->assertStringNotContainsString($pinHash, $content);
        $this->assertSame(OfficeSetting::defaults(), $snapshot['settings']);
        $this->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('officeSettings', OfficeSetting::defaults());
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
                'section' => 'general',
                'values' => ['system_name' => '', 'office_name' => '', 'university_name' => ''],
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

    public function test_drafts_and_legacy_unsubmitted_rows_are_absent_from_office_lists_counts_and_manifest(): void
    {
        $submitted = $this->applicant();
        $this->actingAs($this->officer('oso'), 'office');
        $before = $this->get('/office-desk/tosa')->assertOk()->viewData('subsectionCounts')->all();
        $hidden = [];
        foreach (['draft', 'pending'] as $status) {
            $applicant = $this->applicant();
            $applicant->update(['status' => $status]);
            $hidden[] = $applicant;
        }

        $response = $this->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $submitted->id));
        $this->assertSame($before, $response->viewData('subsectionCounts')->all());
        $manifest = $this->get('/office-desk/settings/exports/tosa-manifest')->assertOk()->streamedContent();
        $this->assertStringContainsString($submitted->full_name, $manifest);
        foreach ($hidden as $applicant) {
            $this->assertFalse($response->viewData('tosaApplicants')->contains('id', $applicant->id));
            $response->assertDontSee($applicant->full_name);
            $this->assertStringNotContainsString($applicant->full_name, $manifest);
        }
    }

    public function test_complete_student_submission_is_visible_to_oso_even_after_document_loss(): void
    {
        Storage::fake('local');
        $applicant = $this->applicant();
        $requirements = [];
        foreach (TosaRequirements::SUBMISSIONS as $key => $document) {
            $path = 'tosa/'.$applicant->sr_code.'/'.$key.'.pdf';
            Storage::disk('local')->put($path, file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf')));
            $requirements[$key] = ['path' => $path];
        }
        $applicant->update(['status' => 'draft', 'requirements' => $requirements]);
        $student = $this->ensureActiveStudent($applicant->sr_code);
        $this->actingAs($student, 'student')->post(route('portal.tosa.submit'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('submitted', $applicant->refresh()->status);
        $this->assertSame('screening', $applicant->subsection);

        $this->actingAs($this->officer('oso'), 'office')->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id));
        $url = route('office.tosa.documents.download', ['applicant' => $applicant, 'key' => 'academic']);
        $this->get($url)->assertOk();
        Storage::disk('local')->delete($applicant->requirementPath('academic'));
        $this->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id))
            ->assertViewHas('subsectionCounts', fn ($counts) => $counts->get('screening') >= 1);
        $this->get($url)->assertNotFound();
        $manifest = $this->get('/office-desk/settings/exports/tosa-manifest')->assertOk()->streamedContent();
        $this->assertStringContainsString($applicant->full_name, $manifest);
        $this->postJson(route('office.tosa.subsection', $applicant), ['subsection' => 'accepted',
            'gwa' => 1.25, 'gwa_verified' => true])->assertOk();
        $this->assertSame('submitted', $applicant->refresh()->status);
        $this->assertSame('accepted', $applicant->subsection);
        $this->assertSame(1.25, $applicant->gwa);
        $this->assertTrue($applicant->gwa_verified);
    }

    public function test_oso_legacy_no_access_opens_and_reviews_applicants_automatically(): void
    {
        $applicant = $this->applicant();
        $officer = $this->officer('oso');
        $officer->update(['tosa_clearance' => 'No Access']);
        $this->assertSame(3, $officer->tosaClearanceLevel());
        $this->assertSame('Level 3 Master', $officer->accountMetadata()['tosa_clearance']);
        $this->actingAs($officer, 'office')->get('/office-desk/tosa')->assertOk()
            ->assertViewHas('tosaClearanceLevel', 3)
            ->assertViewHas('tosaCanReview', true)
            ->assertViewHas('tosaCanManageTemplates', true)
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id))
            ->assertViewHas('subsectionCounts', fn ($counts) => $counts->get('pending') >= 1)
            ->assertSee($applicant->full_name);
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'screening'])
            ->assertOk();
        $this->assertSame('screening', $applicant->refresh()->subsection);
        $this->get('/office-desk/tosa?subsection=screening')->assertOk()
            ->assertViewHas('selectedSubsection', 'screening')
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id));
        $this->get('/office-desk/tosa?subsection=pending')->assertOk()
            ->assertViewHas('selectedSubsection', 'pending')
            ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id));
        $this->assertSame('submitted', $applicant->refresh()->status);
        foreach ([null, '', 'Level 1 Read-only', 'Level 2 Evaluator', 'Level 3 Master'] as $clearance) {
            $officer->forceFill(['tosa_clearance' => $clearance]);
            $this->assertSame(3, $officer->tosaClearanceLevel());
        }
    }

    public function test_tosa_access_preserves_authentication_inactive_and_password_change_gates(): void
    {
        $applicant = $this->applicant();
        $this->get('/office-desk/tosa')->assertRedirect(route('office.login'));
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertUnauthorized();
        $officer = $this->officer('oso');
        $officer->update(['is_active' => false]);
        $this->actingAs($officer, 'office')->get('/office-desk/tosa')
            ->assertRedirect(route('office.login'));
        $this->actingAs($officer, 'office')
            ->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertUnauthorized();
        $officer = $this->officer('oso');
        $officer->update(['must_change_password' => true]);
        $this->actingAs($officer, 'office')->get('/office-desk/tosa')
            ->assertRedirect(route('office.password.change'));
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertStatus(423);
        $this->postJson('/office-desk/tosa/references/template', [])->assertStatus(423);
        $officer = $this->officer('oso');
        $stale = clone $officer;
        $officer->update(['password' => 'RevokedSettings@2026!']);
        $state = ['office_auth_state' => ['id' => $stale->id, 'version' => (int) $stale->auth_version]];
        $this->actingAs($stale, 'office')->withSession($state)->get('/office-desk/tosa')
            ->assertRedirect(route('office.login'));
        $this->actingAs($stale, 'office')->withSession($state)
            ->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertUnauthorized();
        $this->assertSame('pending', $applicant->refresh()->subsection);
    }

    public function test_tosa_clearance_is_enforced_for_view_review_and_template_management(): void
    {
        $applicant = $this->applicant();
        foreach (['so', 'sdo', 'oc'] as $role) {
            $officer = $this->officer($role);
            $officer->update(['tosa_clearance' => 'Level 3 Master']);
            $this->actingAs($officer, 'office')->get('/office-desk/tosa')->assertForbidden();
            $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
                ->assertForbidden();
            $this->postJson('/office-desk/tosa/references/template', [])->assertForbidden();
        }
        $ovcaa = $this->officer('ovcaa');
        $ovcaa->forceFill(['tosa_clearance' => null]);
        $this->assertSame('Level 1 Read-only', $ovcaa->effectiveTosaClearance());
        foreach (['', 'Level 1 Read-only'] as $clearance) {
            $ovcaa->update(['tosa_clearance' => $clearance]);
            $this->assertSame('Level 1 Read-only', $ovcaa->effectiveTosaClearance());
            $this->actingAs($ovcaa, 'office')->get('/office-desk/tosa')->assertOk()
                ->assertViewHas('tosaCanReview', false)
                ->assertViewHas('tosaCanManageTemplates', false)
                ->assertViewHas('tosaApplicants', fn ($applicants) => $applicants->contains('id', $applicant->id));
            $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
                ->assertForbidden();
            $this->postJson('/office-desk/tosa/references/template', [])->assertForbidden();
        }
        $this->assertSame('pending', $applicant->refresh()->subsection);
        foreach (['Level 2 Evaluator', 'Level 3 Master'] as $clearance) {
            $ovcaa->update(['tosa_clearance' => $clearance]);
            $this->assertSame(2, $ovcaa->tosaClearanceLevel());
            $this->actingAs($ovcaa, 'office')->get('/office-desk/tosa')->assertOk()
                ->assertViewHas('tosaCanReview', true)
                ->assertViewHas('tosaCanManageTemplates', false);
            $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'screening'])
                ->assertOk();
            $this->postJson('/office-desk/tosa/references/template', [])->assertForbidden();
            $this->postJson('/office-desk/updates/templates', ['category' => 'TOSA'])->assertForbidden();
        }
        $this->assertSame('screening', $applicant->refresh()->subsection);
        $ovcaa->update(['tosa_clearance' => 'No Access']);
        $this->get('/office-desk/tosa')->assertForbidden();
        $this->postJson('/office-desk/tosa/'.$applicant->id.'/subsection', ['subsection' => 'accepted'])
            ->assertForbidden();
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
        $applicant = $this->applicant();
        $manifest = $this->get('/office-desk/settings/exports/tosa-manifest')->assertOk()->streamedContent();
        $this->assertStringContainsString($applicant->full_name, $manifest);
        $this->assertStringContainsString('pending', $manifest);
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

    public function test_bundled_tosa_files_download_without_database_template_uploads(): void
    {
        OfficeTemplate::query()->where('category', 'TOSA')->delete();
        $this->actingAs($this->officer('oso'), 'office');
        $templates = collect($this->get(route('office.tosa'))->assertOk()->viewData('tosaTemplates'));
        $this->assertCount(5, $templates);
        foreach (TosaRequirements::REFERENCES as $key => $document) {
            $filename = $document['filename'];
            $mime = TosaRequirements::mimeType($key);
            $template = $templates->get($document['title']);
            $this->assertNotNull($template);
            $download = $this->get($template['url'])->assertOk()->assertDownload($filename)
                ->assertHeader('Content-Type', $mime);
            $this->assertSame(hash_file('sha256', base_path('TOSA/'.$filename)),
                hash_file('sha256', $download->baseResponse->getFile()->getPathname()));
        }
        $guidelines = $templates->firstWhere('id', 'source-tosa-guidelines');
        $this->get($guidelines['preview_url'])->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Guidelines TOSA 2025.pdf"');
        $this->actingAs($this->officer('ovcaa'), 'office')->get(route('office.tosa'))->assertOk();
        $this->get($templates->firstWhere('id', 'source-tosa-application')['url'])->assertOk();
    }

    public function test_uploaded_native_template_is_shared_by_office_and_student_and_does_not_change_bundle(): void
    {
        Storage::fake('public');
        OfficeTemplate::query()->where('category', 'TOSA')->delete();
        $officer = $this->officer('oso');
        $student = new UserAccount(['user_id' => 1, 'sr_code' => 'override-owner',
            'account_status' => 'active', 'role' => 'student']);
        $title = 'TOSA Application Form 2025';
        $original = base_path('TOSA/TOSA Application Form 2025.docx');
        $originalHash = hash_file('sha256', $original);
        $bytes = file_get_contents($original);
        $oldPath = null;
        foreach (['replacement.docx', 'updated.docx'] as $filename) {
            $this->actingAs($officer, 'office')->post(route('office.tosa.references.store'), [
                'reference_key' => 'application_form',
                'template_file' => UploadedFile::fake()->createWithContent($filename, $bytes),
            ])->assertRedirect(route('office.tosa'))->assertSessionHasNoErrors();
            $active = $this->get(route('office.tosa'))->assertOk()->viewData('tosaTemplates')[$title];
            $this->assertNull($active['preview_url']);
            $download = $this->get($active['url'])->assertOk()->assertDownload($filename);
            $this->assertSame($bytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
            $source = $this->get(route('office.updates.templates.document', 'source-tosa-application'))
                ->assertOk()->assertDownload('TOSA Application Form 2025.docx');
            $this->assertSame($originalHash, hash_file('sha256', $source->baseResponse->getFile()->getPathname()));
            $download = $this->actingAs($student, 'student')->get(route('portal.tosa.templates.show', ['key' => 'application_form']))
                ->assertOk()->assertDownload($filename);
            $this->assertSame($bytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
            if ($oldPath) {
                Storage::disk('public')->assertMissing($oldPath);
            }
            $oldPath = OfficeTemplate::query()->where('category', 'TOSA')->where('name', $title)->sole()->file_path;
        }
        $this->assertSame($originalHash, hash_file('sha256', $original));
    }

    public function test_oso_legacy_no_access_can_persist_and_replace_reference_templates(): void
    {
        Storage::fake('public');
        $officer = $this->officer('oso');
        $officer->update(['tosa_clearance' => 'No Access']);
        $this->actingAs($officer, 'office');
        OfficeTemplate::query()->where('category', 'TOSA')->where('name', 'Guidelines TOSA 2025')->delete();
        $title = 'Guidelines TOSA 2025';
        $payload = ['reference_key' => 'guidelines'];
        $firstBytes = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        $replacementBytes = $firstBytes."\n% replacement";
        $payload['template_file'] = UploadedFile::fake()->createWithContent('sample.pdf', $firstBytes);
        $this->post('/office-desk/tosa/references/template', $payload)->assertRedirect(route('office.tosa'));
        $template = OfficeTemplate::where('category', 'TOSA')->where('name', $title)->firstOrFail();
        $oldPath = $template->file_path;
        $this->assertSame($firstBytes, Storage::disk('public')->get($oldPath));
        $download = $this->get(route('office.updates.templates.download', $template))
            ->assertOk()->assertDownload('sample.pdf')->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($firstBytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
        $payload['template_file'] = UploadedFile::fake()->createWithContent('replacement.pdf', $replacementBytes);
        $this->post('/office-desk/tosa/references/template', $payload)->assertRedirect(route('office.tosa'));
        $template->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame($replacementBytes, Storage::disk('public')->get($template->file_path));
        $this->assertSame(1, OfficeTemplate::where('category', 'TOSA')->where('name', $title)->count());
        $this->assertSame('replacement.pdf', $template->original_name);
        $download = $this->get(route('office.updates.templates.download', $template))
            ->assertOk()->assertDownload('replacement.pdf')->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($replacementBytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
    }

    public function test_reference_template_upload_rejects_submission_keys_and_wrong_native_formats(): void
    {
        Storage::fake('public');
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->officer($role), 'office')
                ->postJson('/office-desk/updates/templates', ['category' => 'TOSA'])->assertForbidden();
        }
        $this->actingAs($this->officer('oso'), 'office');
        $before = OfficeTemplate::query()->count();
        foreach (['cv', 'good_moral', 'academic', 'leadership', 'social_responsibility', 'application_docs', 'other'] as $key) {
            $this->postJson(route('office.tosa.references.store'), ['reference_key' => $key,
                'template_file' => UploadedFile::fake()->createWithContent('guidelines.pdf', file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf')))])
                ->assertUnprocessable()->assertJsonValidationErrors('reference_key');
        }
        foreach (TosaRequirements::REFERENCES as $key => $document) {
            $bytes = file_get_contents(base_path('TOSA/'.($key === 'guidelines' ? 'Front Page.doc' : 'Guidelines TOSA 2025.pdf')));
            $this->postJson(route('office.tosa.references.store'), ['reference_key' => $key,
                'template_file' => UploadedFile::fake()->createWithContent($document['filename'], $bytes)])
                ->assertUnprocessable()->assertJsonValidationErrors('template_file');
        }
        $this->postJson('/office-desk/updates/templates', [
            'category' => ' tosa ', 'name' => 'Arbitrary TOSA Template',
            'template_file' => UploadedFile::fake()->createWithContent('sample.pdf', file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'))),
        ])->assertUnprocessable()->assertJsonValidationErrors('reference_key');
        $this->assertSame($before, OfficeTemplate::query()->count());
    }

    public function test_office_package_excludes_unmatched_legacy_templates_without_deleting_them(): void
    {
        $legacy = OfficeTemplate::query()->create(['category' => 'TOSA', 'name' => 'Curriculum Vitae',
            'original_name' => 'old.pdf', 'format' => 'PDF']);
        $this->actingAs($this->officer('oso'), 'office');
        $templates = $this->get(route('office.tosa'))->assertOk()->viewData('tosaTemplates');
        $this->assertCount(5, $templates);
        $this->assertArrayNotHasKey('Curriculum Vitae', $templates);
        $this->assertTrue(OfficeTemplate::query()->whereKey($legacy->id)->exists());
        foreach ($templates as $template) {
            if ($template['format'] !== 'PDF') {
                $this->assertNull($template['preview_url']);
            }
        }
    }


    public function test_source_and_uploaded_template_urls_preserve_resource_identity_while_package_uses_latest(): void
    {
        Storage::fake('public');
        OfficeTemplate::query()->where('category', 'TOSA')->where('name', 'Front Page')->delete();
        $originalBytes = file_get_contents(base_path('TOSA/Front Page.doc'));
        $updatedBytes = file_get_contents(base_path('TOSA/TOSA Submission Guide.doc'));
        $this->assertNotSame($originalBytes, $updatedBytes);
        $records = [];
        foreach (['first.doc' => $originalBytes, 'latest.doc' => $updatedBytes] as $name => $bytes) {
            $path = 'tosa-templates/'.$name;
            Storage::disk('public')->put($path, $bytes);
            $records[] = [OfficeTemplate::query()->create([
                'category' => 'TOSA', 'name' => 'Front Page', 'format' => 'DOC',
                'original_name' => $name, 'file_path' => $path,
            ]), $bytes];
        }
        $this->actingAs($this->officer('oso'), 'office');
        foreach ($records as [$record, $bytes]) {
            foreach ([
                route('office.updates.templates.document', ['id' => 'db-'.$record->id]),
                route('office.updates.templates.download', $record),
            ] as $url) {
                $response = $this->get($url)->assertOk()->assertDownload($record->original_name)
                    ->assertHeader('Content-Type', 'application/msword');
                $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
            }
        }
        $source = $this->get(route('office.updates.templates.document', 'source-tosa-front-page'))
            ->assertOk()->assertDownload('Front Page.doc');
        $this->assertSame($originalBytes, file_get_contents($source->baseResponse->getFile()->getPathname()));
        $package = $this->get(route('office.tosa'))->assertOk()->viewData('tosaTemplates');
        $this->assertCount(5, $package);
        $active = $this->get($package['Front Page']['url'])->assertOk()->assertDownload('latest.doc');
        $this->assertSame($updatedBytes, file_get_contents($active->baseResponse->getFile()->getPathname()));
        $student = new UserAccount(['user_id' => 1, 'sr_code' => 'identity-owner',
            'account_status' => 'active', 'role' => 'student']);
        $active = $this->actingAs($student, 'student')->get(route('portal.tosa.templates.show', ['key' => 'front_page']))
            ->assertOk()->assertDownload('latest.doc');
        $this->assertSame($updatedBytes, file_get_contents($active->baseResponse->getFile()->getPathname()));
        $records[0][0]->update(['file_path' => 'tosa-templates/missing.doc']);
        $this->get(route('office.updates.templates.document', ['id' => 'db-'.$records[0][0]->id]))->assertNotFound();
        $this->get(route('office.updates.templates.download', $records[0][0]))->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgFundAccount;
use App\Models\OrgReportDocument;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Models\SystemAdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OfficeAccountManagementTest extends TestCase
{
    use UsesLaragonDatabase;

    private const PASSWORD = 'FixtureOnly@2026!';
    private const TEMPORARY = 'TemporaryOnly@2026!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::beginTransaction();
        DB::connection('orgchain')->beginTransaction();
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
            'name' => 'Account Fixture '.Str::uuid(), 'short_name' => 'ACCT',
            'college' => 'Fixture College', 'academic_year' => '2026-2027', 'is_active' => true,
        ]);
    }

    private function officer(string $role = 'oso', ?StudentOrganization $organization = null): OfficeUser
    {
        $id = 'account-'.Str::uuid();

        return OfficeUser::create([
            'name' => strtoupper($role).' Account Fixture', 'email' => $id.'@g.batstate-u.edu.ph',
            'username' => $id, 'password' => self::PASSWORD, 'office_role' => $role,
            'student_organization_id' => $organization?->id, 'office_title' => 'Fixture Officer',
            'is_active' => true, 'tosa_clearance' => 'No Access',
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'Incoming Fixture', 'email' => 'incoming-'.Str::uuid().'@g.batstate-u.edu.ph',
            'password' => self::TEMPORARY, 'password_confirmation' => self::TEMPORARY,
        ];
    }

    private function login(OfficeUser $user, string $password = self::PASSWORD, bool $remember = false): void
    {
        $this->post('/office/logout');
        $this->post(route('office.login'), ['email' => strtoupper($user->email), 'password' => $password, 'remember' => $remember])
            ->assertRedirect(route($user->must_change_password ? 'office.password.change' : 'office.home'));
        $this->assertAuthenticatedAs($user, 'office');
    }

    public function test_so_creation_requires_registered_organization_and_never_promotes_so(): void
    {
        $this->actingAs($this->officer(), 'office');
        $payload = $this->payload() + ['office_role' => 'so', 'tosa_clearance' => 'No Access'];
        $before = OfficeUser::count();
        $this->postJson(route('office.settings.users.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('student_organization_id');
        $this->postJson(route('office.settings.users.store'), $payload + ['student_organization_id' => 0])->assertUnprocessable();
        $organization = $this->organization();
        $this->postJson(route('office.settings.users.store'), array_replace($payload, [
            'student_organization_id' => $organization->id, 'tosa_clearance' => 'Level 3 Master',
        ]))->assertUnprocessable()->assertJsonValidationErrors('tosa_clearance');
        $this->assertSame($before, OfficeUser::count());
        $response = $this->postJson(route('office.settings.users.store'), $payload + ['student_organization_id' => $organization->id])
            ->assertCreated()->assertJsonPath('user.organization_name', $organization->name)
            ->assertJsonPath('user.must_change_password', true)->assertJsonPath('user.tosa_clearance', 'No Access');
        $user = OfficeUser::findOrFail($response->json('user.id'));
        $this->assertSame('so', $user->office_role);
        $this->assertSame($organization->id, $user->student_organization_id);
        $this->assertSame(0, $user->tosaClearanceLevel());
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('user'));
        $this->login($user, self::TEMPORARY);
        $this->get('/office-desk')->assertRedirect(route('office.password.change'));
        $this->getJson('/office-desk/analytics')->assertStatus(423)->assertJsonPath('password_change_url', route('office.password.change'));
        $this->postJson(route('office.settings.users.store'), $payload)->assertStatus(423);
    }

    public function test_forced_password_change_rejects_wrong_short_mismatched_and_same_password_then_preserves_current_session(): void
    {
        $user = $this->officer('so', $this->organization());
        $user->update(['must_change_password' => true]);
        $this->login($user);
        $this->get(route('office.password.change'))->assertOk()
            ->assertViewHas('office', fn ($office) => $office->id === $user->id)
            ->assertHeader('Cache-Control', 'no-store, private');
        foreach ([
            ['wrong', 'ChangedFixture@2026!', 'ChangedFixture@2026!', 'current_password'],
            [self::PASSWORD, 'short', 'short', 'new_password'],
            [self::PASSWORD, 'ChangedFixture@2026!', 'MismatchFixture@2026!', 'new_password'],
            [self::PASSWORD, self::PASSWORD, self::PASSWORD, 'new_password'],
        ] as [$current, $password, $confirmation, $field]) {
            $this->from(route('office.password.change'))->post(route('office.password.complete'), [
                'current_password' => $current, 'new_password' => $password, 'new_password_confirmation' => $confirmation,
            ])->assertRedirect(route('office.password.change'))->assertSessionHasErrors($field);
            $oldInput = session('_old_input', []);
            foreach (['current_password', 'new_password', 'new_password_confirmation'] as $credential) {
                $this->assertArrayNotHasKey($credential, $oldInput);
            }
            $this->assertTrue($user->refresh()->must_change_password);
            $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        }
        $previous = session('office_auth_state');
        $this->post(route('office.password.complete'), [
            'current_password' => self::PASSWORD, 'new_password' => 'ChangedFixture@2026!', 'new_password_confirmation' => 'ChangedFixture@2026!',
        ])->assertRedirect(route('office.home'));
        $this->assertFalse($user->refresh()->must_change_password);
        $this->assertSame(1, $user->auth_version);
        $this->assertSame(1, session('office_auth_state.version'));
        $this->get(route('office.password.change'))->assertOk();
        $this->actingAs($user, 'office')->withSession(['office_auth_state' => $previous])->getJson('/office-desk/analytics')->assertUnauthorized();
        $this->post('/office/logout')->assertRedirect('/');
    }

    public function test_edit_is_identity_only_and_email_changes_revoke_stale_sessions(): void
    {
        $organization = $this->organization();
        $user = $this->officer('so', $organization);
        $oldState = ['id' => $user->id, 'version' => 0];
        $this->actingAs($this->officer(), 'office');
        $identity = ['name' => 'Edited Officer', 'email' => strtoupper($user->email), 'office_title' => 'President', 'employee_id' => 'FIXTURE-1'];
        foreach ([['office_role' => 'oso'], ['student_organization_id' => $this->organization()->id], ['tosa_clearance' => 'Level 3 Master']] as $spoof) {
            $this->patchJson(route('office.settings.users.update', $user), $identity + $spoof)->assertUnprocessable();
        }
        $this->patchJson(route('office.settings.users.update', $user), $identity)->assertOk()->assertJsonPath('user.office_role', 'so');
        $this->assertSame(0, $user->refresh()->auth_version);
        $identity['email'] = 'edited-'.Str::uuid().'@batstate-u.edu.ph';
        $this->patchJson(route('office.settings.users.update', $user), $identity)->assertOk()->assertJsonPath('user.student_organization_id', $organization->id);
        $this->assertSame(1, $user->refresh()->auth_version);
        $this->actingAs($user, 'office')->withSession(['office_auth_state' => $oldState])->getJson('/office-desk/analytics')->assertUnauthorized();
    }

    public function test_reset_invalidates_stale_guard_password_and_legacy_session_and_new_login_is_gated(): void
    {
        $user = $this->officer('so', $this->organization());
        $stale = clone $user;
        $this->actingAs($this->officer(), 'office');
        foreach ([
            ['password' => 'short', 'password_confirmation' => 'short'],
            ['password' => self::TEMPORARY, 'password_confirmation' => 'MismatchFixture'],
            ['password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD],
        ] as $invalid) {
            $this->postJson(route('office.settings.users.password', $user), $invalid)->assertUnprocessable();
            $this->assertTrue(Hash::check(self::PASSWORD, $user->refresh()->password));
            $this->assertSame(0, $user->auth_version);
        }
        $this->postJson(route('office.settings.users.password', $user), $this->payload())->assertOk()->assertJsonPath('user.must_change_password', true);
        $this->assertFalse(Hash::check(self::PASSWORD, $user->refresh()->password));
        $this->assertTrue(Hash::check(self::TEMPORARY, $user->password));
        $this->actingAs($stale, 'office')->withSession(['office_auth_state' => ['id' => $user->id, 'version' => 0]])
            ->getJson('/office-desk/analytics')->assertUnauthorized();
        $this->actingAs($stale, 'office')->withSession(['office_auth_state' => null])->get('/office-desk')->assertRedirect(route('office.login'));
        $this->post(route('office.login'), ['email' => $user->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->login($user, self::TEMPORARY);
        $this->getJson('/office-desk/analytics')->assertStatus(423);
    }

    public function test_management_denies_self_and_every_non_oso_role(): void
    {
        $admin = $this->officer();
        $user = $this->officer('so', $this->organization());
        $actions = [
            ['POST', 'office.settings.users.store', null, $this->payload() + ['office_role' => 'oso', 'tosa_clearance' => 'No Access']],
            ['PATCH', 'office.settings.users.update', $user, ['name' => $user->name, 'email' => $user->email]],
            ['POST', 'office.settings.users.password', $user, $this->payload()],
            ['PATCH', 'office.settings.users.status', $user, ['is_active' => false]],
            ['POST', 'office.settings.users.turnover', $user, $this->payload()],
        ];
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->officer($role), 'office');
            foreach ($actions as [$method, $route, $target, $payload]) {
                $this->json($method, route($route, $target ? [$target] : []), $payload)->assertForbidden();
            }
        }
        $this->actingAs($admin, 'office');
        foreach (array_slice($actions, 1) as [$method, $route, $target, $payload]) {
            if ($route === 'office.settings.users.update') {
                $payload['email'] = $admin->email;
            }
            $this->json($method, route($route, $admin), $payload)->assertUnprocessable();
        }
        $this->patchJson(route('office.settings.users.status', $admin), ['is_active' => true])->assertUnprocessable();
        $this->assertTrue($admin->refresh()->is_active);
        $this->assertSame(0, $admin->auth_version);
    }

    public function test_turnover_is_atomic_distinct_organization_bound_and_retains_every_history_record(): void
    {
        $organization = $this->organization();
        $outgoing = $this->officer('so', $organization);
        $outgoing->setRememberToken(Str::random(60));
        $outgoing->save();
        $identity = $outgoing->only(['id', 'name', 'email', 'username', 'password', 'student_organization_id']);
        $rememberToken = $outgoing->getRememberToken();
        $folder = ArchiveFolder::create(['name' => 'Account Fixture Archive', 'organization_name' => $organization->name]);
        $archive = ArchiveDocument::create([
            'archive_folder_id' => $folder->id, 'name' => 'Retained Archive', 'original_name' => 'retained.pdf',
            'file_path' => 'fixtures/account-retained.pdf', 'mime_type' => 'application/pdf', 'file_size' => 123, 'uploaded_by' => $outgoing->name,
        ]);
        $fund = OrgFundAccount::create(['organization_name' => $organization->name, 'total_funds' => 1000, 'beginning_balance' => 1000, 'fiscal_year' => '2026-2027']);
        $activity = OrgActivity::create([
            'title' => 'Account Fixture Activity', 'organization_name' => $organization->name, 'org_fund_account_id' => $fund->id,
            'status' => 'upcoming', 'workflow_status' => 'oc_approved', 'starts_at' => now(),
        ]);
        $records = [$organization, $folder, $archive, $fund, $activity];
        foreach (['ar', 'fr'] as $type) {
            $status = OrgReportStatus::create([
                'report_type' => $type, 'organization_name' => $organization->name, 'semester' => '1st Semester',
                'academic_year' => '2026-2027', 'status' => 'archived', 'archive_folder_id' => $folder->id, 'reviewed_by' => $outgoing->id,
            ]);
            $document = OrgReportDocument::create([
                'org_report_status_id' => $status->id, 'report_type' => $type, 'organization_name' => $organization->name,
                'semester' => '1st Semester', 'academic_year' => '2026-2027', 'name' => 'Retained '.$type,
                'original_name' => $type.'.pdf', 'file_path' => 'fixtures/'.$type.'.pdf', 'mime_type' => 'application/pdf',
                'file_size' => 100, 'uploaded_by' => $outgoing->name,
            ]);
            array_push($records, $status, $document);
        }
        $snapshots = array_map(fn ($record) => $record->refresh()->getRawOriginal(), $records);
        $this->actingAs($this->officer(), 'office');
        $payload = $this->payload();
        $count = OfficeUser::count();
        foreach ([
            ['password_confirmation' => 'Mismatch'], ['email' => $outgoing->email], ['email' => 'outside@example.com'],
            ['student_organization_id' => $this->organization()->id], ['office_role' => 'oso'],
            ['password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD],
        ] as $invalid) {
            $this->postJson(route('office.settings.users.turnover', $outgoing), array_replace($payload, $invalid))->assertUnprocessable();
            $this->assertTrue($outgoing->refresh()->is_active);
            $this->assertSame($count, OfficeUser::count());
        }
        $response = $this->postJson(route('office.settings.users.turnover', $outgoing), $payload)->assertCreated()
            ->assertJsonPath('previous_user.id', $outgoing->id)->assertJsonPath('previous_user.is_active', false)
            ->assertJsonPath('user.student_organization_id', $organization->id)->assertJsonPath('user.must_change_password', true);
        $incoming = OfficeUser::findOrFail($response->json('user.id'));
        $this->assertNotSame($outgoing->id, $incoming->id);
        $this->assertSame('so', $incoming->office_role);
        $this->assertSame($outgoing->name, $response->json('previous_user.name'));
        $this->assertSame(1, $outgoing->refresh()->auth_version);
        $this->assertSame($identity, $outgoing->only(array_keys($identity)));
        $this->assertNotSame($rememberToken, $outgoing->getRememberToken());
        $this->postJson(route('office.settings.users.turnover', $outgoing), $this->payload())->assertUnprocessable();
        $this->assertSame($count + 1, OfficeUser::count());
        foreach ($records as $index => $record) {
            $this->assertSame($snapshots[$index], $record->refresh()->getRawOriginal());
        }
        $this->actingAs($outgoing, 'office')->withSession(['office_auth_state' => ['id' => $outgoing->id, 'version' => 0]])
            ->getJson('/office-desk/analytics')->assertUnauthorized();
        $this->login($incoming, self::TEMPORARY);
        $this->post(route('office.password.complete'), [
            'current_password' => self::TEMPORARY, 'new_password' => 'IncomingChanged@2026!',
            'new_password_confirmation' => 'IncomingChanged@2026!',
        ])->assertRedirect(route('office.home'));
        $other = $this->organization();
        $this->get('/office-desk/activities?'.http_build_query(['organization' => $other->name]))->assertOk()
            ->assertViewHas('activities', fn ($activities) => collect($activities)->contains('id', $activity->id)
                && collect($activities)->every(fn ($row) => $row['organization'] === $organization->name));
    }

    public function test_turnover_rejects_unlinked_inactive_and_non_so_accounts(): void
    {
        $this->actingAs($this->officer(), 'office');
        $inactive = $this->officer('so', $this->organization());
        $inactive->update(['is_active' => false]);
        foreach ([$this->officer('so'), $inactive, $this->officer('ovcaa')] as $user) {
            $this->postJson(route('office.settings.users.turnover', $user), $this->payload())->assertUnprocessable();
        }
    }

    public function test_valid_fresh_remember_login_works_but_pre_reset_remember_cookie_never_authenticates(): void
    {
        $user = $this->officer('so', $this->organization());
        $this->login($user, self::PASSWORD, true);
        $guard = Auth::guard('office');
        $cookieName = $guard->getRecallerName();
        $oldCookie = $user->id.'|'.$user->refresh()->getRememberToken().'|'.$user->password;
        $user->password = self::TEMPORARY;
        $user->must_change_password = true;
        $user->save();
        session()->forget([$guard->getName(), 'office_auth_state']);
        Auth::forgetGuards();
        $this->withCookie($cookieName, $oldCookie)->get('/office-desk')->assertRedirect(route('office.login'));
        $this->assertGuest('office');
        $this->defaultCookies = [];
        $this->login($user, self::TEMPORARY, true);
        $freshCookie = $user->id.'|'.$user->refresh()->getRememberToken().'|'.$user->password;
        session()->forget([Auth::guard('office')->getName(), 'office_auth_state']);
        Auth::forgetGuards();
        $this->withCookie($cookieName, $freshCookie)->get(route('office.password.change'))->assertOk();
        $this->assertAuthenticatedAs($user, 'office');
        $this->assertSame($user->auth_version, session('office_auth_state.version'));
    }

    public function test_own_email_and_password_changes_preserve_current_session_but_revoke_other_sessions(): void
    {
        $user = $this->officer('so', $this->organization());
        $this->login($user);
        $state = session('office_auth_state');
        $this->postJson('/office-desk/settings/account', [
            'name' => $user->name, 'email' => 'self-'.Str::uuid().'@batstate-u.edu.ph', 'office_title' => 'President',
        ])->assertOk();
        $this->assertSame(1, session('office_auth_state.version'));
        $this->postJson('/office-desk/settings/password', [
            'current_password' => self::PASSWORD, 'new_password' => 'SelfChanged@2026!', 'new_password_confirmation' => 'SelfChanged@2026!',
        ])->assertOk();
        $this->assertSame(2, session('office_auth_state.version'));
        $this->get(route('office.password.change'))->assertOk();
        $this->withSession(['office_auth_state' => $state])->getJson('/office-desk/analytics')->assertUnauthorized();
    }

    public function test_system_admin_disable_and_reenable_revokes_old_sessions_even_with_cached_guard(): void
    {
        $user = $this->officer('so', $this->organization());
        $this->login($user);
        $state = session('office_auth_state');
        $id = 'account-admin-'.Str::uuid();
        $admin = SystemAdminUser::create([
            'name' => 'Account Admin Fixture', 'email' => $id.'@batstate-u.edu.ph', 'username' => $id,
            'password' => self::PASSWORD, 'role' => 'system_admin', 'is_active' => true,
        ]);
        $this->actingAs($admin, 'system_admin');
        foreach ([false, true] as $active) {
            $this->patch(route('system-admin.office-users.status', $user), ['is_active' => $active])->assertRedirect();
        }
        $this->assertSame(2, $user->refresh()->auth_version);
        $this->actingAs($user, 'office')->withSession(['office_auth_state' => $state])->getJson('/office-desk/analytics')->assertUnauthorized();
        $this->login($user);
        $this->get(route('office.password.change'))->assertOk();
    }

    public function test_oso_roster_and_safe_export_include_active_and_inactive_so_accounts(): void
    {
        $organization = $this->organization();
        $user = $this->officer('so', $organization);
        $user->update(['is_active' => false]);
        $this->actingAs($this->officer(), 'office')->get('/office-desk/activities')->assertOk()
            ->assertViewHas('officeUsers', fn ($users) => $users->contains('id', $user->id)
                && $users->firstWhere('id', $user->id)->relationLoaded('studentOrganization'))
            ->assertViewHas('accountOrganizations', fn ($organizations) => $organizations->contains('id', $organization->id));
        $snapshot = $this->get(route('office.settings.snapshot'))->assertOk();
        $data = json_decode($snapshot->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
        $exported = collect($data['office_users'])->firstWhere('id', $user->id);
        $this->assertFalse($exported['is_active']);
        $this->assertSame($organization->name, $exported['organization_name']);
        $this->assertArrayNotHasKey('password', $exported);
        $this->assertArrayNotHasKey('remember_token', $exported);
        $this->actingAs($this->officer('so', $organization), 'office')->get('/office-desk/activities')->assertOk()
            ->assertViewHas('officeUsers', fn ($users) => $users->isEmpty())
            ->assertViewHas('accountOrganizations', fn ($organizations) => $organizations->isEmpty());
    }

    public function test_concurrent_turnover_requests_create_exactly_one_replacement(): void
    {
        // Independent PHP processes require committed fixtures. Only these owned rows are committed and cleaned up.
        $organization = $this->organization();
        $outgoing = $this->officer('so', $organization);
        $admins = [$this->officer(), $this->officer()];
        $payloads = [$this->payload(), $this->payload()];
        DB::commit();
        DB::connection('orgchain')->commit();
        $processes = [];
        try {
            $code = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = json_decode(base64_decode($argv[1]), true);
config(['database.connections.mysql' => $config['database'], 'database.default' => 'mysql']);
Illuminate\Support\Facades\DB::purge('mysql');
$actor = App\Models\OfficeUser::findOrFail($config['actor']);
$outgoing = App\Models\OfficeUser::findOrFail($config['outgoing']);
Illuminate\Support\Facades\Auth::guard('office')->setUser($actor);
$request = Illuminate\Http\Request::create('/office-desk/settings/users/'.$outgoing->id.'/turnover', 'POST', $config['payload']);
while (microtime(true) < $config['start']) { usleep(1000); }
$response = $app->make(App\Http\Controllers\OfficeAccountController::class)->turnover($request, $outgoing);
echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)]);
PHP;
            $start = microtime(true) + 1;
            foreach ($admins as $index => $admin) {
                $arguments = base64_encode(json_encode([
                    'database' => config('database.connections.mysql'), 'actor' => $admin->id,
                    'outgoing' => $outgoing->id, 'payload' => $payloads[$index], 'start' => $start,
                ], JSON_THROW_ON_ERROR));
                $process = new Process([PHP_BINARY, '-r', $code, $arguments], base_path(), null, null, 30);
                $process->start();
                $processes[] = $process;
            }
            $statuses = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().' '.$process->getOutput());
                $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                $statuses[] = $result['status'];
            }
            sort($statuses);
            $this->assertSame([201, 422], $statuses);
            $this->assertSame(1, OfficeUser::where('student_organization_id', $organization->id)->where('is_active', true)->count());
            $this->assertFalse($outgoing->refresh()->is_active);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            OfficeUser::whereIn('email', array_column($payloads, 'email'))->delete();
            OfficeUser::whereIn('id', [$outgoing->id, $admins[0]->id, $admins[1]->id])->delete();
            $organization->delete();
            DB::beginTransaction();
            DB::connection('orgchain')->beginTransaction();
        }
    }
}

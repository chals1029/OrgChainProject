<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OfficeOrganizationAccountTest extends TestCase
{
    use UsesLaragonDatabase;

    private const PASSWORD = 'FixtureOnly@2026!';

    private mixed $mailManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        DB::beginTransaction();

        config([
            'mail.default' => 'array',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.from.address' => 'oso-noreply@g.batstate-u.edu.ph',
            'mail.from.name' => 'OrgChain OSO',
        ]);
        $this->mailManager = Mail::getFacadeRoot();
        Mail::mailer('array')->getSymfonyTransport()->flush();
    }

    protected function tearDown(): void
    {
        if ($this->mailManager !== null) {
            Mail::swap($this->mailManager);
        }
        Mail::mailer('array')->getSymfonyTransport()->flush();
        DB::rollBack();
        parent::tearDown();
    }

    private function organization(): StudentOrganization
    {
        return StudentOrganization::create([
            'name' => 'Existing Org Fixture '.Str::uuid(),
            'short_name' => 'EXIST',
            'college' => 'College of Informatics and Computing Sciences',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }

    private function officer(string $role = 'oso', ?StudentOrganization $organization = null): OfficeUser
    {
        $id = 'org-acct-'.Str::uuid();

        return OfficeUser::create([
            'name' => strtoupper($role).' Fixture Officer',
            'email' => $id.'@g.batstate-u.edu.ph',
            'username' => $id,
            'password' => self::PASSWORD,
            'office_role' => $role,
            'student_organization_id' => $organization?->id,
            'office_title' => 'Fixture Officer',
            'is_active' => true,
            'must_change_password' => false,
            'tosa_clearance' => $role === 'oso' ? 'Level 3 Master' : 'No Access',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        $suffix = Str::uuid()->toString();

        return array_replace([
            'organization_name' => 'New Student Organization '.$suffix,
            'short_name' => 'NSO-'. strtoupper(substr(str_replace('-', '', $suffix), 0, 6)),
            'college' => 'College of Informatics and Computing Sciences',
            'officer_name' => 'Incoming Org President',
            'email' => 'new-org-'.$suffix.'@g.batstate-u.edu.ph',
        ], $overrides);
    }

    private function lastMailedPassword(): string
    {
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages, 'Expected a credential email in the array transport.');
        $message = $messages->last()->getOriginalMessage();
        $body = (string) $message->getTextBody();
        $this->assertMatchesRegularExpression('/Temporary Password:\s+(\S+)/', $body);
        preg_match('/Temporary Password:\s+(\S+)/', $body, $matches);

        return $matches[1];
    }

    public function test_auth_boundaries_deny_guests_non_oso_roles_and_gated_oso(): void
    {
        $existingOrg = $this->organization();
        $pendingSo = $this->officer('so', $existingOrg);
        $pendingSo->update(['must_change_password' => true]);
        $actors = array_map(
            fn (string $role): OfficeUser => $this->officer($role, $role === 'so' ? $existingOrg : null),
            ['so', 'sdo', 'ovcaa', 'oc']
        );
        $gatedOso = $this->officer('oso');
        $gatedOso->update(['must_change_password' => true]);

        $payload = $this->payload();
        $orgCount = StudentOrganization::count();
        $userCount = OfficeUser::count();

        $this->postJson(route('office.organizations.accounts.store'), $payload)->assertUnauthorized();
        $this->postJson(route('office.organizations.accounts.retry', $pendingSo))->assertUnauthorized();

        foreach ($actors as $actor) {
            $this->actingAs($actor, 'office');
            $this->postJson(route('office.organizations.accounts.store'), $payload)->assertForbidden();
            $this->postJson(route('office.organizations.accounts.retry', $pendingSo))->assertForbidden();
        }

        $this->actingAs($gatedOso, 'office');
        $this->postJson(route('office.organizations.accounts.store'), $payload)->assertStatus(423);
        $this->postJson(route('office.organizations.accounts.retry', $pendingSo))->assertStatus(423);

        $this->assertSame($orgCount, StudentOrganization::count());
        $this->assertSame($userCount, OfficeUser::count());
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_validation_enforces_uniqueness_domain_rules_and_atomic_rollback_without_sending_email(): void
    {
        $existingOrg = $this->organization();
        $existingUser = $this->officer('so', $existingOrg);
        $this->actingAs($this->officer('oso'), 'office');

        $orgCount = StudentOrganization::count();
        $userCount = OfficeUser::count();

        // Duplicate organization name (case-insensitive + whitespace)
        $this->postJson(route('office.organizations.accounts.store'), $this->payload([
            'organization_name' => '  '.strtoupper($existingOrg->name).'  ',
        ]))->assertUnprocessable()->assertJsonValidationErrors('organization_name');

        // Duplicate officer email (must not leave an orphan StudentOrganization row)
        $this->postJson(route('office.organizations.accounts.store'), $this->payload([
            'email' => '  '.strtoupper($existingUser->email).'  ',
        ]))->assertUnprocessable()->assertJsonValidationErrors('email');

        // Outside email domain rejected for SO
        $this->postJson(route('office.organizations.accounts.store'), $this->payload([
            'email' => 'outside-officer@yahoo.com',
        ]))->assertUnprocessable()->assertJsonValidationErrors('email');

        // Missing required fields
        $this->postJson(route('office.organizations.accounts.store'), [
            'organization_name' => '',
            'college' => '',
            'officer_name' => '',
            'email' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'organization_name',
            'college',
            'officer_name',
            'email',
        ]);

        // Prohibited privilege / recognition / manual password fields
        foreach ([
            ['office_role' => 'oso'],
            ['tosa_clearance' => 'Level 3 Master'],
            ['academic_year' => '2026-2027'],
            ['password' => 'ManualPass@2026!'],
        ] as $prohibited) {
            $this->postJson(route('office.organizations.accounts.store'), $this->payload() + $prohibited)
                ->assertUnprocessable();
        }

        $this->assertSame($orgCount, StudentOrganization::count());
        $this->assertSame($userCount, OfficeUser::count());
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_success_creates_org_and_so_account_emails_credentials_never_fabricates_recognition_and_requires_password_change(): void
    {
        $oso = $this->officer('oso');
        $this->actingAs($oso, 'office');

        $overviewBefore = $this->get(route('office.home'))->assertOk()->viewData('osoOverview');

        $payload = $this->payload([
            'email' => 'new.org.'.Str::lower(Str::random(10)).'@gmail.com',
        ]);

        $response = $this->postJson(route('office.organizations.accounts.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('created', true)
            ->assertJsonPath('email_sent', true)
            ->assertJsonPath('organization.name', $payload['organization_name'])
            ->assertJsonPath('organization.short_name', $payload['short_name'])
            ->assertJsonPath('organization.college', $payload['college'])
            ->assertJsonPath('organization.academic_year', '')
            ->assertJsonPath('organization.is_active', true)
            ->assertJsonPath('user.name', $payload['officer_name'])
            ->assertJsonPath('user.email', $payload['email'])
            ->assertJsonPath('user.office_role', 'so')
            ->assertJsonPath('user.tosa_clearance', 'No Access')
            ->assertJsonPath('user.must_change_password', true)
            ->assertJsonPath('user.is_active', true);

        $organization = StudentOrganization::findOrFail($response->json('organization.id'));
        $user = OfficeUser::findOrFail($response->json('user.id'));

        // New organizations must not have fabricated recognition for any academic year
        $this->assertSame('', (string) $organization->academic_year);
        $this->assertFalse(StudentOrganization::forAcademicYear('2025-2026')->whereKey($organization->id)->exists());
        $this->assertFalse(StudentOrganization::forAcademicYear(now()->year.'-'.(now()->year + 1))->whereKey($organization->id)->exists());
        $overviewAfter = $this->get(route('office.home'))->assertOk()->viewData('osoOverview');
        $this->assertSame($overviewBefore['kpis']['totalOrgs'], $overviewAfter['kpis']['totalOrgs']);
        $this->assertFalse(
            collect($overviewAfter['recognitionRows'])->contains('organization_key', mb_strtolower($organization->name))
        );
        $renewalOrgs = collect($this->get(route('office.renewal'))->assertOk()->viewData('allOrganizations'));
        $renewalRow = $renewalOrgs->firstWhere('id', $organization->id);
        $this->assertNotNull($renewalRow);
        $this->assertFalse($renewalRow['officially_active']);

        // Response must never expose credentials
        $rawContent = $response->getContent();
        $temporaryPassword = $this->lastMailedPassword();
        $this->assertGreaterThanOrEqual(12, strlen($temporaryPassword));
        $this->assertStringNotContainsString($temporaryPassword, $rawContent);
        $this->assertStringNotContainsString($user->password, $rawContent);
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('user'));
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));

        // Verify email recipient and content
        $mail = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame($user->email, $mail->getTo()[0]->getAddress());
        $this->assertStringContainsString($organization->name, (string) $mail->getTextBody());

        // First login with emailed temporary password requires password change
        $this->post('/office/logout');
        $this->post(route('office.login'), [
            'email' => $user->email,
            'password' => $temporaryPassword,
        ])->assertRedirect(route('office.password.change'));
        $this->assertAuthenticatedAs($user, 'office');
        $this->get('/office-desk')->assertRedirect(route('office.password.change'));
        $this->getJson('/office-desk/analytics')->assertStatus(423);

        $this->post(route('office.password.complete'), [
            'current_password' => $temporaryPassword,
            'new_password' => 'UpdatedOfficer@2026!',
            'new_password_confirmation' => 'UpdatedOfficer@2026!',
        ])->assertRedirect(route('office.home'));

        $this->assertFalse($user->refresh()->must_change_password);

        // Once the officer has changed their password, onboarding credential retry is disallowed
        $this->actingAs($oso, 'office')
            ->postJson(route('office.organizations.accounts.retry', $user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
    }

    public function test_log_mailer_and_smtp_failure_never_falsely_claim_delivery_never_log_credentials_and_allow_safe_retry(): void
    {
        $oso = $this->officer('oso');
        $this->actingAs($oso, 'office');

        // 1. Log mailer must never be used to falsely claim email sent or write passwords to logs
        config(['mail.default' => 'log']);
        $logPayload = $this->payload();
        $logResponse = $this->postJson(route('office.organizations.accounts.store'), $logPayload)
            ->assertCreated()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('created', true)
            ->assertJsonPath('email_sent', false);

        $logUser = OfficeUser::findOrFail($logResponse->json('user.id'));
        $this->assertTrue($logUser->must_change_password);
        $this->assertSame('', (string) StudentOrganization::findOrFail($logResponse->json('organization.id'))->academic_year);

        // 2. Simulated SMTP transport failure during creation does not fail silently or log credentials
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.invalid',
        ]);
        Log::spy();
        Mail::shouldReceive('raw')->twice()->andThrow(new \RuntimeException('Simulated SMTP failure'));

        $failPayload = $this->payload();
        $failResponse = $this->postJson(route('office.organizations.accounts.store'), $failPayload)
            ->assertCreated()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('created', true)
            ->assertJsonPath('email_sent', false);

        $user = OfficeUser::findOrFail($failResponse->json('user.id'));
        $firstHash = $user->password;

        // First retry while SMTP is still failing returns 502 and rotates the temporary password hash
        $this->postJson(route('office.organizations.accounts.retry', $user))
            ->assertStatus(502)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('email_sent', false);

        $secondHash = $user->refresh()->password;
        $this->assertNotSame($firstHash, $secondHash);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $serialized = json_encode($context);

            return ! str_contains(strtolower($serialized), 'password');
        });

        // 3. Concurrent retry while another delivery holds the per-account lock is rejected without rotating password
        Mail::swap($this->mailManager);
        config(['mail.default' => 'array']);
        Mail::mailer('array')->getSymfonyTransport()->flush();

        $lock = Cache::lock('office_org_credentials_mail:'.$user->id, 30);
        $this->assertTrue($lock->get());
        try {
            $this->postJson(route('office.organizations.accounts.retry', $user))
                ->assertStatus(409)
                ->assertJsonPath('ok', false)
                ->assertJsonPath('email_sent', false);
            $this->assertSame($secondHash, $user->refresh()->password);
            $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
        } finally {
            $lock->release();
        }

        // 4. When mail delivery recovers, retry sends a fresh temporary password and never exposes it in JSON
        $retryResponse = $this->postJson(route('office.organizations.accounts.retry', $user))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('email_sent', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.must_change_password', true);

        $newPassword = $this->lastMailedPassword();
        $this->assertStringNotContainsString($newPassword, $retryResponse->getContent());
        $this->assertNotSame($secondHash, $user->refresh()->password);
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // Officer can log in with the retried temporary password
        $this->post('/office/logout');
        $this->post(route('office.login'), [
            'email' => $user->email,
            'password' => $newPassword,
        ])->assertRedirect(route('office.password.change'));
        $this->assertAuthenticatedAs($user, 'office');
    }
}

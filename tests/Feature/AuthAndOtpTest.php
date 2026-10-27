<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use App\Models\UserAccount;
use App\Services\TosaRequirements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthAndOtpTest extends TestCase
{
    private OfficeUser $officer;
    private UserAccount $student;

    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory, 'cache.default' => 'array', 'mail.default' => 'array']);
        DB::purge('mysql');
        DB::purge('voting');
        Schema::connection('mysql')->create('office_templates', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'category', 'original_name', 'file_path', 'size'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::connection('mysql')->create('student_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'username', 'password', 'office_role', 'office_title', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->unsignedBigInteger('student_organization_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('auth_version')->default(0);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('system_admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('password');
            $table->boolean('is_active')->default(true);
        });
        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table) {
            $table->increments('user_id');
            foreach (['sr_code', 'full_name', 'email', 'college', 'program', 'year_level', 'role', 'account_status', 'password_hash', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
        });
        $organization = StudentOrganization::create(['name' => 'Synthetic Authentication Organization', 'is_active' => true]);
        $this->officer = OfficeUser::create(['name' => 'Synthetic Officer', 'email' => 'auth-fixture@g.batstate-u.edu.ph', 'password' => 'FixtureOfficeOnly@2026!', 'office_role' => 'so', 'student_organization_id' => $organization->id, 'is_active' => true]);
        $this->student = UserAccount::create(['sr_code' => '99-90001', 'full_name' => 'Synthetic Student', 'email' => 'auth-student@g.batstate-u.edu.ph', 'role' => 'student', 'account_status' => 'active']);
    }

    private function mailedCode(): string
    {
        $message = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        preg_match('/verification code is: (\d{6})/', (string) $message->getTextBody(), $matches);

        return $matches[1];
    }

    public function test_office_login_page_renders(): void
    {
        $this->get(route('office.login'))->assertOk();
    }

    public function test_office_login_rejects_non_batstate_domain(): void
    {
        $this->post(route('office.login'), ['email' => 'someone@example.invalid', 'password' => 'FixtureOfficeOnly@2026!'])->assertSessionHasErrors('email');
        $this->assertGuest('office');
    }

    public function test_office_login_rejects_bad_password(): void
    {
        $this->post(route('office.login'), ['email' => $this->officer->email, 'password' => 'WrongPassword1!'])->assertSessionHasErrors('email');
        $this->assertGuest('office');
    }

    public function test_office_login_and_logout_succeeds(): void
    {
        $this->post(route('office.login'), ['email' => $this->officer->email, 'password' => 'FixtureOfficeOnly@2026!'])->assertRedirect(route('office.home'));
        $this->assertAuthenticatedAs($this->officer, 'office');
        $this->post('/office/logout')->assertRedirect('/');
        $this->assertGuest('office');
    }

    public function test_student_otp_unknown_and_active_accounts_have_identical_public_responses_and_no_code(): void
    {
        // Even a leftover deployment flag must not restore the old bypass.
        config(['orgchain.expose_test_otp' => true]);
        $active = $this->postJson('/student/login/code', ['sr_code' => $this->student->sr_code])->assertOk();
        $code = $this->mailedCode();
        $unknown = $this->postJson('/student/login/code', ['sr_code' => '99-99999'])->assertOk();
        $this->assertSame($active->json(), $unknown->json());
        $this->assertStringNotContainsString($code, $active->getContent());
        $this->assertStringNotContainsString($this->student->email, $active->getContent());
        $this->assertArrayNotHasKey('debug_code', $active->json());
        $this->assertNull(session('_testing_otp_plain'));
        $this->assertNull(cache('auth_e2e_otp:'.$this->student->sr_code));
        $this->assertGuest('student');
    }

    public function test_student_otp_rejects_invalid_sr_format(): void
    {
        $this->postJson('/student/login/code', ['sr_code' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('sr_code');
    }

    public function test_student_otp_send_and_verify_logs_in_using_only_mailed_code(): void
    {
        $this->post('/student/login/code', ['sr_code' => $this->student->sr_code])->assertSessionHas('code_sent');
        $this->post('/student/login/verify', ['sr_code' => $this->student->sr_code, 'code' => $this->mailedCode()])->assertRedirect(route('portal.home'));
        $this->assertAuthenticatedAs($this->student, 'student');
        $this->assertNull(session('student_login_code'));
    }

    public function test_student_sign_in_syncs_college_from_separate_voting_roster(): void
    {
        $this->student->forceFill(['college' => 'Previous College'])->save();
        Schema::connection('voting')->create('voters', function (Blueprint $table): void {
            $table->id();
            $table->string('sr_code');
            $table->string('college');
        });
        DB::connection('voting')->table('voters')->insert([
            'sr_code' => $this->student->sr_code,
            'college' => 'College of Informatics and Computing Sciences',
        ]);

        $this->post('/student/login/code', ['sr_code' => $this->student->sr_code])->assertSessionHas('code_sent');
        $this->post('/student/login/verify', ['sr_code' => $this->student->sr_code, 'code' => $this->mailedCode()])
            ->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs($this->student, 'student');
        $this->assertSame('College of Informatics and Computing Sciences', $this->student->fresh()->college);
    }

    public function test_student_otp_wrong_code_is_rejected_and_locks_after_five_failures(): void
    {
        $this->post('/student/login/code', ['sr_code' => $this->student->sr_code]);
        $wrong = $this->mailedCode() === '000000' ? '000001' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/student/login/verify', ['sr_code' => $this->student->sr_code, 'code' => $wrong])->assertSessionHasErrors('code');
            $this->assertGuest('student');
        }
        $this->assertNull(session('student_login_code'));
    }

    public function test_student_code_requests_are_limited_across_sessions_and_ip_addresses(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($i + 1)])->postJson('/student/login/code', ['sr_code' => $this->student->sr_code])->assertOk();
        }
        $before = Mail::mailer('array')->getSymfonyTransport()->messages()->count();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->postJson('/student/login/code', ['sr_code' => $this->student->sr_code])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertSame($before, Mail::mailer('array')->getSymfonyTransport()->messages()->count());
    }

    public function test_office_and_system_admin_logins_limit_normalized_account_attempts(): void
    {
        foreach (['office.login', 'system-admin.login'] as $route) {
            for ($i = 0; $i < 5; $i++) {
                $this->postJson(route($route), ['email' => $i % 2 ? strtoupper($this->officer->email) : $this->officer->email, 'password' => 'WrongPassword1!'])->assertSessionHasErrors('email');
            }
            $this->postJson(route($route), ['email' => $this->officer->email, 'password' => 'WrongPassword1!'])->assertTooManyRequests();
            $this->get(route($route))->assertOk();
        }
    }

    public function test_student_portal_requires_auth(): void
    {
        $this->get('/portal')->assertRedirect();
    }

    public function test_authenticated_student_downloads_all_native_templates_and_previews_native_pdf_and_docx(): void
    {
        $this->actingAs($this->student, 'student');
        foreach (TosaRequirements::REFERENCES as $key => $document) {
            $download = $this->get(route('portal.tosa.templates.show', ['key' => $key]))->assertOk()
                ->assertDownload($document['filename'])->assertHeader('Content-Type', TosaRequirements::mimeType($key))
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame(hash_file('sha256', base_path('TOSA/'.$document['filename'])),
                hash_file('sha256', $download->baseResponse->getFile()->getPathname()));
            if ($document['format'] === 'DOC') {
                // Converted DOC response lifetime and failures use isolated fixtures in TosaPrivateFilesTest.
                continue;
            }
            $preview = $this->get(route('portal.tosa.templates.show', ['key' => $key, 'preview' => 1]))->assertOk();
            if ($document['format'] === 'PDF') {
                $this->assertStringStartsWith('inline;', $preview->headers->get('Content-Disposition'));
            } else {
                $preview->assertDownload($document['filename']);
            }
            $this->assertTrue($download->headers->hasCacheControlDirective('private'));
            $this->assertTrue($download->headers->hasCacheControlDirective('no-store'));
        }
        $this->get(route('portal.tosa.templates.show', ['key' => 'application_docs']))->assertNotFound();
        $this->get('/portal/tosa/template')->assertNotFound();
    }

    public function test_google_student_login_unconfigured_returns_to_home(): void
    {
        config(['voting.google.client_id' => '', 'voting.google.client_secret' => '']);
        $this->get('/student/auth/google')->assertRedirect('/');
    }
}

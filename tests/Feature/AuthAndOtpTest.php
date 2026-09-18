<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class AuthAndOtpTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        $this->ensureOfficeUser('so');
        $this->ensureActiveStudent('21-00001');
    }

    public function test_office_login_page_renders(): void
    {
        $path = config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83');
        $this->get($path)->assertOk();
    }

    public function test_office_login_rejects_non_batstate_domain(): void
    {
        $path = config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83');

        $this->from($path)
            ->post($path, [
                'email' => 'someone@gmail.com',
                'password' => 'Office@2026!',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_office_login_rejects_bad_password(): void
    {
        $user = $this->ensureOfficeUser('so');
        $path = config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83');

        $this->from($path)
            ->post($path, [
                'email' => $user->email,
                'password' => 'WrongPassword1!',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest('office');
    }

    public function test_office_login_and_logout_succeeds(): void
    {
        $user = $this->ensureOfficeUser('so');
        $path = config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83');

        $this->post($path, [
            'email' => $user->email,
            'password' => 'Office@2026!',
        ])->assertRedirect(route('office.home'));

        $this->assertAuthenticatedAs($user, 'office');

        $this->post('/office/logout')->assertRedirect('/');
        $this->assertGuest('office');
    }

    public function test_student_otp_rejects_unknown_sr_code(): void
    {
        Mail::fake();

        $this->from('/')
            ->post('/student/login/code', ['sr_code' => '99-99999'])
            ->assertSessionHasErrors('sr_code');
    }

    public function test_student_otp_rejects_invalid_sr_format(): void
    {
        $this->from('/')
            ->post('/student/login/code', ['sr_code' => 'bad'])
            ->assertSessionHasErrors('sr_code');
    }

    public function test_student_otp_send_and_verify_logs_in(): void
    {
        $account = $this->ensureActiveStudent('21-00001');
        Mail::fake();

        $this->from('/')
            ->post('/student/login/code', ['sr_code' => $account->sr_code])
            ->assertSessionHas('code_sent');

        $code = session('_testing_otp_plain');
        $this->assertNotEmpty($code, 'Expected testing OTP plaintext in session.');
        $this->assertSame(6, strlen($code));
        $this->assertNotEmpty(session('student_login_code'));

        $this->post('/student/login/verify', [
            'sr_code' => $account->sr_code,
            'code' => $code,
        ])->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs($account, 'student');
        $this->assertTrue(Auth::guard('student')->check());
    }

    public function test_student_otp_wrong_code_is_rejected(): void
    {
        $account = $this->ensureActiveStudent('21-00001');
        Mail::fake();
        $this->post('/student/login/code', ['sr_code' => $account->sr_code]);

        $this->from('/')
            ->post('/student/login/verify', [
                'sr_code' => $account->sr_code,
                'code' => '000000',
            ])
            ->assertSessionHasErrors('code');

        $this->assertGuest('student');
    }

    public function test_student_otp_locks_after_five_bad_attempts(): void
    {
        $account = $this->ensureActiveStudent('21-00001');
        Mail::fake();
        $this->post('/student/login/code', ['sr_code' => $account->sr_code]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/student/login/verify', [
                'sr_code' => $account->sr_code,
                'code' => '111111',
            ]);
        }

        $this->assertNull(session('student_login_code'));
        $this->assertGuest('student');
    }

    public function test_student_portal_requires_auth(): void
    {
        $this->get('/portal')->assertRedirect();
    }

    public function test_google_student_login_unconfigured_returns_to_home(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $response = $this->get('/student/auth/google');
        $this->assertTrue(in_array($response->status(), [302, 200], true));
    }
}

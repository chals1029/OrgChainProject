<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use App\VotingSystem\Core\GoogleOAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StudentAuthController extends Controller
{
    /**
     * Step 1 — look up the SR Code and email a 6-digit verification code.
     */
    public function sendCode(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'sr_code' => ['required', 'string', 'regex:/^\d{2}-\d{5}$/'],
        ], [
            'sr_code.required' => 'Please enter your SR Code.',
            'sr_code.regex' => 'SR Code must look like 21-12345.',
        ]);

        $student = UserAccount::query()
            ->where('sr_code', $validated['sr_code'])
            ->where('account_status', 'active')
            ->first();

        if (! $student) {
            return $this->codeStepBack($request, [
                'sr_code' => 'No active student found with this SR Code.',
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('student_login_code', [
            'sr_code' => $student->sr_code,
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + 600, // 10 minutes
            'attempts' => 0,
        ]);

        // Mask the email so the user knows where to look without exposing it fully.
        $maskedEmail = $this->maskEmail($student->email);

        try {
            Mail::raw(
                "Your OrgChain verification code is: {$code}\n\nThis code expires in 10 minutes.",
                function ($message) use ($student): void {
                    $message->to($student->email)->subject('OrgChain Login Verification Code');
                }
            );
        } catch (Throwable $e) {
            report($e);
            // Even if mail fails, keep going — the code is in the log for dev.
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'email' => $maskedEmail,
            ]);
        }

        return $this->codeStepBack($request, [], [
            'code_sent' => true,
            'code_email' => $maskedEmail,
            'code_sr' => $student->sr_code,
        ]);
    }

    /**
     * Step 2 — verify the code and log the student in.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sr_code' => ['required', 'string', 'regex:/^\d{2}-\d{5}$/'],
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Enter the 6-digit code we sent you.',
            'code.size' => 'The code must be exactly 6 digits.',
        ]);

        $stored = $request->session()->get('student_login_code');

        if (! $stored || ($stored['sr_code'] ?? null) !== $validated['sr_code']) {
            return $this->codeStepBack($request, [
                'code' => 'Please request a new code.',
            ], ['code_sent' => true, 'code_sr' => $validated['sr_code']]);
        }

        if (($stored['expires_at'] ?? 0) < time()) {
            $request->session()->forget('student_login_code');

            return $this->codeStepBack($request, [
                'code' => 'Code expired. Please request a new one.',
            ]);
        }

        if (! password_verify($validated['code'], (string) ($stored['code'] ?? ''))) {
            $stored['attempts'] = (int) ($stored['attempts'] ?? 0) + 1;
            $request->session()->put('student_login_code', $stored);

            if ($stored['attempts'] >= 5) {
                $request->session()->forget('student_login_code');

                return $this->codeStepBack($request, [
                    'code' => 'Too many wrong attempts. Please request a new code.',
                ]);
            }

            $left = 5 - $stored['attempts'];

            return $this->codeStepBack($request, [
                'code' => "Invalid code. {$left} attempt".($left === 1 ? '' : 's').' left.',
            ], ['code_sent' => true, 'code_email' => $request->session()->get('code_email'), 'code_sr' => $validated['sr_code']]);
        }

        $student = UserAccount::query()
            ->where('sr_code', $validated['sr_code'])
            ->where('account_status', 'active')
            ->first();

        if (! $student) {
            $request->session()->forget('student_login_code');

            return $this->codeStepBack($request, [
                'code' => 'Account is no longer active.',
            ]);
        }

        $request->session()->forget('student_login_code');
        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.home'));
    }

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $google = $this->studentGoogleClient();

        if (! $google->isConfigured()) {
            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Institutional sign-in is not configured yet. Please use your SR Code and a verification code.',
                ]);
        }

        $state = bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));

        $request->session()->put([
            'student_google_oauth_state' => $state,
            'student_google_oauth_nonce' => $nonce,
            'student_google_oauth_started_at' => time(),
        ]);

        return redirect()->away($google->authorizationUrl($state, $nonce));
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            $this->clearStudentGoogleOAuthState($request);

            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Google sign-in was cancelled or denied.',
                ]);
        }

        $expectedState = (string) $request->session()->get('student_google_oauth_state', '');
        $state = (string) $request->query('state', '');
        $expectedNonce = (string) $request->session()->get('student_google_oauth_nonce', '');

        if ($expectedState === '' || $state === '' || ! hash_equals($expectedState, $state)) {
            $this->clearStudentGoogleOAuthState($request);

            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Google sign-in could not be verified. Please try again.',
                ]);
        }

        $code = (string) $request->query('code', '');
        $this->clearStudentGoogleOAuthState($request);

        if ($code === '') {
            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Google did not return a sign-in code. Please try again.',
                ]);
        }

        try {
            $profile = $this->studentGoogleClient()->userFromCode($code, $expectedNonce);
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Google sign-in failed. Please try again or use your SR Code and a verification code.',
                ]);
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));

        if (! ($profile['email_verified'] ?? false)) {
            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'Your Google email is not verified.',
                ]);
        }

        if (! $this->isAllowedSchoolEmail($email)) {
            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => $this->schoolEmailErrorMessage(),
                ]);
        }

        $student = UserAccount::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('account_status', 'active')
            ->first();

        if (! $student) {
            return redirect('/')
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'We could not match your institutional email with a registered student account.',
                ]);
        }

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function studentGoogleClient(): GoogleOAuthClient
    {
        return new GoogleOAuthClient(route('student.auth.google.callback', absolute: true));
    }

    private function isAllowedSchoolEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        $domain = $this->allowedSchoolEmailDomain();

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && $domain !== ''
            && str_ends_with($email, '@'.$domain);
    }

    private function allowedSchoolEmailDomain(): string
    {
        return ltrim(strtolower(trim((string) config('voting.google.allowed_domain', 'g.batstate-u.edu.ph'))), '@');
    }

    private function schoolEmailErrorMessage(): string
    {
        return 'Please use your official BatStateU Google Workspace email (@'.$this->allowedSchoolEmailDomain().').';
    }

    private function clearStudentGoogleOAuthState(Request $request): void
    {
        $request->session()->forget([
            'student_google_oauth_state',
            'student_google_oauth_nonce',
            'student_google_oauth_started_at',
        ]);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2 || $parts[0] === '') {
            return $email;
        }
        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);
        if ($len <= 2) {
            $masked = $name[0].'*';
        } else {
            $masked = substr($name, 0, 2).str_repeat('*', min($len - 2, 4));
        }

        return $masked.'@'.$domain;
    }

    private function codeStepBack(Request $request, array $errors = [], array $with = []): RedirectResponse
    {
        return back()
            ->withInput($request->only('sr_code'))
            ->with('login', true)
            ->withErrors($errors)
            ->with($with);
    }
}

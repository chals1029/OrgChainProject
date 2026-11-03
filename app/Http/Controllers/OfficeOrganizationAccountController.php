<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeOrganizationAccountController extends Controller
{
    private function officer(): OfficeUser
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);

        return $office;
    }

    private function invalid(array $errors): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'Please correct the highlighted organization account details.',
            'errors' => $errors,
        ], 422);
    }

    private function normalizeInput(Request $request): array
    {
        $shortName = trim((string) $request->input('short_name', ''));
        $officeTitle = trim((string) $request->input('office_title', ''));
        $employeeId = trim((string) $request->input('employee_id', ''));

        $input = $request->all();
        $input['organization_name'] = trim((string) $request->input('organization_name', ''));
        $input['short_name'] = $shortName === '' ? null : $shortName;
        $input['college'] = trim((string) $request->input('college', ''));
        $input['officer_name'] = trim((string) $request->input('officer_name', ''));
        $input['email'] = strtolower(trim((string) $request->input('email', '')));

        if ($request->has('office_title')) {
            $input['office_title'] = $officeTitle === '' ? null : $officeTitle;
        }
        if ($request->has('employee_id')) {
            $input['employee_id'] = $employeeId === '' ? null : $employeeId;
        }

        return $input;
    }

    private function rules(): array
    {
        return [
            'organization_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('student_organizations', 'name'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = mb_strtolower(trim((string) $value));
                    if ($normalized !== '' && StudentOrganization::query()->whereRaw('LOWER(name) = ?', [$normalized])->exists()) {
                        $fail('This student organization is already registered.');
                    }
                },
            ],
            'short_name' => ['nullable', 'string', 'max:64'],
            'college' => ['required', 'string', 'max:255'],
            'officer_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('office_users', 'email'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! OfficeUser::allowsEmailForRole((string) $value, 'so')) {
                        $fail('Use a BatStateU email or an organization Gmail address.');
                    }
                },
            ],
            'office_title' => ['nullable', 'string', 'max:160'],
            'employee_id' => ['nullable', 'string', 'max:80'],
            'office_role' => ['prohibited'],
            'tosa_clearance' => ['prohibited'],
            'academic_year' => ['prohibited'],
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $office = $this->officer();
        $input = $this->normalizeInput($request);
        $validator = Validator::make($input, $this->rules());
        if ($validator->fails()) {
            return $this->invalid($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $temporaryPassword = $this->generateTemporaryPassword();

        try {
            [$organization, $user, $expectedVersion] = DB::transaction(function () use ($office, $data, $temporaryPassword): array {
                $actor = OfficeUser::query()->lockForUpdate()->findOrFail($office->id);
                abort_unless($actor->is_active && $actor->office_role === 'oso'
                    && (int) $actor->auth_version === (int) $office->auth_version, 403);

                if (StudentOrganization::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['organization_name'])])
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'organization_name' => ['This student organization is already registered.'],
                    ]);
                }

                if (OfficeUser::query()
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'email' => ['This email address already belongs to an office account.'],
                    ]);
                }

                $organization = StudentOrganization::query()->create([
                    'name' => $data['organization_name'],
                    'short_name' => $data['short_name'] ?? null,
                    'college' => $data['college'],
                    'academic_year' => '',
                    'is_active' => true,
                    'is_qualified_for_renewal' => true,
                ]);

                $username = substr(Str::slug(Str::before($data['email'], '@'), '_') ?: 'office_user', 0, 27).'_'.Str::uuid();

                $user = OfficeUser::query()->create([
                    'name' => $data['officer_name'],
                    'email' => $data['email'],
                    'username' => $username,
                    'password' => $temporaryPassword,
                    'office_role' => 'so',
                    'office_title' => $data['office_title'] ?? 'Student Organization Officer',
                    'employee_id' => $data['employee_id'] ?? null,
                    'student_organization_id' => $organization->id,
                    'tosa_clearance' => 'No Access',
                    'is_active' => true,
                    'must_change_password' => true,
                ]);

                $user->setRelation('studentOrganization', $organization);

                return [$organization, $user, (int) $user->auth_version];
            }, 3);
        } catch (ValidationException $exception) {
            return $this->invalid($exception->errors());
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) {
                $message = strtolower($exception->getMessage());
                if (str_contains($message, 'student_organizations') || str_contains($message, 'organizations.name')) {
                    return $this->invalid(['organization_name' => ['This student organization is already registered.']]);
                }
                if (str_contains($message, 'email')) {
                    return $this->invalid(['email' => ['This email address already belongs to an office account.']]);
                }
            }

            throw $exception;
        }

        $lock = Cache::lock($this->deliveryLockKey($user), 30);
        $acquired = $lock->get();
        try {
            $emailSent = $acquired && $this->sendCredentialsEmail($user, $organization, $temporaryPassword, $expectedVersion);
        } finally {
            if ($acquired) {
                $lock->release();
            }
        }

        return response()->json([
            'ok' => $emailSent,
            'created' => true,
            'email_sent' => $emailSent,
            'message' => $emailSent
                ? "Organization {$organization->name} and officer account for {$user->name} created. Temporary login credentials were emailed to {$user->email}."
                : 'Organization and officer account were created, but the credential email could not be delivered. Check mail settings and use Retry to send a new temporary password.',
            'organization' => $this->organizationMetadata($organization),
            'user' => $user->accountMetadata(),
            'retry_url' => route('office.organizations.accounts.retry', $user),
        ], 201);
    }

    public function retryEmail(Request $request, OfficeUser $user): JsonResponse
    {
        $office = $this->officer();
        $lock = Cache::lock($this->deliveryLockKey($user), 30);
        if (! $lock->get()) {
            return response()->json([
                'ok' => false,
                'email_sent' => false,
                'message' => 'A credential email delivery is already in progress for this account.',
                'retry_url' => route('office.organizations.accounts.retry', $user),
            ], 409);
        }

        try {
            $temporaryPassword = $this->generateTemporaryPassword();

            try {
                [$organization, $target, $expectedVersion] = DB::transaction(function () use ($office, $user, $temporaryPassword): array {
                    $actor = OfficeUser::query()->lockForUpdate()->findOrFail($office->id);
                    abort_unless($actor->is_active && $actor->office_role === 'oso'
                        && (int) $actor->auth_version === (int) $office->auth_version, 403);

                    $target = OfficeUser::query()->lockForUpdate()->findOrFail($user->id);
                    if ((int) $target->id === (int) $actor->id) {
                        throw ValidationException::withMessages([
                            'user' => ['Use your own account profile and password settings instead.'],
                        ]);
                    }

                    if ($target->office_role !== 'so' || ! $target->is_active || ! $target->student_organization_id) {
                        throw ValidationException::withMessages([
                            'user' => ['Credential email retry requires an active Student Organization officer account.'],
                        ]);
                    }

                    if (! $target->must_change_password) {
                        throw ValidationException::withMessages([
                            'user' => ['This officer has already completed their first-login password change.'],
                        ]);
                    }

                    $organization = StudentOrganization::query()->lockForUpdate()->find($target->student_organization_id);
                    if (! $organization || ! $organization->is_active) {
                        throw ValidationException::withMessages([
                            'user' => ['The officer account must be linked to an active registered organization.'],
                        ]);
                    }

                    if (! OfficeUser::allowsEmailForRole((string) $target->email, 'so')) {
                        throw ValidationException::withMessages([
                            'email' => ['Use a BatStateU email or an organization Gmail address.'],
                        ]);
                    }

                    $target->password = $temporaryPassword;
                    $target->must_change_password = true;
                    $target->revokeCredentials();
                    $target->save();
                    $target->setRelation('studentOrganization', $organization);

                    return [$organization, $target, (int) $target->auth_version];
                }, 3);
            } catch (ValidationException $exception) {
                return $this->invalid($exception->errors());
            }

            $emailSent = $this->sendCredentialsEmail($target, $organization, $temporaryPassword, $expectedVersion);

            return response()->json([
                'ok' => $emailSent,
                'email_sent' => $emailSent,
                'message' => $emailSent
                    ? "Temporary login credentials were emailed to {$target->email}."
                    : 'Credential email could not be delivered. Check mail settings and try again.',
                'user' => $target->accountMetadata(),
                'retry_url' => route('office.organizations.accounts.retry', $target),
            ], $emailSent ? 200 : 502);
        } finally {
            $lock->release();
        }
    }

    private function deliveryLockKey(OfficeUser $user): string
    {
        return 'office_org_credentials_mail:'.$user->id;
    }

    private function organizationMetadata(StudentOrganization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'short_name' => $organization->short_name,
            'college' => $organization->college,
            'academic_year' => (string) $organization->academic_year,
            'is_active' => (bool) $organization->is_active,
        ];
    }

    private function generateTemporaryPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%&*';
        $all = $upper.$lower.$digits.$symbols;

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = 0; $i < 12; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    private function canDeliverCredentialsMail(): bool
    {
        $default = strtolower(trim((string) config('mail.default', '')));
        $transport = strtolower(trim((string) config("mail.mailers.{$default}.transport", $default)));

        if ($transport === 'array') {
            return app()->environment('testing');
        }

        if ($transport === 'smtp') {
            $host = trim((string) config("mail.mailers.{$default}.host", ''));
            $url = trim((string) config("mail.mailers.{$default}.url", ''));
            $from = trim((string) config('mail.from.address', ''));

            return ($host !== '' || $url !== '') && filter_var($from, FILTER_VALIDATE_EMAIL) !== false;
        }

        return in_array($transport, ['ses', 'ses-v2', 'postmark', 'resend', 'sendmail'], true);
    }

    private function sendCredentialsEmail(
        OfficeUser $user,
        StudentOrganization $organization,
        string $temporaryPassword,
        int $expectedVersion
    ): bool {
        if (! $this->canDeliverCredentialsMail()) {
            Log::warning('Organization officer credential email skipped: mail transport is not configured for delivery.', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'mailer' => (string) config('mail.default', ''),
            ]);

            return false;
        }

        $current = OfficeUser::query()->whereKey($user->id)->first(['id', 'auth_version', 'is_active', 'must_change_password']);
        if (! $current || ! $current->is_active || ! $current->must_change_password || (int) $current->auth_version !== $expectedVersion) {
            return false;
        }

        $loginUrl = route('office.login');
        $subject = 'OrgChain Student Organization Officer Account Credentials';
        $body = "Hello {$user->name},\n\n"
            ."An Officer account for {$organization->name} ({$organization->college}) has been created for you in OrgChain by the Office of Student Organizations (OSO).\n\n"
            ."Login URL: {$loginUrl}\n"
            ."Email: {$user->email}\n"
            ."Temporary Password: {$temporaryPassword}\n\n"
            ."For security, you will be required to change this temporary password immediately upon your first login.\n\n"
            .'— Office of Student Organizations';

        try {
            $sent = Mail::raw($body, static function ($message) use ($user, $subject): void {
                $message->to($user->email)->subject($subject);
            });

            return $sent !== null
                && (int) OfficeUser::query()->whereKey($user->id)->value('auth_version') === $expectedVersion;
        } catch (\Throwable $error) {
            Log::warning('Organization officer credential email delivery failed.', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'exception' => $error::class,
            ]);

            return false;
        }
    }
}

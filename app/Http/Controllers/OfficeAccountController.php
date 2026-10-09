<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeAccountController extends Controller
{
    private const ROLES = ['so', 'oso', 'sdo', 'ovcaa', 'oc'];
    private const CLEARANCES = ['No Access', 'Level 1 Read-only', 'Level 2 Evaluator', 'Level 3 Master'];

    private function officer(): OfficeUser
    {
        $office = Auth::guard('office')->user();
        abort_unless($office instanceof OfficeUser && $office->office_role === 'oso', 403);

        return $office;
    }

    private function emailRules(?OfficeUser $user = null): array
    {
        return [
            'required', 'email', 'max:255', Rule::unique('office_users', 'email')->ignore($user?->id),
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array(Str::afterLast(strtolower((string) $value), '@'), ['g.batstate-u.edu.ph', 'batstate-u.edu.ph'], true)) {
                    $fail('Use your official BatStateU email address.');
                }
            },
        ];
    }

    private function identityRules(?OfficeUser $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules($user),
            'office_title' => ['nullable', 'string', 'max:160'],
            'employee_id' => ['nullable', 'string', 'max:80'],
        ];
    }

    private function perform(Request $request, array $rules, \Closure $action): JsonResponse
    {
        $office = $this->officer();
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->invalid($validator->errors()->toArray());
        }
        try {
            return DB::transaction(function () use ($request, $office, $validator, $action): JsonResponse {
                $actor = OfficeUser::query()->lockForUpdate()->findOrFail($office->id);
                abort_unless($actor->is_active && $actor->office_role === 'oso'
                    && (int) $actor->auth_version === (int) $office->auth_version, 403);

                return $action($validator->validated(), $actor);
            }, 3);
        } catch (ValidationException $exception) {
            return $this->invalid($exception->errors());
        } catch (QueryException $exception) {
            // The unique constraint remains authoritative when two administrators submit the same email.
            if ((string) $exception->getCode() === '23000' && str_contains(strtolower($exception->getMessage()), 'email')) {
                return $this->invalid(['email' => ['This institutional email already belongs to an account.']]);
            }
            throw $exception;
        }
    }

    private function invalid(array $errors): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'Please correct the highlighted account details.', 'errors' => $errors], 422);
    }

    private function target(OfficeUser $user, OfficeUser $actor): OfficeUser
    {
        $target = OfficeUser::query()->lockForUpdate()->findOrFail($user->id);
        abort_unless(in_array($target->office_role, self::ROLES, true), 404);
        if ((int) $target->id === (int) $actor->id) {
            throw ValidationException::withMessages(['user' => 'Use your own account profile and password settings instead.']);
        }

        return $target;
    }

    private function createAccount(array $data): OfficeUser
    {
        $titles = [
            'so' => 'Student Organization Officer', 'oso' => 'OSO Review Officer',
            'sdo' => 'SDO Document Reviewer', 'ovcaa' => 'OVCAA Final Endorser', 'oc' => 'OC Final Approval Officer',
        ];
        $username = substr(Str::slug(Str::before($data['email'], '@'), '_') ?: 'office_user', 0, 27).'_'.Str::uuid();

        return OfficeUser::query()->create([
            'name' => $data['name'], 'email' => $data['email'], 'username' => $username,
            'password' => $data['password'], 'office_role' => $data['office_role'],
            'office_title' => $data['office_title'] ?? $titles[$data['office_role']],
            'employee_id' => $data['employee_id'] ?? null,
            'student_organization_id' => $data['office_role'] === 'so' ? $data['student_organization_id'] : null,
            'tosa_clearance' => $data['tosa_clearance'], 'is_active' => true, 'must_change_password' => true,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->perform($request, $this->identityRules() + [
            'office_role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'student_organization_id' => [Rule::requiredIf($request->input('office_role') === 'so'), 'nullable', 'integer', Rule::exists('student_organizations', 'id')],
            'tosa_clearance' => ['required', Rule::in(self::CLEARANCES)],
        ], function (array $data): JsonResponse {
            $maximum = match ($data['office_role']) { 'oso' => 3, 'ovcaa' => 2, default => 0 };
            if (array_search($data['tosa_clearance'], self::CLEARANCES, true) > $maximum) {
                throw ValidationException::withMessages(['tosa_clearance' => 'Select a clearance supported by this office role.']);
            }
            if ($data['office_role'] === 'so') {
                StudentOrganization::query()->lockForUpdate()->findOrFail($data['student_organization_id']);
            } elseif (! empty($data['student_organization_id'])) {
                throw ValidationException::withMessages(['student_organization_id' => 'Only SO accounts can be assigned an organization.']);
            }
            $user = $this->createAccount($data);

            return response()->json(['ok' => true, 'message' => "Account for {$user->name} created. A password change is required on first login.", 'user' => $user->accountMetadata()], 201);
        });
    }

    public function update(Request $request, OfficeUser $user): JsonResponse
    {
        return $this->perform($request, $this->identityRules($user) + [
            'office_role' => ['prohibited'], 'student_organization_id' => ['prohibited'],
            'password' => ['prohibited'], 'tosa_clearance' => ['prohibited'],
        ], function (array $data, OfficeUser $actor) use ($user): JsonResponse {
            $target = $this->target($user, $actor);
            $target->update($data);

            return response()->json(['ok' => true, 'message' => 'Account details saved.', 'user' => $target->accountMetadata()]);
        });
    }

    public function resetPassword(Request $request, OfficeUser $user): JsonResponse
    {
        return $this->perform($request, ['password' => ['required', 'string', 'min:8', 'confirmed']], function (array $data, OfficeUser $actor) use ($user): JsonResponse {
            $target = $this->target($user, $actor);
            if (Hash::check($data['password'], $target->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a temporary password different from the account’s current password.']);
            }
            $target->password = $data['password'];
            $target->must_change_password = true;
            $target->revokeCredentials();
            $target->save();

            return response()->json(['ok' => true, 'message' => 'Temporary password reset. Previous sessions have been revoked.', 'user' => $target->accountMetadata()]);
        });
    }

    public function status(Request $request, OfficeUser $user): JsonResponse
    {
        return $this->perform($request, ['is_active' => ['required', 'boolean']], function (array $data, OfficeUser $actor) use ($user): JsonResponse {
            $target = $this->target($user, $actor);
            $target->update(['is_active' => (bool) $data['is_active']]);

            return response()->json([
                'ok' => true, 'message' => $target->is_active ? 'Account activated.' : 'Account disabled and previous sessions revoked.',
                'is_active' => (bool) $target->is_active, 'user' => $target->accountMetadata(),
            ]);
        });
    }

    public function turnover(Request $request, OfficeUser $user): JsonResponse
    {
        return $this->perform($request, $this->identityRules() + [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'office_role' => ['prohibited'], 'student_organization_id' => ['prohibited'], 'tosa_clearance' => ['prohibited'],
        ], function (array $data, OfficeUser $actor) use ($user): JsonResponse {
            $outgoing = $this->target($user, $actor);
            if ($outgoing->office_role !== 'so' || ! $outgoing->is_active || ! $outgoing->student_organization_id) {
                throw ValidationException::withMessages(['user' => 'Turnover requires an active SO account linked to a registered organization.']);
            }
            if (Hash::check($data['password'], $outgoing->password)) {
                throw ValidationException::withMessages(['password' => 'The incoming temporary password must differ from the outgoing officer’s password.']);
            }
            $organization = StudentOrganization::query()->lockForUpdate()->find($outgoing->student_organization_id);
            if (! $organization) {
                throw ValidationException::withMessages(['user' => 'The outgoing account is not linked to a registered organization.']);
            }
            $incoming = $this->createAccount($data + [
                'office_role' => 'so', 'student_organization_id' => $organization->id, 'tosa_clearance' => 'No Access',
            ]);
            $outgoing->update(['is_active' => false]);

            return response()->json([
                'ok' => true, 'message' => 'Officer turnover complete. The outgoing account is disabled; organization history is retained.',
                'user' => $incoming->accountMetadata(), 'previous_user' => $outgoing->accountMetadata(),
            ], 201);
        });
    }
}

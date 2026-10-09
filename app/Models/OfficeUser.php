<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OfficeUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'office_role',
        'student_organization_id',
        'office_title',
        'employee_id',
        'tosa_clearance',
        'is_active',
        'must_change_password',
        'auth_version',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'auth_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (OfficeUser $user): void {
            if ($user->isDirty(['password', 'email', 'is_active']) && ! $user->isDirty('auth_version')) {
                $user->revokeCredentials();
            }
        });
    }

    public function revokeCredentials(): void
    {
        $this->auth_version = (int) $this->auth_version + 1;
        $this->setRememberToken(Str::random(60));
    }

    public function bindCurrentSession(Request $request): void
    {
        $guard = Auth::guard('office');
        if ($request->hasCookie($guard->getRecallerName())) {
            $guard->login($this, true);
        } else {
            $guard->setUser($this);
        }
        $request->session()->put('office_auth_state', [
            'id' => (int) $this->id,
            'version' => (int) $this->auth_version,
        ]);
    }

    public function accountMetadata(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'office_role' => $this->office_role,
            'role' => $this->roleLabel(),
            'office_title' => $this->office_title,
            'employee_id' => $this->employee_id,
            'student_organization_id' => $this->student_organization_id,
            'organization_name' => $this->organizationName(),
            'initials' => $this->initials(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'tosa_clearance' => $this->effectiveTosaClearance(),
            'is_active' => (bool) $this->is_active,
            'must_change_password' => (bool) $this->must_change_password,
        ];
    }

    public static function roleLabels(): array
    {
        return [
            'so' => 'Student Organization (SO)',
            'oso' => 'Office of Student Organization (OSO)',
            'sdo' => 'Sustainable Development Office (SDO)',
            'ovcaa' => 'OVCAA',
            'oc' => 'Office of the Chancellor (OC)',
        ];
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->office_role] ?? strtoupper($this->office_role);
    }

    public function tosaClearanceLevel(): int
    {
        if (! in_array($this->office_role, ['oso', 'ovcaa'], true)) {
            return 0;
        }

        $level = match ($this->tosa_clearance) {
            null, '' => $this->office_role === 'oso' ? 3 : 1,
            'Level 1 Read-only' => 1,
            'Level 2 Evaluator' => 2,
            'Level 3 Master' => 3,
            default => 0,
        };

        return min($level, $this->office_role === 'oso' ? 3 : 2);
    }

    public function effectiveTosaClearance(): string
    {
        return match ($this->tosaClearanceLevel()) {
            1 => 'Level 1 Read-only',
            2 => 'Level 2 Evaluator',
            3 => 'Level 3 Master',
            default => 'No Access',
        };
    }

    public function studentOrganization(): BelongsTo
    {
        return $this->belongsTo(StudentOrganization::class, 'student_organization_id');
    }

    public function organizationName(): ?string
    {
        return $this->studentOrganization?->name;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? 'O', 0, 1);
        $last = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);

        return mb_strtoupper($first.($last !== $first ? $last : ''));
    }
}

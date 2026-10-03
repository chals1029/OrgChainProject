<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

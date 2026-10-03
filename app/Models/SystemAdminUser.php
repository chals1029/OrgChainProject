<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SystemAdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'system_admin_users';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'is_active',
        'last_login_at',
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
            'last_login_at' => 'datetime',
        ];
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'system_admin' => 'System Maintenance Administrator',
            default => ucwords(str_replace('_', ' ', (string) $this->role)),
        };
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $first = mb_substr($parts[0] ?? 'S', 0, 1);
        $last = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);

        return mb_strtoupper($first.($last !== $first ? $last : ''));
    }
}

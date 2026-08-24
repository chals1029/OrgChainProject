<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserAccount extends Authenticatable
{
    use Notifiable;

    protected $connection = 'orgchain';
    protected $table = 'user_accounts';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'org_id',
        'sr_code',
        'full_name',
        'password_hash',
        'email',
        'college',
        'program',
        'year_level',
        'role',
        'account_status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    public function getIdAttribute()
    {
        return $this->user_id;
    }

    public function getNameAttribute()
    {
        return $this->full_name ?: $this->sr_code ?: $this->email;
    }

    public function initials(): string
    {
        $name = $this->full_name ?: $this->sr_code ?: $this->email ?: 'U';
        $parts = preg_split('/[\s._-]+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $last = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
        return mb_strtoupper($first . ($last !== $first ? $last : ''));
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'student_id', 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'student_id', 'user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityLike::class, 'student_id', 'user_id');
    }
}

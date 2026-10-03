<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemAdminAuditLog extends Model
{
    protected $fillable = [
        'system_admin_user_id',
        'event',
        'target',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(SystemAdminUser::class, 'system_admin_user_id');
    }

    public static function record(string $event, ?string $target = null, array $details = []): self
    {
        $request = app()->bound('request') ? request() : null;
        $admin = Auth::guard('system_admin')->user();

        return static::query()->create([
            'system_admin_user_id' => $admin?->id,
            'event' => $event,
            'target' => $target,
            'details' => $details ?: null,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
        ]);
    }
}

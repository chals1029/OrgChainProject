<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgActivity extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'title',
        'description',
        'status',
        'location',
        'starts_at',
        'ends_at',
        'cover_image',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'activity_id');
    }
}

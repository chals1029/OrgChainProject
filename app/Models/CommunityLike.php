<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLike extends Model
{
    protected $connection = 'orgchain';

    protected $fillable = [
        'post_id',
        'student_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'student_id', 'user_id');
    }
}

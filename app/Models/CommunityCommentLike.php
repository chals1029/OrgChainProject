<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityCommentLike extends Model
{
    protected $connection = 'orgchain';

    protected $fillable = [
        'comment_id',
        'student_id',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CommunityComment::class, 'comment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'student_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeedback extends Model
{
    protected $connection = 'mysql';

    protected $table = 'student_feedback';

    protected $fillable = [
        'student_id',
        'author_name',
        'college',
        'program',
        'topic',
        'body',
        'is_anonymous',
        'visibility',
        'status',
        'review_note',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

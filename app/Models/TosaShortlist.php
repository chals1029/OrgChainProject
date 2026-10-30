<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TosaShortlist extends Model
{
    protected $connection = 'mysql';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'draft_ids' => 'array',
            'published_entries' => 'array',
            'published_at' => 'datetime',
        ];
    }
}

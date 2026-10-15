<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeAnnouncementAcknowledgment extends Model
{
    protected $connection = 'mysql';

    public $timestamps = false;

    protected $fillable = [
        'office_announcement_id',
        'office_user_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }
}

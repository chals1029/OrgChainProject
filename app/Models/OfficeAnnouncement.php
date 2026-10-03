<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeAnnouncement extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'title',
        'type',
        'body',
        'author',
        'priority',
        'attachment_path',
        'attachment_name',
    ];
}

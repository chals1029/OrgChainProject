<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeTemplate extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'category',
        'format',
        'size',
        'description',
        'file_path',
        'original_name',
        'downloads',
    ];
}

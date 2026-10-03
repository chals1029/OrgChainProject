<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReceiptScan extends Model
{
    use HasUuids;

    protected $connection = 'mysql';
    protected $guarded = [];
    protected $hidden = ['raw_text', 'file_hash'];
    protected function casts(): array
    {
        return ['extracted' => 'array', 'expires_at' => 'datetime'];
    }
}

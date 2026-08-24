<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveDocument extends Model
{
    protected $fillable = [
        'archive_folder_id',
        'name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ArchiveFolder::class, 'archive_folder_id');
    }
}

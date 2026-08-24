<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveFolder extends Model
{
    protected $fillable = ['name', 'organization_name', 'semester', 'color'];

    public function documents(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class);
    }
}

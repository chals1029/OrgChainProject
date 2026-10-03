<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveFolder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'organization_name',
        'semester',
        'color',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArchiveFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ArchiveFolder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ArchiveDocument::class, 'archive_folder_id');
    }

    /**
     * Return ancestors leading to this folder including this folder.
     * Ordered from root to this folder.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getBreadcrumbs(): array
    {
        $crumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($crumbs, [
                'id' => $current->id,
                'name' => $current->name,
            ]);
            $current = $current->parent;
        }

        return $crumbs;
    }

    /**
     * Recursively retrieve all descendant folder IDs.
     *
     * @return array<int>
     */
    public function getAllDescendantIds(): array
    {
        $ids = [];
        $children = $this->children()->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'title',
        'category',
        'college',
        'organization_name',
        'allocated',
        'utilized',
        'fiscal_year',
        'notes',
        'supplier',
        'is_approved',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    public function utilizationPercent(): int
    {
        if ($this->allocated <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->utilized / $this->allocated) * 100));
    }

    public function remaining(): int
    {
        return max(0, (int) $this->allocated - (int) $this->utilized);
    }
}

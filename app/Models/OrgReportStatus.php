<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgReportStatus extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'report_type',
        'organization_name',
        'college',
        'semester',
        'academic_year',
        'status',
        'returned_to',
        'notes',
    ];
}

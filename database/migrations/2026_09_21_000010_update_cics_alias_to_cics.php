<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ORGANIZATION = 'College of Informatics and Computing Sciences Student Council (CICS-SC)';

    public function up(): void
    {
        DB::table('student_organizations')
            ->where('name', self::ORGANIZATION)
            ->update(['short_name' => 'CICS']);
    }

    public function down(): void
    {
        DB::table('student_organizations')
            ->where('name', self::ORGANIZATION)
            ->update(['short_name' => 'CICS-SC']);
    }
};

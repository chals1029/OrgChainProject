<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_organizations') && ! Schema::hasColumn('student_organizations', 'logo_path')) {
            Schema::table('student_organizations', function (Blueprint $table): void {
                $table->string('logo_path')->nullable()->after('academic_year');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_organizations') && Schema::hasColumn('student_organizations', 'logo_path')) {
            Schema::table('student_organizations', function (Blueprint $table): void {
                $table->dropColumn('logo_path');
            });
        }
    }
};

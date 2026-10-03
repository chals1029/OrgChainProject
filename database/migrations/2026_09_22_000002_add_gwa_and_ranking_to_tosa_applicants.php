<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tosa_applicants')) {
            Schema::table('tosa_applicants', function (Blueprint $table) {
                if (! Schema::hasColumn('tosa_applicants', 'gwa')) {
                    $table->decimal('gwa', 3, 2)->nullable()->after('year_level');
                }
                if (! Schema::hasColumn('tosa_applicants', 'gwa_verified')) {
                    $table->boolean('gwa_verified')->default(false)->after('gwa');
                }
                if (! Schema::hasColumn('tosa_applicants', 'academic_year')) {
                    $table->string('academic_year', 20)->default('2025-2026')->after('gwa_verified');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tosa_applicants')) {
            Schema::table('tosa_applicants', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('tosa_applicants', 'gwa')) {
                    $columns[] = 'gwa';
                }
                if (Schema::hasColumn('tosa_applicants', 'gwa_verified')) {
                    $columns[] = 'gwa_verified';
                }
                if (Schema::hasColumn('tosa_applicants', 'academic_year')) {
                    $columns[] = 'academic_year';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};

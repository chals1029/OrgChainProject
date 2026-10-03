<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('office_users', 'employee_id')) {
            Schema::table('office_users', function (Blueprint $table) {
                $table->string('employee_id', 80)->nullable()->after('office_title');
            });
        }

        if (! Schema::hasColumn('office_users', 'tosa_clearance')) {
            Schema::table('office_users', function (Blueprint $table) {
                $table->string('tosa_clearance', 40)->default('No Access')->after('employee_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('office_users', 'tosa_clearance')) {
            Schema::table('office_users', function (Blueprint $table) {
                $table->dropColumn('tosa_clearance');
            });
        }

        if (Schema::hasColumn('office_users', 'employee_id')) {
            Schema::table('office_users', function (Blueprint $table) {
                $table->dropColumn('employee_id');
            });
        }
    }
};

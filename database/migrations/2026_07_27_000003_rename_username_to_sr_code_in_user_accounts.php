<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orgchain')->table('user_accounts', function (Blueprint $table) {
            $table->renameColumn('username', 'sr_code');
            $table->string('college', 255)->nullable()->after('full_name');
            $table->string('program', 255)->nullable()->after('college');
            $table->string('year_level', 50)->nullable()->after('program');
        });
    }

    public function down(): void
    {
        Schema::connection('orgchain')->table('user_accounts', function (Blueprint $table) {
            $table->dropColumn(['college', 'program', 'year_level']);
            $table->renameColumn('sr_code', 'username');
        });
    }
};

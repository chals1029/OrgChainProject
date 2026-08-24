<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orgchain')->table('user_accounts', function (Blueprint $table) {
            $table->rememberToken()->after('account_status');
        });
    }

    public function down(): void
    {
        Schema::connection('orgchain')->table('user_accounts', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};

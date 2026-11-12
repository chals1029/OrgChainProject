<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql')->hasTable('user_accounts') && ! Schema::connection('mysql')->hasColumn('user_accounts', 'avatar_path')) {
            Schema::connection('mysql')->table('user_accounts', function (Blueprint $table): void {
                $table->string('avatar_path', 255)->nullable()->after('account_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql')->hasTable('user_accounts') && Schema::connection('mysql')->hasColumn('user_accounts', 'avatar_path')) {
            Schema::connection('mysql')->table('user_accounts', function (Blueprint $table): void {
                $table->dropColumn('avatar_path');
            });
        }
    }
};

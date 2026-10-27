<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('user_accounts')) {
            return;
        }

        $schema->create('user_accounts', function (Blueprint $table): void {
            $table->integer('user_id')->primary();
            $table->unsignedBigInteger('org_id')->nullable();
            $table->string('username', 100)->nullable();
            $table->string('password_hash', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('role', 50)->nullable();
            $table->string('account_status', 50)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Imported accounts may predate this migration; never drop their records on rollback.
    }
};

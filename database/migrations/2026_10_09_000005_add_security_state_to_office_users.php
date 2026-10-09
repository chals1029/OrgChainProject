<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_users', function (Blueprint $table): void {
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('auth_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('office_users', function (Blueprint $table): void {
            $table->dropColumn(['must_change_password', 'auth_version']);
        });
    }
};

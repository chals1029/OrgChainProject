<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('office_announcements', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('office_announcements', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};

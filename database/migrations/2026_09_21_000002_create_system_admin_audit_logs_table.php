<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('system_admin_user_id')->nullable()->constrained('system_admin_users')->nullOnDelete();
            $table->string('event', 100);
            $table->string('target', 190)->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_admin_audit_logs');
    }
};

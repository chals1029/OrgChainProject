<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('office_announcement_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_announcement_id')->constrained('office_announcements', indexName: 'notice_ack_announcement_fk')->cascadeOnDelete();
            $table->foreignId('office_user_id')->constrained('office_users', indexName: 'notice_ack_office_user_fk')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->unique(['office_announcement_id', 'office_user_id'], 'announcement_actor_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('office_announcement_acknowledgments');
    }
};

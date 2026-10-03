<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('General Announcement');
            $table->text('body');
            $table->string('author')->default('Office of Student Organizations (OSO)');
            $table->string('priority')->default('normal');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_announcements');
    }
};

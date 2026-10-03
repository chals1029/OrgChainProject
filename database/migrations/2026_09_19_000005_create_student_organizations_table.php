<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('short_name', 64)->nullable();
            $table->string('college')->nullable();
            $table->string('academic_year', 16)->default('2025-2026');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academic_year', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_organizations');
    }
};

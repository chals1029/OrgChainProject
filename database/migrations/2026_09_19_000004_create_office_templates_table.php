<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('Forms');
            $table->string('format')->default('PDF');
            $table->string('size')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organization_name');
            $table->string('semester', 32)->default('2nd Semester');
            $table->string('color', 20)->default('red');
            $table->timestamps();
        });

        Schema::create('archive_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_folder_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_documents');
        Schema::dropIfExists('archive_folders');
    }
};

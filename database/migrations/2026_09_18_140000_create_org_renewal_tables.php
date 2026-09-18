<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_renewal_windows')) {
            Schema::create('org_renewal_windows', function (Blueprint $table) {
                $table->id();
                $table->string('academic_year', 32);
                $table->string('semester', 40)->default('1st Semester');
                $table->boolean('is_open')->default(false);
                $table->timestamp('opens_at')->nullable();
                $table->timestamp('closes_at')->nullable();
                $table->text('instructions')->nullable();
                $table->json('required_docs')->nullable();
                $table->foreignId('opened_by')->nullable();
                $table->foreignId('closed_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('org_renewal_submissions')) {
            Schema::create('org_renewal_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('renewal_window_id')->constrained('org_renewal_windows')->cascadeOnDelete();
                $table->string('organization_name');
                $table->string('college')->nullable();
                $table->foreignId('submitted_by')->nullable();
                $table->string('adviser_name')->nullable();
                $table->string('dean_name')->nullable();
                $table->string('status', 40)->default('draft');
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['renewal_window_id', 'organization_name'], 'renewal_window_org_unique');
            });
        }

        if (! Schema::hasTable('org_renewal_documents')) {
            Schema::create('org_renewal_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('org_renewal_submissions')->cascadeOnDelete();
                $table->string('doc_key', 80);
                $table->string('title');
                $table->string('file_path');
                $table->string('file_name');
                $table->timestamps();

                $table->unique(['submission_id', 'doc_key'], 'renewal_submission_doc_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_renewal_documents');
        Schema::dropIfExists('org_renewal_submissions');
        Schema::dropIfExists('org_renewal_windows');
    }
};

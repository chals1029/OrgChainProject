<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'batch_key' => fn (Blueprint $table) => $table->string('batch_key', 64)->nullable()->after('id'),
            'submitted_at' => fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable()->after('notes'),
            'reviewed_at' => fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable()->after('submitted_at'),
            'reviewed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at'),
            'archived_at' => fn (Blueprint $table) => $table->timestamp('archived_at')->nullable()->after('reviewed_by'),
        ] as $column => $addColumn) {
            if (! Schema::hasColumn('org_report_statuses', $column)) {
                Schema::table('org_report_statuses', $addColumn);
            }
        }

        if (! Schema::hasColumn('org_report_statuses', 'archive_folder_id')) {
            Schema::table('org_report_statuses', function (Blueprint $table): void {
                $table->foreignId('archive_folder_id')
                    ->nullable()
                    ->after('archived_at')
                    ->constrained('archive_folders')
                    ->nullOnDelete();
            });
        }

        Schema::table('org_report_statuses', function (Blueprint $table): void {
            $table->index(
                ['report_type', 'organization_name', 'semester'],
                'org_report_statuses_period_idx'
            );
            $table->index('batch_key', 'org_report_statuses_batch_idx');
        });

        if (! Schema::hasTable('org_report_documents')) {
            Schema::create('org_report_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('org_report_status_id')
                    ->constrained('org_report_statuses')
                    ->cascadeOnDelete();
                $table->string('report_type', 20); // ar | fr
                $table->string('organization_name');
                $table->string('semester', 32);
                $table->string('academic_year', 20);
                $table->string('name');
                $table->string('original_name');
                $table->string('file_path');
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size');
                $table->string('uploaded_by')->nullable();
                $table->timestamps();

                $table->index(
                    ['report_type', 'organization_name', 'semester', 'academic_year'],
                    'org_report_documents_period_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_report_documents');

        if (Schema::hasTable('org_report_statuses')) {
            Schema::table('org_report_statuses', function (Blueprint $table): void {
                $table->dropIndex('org_report_statuses_period_idx');
                $table->dropIndex('org_report_statuses_batch_idx');
                $table->dropConstrainedForeignId('archive_folder_id');
                $table->dropColumn([
                    'batch_key',
                    'submitted_at',
                    'reviewed_at',
                    'reviewed_by',
                    'archived_at',
                ]);
            });
        }
    }
};

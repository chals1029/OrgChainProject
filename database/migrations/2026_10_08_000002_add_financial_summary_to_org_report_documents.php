<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('org_report_documents', 'financial_summary')) {
            Schema::table('org_report_documents', function (Blueprint $table): void {
                $table->json('financial_summary')->nullable()->after('file_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('org_report_documents', 'financial_summary')) {
            Schema::table('org_report_documents', function (Blueprint $table): void {
                $table->dropColumn('financial_summary');
            });
        }
    }
};

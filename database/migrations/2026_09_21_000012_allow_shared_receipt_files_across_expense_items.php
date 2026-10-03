<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        if (! Schema::connection('mysql')->hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            // Keep a non-unique leading index for the org_activity_id foreign
            // key before removing the old uniqueness rule.
            $table->index(['org_activity_id', 'file_hash'], 'receipt_activity_file_index');
            $table->dropUnique('receipt_activity_file_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::connection('mysql')->hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->dropIndex('receipt_activity_file_index');
            $table->unique(['org_activity_id', 'file_hash'], 'receipt_activity_file_unique');
        });
    }
};

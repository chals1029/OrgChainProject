<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_receipt_reviews', 'receipt_reference')) {
                $table->string('receipt_reference', 120)->nullable()->after('receipt_name');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'ocr_quality')) {
                $table->string('ocr_quality', 40)->nullable()->after('ocr_confidence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('expense_receipt_reviews', 'receipt_reference')) {
                $table->dropColumn('receipt_reference');
            }
            if (Schema::hasColumn('expense_receipt_reviews', 'ocr_quality')) {
                $table->dropColumn('ocr_quality');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_receipt_reviews', 'chain_hash')) {
                $table->string('chain_hash', 64)->nullable()->after('ocr_quality');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'previous_hash')) {
                $table->string('previous_hash', 64)->nullable()->after('chain_hash');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'nodes_confirmed')) {
                $table->unsignedTinyInteger('nodes_confirmed')->default(0)->after('previous_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            foreach (['chain_hash', 'previous_hash', 'nodes_confirmed'] as $col) {
                if (Schema::hasColumn('expense_receipt_reviews', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_receipt_reviews', 'chain_driver')) {
                $table->string('chain_driver', 32)->nullable()->after('nodes_confirmed');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'chain_tx_hash')) {
                $table->string('chain_tx_hash', 66)->nullable()->after('chain_driver');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'chain_block_number')) {
                $table->unsignedBigInteger('chain_block_number')->nullable()->after('chain_tx_hash');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'chain_contract_address')) {
                $table->string('chain_contract_address', 42)->nullable()->after('chain_block_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            foreach (['chain_driver', 'chain_tx_hash', 'chain_block_number', 'chain_contract_address'] as $column) {
                if (Schema::hasColumn('expense_receipt_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


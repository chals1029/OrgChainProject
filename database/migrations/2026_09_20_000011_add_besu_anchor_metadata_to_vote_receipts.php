<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('voting')->hasTable('vote_receipts')) {
            return;
        }

        Schema::connection('voting')->table('vote_receipts', function (Blueprint $table) {
            if (! Schema::connection('voting')->hasColumn('vote_receipts', 'chain_driver')) {
                $table->string('chain_driver', 32)->nullable()->after('node_confirmations');
            }
            if (! Schema::connection('voting')->hasColumn('vote_receipts', 'chain_tx_hash')) {
                $table->string('chain_tx_hash', 66)->nullable()->after('chain_driver');
            }
            if (! Schema::connection('voting')->hasColumn('vote_receipts', 'chain_block_number')) {
                $table->unsignedBigInteger('chain_block_number')->nullable()->after('chain_tx_hash');
            }
            if (! Schema::connection('voting')->hasColumn('vote_receipts', 'chain_contract_address')) {
                $table->string('chain_contract_address', 42)->nullable()->after('chain_block_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('voting')->hasTable('vote_receipts')) {
            return;
        }

        Schema::connection('voting')->table('vote_receipts', function (Blueprint $table) {
            foreach (['chain_driver', 'chain_tx_hash', 'chain_block_number', 'chain_contract_address'] as $column) {
                if (Schema::connection('voting')->hasColumn('vote_receipts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


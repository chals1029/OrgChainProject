<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vote_receipts')) {
            return;
        }

        Schema::table('vote_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('vote_receipts', 'previous_hash')) {
                $table->string('previous_hash', 64)->nullable()->after('reference_code');
            }
            if (! Schema::hasColumn('vote_receipts', 'block_hash')) {
                $table->string('block_hash', 64)->nullable()->after('previous_hash');
            }
            if (! Schema::hasColumn('vote_receipts', 'ballot_root')) {
                $table->string('ballot_root', 64)->nullable()->after('block_hash');
            }
            if (! Schema::hasColumn('vote_receipts', 'voter_commitment')) {
                $table->string('voter_commitment', 64)->nullable()->after('ballot_root');
            }
            if (! Schema::hasColumn('vote_receipts', 'nodes_confirmed')) {
                $table->unsignedTinyInteger('nodes_confirmed')->default(0)->after('voter_commitment');
            }
            if (! Schema::hasColumn('vote_receipts', 'node_confirmations')) {
                $table->json('node_confirmations')->nullable()->after('nodes_confirmed');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vote_receipts')) {
            return;
        }

        Schema::table('vote_receipts', function (Blueprint $table) {
            foreach (['previous_hash', 'block_hash', 'ballot_root', 'voter_commitment', 'nodes_confirmed', 'node_confirmations'] as $column) {
                if (Schema::hasColumn('vote_receipts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

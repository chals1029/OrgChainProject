<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('voting');
        if ($schema->hasColumn('voters', 'department') && ! $schema->hasColumn('voters', 'program')) {
            $schema->table('voters', function (Blueprint $table): void {
                $table->renameColumn('department', 'program');
            });
        }
        $indexes = [
            ['voters', 'idx_voters_has_voted', ['has_voted']],
            ['voters', 'idx_voters_voted_at', ['voted_at']],
            ['voters', 'idx_voters_college', ['college']],
            ['voters', 'idx_voters_program', ['program']],
            ['voters', 'idx_voters_grade_level', ['grade_level']],
            ['audit_logs', 'idx_audit_logs_created_at', ['created_at']],
            ['candidates', 'idx_candidates_position', ['position_id', 'sort_order']],
            ['positions', 'idx_positions_election', ['election_id', 'sort_order']],
            ['votes', 'idx_votes_election_candidate', ['election_id', 'candidate_id']],
            ['vote_receipts', 'idx_vote_receipts_block_hash', ['block_hash']],
        ];
        foreach ($indexes as [$tableName, $name, $columns]) {
            $existing = $schema->getIndexes($tableName);
            if (array_filter($existing, fn (array $index): bool => $index['columns'] === $columns)) {
                continue;
            }
            $schema->table($tableName, function (Blueprint $table) use ($name, $columns): void {
                $table->index($columns, $name);
            });
        }
        $createdIndexes = array_values(array_filter($schema->getIndexes('audit_logs'),
            fn (array $index): bool => $index['columns'] === ['created_at'] && ! $index['unique']));
        usort($createdIndexes, fn (array $a, array $b): int =>
            ($b['name'] === 'idx_audit_logs_created_at') <=> ($a['name'] === 'idx_audit_logs_created_at'));
        foreach (array_slice($createdIndexes, 1) as $index) {
            $schema->table('audit_logs', function (Blueprint $table) use ($index): void {
                $table->dropIndex($index['name']);
            });
        }
    }

    public function down(): void
    {
        // Retain indexes that may predate this migration on imported installations.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('tosa_applicants')) {
            return;
        }
        foreach (['document_review', 'committee_members'] as $column) {
            if (! $schema->hasColumn('tosa_applicants', $column)) {
                $schema->table('tosa_applicants', function (Blueprint $table) use ($column): void {
                    $table->json($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('tosa_applicants')) {
            return;
        }
        foreach (['document_review', 'committee_members'] as $column) {
            if ($schema->hasColumn('tosa_applicants', $column)) {
                $schema->table('tosa_applicants', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

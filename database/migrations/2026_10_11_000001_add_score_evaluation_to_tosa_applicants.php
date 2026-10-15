<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('tosa_applicants') && ! $schema->hasColumn('tosa_applicants', 'score_evaluation')) {
            $schema->table('tosa_applicants', function (Blueprint $table): void {
                $table->json('score_evaluation')->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('tosa_applicants') && $schema->hasColumn('tosa_applicants', 'score_evaluation')) {
            $schema->table('tosa_applicants', function (Blueprint $table): void {
                $table->dropColumn('score_evaluation');
            });
        }
    }
};

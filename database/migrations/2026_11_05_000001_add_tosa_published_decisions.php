<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('tosa_shortlists') && ! $schema->hasColumn('tosa_shortlists', 'published_decisions')) {
            $schema->table('tosa_shortlists', function (Blueprint $table): void {
                $table->json('published_decisions')->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('tosa_shortlists') && $schema->hasColumn('tosa_shortlists', 'published_decisions')) {
            $schema->table('tosa_shortlists', function (Blueprint $table): void {
                $table->dropColumn('published_decisions');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('tosa_shortlists')) {
            return;
        }
        $hasQualified = $schema->hasColumn('tosa_shortlists', 'caption_qualified');
        $hasNotQualified = $schema->hasColumn('tosa_shortlists', 'caption_not_qualified');
        if (! $hasQualified || ! $hasNotQualified) {
            $schema->table('tosa_shortlists', function (Blueprint $table) use ($hasQualified, $hasNotQualified): void {
                if (! $hasQualified) {
                    $table->text('caption_qualified')->nullable();
                }
                if (! $hasNotQualified) {
                    $table->text('caption_not_qualified')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('tosa_shortlists')) {
            return;
        }
        foreach (['caption_qualified', 'caption_not_qualified'] as $column) {
            if ($schema->hasColumn('tosa_shortlists', $column)) {
                $schema->table('tosa_shortlists', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

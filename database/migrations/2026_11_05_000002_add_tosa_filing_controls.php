<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('office_announcements') && ! $schema->hasColumn('office_announcements', 'ended_at')) {
            $schema->table('office_announcements', function (Blueprint $table): void {
                $table->timestamp('ended_at')->nullable();
            });
        }
        if (! $schema->hasTable('tosa_submission_settings')) {
            $schema->create('tosa_submission_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->boolean('is_open')->default(true);
                $table->timestamps();
            });
        }
        DB::connection('mysql')->table('tosa_submission_settings')->insertOrIgnore([
            'id' => 1, 'is_open' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        $schema->dropIfExists('tosa_submission_settings');
        if ($schema->hasTable('office_announcements') && $schema->hasColumn('office_announcements', 'ended_at')) {
            $schema->table('office_announcements', function (Blueprint $table): void {
                $table->dropColumn('ended_at');
            });
        }
    }
};

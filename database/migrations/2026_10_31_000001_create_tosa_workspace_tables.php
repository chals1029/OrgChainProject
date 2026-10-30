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
        if (! $schema->hasTable('tosa_history_events')) {
            $schema->create('tosa_history_events', function (Blueprint $table): void {
                $table->id();
                $table->string('action', 40);
                $table->unsignedBigInteger('applicant_id')->nullable()->index();
                $table->string('actor_type', 20);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name');
                $table->json('details')->nullable();
                $table->timestamp('created_at')->index();
            });
        }
        if (! $schema->hasTable('tosa_shortlists')) {
            $schema->create('tosa_shortlists', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->json('draft_ids')->nullable();
                $table->json('published_entries')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }
        DB::connection('mysql')->table('tosa_shortlists')->insertOrIgnore([
            'id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        $schema->dropIfExists('tosa_shortlists');
        $schema->dropIfExists('tosa_history_events');
    }
};

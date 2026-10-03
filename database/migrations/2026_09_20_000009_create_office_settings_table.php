<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('office_settings')) {
            return;
        }

        Schema::create('office_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 32)->unique();
            $table->json('values')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_settings');
    }
};

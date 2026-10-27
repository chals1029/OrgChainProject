<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('General');
            $table->unsignedBigInteger('allocated')->default(0);
            $table->unsignedBigInteger('utilized')->default(0);
            $table->string('fiscal_year', 16)->default('2026');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('org_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 24)->default('upcoming'); // upcoming | ongoing | completed
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('org_activities');
        Schema::dropIfExists('budget_items');
    }
};

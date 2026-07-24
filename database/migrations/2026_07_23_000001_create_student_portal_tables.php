<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('sr_code', 16)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('college')->nullable();
            $table->string('program')->nullable();
            $table->string('year_level')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

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

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('org_activities')->nullOnDelete();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
        });

        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('community_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_likes');
        Schema::dropIfExists('community_comments');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('org_activities');
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('students');
    }
};

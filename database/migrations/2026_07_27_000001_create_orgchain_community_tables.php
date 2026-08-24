<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orgchain')->create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
        });

        Schema::connection('orgchain')->create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('student_id');
            $table->text('body');
            $table->timestamps();
        });

        Schema::connection('orgchain')->create('community_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamps();
            $table->unique(['post_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('orgchain')->dropIfExists('community_likes');
        Schema::connection('orgchain')->dropIfExists('community_comments');
        Schema::connection('orgchain')->dropIfExists('community_posts');
    }
};

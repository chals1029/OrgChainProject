<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('orgchain');

        if (! $schema->hasTable('community_comment_likes')) {
            $schema->create('community_comment_likes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('comment_id');
                $table->unsignedBigInteger('student_id');
                $table->timestamps();
                $table->unique(['comment_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('orgchain')->dropIfExists('community_comment_likes');
    }
};

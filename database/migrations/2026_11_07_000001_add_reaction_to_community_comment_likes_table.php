<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('community_comment_likes') && ! $schema->hasColumn('community_comment_likes', 'reaction')) {
            $schema->table('community_comment_likes', function (Blueprint $table): void {
                $table->string('reaction', 32)->nullable()->default('like')->after('student_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('community_comment_likes') && $schema->hasColumn('community_comment_likes', 'reaction')) {
            $schema->table('community_comment_likes', function (Blueprint $table): void {
                $table->dropColumn('reaction');
            });
        }
    }
};

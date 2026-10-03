<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('orgchain');

        if ($schema->hasTable('community_likes') && ! $schema->hasColumn('community_likes', 'reaction')) {
            $schema->table('community_likes', function (Blueprint $table): void {
                $table->string('reaction', 20)->default('like')->after('student_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('orgchain');

        if ($schema->hasTable('community_likes') && $schema->hasColumn('community_likes', 'reaction')) {
            $schema->table('community_likes', function (Blueprint $table): void {
                $table->dropColumn('reaction');
            });
        }
    }
};

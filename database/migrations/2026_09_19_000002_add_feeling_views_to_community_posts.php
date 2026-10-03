<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orgchain')->table('community_posts', function (Blueprint $table) {
            if (! Schema::connection('orgchain')->hasColumn('community_posts', 'feeling')) {
                $table->string('feeling', 100)->nullable()->after('body');
            }
            if (! Schema::connection('orgchain')->hasColumn('community_posts', 'tagged_users')) {
                $table->string('tagged_users', 500)->nullable()->after('feeling');
            }
            if (! Schema::connection('orgchain')->hasColumn('community_posts', 'audience')) {
                $table->string('audience', 20)->default('public')->after('tagged_users');
            }
            if (! Schema::connection('orgchain')->hasColumn('community_posts', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('comments_count');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('orgchain')->table('community_posts', function (Blueprint $table) {
            $table->dropColumn(['feeling', 'tagged_users', 'audience', 'views_count']);
        });
    }
};

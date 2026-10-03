<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_organizations', function (Blueprint $table) {
            $table->boolean('is_qualified_for_renewal')->default(true)->after('is_active');
            $table->string('disqualification_reason', 500)->nullable()->after('is_qualified_for_renewal');
            $table->timestamp('status_updated_at')->nullable()->after('disqualification_reason');
        });
    }

    public function down(): void
    {
        Schema::table('student_organizations', function (Blueprint $table) {
            $table->dropColumn([
                'is_qualified_for_renewal',
                'disqualification_reason',
                'status_updated_at',
            ]);
        });
    }
};

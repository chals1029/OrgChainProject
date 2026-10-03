<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_renewal_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('org_renewal_submissions', 'review_remarks')) {
                $table->text('review_remarks')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('org_renewal_submissions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('org_renewal_submissions', function (Blueprint $table) {
            $table->dropColumn(['review_remarks', 'reviewed_at']);
        });
    }
};

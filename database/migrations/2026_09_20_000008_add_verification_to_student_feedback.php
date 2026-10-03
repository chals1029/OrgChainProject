<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('student_feedback', 'status')) {
                $table->string('status', 24)->default('pending')->after('visibility');
            }
            if (! Schema::hasColumn('student_feedback', 'review_note')) {
                $table->text('review_note')->nullable()->after('status');
            }
            if (! Schema::hasColumn('student_feedback', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('student_feedback', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_feedback', function (Blueprint $table) {
            $table->dropColumn(['status', 'review_note', 'verified_by', 'verified_at']);
        });
    }
};

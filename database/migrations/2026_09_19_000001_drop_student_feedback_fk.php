<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Portal feedback is authored by orgchain UserAccount rows (user_id),
     * not mysql students rows — drop the FK so sends don't 500.
     */
    public function up(): void
    {
        if (! Schema::hasTable('student_feedback')) {
            return;
        }

        try {
            Schema::table('student_feedback', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
            });
        } catch (Throwable) {
            // FK already dropped or named differently — nothing to do.
        }
    }

    public function down(): void
    {
        // Do not re-add the FK: portal authors are not students-table rows.
    }
};

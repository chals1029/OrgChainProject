<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->longText('wpcf_html')->nullable()->after('sample_letter_html');
            $table->longText('approved_plan_html')->nullable()->after('wpcf_html');
            $table->longText('class_schedule_html')->nullable()->after('approved_plan_html');
            $table->longText('meeting_minutes_html')->nullable()->after('class_schedule_html');
        });
    }

    public function down(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'wpcf_html',
                'approved_plan_html',
                'class_schedule_html',
                'meeting_minutes_html',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->longText('off_campus_req_html')->nullable()->after('faculty_in_charge_html');
            $table->longText('cert_compliance_html')->nullable()->after('off_campus_req_html');
            $table->longText('ched_report_html')->nullable()->after('cert_compliance_html');
            $table->longText('travel_matrix_html')->nullable()->after('ched_report_html');
            $table->longText('passenger_matrix_html')->nullable()->after('travel_matrix_html');
            $table->longText('course_activities_html')->nullable()->after('passenger_matrix_html');
        });
    }

    public function down(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'off_campus_req_html',
                'cert_compliance_html',
                'ched_report_html',
                'travel_matrix_html',
                'passenger_matrix_html',
                'course_activities_html',
            ]);
        });
    }
};

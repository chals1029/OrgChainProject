<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->longText('medical_request_html')->nullable()->after('faculty_in_charge_html');
            $table->longText('insurance_request_html')->nullable()->after('medical_request_html');
            $table->longText('resolution_html')->nullable()->after('insurance_request_html');
            $table->longText('sample_letter_html')->nullable()->after('resolution_html');
        });
    }

    public function down(): void
    {
        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'medical_request_html',
                'insurance_request_html',
                'resolution_html',
                'sample_letter_html',
            ]);
        });
    }
};

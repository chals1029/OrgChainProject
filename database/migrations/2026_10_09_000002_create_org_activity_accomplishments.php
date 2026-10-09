<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->create('org_activity_accomplishments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('org_activity_id')->unique();
            $table->string('organization_name');
            $table->string('academic_year', 9);
            $table->string('semester', 32);
            $table->string('sponsor');
            foreach (['brief_description', 'objectives', 'narrative', 'people_involved', 'problems_encountered', 'recommendations'] as $field) {
                $table->longText($field);
            }
            $table->unsignedInteger('male_participants');
            $table->unsignedInteger('female_participants');
            foreach (['signatories', 'activity_snapshot', 'financial_snapshot', 'evidence'] as $field) {
                $table->json($field);
            }
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['organization_name', 'academic_year', 'semester'], 'accomplishment_period_index');
        });
        Schema::connection('mysql')->table('org_report_documents', function (Blueprint $table): void {
            $table->json('accomplishment_summary')->nullable()->after('financial_summary');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('org_report_documents', function (Blueprint $table): void {
            $table->dropColumn('accomplishment_summary');
        });
        Schema::connection('mysql')->dropIfExists('org_activity_accomplishments');
    }
};

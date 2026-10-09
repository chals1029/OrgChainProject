<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->create('org_report_submission_windows', function (Blueprint $table): void {
            $table->id();
            $table->string('report_type', 2);
            $table->string('academic_year', 9);
            $table->string('semester', 32);
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['report_type', 'academic_year', 'semester'], 'report_submission_window_period_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('org_report_submission_windows');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_registrations')) {
            Schema::create('activity_registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('org_activity_id')->constrained('org_activities')->cascadeOnDelete();
                $table->unsignedBigInteger('student_id');
                $table->string('sr_code', 32)->nullable();
                $table->string('full_name')->nullable();
                $table->string('year_level', 32)->nullable();
                $table->timestamps();

                $table->unique(['org_activity_id', 'student_id'], 'activity_student_unique');
                $table->index('org_activity_id');
            });
        }

        Schema::table('org_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('org_activities', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_registrations');

        Schema::table('org_activities', function (Blueprint $table) {
            if (Schema::hasColumn('org_activities', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};

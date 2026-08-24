<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_campus_activity_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_activity_id')->nullable()->constrained('org_activities')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->string('activity_type', 30)->default('in_campus');
            $table->string('organization_name')->nullable();
            $table->text('rationale')->nullable();
            $table->text('objectives')->nullable();
            $table->text('participants')->nullable();
            $table->text('safety_plan')->nullable();
            $table->longText('programme_html')->nullable();
            $table->longText('project_proposal_html')->nullable();
            $table->longText('budget_proposal_html')->nullable();
            $table->longText('faculty_in_charge_html')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_campus_activity_submissions');
    }
};

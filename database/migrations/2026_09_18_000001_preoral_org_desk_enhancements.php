<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('org_activities', 'college')) {
                $table->string('college')->nullable()->after('location');
            }
            if (! Schema::hasColumn('org_activities', 'organization_name')) {
                $table->string('organization_name')->nullable()->after('college');
            }
            if (! Schema::hasColumn('org_activities', 'program')) {
                $table->string('program')->nullable()->after('organization_name');
            }
            if (! Schema::hasColumn('org_activities', 'workflow_status')) {
                $table->string('workflow_status', 40)->default('created')->after('status');
            }
            if (! Schema::hasColumn('org_activities', 'returned_to')) {
                $table->string('returned_to', 40)->nullable()->after('workflow_status');
            }
            if (! Schema::hasColumn('org_activities', 'activity_scope')) {
                $table->string('activity_scope', 40)->default('in_campus')->after('returned_to');
            }
            if (! Schema::hasColumn('org_activities', 'sdg_goals')) {
                $table->json('sdg_goals')->nullable()->after('activity_scope');
            }
            if (! Schema::hasColumn('org_activities', 'core_values')) {
                $table->json('core_values')->nullable()->after('sdg_goals');
            }
            if (! Schema::hasColumn('org_activities', 'male_participants')) {
                $table->unsignedInteger('male_participants')->default(0)->after('core_values');
            }
            if (! Schema::hasColumn('org_activities', 'female_participants')) {
                $table->unsignedInteger('female_participants')->default(0)->after('male_participants');
            }
            if (! Schema::hasColumn('org_activities', 'approved_budget')) {
                $table->unsignedBigInteger('approved_budget')->default(0)->after('female_participants');
            }
            if (! Schema::hasColumn('org_activities', 'implemented_budget')) {
                $table->unsignedBigInteger('implemented_budget')->default(0)->after('approved_budget');
            }
        });

        Schema::table('in_campus_activity_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('in_campus_activity_submissions', 'workflow_status')) {
                $table->string('workflow_status', 40)->default('created')->after('status');
            }
            if (! Schema::hasColumn('in_campus_activity_submissions', 'returned_to')) {
                $table->string('returned_to', 40)->nullable()->after('workflow_status');
            }
            if (! Schema::hasColumn('in_campus_activity_submissions', 'college')) {
                $table->string('college')->nullable()->after('organization_name');
            }
            if (! Schema::hasColumn('in_campus_activity_submissions', 'document_statuses')) {
                $table->json('document_statuses')->nullable()->after('attachments');
            }
            if (! Schema::hasColumn('in_campus_activity_submissions', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('in_campus_activity_submissions', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('sla_due_at');
            }
        });

        Schema::table('budget_items', function (Blueprint $table) {
            if (! Schema::hasColumn('budget_items', 'college')) {
                $table->string('college')->nullable()->after('category');
            }
            if (! Schema::hasColumn('budget_items', 'organization_name')) {
                $table->string('organization_name')->nullable()->after('college');
            }
            if (! Schema::hasColumn('budget_items', 'supplier')) {
                $table->string('supplier')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('budget_items', 'is_approved')) {
                $table->boolean('is_approved')->default(true)->after('supplier');
            }
            if (! Schema::hasColumn('budget_items', 'scope')) {
                $table->string('scope', 40)->default('in_campus')->after('is_approved');
            }
        });

        Schema::table('expense_receipt_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_receipt_reviews', 'supplier')) {
                $table->string('supplier')->nullable()->after('item_name');
            }
            if (! Schema::hasColumn('expense_receipt_reviews', 'organization_name')) {
                $table->string('organization_name')->nullable()->after('supplier');
            }
        });

        if (! Schema::hasTable('org_fund_accounts')) {
            Schema::create('org_fund_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('organization_name');
                $table->string('college')->nullable();
                $table->unsignedBigInteger('total_funds')->default(0);
                $table->unsignedBigInteger('beginning_balance')->default(0);
                $table->unsignedBigInteger('total_funds_received')->default(0);
                $table->string('fiscal_year', 16)->default('2025-2026');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('org_fund_sources')) {
            Schema::create('org_fund_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('org_fund_account_id')->constrained('org_fund_accounts')->cascadeOnDelete();
                $table->string('category'); // ssc_fee, fundraising, sponsorship, etc.
                $table->string('label');
                $table->unsignedBigInteger('amount')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('org_report_statuses')) {
            Schema::create('org_report_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('report_type', 20); // fr | ar | budget
                $table->string('organization_name')->nullable();
                $table->string('college')->nullable();
                $table->string('semester')->nullable();
                $table->string('academic_year')->nullable();
                $table->string('status', 40)->default('draft');
                $table->string('returned_to', 40)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('student_feedback')) {
            Schema::create('student_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->string('author_name')->nullable();
                $table->string('college')->nullable();
                $table->string('program')->nullable();
                $table->string('topic')->default('general');
                $table->text('body');
                $table->boolean('is_anonymous')->default(false);
                $table->string('visibility', 20)->default('oso'); // oso | public
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tosa_applicants')) {
            Schema::create('tosa_applicants', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('sr_code', 32)->nullable();
                $table->string('college')->nullable();
                $table->string('program')->nullable();
                $table->string('year_level')->nullable();
                $table->string('organization_name')->nullable();
                $table->string('subsection', 40)->default('pending'); // pending | screening | interview | accepted | rejected
                $table->string('status', 40)->default('submitted');
                $table->json('requirements')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_compliance_docs')) {
            Schema::create('activity_compliance_docs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('org_activity_id')->nullable()->constrained('org_activities')->nullOnDelete();
                $table->foreignId('submission_id')->nullable()->constrained('in_campus_activity_submissions')->nullOnDelete();
                $table->string('doc_key');
                $table->string('title');
                $table->string('status', 40)->default('pending'); // pending | approved | returned
                $table->string('returned_to', 40)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_compliance_docs');
        Schema::dropIfExists('tosa_applicants');
        Schema::dropIfExists('student_feedback');
        Schema::dropIfExists('org_report_statuses');
        Schema::dropIfExists('org_fund_sources');
        Schema::dropIfExists('org_fund_accounts');
    }
};

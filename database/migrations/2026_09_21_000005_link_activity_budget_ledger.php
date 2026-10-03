<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection('mysql')->table('org_activities', function (Blueprint $table) {
            $table->decimal('approved_budget', 16, 2)->default(0)->change();
            $table->decimal('implemented_budget', 16, 2)->default(0)->change();
            $table->decimal('opening_spent', 16, 2)->default(0);
            $table->foreignId('org_fund_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('sdo_review_notes')->nullable();
        });
        Schema::connection('mysql')->table('budget_items', function (Blueprint $table) {
            $table->foreignId('org_activity_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->decimal('allocated', 16, 2)->default(0)->change();
            $table->decimal('utilized', 16, 2)->default(0)->change();
        });
        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->foreignId('org_activity_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->uuid('request_key')->nullable()->unique();
            $table->string('file_hash', 64)->nullable();
            $table->unique(['org_activity_id', 'file_hash'], 'receipt_activity_file_unique');
        });
        Schema::connection('mysql')->create('activity_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('role', 32);
            $table->string('from_status', 40);
            $table->string('to_status', 40);
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
        });

        // Link legacy receipts only where both title and organization resolve
        // unambiguously. Preserve prior utilization as an explicit opening amount.
        $db = DB::connection('mysql');
        foreach ($db->table('expense_receipt_reviews')->get() as $receipt) {
            $matches = $db->table('org_activities')->where('title', $receipt->activity_title)
                ->when($receipt->organization_name, fn ($q) => $q->where('organization_name', $receipt->organization_name))->get();
            if ($matches->count() === 1) {
                $db->table('expense_receipt_reviews')->where('id', $receipt->id)->update([
                    'org_activity_id' => $matches->first()->id,
                    'organization_name' => $matches->first()->organization_name,
                ]);
            }
        }
        foreach ($db->table('org_activities')->get() as $activity) {
            $receipts = (float) $db->table('expense_receipt_reviews')->where('org_activity_id', $activity->id)
                ->whereIn('verification_status', ['verified', 'approved'])->sum(DB::raw('quantity * unit_cost'));
            $db->table('org_activities')->where('id', $activity->id)->update([
                'opening_spent' => max(0, (float) $activity->implemented_budget - $receipts),
                'implemented_budget' => max((float) $activity->implemented_budget, $receipts),
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('activity_workflow_events');
        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->dropUnique('receipt_activity_file_unique');
            $table->dropConstrainedForeignId('org_activity_id');
            $table->dropColumn(['uploaded_by', 'request_key', 'file_hash']);
        });
        Schema::connection('mysql')->table('budget_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('org_activity_id'));
        Schema::connection('mysql')->table('org_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('org_fund_account_id');
            $table->dropColumn(['opening_spent', 'approved_at', 'sdo_review_notes']);
        });
    }
};

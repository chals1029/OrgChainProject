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
        Schema::connection('mysql')->table('org_fund_accounts', function (Blueprint $table) {
            $table->decimal('cash_opening_balance', 16, 2)->default(0)->after('total_funds_received');
            // Non-unique: existing duplicate org/year rows are preserved. The
            // index keeps account row locks from scanning every organization.
            $table->index(['organization_name', 'fiscal_year'], 'org_fund_accounts_org_year_index');
        });

        // Earlier approval and cash checks used total_funds as the annual
        // capital, so it becomes the saved opening. Legacy columns and
        // org_fund_sources stay untouched; their credits are already inside it.
        DB::connection('mysql')->table('org_fund_accounts')->update([
            'cash_opening_balance' => DB::raw('total_funds'),
        ]);

        Schema::connection('mysql')->create('org_cash_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_fund_account_id')->constrained()->restrictOnDelete();
            $table->string('organization_name');
            $table->foreignId('org_activity_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('transaction_date');
            $table->decimal('amount', 16, 2);
            $table->string('purpose');
            $table->string('received_from');
            $table->string('reference', 120);
            $table->uuid('request_key')->unique();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->unique(['org_fund_account_id', 'reference'], 'org_cash_income_reference_unique');
            $table->index(['org_fund_account_id', 'transaction_date'], 'org_cash_income_account_date');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('org_cash_incomes');
        Schema::connection('mysql')->table('org_fund_accounts', function (Blueprint $table) {
            $table->dropIndex('org_fund_accounts_org_year_index');
            $table->dropColumn('cash_opening_balance');
        });
    }
};

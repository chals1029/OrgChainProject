<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'opened_at' => fn (Blueprint $table) => $table->timestamp('opened_at')->nullable()->after('submitted_at'),
            'opened_by' => fn (Blueprint $table) => $table->unsignedBigInteger('opened_by')->nullable()->after('opened_at'),
        ] as $column => $addColumn) {
            if (! Schema::hasColumn('org_report_statuses', $column)) {
                Schema::table('org_report_statuses', $addColumn);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('org_report_statuses')) {
            return;
        }

        $columns = collect(['opened_at', 'opened_by'])
            ->filter(fn (string $column): bool => Schema::hasColumn('org_report_statuses', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('org_report_statuses', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};

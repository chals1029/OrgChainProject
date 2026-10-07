<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('org_renewal_documents')) {
            return;
        }

        $schema->table('org_renewal_documents', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('org_renewal_documents', 'review_status')) {
                $table->string('review_status', 20)->default('pending')->after('file_name');
            }
            if (! $schema->hasColumn('org_renewal_documents', 'review_remarks')) {
                $table->text('review_remarks')->nullable()->after('review_status');
            }
            if (! $schema->hasColumn('org_renewal_documents', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable()->after('review_remarks');
            }
            if (! $schema->hasColumn('org_renewal_documents', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('org_renewal_documents')) {
            return;
        }

        $schema->table('org_renewal_documents', function (Blueprint $table) use ($schema) {
            $columns = array_values(array_filter(
                ['review_status', 'review_remarks', 'reviewed_at', 'reviewed_by'],
                fn (string $column): bool => $schema->hasColumn('org_renewal_documents', $column),
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

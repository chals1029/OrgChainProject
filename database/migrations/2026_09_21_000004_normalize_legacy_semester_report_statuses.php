<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Before the paired workflow existed, demo AR/FR rows could claim a
        // review stage without a submission batch or staged documents. Reset
        // only those unbatched legacy rows; real new packages carry batch_key.
        DB::table('org_report_statuses')
            ->whereIn('report_type', ['ar', 'fr'])
            ->whereNull('batch_key')
            ->update([
                'status' => 'draft',
                'returned_to' => null,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'archived_at' => null,
                'archive_folder_id' => null,
            ]);
    }

    public function down(): void
    {
        // Legacy status values are intentionally not reconstructed.
    }
};

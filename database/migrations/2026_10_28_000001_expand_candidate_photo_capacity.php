<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('voting');
        $schema = Schema::connection('voting');

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            || ! $schema->hasTable('candidates') || ! $schema->hasColumn('candidates', 'image_blob')) {
            return;
        }

        if (in_array(strtolower($schema->getColumnType('candidates', 'image_blob')), ['tinyblob', 'blob'], true)) {
            $connection->statement('ALTER TABLE candidates MODIFY image_blob MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        // Never shrink a column that may contain photos larger than 64 KB.
    }
};

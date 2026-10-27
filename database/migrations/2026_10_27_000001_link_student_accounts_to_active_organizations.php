<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        $connection = DB::connection('mysql');
        $foreignKeys = $schema->getForeignKeys('user_accounts');
        foreach ($foreignKeys as $key) {
            if ($key['columns'] !== ['org_id']) {
                continue;
            }
            if ($key['foreign_table'] === 'student_organizations' && $key['foreign_columns'] === ['id']) {
                return;
            }
            $schema->table('user_accounts', function (Blueprint $table) use ($key): void {
                $table->dropForeign($key['name']);
            });
        }
        $dangling = $connection->table('user_accounts as account')
            ->whereNotNull('account.org_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('student_organizations as organization')
                    ->whereColumn('organization.id', 'account.org_id');
            })->exists();
        if ($dangling) {
            throw new RuntimeException('Resolve student organization memberships before linking the active registry.');
        }
        $schema->table('user_accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('org_id')->nullable()->change();
            $table->foreign('org_id')->references('id')->on('student_organizations');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('user_accounts', function (Blueprint $table): void {
            $table->dropForeign(['org_id']);
        });
    }
};

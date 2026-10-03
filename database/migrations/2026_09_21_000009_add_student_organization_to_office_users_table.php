<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('office_users', 'student_organization_id')) {
            Schema::table('office_users', function (Blueprint $table): void {
                $table->foreignId('student_organization_id')
                    ->nullable()
                    ->after('office_role')
                    ->constrained('student_organizations')
                    ->nullOnDelete();
            });
        }

        // Keep the existing development SO account tied to the real CICS-SC
        // organization record. OSO/SDO/OVCAA accounts remain institution-wide.
        $organizationId = DB::table('student_organizations')
            ->where('name', 'College of Informatics and Computing Sciences Student Council (CICS-SC)')
            ->value('id');

        if ($organizationId) {
            DB::table('office_users')
                ->where('office_role', 'so')
                ->whereIn('email', [
                    'so.office@g.batstate-u.edu.ph',
                    'so@orgchain.local',
                ])
                ->update(['student_organization_id' => $organizationId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('office_users', 'student_organization_id')) {
            Schema::table('office_users', function (Blueprint $table): void {
                $table->dropForeign(['student_organization_id']);
                $table->dropColumn('student_organization_id');
            });
        }
    }
};

<?php

namespace Database\Seeders;

use App\Models\StudentOrganization;
use Illuminate\Database\Seeder;

class StudentOrganizationSeeder extends Seeder
{
    /**
     * Seeds the recognized / renewed orgs for AY 2025-2026.
     * Source: OSO ARASOF-Nasugbu list. Real contact emails from that
     * document are intentionally NOT seeded — contacts stay null.
     */
    public function run(): void
    {
        $year = config('student_orgs.academic_year', '2025-2026');

        foreach (config('student_orgs.organizations', []) as $org) {
            StudentOrganization::updateOrCreate(
                ['name' => $org['name']],
                [
                    'short_name' => $org['short'] ?? null,
                    'college' => $org['college'] ?? null,
                    'academic_year' => $year,
                    'is_active' => true,
                ]
            );
        }
    }
}

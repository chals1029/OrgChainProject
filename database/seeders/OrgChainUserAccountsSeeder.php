<?php

namespace Database\Seeders;

use App\Models\UserAccount;
use Illuminate\Database\Seeder;

class OrgChainUserAccountsSeeder extends Seeder
{
    public function run(): void
    {
        UserAccount::query()->updateOrCreate(
            ['sr_code' => '21-00001'],
            [
                'user_id' => 1,
                'org_id' => null,
                'sr_code' => '21-00001',
                'full_name' => 'Charles Samotanez',
                'password_hash' => null,
                'email' => '21-00001@g.batstate-u.edu.ph',
                'college' => 'College of Informatics and Computing Sciences',
                'program' => 'BS Information Technology',
                'year_level' => '4th Year',
                'role' => 'student',
                'account_status' => 'active',
                'created_at' => now(),
            ]
        );

        UserAccount::query()->updateOrCreate(
            ['sr_code' => '21-00002'],
            [
                'user_id' => 2,
                'org_id' => null,
                'sr_code' => '21-00002',
                'full_name' => 'Maria Santos',
                'password_hash' => null,
                'email' => '21-00002@g.batstate-u.edu.ph',
                'college' => 'College of Arts and Sciences',
                'program' => 'BS Psychology',
                'year_level' => '3rd Year',
                'role' => 'student',
                'account_status' => 'active',
                'created_at' => now(),
            ]
        );
    }
}

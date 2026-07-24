<?php

namespace Database\Seeders;

use App\Models\OfficeUser;
use Illuminate\Database\Seeder;

class OfficeUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'so.office',
                'email' => 'so.office@g.batstate-u.edu.ph',
                'name' => 'SO Officer',
                'office_role' => 'so',
                'office_title' => 'Student Organization',
            ],
            [
                'username' => 'oso.office',
                'email' => 'oso.office@g.batstate-u.edu.ph',
                'name' => 'OSO Officer',
                'office_role' => 'oso',
                'office_title' => 'Office of Student Organization',
            ],
            [
                'username' => 'sdo.office',
                'email' => 'sdo.office@g.batstate-u.edu.ph',
                'name' => 'SDO Officer',
                'office_role' => 'sdo',
                'office_title' => 'Sustainable Development Office',
            ],
            [
                'username' => 'ovcaa.office',
                'email' => 'ovcaa.office@g.batstate-u.edu.ph',
                'name' => 'OVCAA Officer',
                'office_role' => 'ovcaa',
                'office_title' => 'Office of the Vice Chancellor for Academic Affairs',
            ],
        ];

        foreach ($accounts as $account) {
            OfficeUser::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'password' => 'Office@2026!',
                    'office_role' => $account['office_role'],
                    'office_title' => $account['office_title'],
                    'is_active' => true,
                ]
            );
        }
    }
}

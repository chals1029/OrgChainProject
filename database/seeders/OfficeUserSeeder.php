<?php

namespace Database\Seeders;

use App\Models\OfficeUser;
use Illuminate\Database\Seeder;

class OfficeUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('OFFICE_SEED_PASSWORD');
        if ($password === '') {
            throw new \RuntimeException('Set OFFICE_SEED_PASSWORD privately before seeding office accounts.');
        }

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
            [
                'username' => 'oc.office',
                'email' => 'oc.office@g.batstate-u.edu.ph',
                'name' => 'OC Officer',
                'office_role' => 'oc',
                'office_title' => 'Office of the Chancellor',
            ],
        ];

        foreach ($accounts as $account) {
            OfficeUser::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'password' => $password,
                    'office_role' => $account['office_role'],
                    'office_title' => $account['office_title'],
                    'is_active' => true,
                ]
            );
        }
    }
}

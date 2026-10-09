<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VotingStaffSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::connection('mysql')->hasTable('admin_users')) {
            return;
        }

        $adminPassword = (string) env('VOTING_ADMIN_SEED_PASSWORD');
        $canvassingPassword = (string) env('VOTING_CANVASSING_SEED_PASSWORD');
        if ($adminPassword === '' || $canvassingPassword === '') {
            throw new \RuntimeException('Set VOTING_ADMIN_SEED_PASSWORD and VOTING_CANVASSING_SEED_PASSWORD privately before seeding voting staff.');
        }

        $accounts = [
            [
                'name' => 'SSC Election Admin',
                'email' => 'admin@ssc.test',
                'password' => $adminPassword,
                'role' => 'admin',
            ],
            [
                'name' => 'SSC Election Commission Admin',
                'email' => 'ssc.admin@g.batstate-u.edu.ph',
                'password' => $adminPassword,
                'role' => 'admin',
            ],
            [
                'name' => 'SSC Canvassing Officer',
                'email' => 'canvass@ssc.test',
                'password' => $canvassingPassword,
                'role' => 'canvassing',
            ],
            [
                'name' => 'SSC Canvassing Officer',
                'email' => 'canvassing@ssc.test',
                'password' => $canvassingPassword,
                'role' => 'canvassing',
            ],
            [
                'name' => 'SSC Canvassing Desk Officer',
                'email' => 'ssc.canvass@g.batstate-u.edu.ph',
                'password' => $canvassingPassword,
                'role' => 'canvassing',
            ],
        ];

        foreach ($accounts as $acc) {
            $existing = DB::connection('mysql')->table('admin_users')->where('email', $acc['email'])->first();

            $data = [
                'name' => $acc['name'],
                'email' => strtolower($acc['email']),
                'password_hash' => password_hash($acc['password'], PASSWORD_DEFAULT),
                'role' => $acc['role'],
                'is_active' => 1,
            ];

            if ($existing) {
                DB::connection('mysql')->table('admin_users')->where('id', $existing->id)->update($data);
            } else {
                DB::connection('mysql')->table('admin_users')->insert($data);
            }
        }
    }
}

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

        $accounts = [
            [
                'name' => 'SSC Election Admin',
                'email' => 'admin@ssc.test',
                'password' => 'Admin@2026!',
                'role' => 'admin',
            ],
            [
                'name' => 'SSC Election Commission Admin',
                'email' => 'ssc.admin@g.batstate-u.edu.ph',
                'password' => 'Admin@2026!',
                'role' => 'admin',
            ],
            [
                'name' => 'SSC Canvassing Officer',
                'email' => 'canvass@ssc.test',
                'password' => 'Canvass@2026!',
                'role' => 'canvassing',
            ],
            [
                'name' => 'SSC Canvassing Officer',
                'email' => 'canvassing@ssc.test',
                'password' => 'Canvass@2026!',
                'role' => 'canvassing',
            ],
            [
                'name' => 'SSC Canvassing Desk Officer',
                'email' => 'ssc.canvass@g.batstate-u.edu.ph',
                'password' => 'Canvass@2026!',
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

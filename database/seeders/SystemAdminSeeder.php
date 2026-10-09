<?php

namespace Database\Seeders;

use App\Models\SystemAdminUser;
use Illuminate\Database\Seeder;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('orgchain.system_admin_seed_email')));
        $password = (string) config('orgchain.system_admin_seed_password');

        if ($password === '') {
            throw new \RuntimeException('Set SYSTEM_ADMIN_PASSWORD privately before seeding the system administrator.');
        }

        SystemAdminUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'OrgChain System Administrator',
                'username' => 'system_admin',
                'password' => $password,
                'role' => 'system_admin',
                'is_active' => true,
            ]
        );
    }
}

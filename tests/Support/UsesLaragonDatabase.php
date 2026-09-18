<?php

namespace Tests\Support;

use App\Models\OfficeUser;
use App\Models\UserAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Point Feature tests at the real Laragon MySQL backends (votingsystem + orgchain).
 * PHPUnit forces DB_DATABASE=:memory: for the default sqlite connection; this restores
 * named mysql/orgchain connections (and default) from the project .env so backend
 * auth / renewal / DB tests never skip.
 */
trait UsesLaragonDatabase
{
    protected function useLaragonDatabase(): void
    {
        $env = $this->readProjectEnv();

        $mysql = [
            'driver' => 'mysql',
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? 'votingsystem',
            'username' => $env['DB_USERNAME'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        $orgchain = $mysql;
        $orgchain['database'] = $env['DB_ORGCHAIN_DATABASE'] ?? 'orgchain';

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql', []), $mysql),
            'database.connections.orgchain' => array_merge(config('database.connections.orgchain', []), $orgchain),
        ]);

        DB::purge('mysql');
        DB::purge('orgchain');
        DB::setDefaultConnection('mysql');
    }

    /**
     * @return array<string, string>
     */
    protected function readProjectEnv(): array
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return [];
        }

        $vars = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value, " \t\"'");
        }

        return $vars;
    }

    protected function ensureOfficeUser(string $role): OfficeUser
    {
        $email = "{$role}.office@g.batstate-u.edu.ph";

        $user = OfficeUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => strtoupper($role).' Office',
                'username' => $role.'_office',
                'password' => Hash::make('Office@2026!'),
                'office_role' => $role,
                'office_title' => strtoupper($role).' Desk',
                'is_active' => true,
            ]
        );

        if (! Hash::check('Office@2026!', $user->password)) {
            $user->forceFill(['password' => 'Office@2026!'])->save();
            $user->refresh();
        }

        return $user;
    }

    protected function ensureActiveStudent(string $srCode = '21-00001'): UserAccount
    {
        return UserAccount::query()->updateOrCreate(
            ['sr_code' => $srCode],
            [
                'full_name' => 'Test Student',
                'email' => $srCode.'@g.batstate-u.edu.ph',
                'college' => 'CICS',
                'program' => 'BSIT',
                'year_level' => '4th Year',
                'role' => 'student',
                'account_status' => 'active',
                'password_hash' => Hash::make('Student@2026!'),
                'created_at' => now(),
            ]
        );
    }
}

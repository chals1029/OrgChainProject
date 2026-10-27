<?php

namespace Tests\Support;

use App\Models\OfficeUser;
use App\Models\StudentOrganization;
use App\Models\SystemAdminUser;
use App\Models\UserAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Configure explicitly isolated core and voting databases for MySQL feature tests. */
trait UsesLaragonDatabase
{
    protected function useLaragonDatabase(): void
    {
        $env = $this->readProjectEnv();

        $coreDatabase = getenv('ORGCHAIN_TEST_DATABASE');
        $votingDatabase = getenv('ORGCHAIN_TEST_VOTING_DATABASE');
        $live = ['orgchain', 'votingsystem', $env['DB_DATABASE'] ?? '', $env['VOTING_DB_DATABASE'] ?? ''];
        if (! $coreDatabase || ! $votingDatabase || $coreDatabase === $votingDatabase
            || in_array($coreDatabase, $live, true) || in_array($votingDatabase, $live, true)) {
            throw new \RuntimeException('Set distinct isolated ORGCHAIN_TEST_DATABASE and ORGCHAIN_TEST_VOTING_DATABASE schemas.');
        }

        $mysql = [
            'driver' => 'mysql',
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $coreDatabase,
            'username' => $env['DB_USERNAME'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        $voting = $mysql;
        $voting['database'] = $votingDatabase;

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql', []), $mysql),
            'database.connections.voting' => $voting,
        ]);

        DB::purge('mysql');
        DB::purge('voting');
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
        $organization = $role === 'so'
            ? StudentOrganization::updateOrCreate(['name' => 'Synthetic Shared Test Organization'], [
                'short_name' => 'TEST', 'college' => 'Fixture College', 'academic_year' => '2026-2027', 'is_active' => true,
            ])
            : null;

        $user = OfficeUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => strtoupper($role).' Office',
                'username' => $role.'_office',
                'password' => Hash::make('FixtureOfficeOnly@2026!'),
                'office_role' => $role,
                'office_title' => strtoupper($role).' Desk',
                'student_organization_id' => $organization?->id,
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        if (! Hash::check('FixtureOfficeOnly@2026!', $user->password)) {
            $user->forceFill(['password' => 'FixtureOfficeOnly@2026!'])->save();
            $user->refresh();
        }

        return $user;
    }

    protected function ensureSystemAdmin(): SystemAdminUser
    {
        return SystemAdminUser::query()->updateOrCreate(
            ['email' => 'system.admin@g.batstate-u.edu.ph'],
            [
                'name' => 'OrgChain System Administrator',
                'username' => 'system_admin',
                'password' => 'FixtureSystemAdminOnly@2026!',
                'role' => 'system_admin',
                'is_active' => true,
            ]
        );
    }

    protected function ensureActiveStudent(string $srCode = '21-00001'): UserAccount
    {
        $user = UserAccount::query()->firstOrNew(['sr_code' => $srCode]);
        if (! $user->exists) {
            $user->user_id = (int) UserAccount::query()->max('user_id') + 1;
            // Imported schemas may require explicit IDs rather than AUTO_INCREMENT.
            $user->incrementing = false;
        }

        $user->fill([
            'full_name' => 'Test Student',
            'email' => $srCode.'@g.batstate-u.edu.ph',
            'college' => 'CICS',
            'program' => 'BSIT',
            'year_level' => '4th Year',
            'role' => 'student',
            'account_status' => 'active',
            'password_hash' => Hash::make('FixtureStudentOnly@2026!'),
            'created_at' => now(),
        ])->save();

        return $user;
    }
}

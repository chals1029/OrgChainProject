<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgRenewalWindow;
use App\Models\UserAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

/**
 * Live Laragon database backend checks (votingsystem mysql + orgchain).
 * These always run against the real MySQL from .env — not sqlite memory.
 */
class DatabaseBackendTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_mysql_votingsystem_connection_works(): void
    {
        $pdo = DB::connection('mysql')->getPdo();
        $this->assertNotNull($pdo);

        $db = DB::connection('mysql')->selectOne('select database() as db');
        $this->assertSame('votingsystem', $db->db);
    }

    public function test_orgchain_connection_works(): void
    {
        $pdo = DB::connection('orgchain')->getPdo();
        $this->assertNotNull($pdo);

        $db = DB::connection('orgchain')->selectOne('select database() as db');
        $this->assertSame('orgchain', $db->db);
    }

    public function test_required_votingsystem_tables_exist(): void
    {
        $required = [
            'office_users',
            'users',
            'org_renewal_windows',
            'org_renewal_submissions',
            'org_renewal_documents',
            'budget_items',
            'expense_receipt_reviews',
        ];

        foreach ($required as $table) {
            $this->assertTrue(
                Schema::connection('mysql')->hasTable($table),
                "Missing mysql/votingsystem table: {$table}"
            );
        }
    }

    public function test_required_orgchain_tables_exist(): void
    {
        $required = [
            'user_accounts',
            'community_posts',
            'community_comments',
            'community_likes',
        ];

        foreach ($required as $table) {
            $this->assertTrue(
                Schema::connection('orgchain')->hasTable($table),
                "Missing orgchain table: {$table}"
            );
        }
    }

    public function test_office_roles_exist_for_all_desks(): void
    {
        foreach (['so', 'oso', 'sdo', 'ovcaa'] as $role) {
            $user = $this->ensureOfficeUser($role);
            $this->assertTrue($user->is_active);
            $this->assertSame($role, $user->office_role);
            $this->assertStringContainsString('@g.batstate-u.edu.ph', $user->email);
        }

        $this->assertGreaterThanOrEqual(
            4,
            OfficeUser::query()->where('is_active', true)->count()
        );
    }

    public function test_active_student_account_exists_for_otp(): void
    {
        $student = $this->ensureActiveStudent('21-00001');

        $this->assertSame('active', $student->account_status);
        $this->assertSame('21-00001', $student->sr_code);
        $this->assertGreaterThanOrEqual(
            1,
            UserAccount::query()->where('account_status', 'active')->count()
        );
    }

    public function test_renewal_window_row_can_be_read_and_written(): void
    {
        $before = OrgRenewalWindow::query()->count();

        $row = OrgRenewalWindow::query()->create([
            'academic_year' => '2099-2100',
            'semester' => 'DB Backend Test',
            'is_open' => false,
            'instructions' => 'Temporary row from DatabaseBackendTest',
            'required_docs' => OrgRenewalWindow::defaultRequiredDocs(),
        ]);

        $this->assertNotNull($row->id);
        $this->assertSame($before + 1, OrgRenewalWindow::query()->count());

        $row->delete();
        $this->assertSame($before, OrgRenewalWindow::query()->count());
    }

    public function test_budget_chain_storage_directories_are_writable(): void
    {
        $root = storage_path('app/orgchain/budget');
        foreach (['node-1', 'node-2', 'node-3'] as $node) {
            $dir = $root.DIRECTORY_SEPARATOR.$node;
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->assertDirectoryExists($dir);
            $this->assertTrue(is_writable($dir), "Budget chain dir not writable: {$dir}");
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgRenewalWindow;
use App\Models\UserAccount;
use App\VotingSystem\Core\Database as VotingDatabase;
use App\VotingSystem\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

/** MySQL backend behavior on explicitly isolated test schemas. */
class DatabaseBackendTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_candidate_photo_larger_than_64_kilobytes_is_preserved(): void
    {
        $voting = DB::connection('voting');
        $connectionProperty = new \ReflectionProperty(VotingDatabase::class, 'connection');
        $previousConnection = $connectionProperty->getValue();
        $connectionProperty->setValue(null, null);
        $voting->beginTransaction();

        try {
            $election = $voting->table('elections')->insertGetId(['title' => 'Synthetic Photo Election', 'status' => 'open']);
            $position = $voting->table('positions')->insertGetId(['election_id' => $election, 'title' => 'Synthetic Position']);
            $image = imagecreatetruecolor(200, 200);
            ob_start();
            imagepng($image, null, 0);
            $png = ob_get_clean();
            $this->assertGreaterThan(65535, strlen($png));

            (new Candidate)->create([
                'position_id' => $position,
                'name' => 'Synthetic Photo Candidate',
                'party' => 'Fixture',
                'image_blob' => $png,
                'image_mime' => 'image/png',
                'sort_order' => 1,
            ]);

            $stored = $voting->table('candidates')->where('position_id', $position)->first(['image_blob', 'image_mime']);
            $this->assertSame('image/png', $stored->image_mime);
            $this->assertSame($png, $stored->image_blob);
        } finally {
            $voting->rollBack();
            $connectionProperty->setValue(null, $previousConnection);
        }
    }

    public function test_office_roles_exist_for_all_desks(): void
    {
        foreach (['so', 'oso', 'sdo', 'ovcaa', 'oc'] as $role) {
            $user = $this->ensureOfficeUser($role);
            $this->assertTrue($user->is_active);
            $this->assertSame($role, $user->office_role);
            $this->assertStringContainsString('@g.batstate-u.edu.ph', $user->email);
        }

        $this->assertGreaterThanOrEqual(
            5,
            OfficeUser::query()->where('is_active', true)->count()
        );
    }

    public function test_active_student_account_exists_for_otp(): void
    {
        $student = $this->ensureActiveStudent('21-00001');

        $this->assertSame('active', $student->account_status);
        $this->assertSame('21-00001', $student->sr_code);
        $this->assertGreaterThan(0, $student->user_id);
        $this->assertSame($student->user_id, $this->ensureActiveStudent('21-00001')->user_id);
        $this->assertDatabaseHas('user_accounts', [
            'user_id' => $student->user_id,
            'sr_code' => '21-00001',
        ], 'mysql');
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

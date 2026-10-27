<?php

namespace Tests\Feature;

use App\Models\ArchiveDocument;
use App\Models\OfficeTemplate;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgReportDocument;
use App\Models\StudentFeedback;
use App\Models\StudentOrganization;
use App\Models\UserAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ReportReviewBoundaryTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $env = $this->readProjectEnv();
        foreach (['ORGCHAIN_TEST_DATABASE' => 'DB_DATABASE', 'ORGCHAIN_TEST_VOTING_DATABASE' => 'VOTING_DB_DATABASE'] as $test => $source) {
            if (!getenv($test) || getenv($test) === ($env[$source] ?? null)) {
                throw new \RuntimeException('Report review tests require isolated databases.');
            }
        }
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach (['mysql'] as $connection) {
            while (DB::connection($connection)->transactionLevel() > 0) {
                DB::connection($connection)->rollBack();
            }
        }
        parent::tearDown();
    }

    private function officer(string $role): OfficeUser
    {
        $key = (string) Str::uuid();
        $organization = $role === 'so' ? StudentOrganization::create([
            'name' => 'Upload boundary '.$key, 'college' => 'CICS',
            'academic_year' => '2030-2031', 'is_active' => true,
        ]) : null;

        return OfficeUser::create([
            'name' => 'Upload boundary reviewer', 'username' => $key,
            'email' => $key.'@example.test', 'password' => Str::random(32),
            'office_role' => $role, 'office_title' => 'Isolated reviewer',
            'student_organization_id' => $organization?->id,
            'is_active' => true, 'must_change_password' => false,
        ]);
    }

    public static function emptyDocumentEndpoints(): array
    {
        return [
            'official template' => ['oso', '/office-desk/updates/templates', 'template_file', ['name' => 'Boundary', 'category' => 'Forms']],
            'renewal requirement' => ['oso', '/office-desk/renewal/requirements', 'document', ['window_id' => 1, 'code' => 'boundary', 'title' => 'Boundary']],
            'renewal template' => ['oso', '/office-desk/renewal/requirements/template', 'document', ['window_id' => 1, 'doc_key' => 'boundary']],
            'archive' => ['oso', '/office-desk/archive/documents', 'document', ['archive_folder_id' => 1]],
            'accomplishment report' => ['so', '/office-desk/reports/ar/documents', 'document', ['semester' => '1st Semester', 'academic_year' => '2030-2031']],
        ];
    }

    #[DataProvider('emptyDocumentEndpoints')]
    public function test_empty_document_never_creates_metadata_or_a_stored_file(string $role, string $url, string $field, array $payload): void
    {
        $office = $this->officer($role);
        if ($role === 'so') {
            $payload['organization_name'] = $office->studentOrganization->name;
        }
        $before = [OfficeTemplate::count(), ArchiveDocument::count(), OrgReportDocument::count()];
        $this->actingAs($office, 'office')->postJson($url, $payload + [
            $field => UploadedFile::fake()->createWithContent('empty.pdf', ''),
        ])->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, [OfficeTemplate::count(), ArchiveDocument::count(), OrgReportDocument::count()]);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_concern_search_intersects_filters_and_retains_them_across_pages(): void
    {
        $office = $this->officer('oso');
        $needle = 'Boundary '.Str::uuid();
        $matching = [];
        for ($index = 0; $index < 16; $index++) {
            $matching[] = StudentFeedback::create([
                'author_name' => $needle, 'college' => 'CICS', 'topic' => 'campus',
                'body' => 'Campus accessibility concern '.$index, 'status' => 'pending',
            ])->id;
        }
        foreach ([['college' => 'CAS'], ['topic' => 'admin'], ['status' => 'dismissed'], ['author_name' => 'Unrelated person']] as $different) {
            StudentFeedback::create(array_replace([
                'author_name' => $needle, 'college' => 'CICS', 'topic' => 'campus',
                'body' => 'Not part of the filtered concern queue.', 'status' => 'pending',
            ], $different));
        }
        $filters = ['q' => $needle, 'college' => 'CICS', 'topic' => 'campus', 'status' => 'pending'];
        $first = $this->actingAs($office, 'office')->get(route('office.student-reports', $filters))->assertOk()->viewData('reports');
        $second = $this->get(route('office.student-reports', $filters + ['page' => 2]))->assertOk()->viewData('reports');
        $actual = $first->getCollection()->pluck('id')->merge($second->getCollection()->pluck('id'))->sort()->values()->all();
        sort($matching);
        $this->assertSame($matching, $actual);
        parse_str(parse_url($first->nextPageUrl(), PHP_URL_QUERY), $next);
        $this->assertSame($filters + ['page' => '2'], $next);
    }

    public function test_student_department_cannot_be_overridden_by_filters_or_foreign_rsvp(): void
    {
        OrgActivity::query()->delete();
        $student = new UserAccount([
            'user_id' => (int) UserAccount::max('user_id') + 1,
            'sr_code' => '99-'.Str::lower(Str::random(8)), 'full_name' => 'Department boundary student',
            'email' => Str::uuid().'@example.test', 'college' => 'CICS', 'program' => 'BSIT',
            'role' => 'student', 'account_status' => 'active',
        ]);
        $student->incrementing = false;
        $student->save();
        $own = [];
        foreach ([['CICS', 1200, 100], ['College of Informatics and Computing Sciences', 800, 200], ['CAS', 9000, 5000]] as $index => [$college, $budget, $spent]) {
            $organization = StudentOrganization::create([
                'name' => 'Department boundary '.Str::uuid(), 'college' => $college,
                'academic_year' => '2030-2031', 'is_active' => true,
            ]);
            $activity = OrgActivity::create([
                'title' => 'Department visibility '.$index, 'organization_name' => $organization->name,
                'college' => $college, 'approved_budget' => $budget, 'implemented_budget' => $spent,
                'workflow_status' => 'oc_approved', 'status' => 'upcoming', 'activity_scope' => $index === 1 ? 'local_off_campus' : 'in_campus',
                'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(2), 'sdg_goals' => ['SDG 4'],
            ]);
            if ($index < 2) $own[] = $activity->id;
            else $foreign = $activity;
        }
        $this->actingAs($student, 'student');
        foreach ([[], ['college' => ''], ['college' => 'CAS'], ['college' => 'all']] as $filters) {
            $response = $this->get(route('portal.home', $filters))->assertOk();
            $this->assertSame(2000.0, $response->viewData('totalAllocated'));
            $this->assertSame(300.0, $response->viewData('totalUtilized'));
            $offCampus = collect($response->viewData('budgetCategoryBreakdown'))->firstWhere('category', 'Off-Campus');
            $this->assertSame(800.0, $offCampus['allocated']);
            $this->assertSame(200.0, $offCampus['utilized']);
            $ids = $response->viewData('upcoming')->pluck('id');
            foreach ($own as $id) $this->assertTrue($ids->contains($id));
            $this->assertFalse($ids->contains($foreign->id));
        }
        $before = DB::table('activity_registrations')->count();
        $this->postJson(route('portal.activities.rsvp', $foreign))->assertNotFound();
        $this->assertSame($before, DB::table('activity_registrations')->count());
        $student->college = null;
        $student->save();
        $empty = $this->get(route('portal.home', ['college' => 'CICS']))->assertOk();
        $this->assertSame(0.0, $empty->viewData('totalAllocated'));
        $this->assertSame(0.0, $empty->viewData('totalUtilized'));
        $this->assertTrue($empty->viewData('upcoming')->isEmpty());
    }

    public function test_empty_analytics_does_not_fabricate_approved_activities_or_budgets(): void
    {
        OrgActivity::query()->delete();
        $response = $this->actingAs($this->officer('oso'), 'office')
            ->get(route('office.analytics'))->assertOk();
        $this->assertSame([], $response->viewData('activityFinancials'));
        $this->assertSame(0, $response->viewData('overview')['inCampusAllocated']);
        $this->assertSame(0, $response->viewData('overview')['offCampusAllocated']);
    }
}

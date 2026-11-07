<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\StudentFeedback;
use App\Models\UserAccount;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class StudentReportsOsoTest extends TestCase
{
    use UsesLaragonDatabase;

    private OfficeUser $oso;
    private UserAccount $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();

        $this->student = new UserAccount([
            'user_id' => (int) UserAccount::max('user_id') + 1,
            'sr_code' => '24-'.Str::lower(Str::random(8)),
            'full_name' => 'Maria Santos Dela Cruz',
            'email' => Str::uuid().'@example.test',
            'college' => 'CICS',
            'program' => 'BSIT',
            'role' => 'student',
            'account_status' => 'active',
        ]);
        $this->student->incrementing = false;
        $this->student->save();

        $this->oso = OfficeUser::create([
            'name' => 'OSO Review Officer',
            'email' => Str::uuid().'@example.test',
            'username' => 'oso-'.Str::random(10),
            'password' => Str::random(30),
            'office_role' => 'oso',
            'office_title' => 'OSO Review Desk',
            'is_active' => true,
        ]);
    }

    public function test_student_reports_page_renders_all_required_elements_and_columns(): void
    {
        $report = StudentFeedback::create([
            'student_id' => $this->student->id,
            'author_name' => $this->student->full_name,
            'college' => 'CICS',
            'program' => 'BSIT',
            'topic' => 'Facility Issue',
            'body' => 'The laboratory computers in CICS Room 302 need software updates.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->oso, 'office')
            ->get(route('office.student-reports'))
            ->assertOk();

        // 1. Concern queue word bold
        $response->assertSee('<strong>Concern queue</strong>', false);

        // 2. Concern queue column headers
        $response->assertSee('Reporter (Name)');
        $response->assertSee('Date Submitted');
        $response->assertSee('Concern / Feedback');
        $response->assertSee('Status');

        // 3. Queue item columns
        $response->assertSee('sr-col-reporter', false);
        $response->assertSee('sr-col-date', false);
        $response->assertSee('sr-col-concern', false);
        $response->assertSee('sr-col-status', false);

        // 4. Word Report indicator on the report itself
        $response->assertSee('Student Report');
        $response->assertSee('Report #'.$report->id);

        // 5. Resolved button at the bottom of OSO remarks
        $response->assertSee('name="decision" value="resolved"', false);
        $response->assertSee('Resolved');
    }

    public function test_oso_can_resolve_student_report_with_remarks(): void
    {
        $report = StudentFeedback::create([
            'student_id' => $this->student->id,
            'author_name' => $this->student->full_name,
            'college' => 'CICS',
            'program' => 'BSIT',
            'body' => 'Wi-Fi connection is intermittent in the 4th floor study hall.',
            'status' => 'pending',
        ]);

        $token = 'test-token-'.Str::random(32);
        $response = $this->actingAs($this->oso, 'office')
            ->withSession(['_token' => $token])
            ->post(route('office.student-reports.review', $report), [
                '_token' => $token,
                'decision' => 'resolved',
                'review_note' => 'ICT department has replaced the router and verified coverage.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame('resolved', $report->status);
        $this->assertSame('ICT department has replaced the router and verified coverage.', $report->review_note);
        $this->assertSame((int) $this->oso->id, (int) $report->verified_by);
        $this->assertNotNull($report->verified_at);
    }
}

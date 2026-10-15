<?php

namespace Tests\Feature;

use App\Models\OfficeAnnouncement;
use App\Models\OfficeAnnouncementAcknowledgment;
use App\Models\OfficeUser;
use App\Models\StudentFeedback;
use App\Models\UserAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class AnnouncementFeedbackTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('ORGCHAIN_TEST_DATABASE') || ! getenv('ORGCHAIN_TEST_ORGCHAIN_DATABASE')) {
            throw new \RuntimeException('AnnouncementFeedbackTest requires isolated ORGCHAIN_TEST_DATABASE and ORGCHAIN_TEST_ORGCHAIN_DATABASE databases.');
        }
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        DB::connection('orgchain')->beginTransaction();
        Carbon::setTestNow('2030-05-01 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['orgchain', 'mysql'] as $connection) {
            if (DB::connection($connection)->transactionLevel() > 0) {
                DB::connection($connection)->rollBack();
            }
        }
        parent::tearDown();
    }

    private function officer(string $role = 'oso', bool $active = true): OfficeUser
    {
        $key = 'notice-test-'.Str::uuid();

        return OfficeUser::create([
            'name' => 'Notice Test Officer', 'username' => $key,
            'email' => $key.'@g.batstate-u.edu.ph', 'password' => 'IsolatedFixture@2030!',
            'office_role' => $role, 'office_title' => 'Isolated Notice Test',
            'is_active' => $active, 'must_change_password' => false,
        ]);
    }

    private function student(): UserAccount
    {
        $student = new UserAccount([
            'user_id' => (int) UserAccount::query()->max('user_id') + 1,
            'sr_code' => '99-'.Str::lower(Str::random(8)), 'full_name' => 'Isolated Notice Student',
            'email' => Str::uuid().'@g.batstate-u.edu.ph', 'college' => 'CICS',
            'program' => 'BSIT', 'year_level' => '4th Year',
            'password_hash' => password_hash('IsolatedFixture@2030!', PASSWORD_BCRYPT),
            'role' => 'student', 'account_status' => 'active',
        ]);
        $student->incrementing = false;
        $student->save();

        return $student;
    }

    private function announcement(): OfficeAnnouncement
    {
        return OfficeAnnouncement::create([
            'title' => 'Isolated notice', 'type' => 'Official Notice',
            'body' => 'Persisted official notice.', 'author' => 'OSO', 'priority' => 'normal',
        ]);
    }

    private function feedback(UserAccount $student): StudentFeedback
    {
        return StudentFeedback::create([
            'student_id' => $student->id, 'author_name' => $student->full_name,
            'college' => 'CICS', 'topic' => 'campus', 'body' => 'Private anonymous feedback.',
            'is_anonymous' => true, 'visibility' => 'oso', 'status' => 'pending',
        ]);
    }

    public function test_published_notice_has_consistent_student_bulletin_metadata_without_a_second_limit(): void
    {
        $office = $this->officer();
        $student = $this->student();
        $body = 'The filing deadline for the isolated publication fixture is Friday.';
        $this->actingAs($office, 'office')->post(route('office.updates.announcements.store'), [
            'type' => 'Deadline', 'body' => $body, 'priority' => 'high',
        ])->assertRedirect(route('office.updates'));
        $published = OfficeAnnouncement::query()->where('body', $body)->firstOrFail();
        for ($index = 1; $index <= 16; $index++) {
            Carbon::setTestNow(Carbon::parse('2030-05-01 10:00:00')->addMinutes($index));
            $this->announcement();
        }

        $response = $this->actingAs($student, 'student')->get(route('portal.home', ['announcement' => $published->id]))->assertOk();
        $row = collect($response->viewData('announcements'))->firstWhere('announcement_id', $published->id);
        $bulletin = collect($response->viewData('bulletins'))->firstWhere('announcement_id', $published->id);
        $this->assertNotNull($row);
        $this->assertSame('deadlines', $row['category']);
        $this->assertSame($body, $row['body']);
        $this->assertSame($published->created_at->toIso8601String(), $row['published_at']);
        $this->assertSame(route('portal.home', ['announcement' => $published->id]), $row['url']);
        unset($bulletin['order']);
        $this->assertSame($row, $bulletin);
    }

    public function test_student_bulletin_categories_follow_publication_type_not_body_keywords(): void
    {
        $expected = [];
        foreach ([
            'General Announcement' => 'general',
            'Deadline' => 'deadlines',
            'Official Notice' => 'oso',
            'Call for TOSA' => 'tosa',
        ] as $type => $category) {
            $notice = OfficeAnnouncement::create([
                'title' => 'Publication category fixture', 'type' => $type,
                'body' => 'This publication mentions TOSA without changing its explicit category.',
                'author' => 'OSO', 'priority' => 'normal',
            ]);
            $expected[$notice->id] = $category;
        }

        $response = $this->actingAs($this->student(), 'student')->get(route('portal.home'))->assertOk();
        foreach (['announcements', 'bulletins'] as $surface) {
            $rows = collect($response->viewData($surface))->keyBy('announcement_id');
            foreach ($expected as $id => $category) {
                $this->assertSame($category, $rows->get($id)['category']);
            }
        }
    }

    public function test_office_acknowledgment_is_persisted_idempotent_and_actor_scoped(): void
    {
        $notice = $this->announcement();
        $first = $this->officer();
        $second = $this->officer('sdo');
        $url = route('office.updates.announcements.acknowledge', $notice);
        $receipt = $this->actingAs($first, 'office')->postJson($url)->assertOk()->json('acknowledged_at');
        Carbon::setTestNow('2030-05-02 10:00:00');
        $this->postJson($url)->assertOk()->assertJsonPath('acknowledged_at', $receipt);
        $this->assertSame(1, OfficeAnnouncementAcknowledgment::query()->where('office_announcement_id', $notice->id)->count());
        $firstRow = collect($this->get(route('office.updates'))->assertOk()->viewData('announcements'))->firstWhere('id', $notice->id);
        $this->assertNotNull($firstRow['acknowledged_at']);
        $secondRow = collect($this->actingAs($second, 'office')->get($firstRow['url'])->assertOk()->viewData('announcements'))->firstWhere('id', $notice->id);
        $this->assertNull($secondRow['acknowledged_at']);
        $this->postJson($url)->assertOk();
        $this->assertSame(2, OfficeAnnouncementAcknowledgment::query()->where('office_announcement_id', $notice->id)->count());
        $this->get($firstRow['document_url'])->assertOk();
    }

    public function test_notice_acknowledgment_requires_an_active_office_account(): void
    {
        $notice = $this->announcement();
        $url = route('office.updates.announcements.acknowledge', $notice);
        $this->postJson($url)->assertUnauthorized();
        $this->actingAs($this->student(), 'student')->postJson($url)->assertUnauthorized();
        $this->actingAs($this->officer('oso', false), 'office')->postJson($url)->assertUnauthorized();
        $this->assertSame(0, OfficeAnnouncementAcknowledgment::query()->where('office_announcement_id', $notice->id)->count());
    }

    public function test_private_anonymous_feedback_acknowledgment_notifies_only_its_owner_and_retains_first_provenance(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $feedback = $this->feedback($owner);
        $first = $this->officer();
        $reviewUrl = route('office.student-reports.review', $feedback);
        $this->actingAs($first, 'office')->post($reviewUrl, [
            'decision' => 'acknowledged', 'review_note' => 'Received by OSO.',
        ])->assertRedirect();
        $feedback->refresh();
        $firstTimestamp = $feedback->verified_at->toIso8601String();
        $this->assertSame('verified', $feedback->status);
        $this->assertSame($owner->id, $feedback->student->id);
        Carbon::setTestNow('2030-05-03 10:00:00');
        $this->actingAs($this->officer(), 'office')->post($reviewUrl, ['decision' => 'acknowledged', 'review_note' => 'Replacement'])->assertRedirect();
        $this->post($reviewUrl, ['decision' => 'dismissed'])->assertRedirect();
        $feedback->refresh();
        $this->assertSame('verified', $feedback->status);
        $this->assertSame((int) $first->id, (int) $feedback->verified_by);
        $this->assertSame($firstTimestamp, $feedback->verified_at->toIso8601String());
        $this->assertSame('Received by OSO.', $feedback->review_note);

        $ownerView = $this->actingAs($owner, 'student')->get(route('portal.home'))->assertOk();
        $notification = collect($ownerView->viewData('feedbackNotifications'))->firstWhere('id', 'feedback-'.$feedback->id);
        $this->assertSame($firstTimestamp, $notification['acknowledgedAt']);
        $this->assertTrue($ownerView->viewData('myFeedback')->contains('id', $feedback->id));
        $otherView = $this->actingAs($other, 'student')->get(route('portal.home'))->assertOk();
        $this->assertNull(collect($otherView->viewData('feedbackNotifications'))->firstWhere('id', 'feedback-'.$feedback->id));
        $this->assertFalse($otherView->viewData('myFeedback')->contains('id', $feedback->id));
    }

    public function test_dismissal_is_not_acknowledgment_and_only_oso_can_review_feedback(): void
    {
        $student = $this->student();
        $feedback = $this->feedback($student);
        $url = route('office.student-reports.review', $feedback);
        $this->post($url, ['decision' => 'acknowledged'])->assertRedirect(route('office.login'));
        $this->actingAs($student, 'student')->post($url, ['decision' => 'acknowledged'])->assertRedirect(route('office.login'));
        $this->actingAs($this->officer('oso', false), 'office')->postJson($url, ['decision' => 'acknowledged'])->assertUnauthorized();
        $this->actingAs($this->officer('sdo'), 'office')->post($url, ['decision' => 'acknowledged'])->assertForbidden();
        $this->assertSame('pending', $feedback->fresh()->status);
        $this->actingAs($this->officer(), 'office')->post($url, ['decision' => 'dismissed', 'review_note' => 'Duplicate submission.'])->assertRedirect();
        $feedback->refresh();
        $this->assertSame('dismissed', $feedback->status);
        $this->assertNull($feedback->verified_at);
        $this->assertNull($feedback->verified_by);
        $view = $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk();
        $this->assertNull(collect($view->viewData('feedbackNotifications'))->firstWhere('id', 'feedback-'.$feedback->id));
        $this->assertTrue($view->viewData('myFeedback')->contains('id', $feedback->id));
    }
}

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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class AnnouncementFeedbackTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! getenv('ORGCHAIN_TEST_DATABASE') || ! getenv('ORGCHAIN_TEST_VOTING_DATABASE')) {
            throw new \RuntimeException('AnnouncementFeedbackTest requires isolated core and voting test databases.');
        }
        $this->useLaragonDatabase();
        DB::connection('mysql')->beginTransaction();
        Carbon::setTestNow('2030-05-01 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['mysql'] as $connection) {
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
            'expires_at' => '2030-05-02T10:00',
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

    public function test_expiration_hides_notices_across_active_feeds_without_deleting_legacy_records(): void
    {
        $legacy = $this->announcement();
        $active = $this->announcement();
        $active->update(['expires_at' => now()->addMinute()]);
        $boundary = $this->announcement();
        $boundary->update(['expires_at' => now()]);
        $expired = $this->announcement();
        $expired->update(['expires_at' => now()->subSecond()]);
        $fixtureIds = [$legacy->id, $active->id, $boundary->id, $expired->id];
        $office = $this->officer();
        $updates = $this->actingAs($office, 'office')->get(route('office.updates'))->assertOk();
        $visible = collect($updates->viewData('announcements'))->pluck('id')->intersect($fixtureIds)->sort()->values()->all();
        $this->assertSame([$legacy->id, $active->id], $visible);
        $dashboard = $this->get(route('office.home'))->assertOk();
        $notificationUrls = $dashboard->viewData('officeNotifications')->pluck('url');
        $this->assertTrue($notificationUrls->contains(route('office.updates', ['announcement' => $active->id])));
        $this->assertFalse($notificationUrls->contains(route('office.updates', ['announcement' => $boundary->id])));
        $student = $this->student();
        $portal = $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk();
        $studentVisible = collect($portal->viewData('announcements'))->pluck('announcement_id')->intersect($fixtureIds)->sort()->values()->all();
        $this->assertSame([$legacy->id, $active->id], $studentVisible);
        Carbon::setTestNow(now()->addMinute());
        $this->assertFalse(OfficeAnnouncement::published()->whereKey($active->id)->exists());
        $this->assertTrue(OfficeAnnouncement::published()->whereKey($legacy->id)->exists());
        $this->assertSame(4, OfficeAnnouncement::whereKey($fixtureIds)->count());
    }

    public function test_invalid_publication_fields_write_no_announcement_or_attachment(): void
    {
        Storage::fake('public');
        $this->actingAs($this->officer(), 'office');
        $valid = ['type' => 'Deadline', 'priority' => 'normal', 'body' => 'Real publication details.', 'expires_at' => '2030-05-02T10:00'];
        $before = OfficeAnnouncement::count();
        foreach ([
            ['expires_at' => null], ['expires_at' => '2030-05-01T09:59'],
            ['expires_at' => '2030-05-01T10:00'], ['expires_at' => 'invalid'],
            ['body' => '   '], ['type' => 'Official Notice'],
        ] as $invalid) {
            $field = array_key_first($invalid);
            $this->postJson(route('office.updates.announcements.store'), array_replace($valid, $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame($before, OfficeAnnouncement::count());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_future_text_only_notice_persists_the_local_expiration_time(): void
    {
        $this->actingAs($this->officer(), 'office')->post(route('office.updates.announcements.store'), [
            'type' => 'Reminder', 'priority' => 'normal', 'body' => 'Text-only notice without an attachment.',
            'expires_at' => '2030-05-02T15:45',
        ])->assertSessionHasNoErrors()->assertRedirect(route('office.updates'));
        $notice = OfficeAnnouncement::where('body', 'Text-only notice without an attachment.')->firstOrFail();
        $this->assertSame('2030-05-02 15:45:00', $notice->expires_at->format('Y-m-d H:i:s'));
        $this->assertNull($notice->attachment_path);
        $this->assertTrue(OfficeAnnouncement::published()->whereKey($notice->id)->exists());
    }

    public function test_attachment_limit_accepts_exactly_fifteen_mebibytes_and_rejects_zero_and_one_byte_over(): void
    {
        Storage::fake('public');
        $this->actingAs($this->officer(), 'office');
        $payload = ['type' => 'Deadline', 'priority' => 'normal', 'body' => 'Attachment boundary notice.', 'expires_at' => '2030-05-02T10:00'];
        $before = OfficeAnnouncement::count();
        foreach ([0, 15 * 1024 * 1024 + 1] as $size) {
            $bytes = $size ? "%PDF-1.4\n".str_repeat(' ', $size - 9) : '';
            $this->postJson(route('office.updates.announcements.store'), $payload + [
                'attachment' => UploadedFile::fake()->createWithContent('boundary.pdf', $bytes),
            ])->assertUnprocessable()->assertJsonValidationErrors('attachment');
            $this->assertSame($before, OfficeAnnouncement::count());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
        $bytes = "%PDF-1.4\n".str_repeat(' ', 15 * 1024 * 1024 - 9);
        $this->post(route('office.updates.announcements.store'), $payload + [
            'attachment' => UploadedFile::fake()->createWithContent('boundary.pdf', $bytes),
        ])->assertSessionHasNoErrors()->assertRedirect(route('office.updates'));
        $notice = OfficeAnnouncement::where('body', $payload['body'])->firstOrFail();
        $this->assertSame(15 * 1024 * 1024, Storage::disk('public')->size($notice->attachment_path));
    }

    public function test_nonempty_sub_kilobyte_attachment_is_not_treated_as_an_empty_file(): void
    {
        Storage::fake('public');
        $bytes = "%PDF-1.4\n%%EOF\n";
        $this->actingAs($this->officer(), 'office')->post(route('office.updates.announcements.store'), [
            'type' => 'Reminder', 'priority' => 'normal', 'body' => 'Small attachment notice.',
            'expires_at' => '2030-05-02T10:00',
            'attachment' => UploadedFile::fake()->createWithContent('small.pdf', $bytes),
        ])->assertSessionHasNoErrors()->assertRedirect(route('office.updates'));
        $notice = OfficeAnnouncement::where('body', 'Small attachment notice.')->firstOrFail();
        $this->assertSame(strlen($bytes), Storage::disk('public')->size($notice->attachment_path));
    }

    public function test_attachment_download_and_inline_preview_preserve_original_bytes_and_filename(): void
    {
        Storage::fake('public');
        $bytes = "%PDF-1.4\nOriginal notice attachment\n%%EOF\n";
        Storage::disk('public')->put('announcements/original.pdf', $bytes);
        $notice = $this->announcement();
        $notice->update(['attachment_path' => 'announcements/original.pdf', 'attachment_name' => 'Minutes "Résumé".pdf']);
        $this->actingAs($this->officer(), 'office');
        $download = $this->get(route('office.updates.announcements.attachment', $notice))->assertOk()->assertDownload();
        $this->assertSame(1, preg_match("/filename\\*=utf-8''([^;]+)/i", $download->headers->get('Content-Disposition'), $filename));
        $this->assertSame('Minutes "Résumé".pdf', rawurldecode($filename[1]));
        $this->assertSame($bytes, file_get_contents($download->baseResponse->getFile()->getPathname()));
        $preview = $this->get(route('office.updates.announcements.attachment', ['announcement' => $notice, 'preview' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $preview->headers->get('Content-Disposition'));
        $this->assertSame($bytes, file_get_contents($preview->baseResponse->getFile()->getPathname()));
    }

    public function test_missing_or_empty_legacy_attachment_is_unavailable_not_a_server_error(): void
    {
        Storage::fake('public');
        $notice = $this->announcement();
        $notice->update(['attachment_path' => 'announcements/unavailable.pdf', 'attachment_name' => 'unavailable.pdf']);
        $this->actingAs($this->officer(), 'office')->get(route('office.updates.announcements.attachment', $notice))->assertNotFound();
        Storage::disk('public')->put($notice->attachment_path, '');
        $this->get(route('office.updates.announcements.attachment', ['announcement' => $notice, 'preview' => 1]))->assertNotFound();
    }
}

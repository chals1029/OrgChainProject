<?php

namespace Tests\Feature;

use App\Models\OfficeAnnouncement;
use App\Models\OfficeAnnouncementAcknowledgment;
use App\Models\OfficeUser;
use App\Models\StudentFeedback;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
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
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
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
            'Deadline Notice' => 'deadlines',
            'Guideline' => 'guidelines',
            'Policy & Guideline' => 'guidelines',
            'Reminder' => 'reminders',
            'Compliance Reminder' => 'reminders',
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

    public function test_tosa_call_from_the_desk_is_published_and_recorded(): void
    {
        $office = $this->officer();
        $body = 'Synthetic TOSA filing instructions.';
        $this->actingAs($office, 'office')->post(route('office.updates.announcements.store'), [
            'type' => 'Call for TOSA', 'body' => $body, 'priority' => 'normal',
            'expires_at' => '2030-05-02T10:00', 'return_to' => 'tosa',
        ])->assertRedirect(route('office.tosa', ['tab' => 'documents']));
        $call = OfficeAnnouncement::query()->where('body', $body)->firstOrFail();
        $this->assertDatabaseHas('tosa_history_events', [
            'action' => 'call_published', 'actor_id' => $office->id,
        ], 'mysql');
        $this->assertSame('tosa', $call->studentPublication()['category']);
    }

    public function test_archived_tosa_calls_preserve_history(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/tosa-test.pdf', '%PDF-1.4 synthetic attachment');
        $call = OfficeAnnouncement::query()->create([
            'title' => 'Synthetic TOSA call', 'type' => 'Call for TOSA', 'body' => 'Synthetic application details.',
            'priority' => 'normal', 'expires_at' => now()->addDay(), 'attachment_path' => 'announcements/tosa-test.pdf', 'attachment_name' => 'tosa-test.pdf',
        ]);
        $originalExpiry = $call->expires_at->toDateTimeString();
        $endUrl = route('office.tosa.announcements.end', $call);
        $this->postJson($endUrl)->assertUnauthorized();
        $this->actingAs($this->officer('ovcaa'), 'office')->postJson($endUrl)->assertForbidden();
        $office = $this->officer();
        $this->assertTrue(OfficeAnnouncement::published()->whereKey($call->id)->exists());
        $student = $this->student();
        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()->assertSee('The Call for TOSA is open.');
        $this->actingAs($office, 'office');
        $this->post($endUrl)->assertRedirect(route('office.tosa', ['tab' => 'documents']));
        $this->assertNotNull($call->fresh()->ended_at);
        $this->assertTrue(Storage::disk('public')->exists('announcements/tosa-test.pdf'));
        $this->assertSame($originalExpiry, $call->fresh()->expires_at->toDateTimeString());
        $this->assertFalse(OfficeAnnouncement::published()->whereKey($call->id)->exists());
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'call_ended', 'actor_id' => $office->id], 'mysql');
        $this->get(route('office.tosa', ['tab' => 'documents']))->assertOk()->assertSee('Synthetic application details.')->assertSee('Archived');
        $this->post(route('office.tosa.announcements.end', $this->announcement()))->assertNotFound();
    }

    public function test_returned_student_sees_call_deadline_state(): void
    {
        OfficeAnnouncement::query()->whereIn('type', ['TOSA', 'Call for TOSA'])->update(['expires_at' => now()->subMinute()]);
        $call = OfficeAnnouncement::query()->create([
            'title' => 'Revision call', 'type' => 'Call for TOSA', 'body' => 'Synthetic deadline.',
            'priority' => 'normal', 'expires_at' => now()->addDay(),
        ]);
        $student = $this->student();
        TosaApplicant::query()->create([
            'sr_code' => $student->sr_code, 'full_name' => $student->full_name, 'email' => $student->email,
            'college' => $student->college, 'program' => $student->program, 'year_level' => $student->year_level,
            'status' => 'submitted', 'subsection' => 'returned', 'requirements' => [],
        ]);
        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()
            ->assertSee('OSO requested a revision. Resubmit before the TOSA call closes.');
        $call->update(['expires_at' => now()]);
        $response = $this->get(route('portal.home'))->assertOk()
            ->assertSee('OSO requested a revision, but TOSA applications are closed.')
            ->assertSee('The TOSA call is closed; resubmission is unavailable.');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $button = (new \DOMXPath($document))->query('//form[@id="tosaSubmitForm"]//button[@type="submit"]')->item(0);
        $this->assertNotNull($button);
        $this->assertTrue($button->hasAttribute('disabled'));
    }

    public function test_student_can_open_the_tosa_computation_preview(): void
    {
        $html = $this->actingAs($this->student(), 'student')->get(route('portal.home', ['tab' => 'tosa']))->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $this->assertSame(1, (new \DOMXPath($document))->query('//li[@data-tosa-reference="computation"]//button[contains(@aria-label,"View")]')->length);
    }

    public function test_students_never_receive_the_published_top_ten_list(): void
    {
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->forceFill(['draft_ids' => [9876], 'published_entries' => null, 'published_at' => null])->save();
        $student = $this->student();
        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()->assertDontSee('Published Top 10');
        $entries = collect(range(1, 10))->map(fn (int $number): array => [
            'name' => 'Synthetic Top Student '.$number, 'program' => 'Test Program',
        ])->all();
        $shortlist->forceFill(['published_entries' => $entries, 'published_at' => now()])->save();
        $response = $this->get(route('portal.home'))->assertOk()->assertDontSee('Published Top 10');
        foreach ($entries as $entry) {
            $response->assertDontSee($entry['name']);
        }
        $shortlist->forceFill(['published_entries' => null, 'published_at' => null])->save();
        $this->get(route('portal.home'))->assertOk()->assertDontSee('Published Top 10');
    }

    public function test_publication_controls_reflect_published_and_unpublished_results(): void
    {
        Storage::fake('local');
        $office = $this->officer();
        $ids = [];
        $docx = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));
        $pdf = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        foreach (range(1, 11) as $number) {
            $srCode = 'publication-test-'.$number;
            $requirements = $documents = [];
            foreach (\App\Services\TosaRequirements::SUBMISSIONS as $key => $definition) {
                $extension = $key === 'application_form' ? 'docx' : 'pdf';
                $path = 'tosa/'.$srCode.'/'.$key.'.'.$extension;
                Storage::disk('local')->put($path, $extension === 'docx' ? $docx : $pdf);
                $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$extension];
                $documents[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
            }
            $applicant = TosaApplicant::query()->create([
                'sr_code' => $srCode, 'full_name' => 'Synthetic Applicant '.$number, 'email' => $srCode.'@example.test',
                'college' => 'CICS', 'program' => 'BSIT', 'year_level' => '4th Year',
                'status' => 'submitted', 'subsection' => 'screening', 'requirements' => $requirements,
            ]);
            $applicant->forceFill(['document_review' => ['documents' => $documents]])->save();
            if ($number <= 10) $ids[] = $applicant->id;
        }
        TosaShortlist::query()->findOrFail(1)->forceFill(['draft_ids' => $ids, 'published_at' => null, 'published_decisions' => null, 'published_entries' => null])->save();
        $this->actingAs($office, 'office');
        $render = function (): \DOMXPath {
            $html = $this->get(route('office.tosa', ['tab' => 'top10']))->assertOk()->getContent();
            $document = new \DOMDocument;
            @$document->loadHTML($html);
            return new \DOMXPath($document);
        };
        $statusFor = function (int $number): string {
            $html = $this->get(route('office.tosa', ['search' => 'publication-test-'.$number]))->assertOk()->getContent();
            $document = new \DOMDocument;
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $cell = $xpath->query('//tr[.//strong[normalize-space(.)="Synthetic Applicant '.$number.'"]]/td[@data-label="Review / result"]')->item(0);
            $this->assertNotNull($cell);
            return trim($cell->textContent);
        };
        $this->assertSame('Qualified', $statusFor(1));
        $url = route('office.tosa.top10.save');
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids, 'caption_qualified' => 'Private selected notice', 'caption_not_qualified' => 'Private other notice'])->assertOk();
        $this->assertSame('Qualified', $statusFor(1));
        $this->assertSame('Not qualified', $statusFor(11));
        $published = $render();
        $this->assertTrue($published->query('//textarea[@name="caption_qualified"]')->item(0)->hasAttribute('readonly'));
        $this->assertSame('Private selected notice', $published->query('//textarea[@name="caption_qualified"]')->item(0)->textContent);
        $this->assertSame('Private other notice', $published->query('//textarea[@name="caption_not_qualified"]')->item(0)->textContent);
        $this->postJson(route('office.tosa.top10.remove', $ids[0]), [])->assertForbidden();
        $this->postJson($url, ['intent' => 'unpublish'])->assertOk();
        $unpublished = $render();
        $this->assertFalse($unpublished->query('//textarea[@name="caption_qualified"]')->item(0)->hasAttribute('readonly'));
        $this->assertSame('Private selected notice', $unpublished->query('//textarea[@name="caption_qualified"]')->item(0)->textContent);
        $this->postJson(route('office.tosa.top10.remove', $ids[0]), [])->assertOk();
        $this->assertSame('Reviewed', $statusFor(1));
        $this->postJson(route('office.tosa.top10.add', $ids[0]), [])->assertOk();
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids, 'caption_qualified' => 'Updated selected notice', 'caption_not_qualified' => 'Private other notice'])->assertOk();
        $this->assertSame('Updated selected notice', $render()->query('//textarea[@name="caption_qualified"]')->item(0)->textContent);
    }

    public function test_each_student_receives_only_their_own_current_tosa_result(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $other = $this->student();
        $requirements = [];
        $documents = [];
        foreach (\App\Services\TosaRequirements::SUBMISSIONS as $key => $definition) {
            $extension = $key === 'application_form' ? 'docx' : 'pdf';
            $reference = $key === 'application_form' ? 'application_form' : 'guidelines';
            $path = 'tosa/'.$student->sr_code.'/'.$key.'.'.$extension;
            Storage::disk('local')->put($path, file_get_contents(base_path('TOSA/'.\App\Services\TosaRequirements::REFERENCES[$reference]['filename'])));
            $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$extension];
            $documents[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
        }
        $applicant = TosaApplicant::query()->create([
            'sr_code' => $student->sr_code, 'full_name' => $student->full_name, 'email' => $student->email,
            'college' => $student->college, 'program' => $student->program, 'year_level' => $student->year_level,
            'status' => 'submitted', 'subsection' => 'screening', 'requirements' => $requirements,
        ]);
        $applicant->forceFill(['document_review' => ['documents' => $documents]])->save();
        $shortlist = TosaShortlist::query()->findOrFail(1);
        foreach (['qualified', 'not_qualified'] as $status) {
            $shortlist->forceFill(['published_at' => now(), 'published_entries' => [['name' => 'Other Private Applicant', 'program' => 'Private Program']], 'published_decisions' => [
                $applicant->id => ['status' => $status, 'review' => TosaShortlist::reviewFingerprint($applicant->fresh()), 'name' => $student->full_name, 'program' => $student->program, 'caption' => 'Private '.$status.' notice'],
                999999999 => ['status' => 'qualified', 'name' => 'Other Private Applicant', 'caption' => 'Other private notice'],
            ]])->save();
            $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()
                ->assertSee('Your TOSA result notice')->assertSee('Private '.$status.' notice')
                ->assertDontSee('Other Private Applicant')->assertDontSee('Other private notice')->assertDontSee('Published Top 10');
            $this->actingAs($other, 'student')->get(route('portal.home'))->assertOk()
                ->assertDontSee('Private '.$status.' notice')->assertDontSee('Other Private Applicant');
        }
        $shortlist->forceFill(['published_at' => null])->save();
        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()->assertDontSee('Your TOSA result notice');
    }

    public function test_correction_reason_is_visible_only_to_the_owner_while_the_packet_is_returned(): void
    {
        $student = $this->student();
        $other = $this->student();
        $applicant = TosaApplicant::query()->create([
            'sr_code' => $student->sr_code, 'full_name' => $student->full_name, 'email' => $student->email,
            'college' => $student->college, 'program' => $student->program, 'year_level' => $student->year_level,
            'status' => 'submitted', 'subsection' => 'returned', 'requirements' => [],
        ]);
        $reason = 'Add the <candidate> signature & acknowledgment.';
        TosaHistoryEvent::record('returned', 'office', 1, 'Synthetic OSO', (int) $applicant->id, [
            'document_key' => 'application_form', 'reason' => $reason,
        ]);

        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()
            ->assertSee('Completed TOSA Application Form')->assertSee($reason)->assertDontSee($reason, false);
        $this->actingAs($other, 'student')->get(route('portal.home'))->assertOk()->assertDontSee($reason);
        $applicant->update(['subsection' => 'screening']);
        $this->actingAs($student, 'student')->get(route('portal.home'))->assertOk()->assertDontSee($reason);
    }
}

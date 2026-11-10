<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaHistoryEvent;
use App\Models\TosaShortlist;
use App\Models\UserAccount;
use App\Services\TosaNotificationService;
use App\Services\TosaRequirements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class TosaNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory, 'mail.default' => 'array']);
        DB::purge('mysql');
        Storage::fake('local');
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'program', 'email', 'status', 'subsection', 'requirements', 'document_review'] as $column) {
                $table->text($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table): void {
            $table->id('user_id');
            $table->string('sr_code')->nullable();
            $table->string('email')->nullable();
            $table->string('account_status')->nullable();
        });
        Schema::connection('mysql')->create('office_users', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'office_role', 'remember_token'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->integer('auth_version')->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_31_000001_create_tosa_workspace_tables.php'))->up();
        (require database_path('migrations/2026_11_05_000001_add_tosa_published_decisions.php'))->up();
        (require database_path('migrations/2026_11_06_000001_add_tosa_shortlist_captions.php'))->up();
        (require database_path('migrations/2026_09_19_000004_create_office_templates_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function applicant(string $srCode, string $name, ?string $email, bool $reviewed = false): TosaApplicant
    {
        $requirements = [];
        $documents = [];
        if ($reviewed) {
            foreach (TosaRequirements::SUBMISSIONS as $key => $_definition) {
                $extension = $key === 'application_form' ? 'docx' : 'pdf';
                $path = 'tosa/'.$srCode.'/'.$key.'.'.$extension;
                $source = $extension === 'docx' ? 'TOSA/TOSA Application Form 2025.docx' : 'TOSA/Guidelines TOSA 2025.pdf';
                Storage::disk('local')->put($path, file_get_contents(base_path($source)));
                $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$extension];
                $documents[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
            }
        }
        $applicant = TosaApplicant::query()->create([
            'sr_code' => $srCode, 'full_name' => $name, 'email' => $email, 'program' => 'BSCS',
            'status' => 'submitted', 'subsection' => 'screening', 'requirements' => $requirements,
        ]);
        if ($reviewed) {
            $applicant->forceFill(['document_review' => ['documents' => $documents]])->save();
        }

        return $applicant;
    }

    public function test_whitelist_and_active_account_email_resolution(): void
    {
        $service = app(TosaNotificationService::class);
        foreach (['23-73068' => 'Charles Samontañez', '23-70834' => 'Michelle Viva', '23-73600' => 'Lilian Cabadin'] as $sr => $name) {
            $applicant = $this->applicant($sr, $name, null);
            UserAccount::query()->create(['sr_code' => $sr, 'email' => $sr.'@g.batstate-u.edu.ph', 'account_status' => 'active']);
            $this->assertSame($sr.'@g.batstate-u.edu.ph', $service->resolveApplicantEmail($applicant));
            $this->assertTrue($service->isWhitelisted($applicant, $service->resolveApplicantEmail($applicant)));
        }
        $dummy = $this->applicant('00-00000', 'Dummy Viva', 'dummy@example.com');
        $this->assertFalse($service->isWhitelisted($dummy, $dummy->email));
        $this->assertTrue($service->isWhitelisted($dummy, '23-73068@g.batstate-u.edu.ph'));
    }

    public function test_dummy_pending_and_nonofficial_addresses_never_send(): void
    {
        Mail::shouldReceive('raw')->never();
        $service = app(TosaNotificationService::class);
        $dummy = $this->applicant('00-00000', 'Dummy', 'dummy@example.com');
        $authorized = $this->applicant('23-73068', 'Charles Samontañez', 'wrong@example.com');
        $this->assertSame('skipped', $service->sendApplicantNotification($dummy, 'qualified', 1, 'Officer')['status']);
        $this->assertSame('pending', $service->sendApplicantNotification($authorized, 'pending', 1, 'Officer')['status']);
        $this->assertSame('failed', $service->sendApplicantNotification($authorized, 'qualified', 1, 'Officer')['status']);
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'result_email_skipped', 'applicant_id' => $dummy->id], 'mysql');
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'result_email_failed', 'applicant_id' => $authorized->id], 'mysql');
        $this->assertSame(1, TosaHistoryEvent::query()->where('applicant_id', $authorized->id)->count());
    }

    public function test_mail_failure_is_recorded(): void
    {
        $applicant = $this->applicant('23-73600', 'Lilian Cabadin', '23-73600@g.batstate-u.edu.ph');
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $result = app(TosaNotificationService::class)->sendApplicantNotification($applicant, 'not_qualified', 1, 'Officer');
        $this->assertSame('failed', $result['status']);
        $event = TosaHistoryEvent::query()->where('applicant_id', $applicant->id)->sole();
        $this->assertSame('result_email_failed', $event->action);
        $this->assertSame('SMTP unavailable', $event->details['error']);
    }

    public function test_publish_sends_personalized_attachment_once_per_decision(): void
    {
        $officer = OfficeUser::query()->create(['name' => 'Officer', 'office_role' => 'oso', 'is_active' => true, 'auth_version' => 1]);
        $this->actingAs($officer, 'office');
        $selected = collect();
        $selected->push($this->applicant('23-73068', 'Charles Samontañez', '23-73068@g.batstate-u.edu.ph', true));
        foreach (range(2, 10) as $number) {
            $selected->push($this->applicant('dummy-'.$number, 'Dummy '.$number, 'dummy'.$number.'@example.com', true));
        }
        $pending = $this->applicant('23-70834', 'Michelle Viva', '23-70834@g.batstate-u.edu.ph');
        foreach ($selected as $applicant) {
            $this->postJson(route('office.tosa.top10.add', $applicant), [])->assertOk();
        }
        $payload = ['intent' => 'publish', 'applicant_ids' => $selected->pluck('id')->all(), 'caption_qualified' => 'Qualified', 'caption_not_qualified' => 'Not qualified'];
        $this->postJson(route('office.tosa.top10.save'), $payload)->assertOk();
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $mail = $messages->sole()->getOriginalMessage();
        $this->assertSame('23-73068@g.batstate-u.edu.ph', $mail->getTo()[0]->getAddress());
        $this->assertSame('TOSA Application Result', $mail->getSubject());
        $this->assertStringContainsString('Please see the attached Word document', $mail->getTextBody());
        $attachments = $mail->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('TOSA-Application-Result.docx', $attachments[0]->getFilename());
        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $attachments[0]->getMediaType().'/'.$attachments[0]->getMediaSubtype());
        $temporary = tempnam(sys_get_temp_dir(), 'tosa-test-');
        try {
            file_put_contents($temporary, $attachments[0]->getBody());
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($temporary));
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('Charles Samontañez', $xml);
            $this->assertStringContainsString('BSCS', $xml);
        } finally {
            @unlink($temporary);
        }
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'result_email_sent', 'applicant_id' => $selected[0]->id], 'mysql');
        $this->assertSame(9, TosaHistoryEvent::query()->where('action', 'result_email_skipped')->count());
        $this->assertSame(0, TosaHistoryEvent::query()->where('applicant_id', $pending->id)->where('action', 'like', 'result_email_%')->count());
        $this->postJson(route('office.tosa.top10.save'), ['intent' => 'unpublish'])->assertOk();
        $this->postJson(route('office.tosa.top10.save'), $payload)->assertOk();
        $this->assertCount(1, Mail::mailer('array')->getSymfonyTransport()->messages());
        app(TosaNotificationService::class)->sendResultNotifications(TosaShortlist::query()->findOrFail(1), $officer);
        $this->assertCount(1, Mail::mailer('array')->getSymfonyTransport()->messages());
        $this->assertSame('sent', app(TosaNotificationService::class)->sendApplicantNotification($selected[0], 'not_qualified', $officer->id, $officer->name)['status']);
        $this->assertCount(2, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_oso_can_download_and_replace_letter_templates(): void
    {
        $officer = OfficeUser::query()->create(['name' => 'Officer', 'office_role' => 'oso', 'is_active' => true, 'auth_version' => 1]);
        $this->actingAs($officer, 'office');

        // 1. Download bundled template
        $response = $this->get(route('office.tosa.letters.template.download', ['decision' => 'qualified']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // 2. Upload invalid template (missing placeholders) should fail validation
        $invalidFile = \Illuminate\Http\UploadedFile::fake()->create('bad-template.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->post(route('office.tosa.letters.template.store'), [
            'decision' => 'qualified',
            'template_file' => $invalidFile,
        ])->assertSessionHasErrors('template_file');

        // 3. Upload valid template (using bundled template copy)
        $validPath = resource_path('tosa/letters/qualified.docx');
        $validFile = new \Illuminate\Http\UploadedFile($validPath, 'custom-qualified.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
        $uploadResponse = $this->post(route('office.tosa.letters.template.store'), [
            'decision' => 'qualified',
            'template_file' => $validFile,
        ]);
        $uploadResponse->assertRedirect(route('office.tosa', ['tab' => 'documents']));
        $uploadResponse->assertSessionHas('success');

        $this->assertDatabaseHas('office_templates', [
            'category' => 'TOSA_LETTER',
            'name' => 'Qualified Decision Letter',
            'format' => 'DOCX',
        ], 'mysql');

        // 4. Verify templatesSummary reflects custom template
        $service = app(\App\Services\TosaLetterService::class);
        $summary = collect($service->templatesSummary())->keyBy('key');
        $this->assertTrue($summary['qualified']['is_custom']);
        $this->assertFalse($summary['not_qualified']['is_custom']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\OfficeAnnouncement;
use App\Models\OfficeTemplate;
use App\Models\TosaApplicant;
use App\Models\UserAccount;
use App\Services\OfficeDocumentConverter;
use App\Services\TosaRequirements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TosaPrivateFilesTest extends TestCase
{
    private const SECTIONS = ['table_of_contents', 'application_form', 'cv', 'good_moral', 'academic', 'leadership', 'social_responsibility'];
    private ?string $filledDocx = null;

    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.connections.mysql' => $memory, 'database.connections.voting' => $memory]);
        DB::purge('mysql');
        Storage::fake('local');
        Storage::fake('public');
        Schema::connection('mysql')->create('user_accounts', function (Blueprint $table): void {
            $table->increments('user_id');
            foreach (['sr_code', 'full_name', 'email', 'college', 'program', 'year_level', 'account_status', 'role'] as $column) {
                $table->text($column)->nullable();
            }
        });
        UserAccount::query()->create(['user_id' => 1, 'sr_code' => 'fixture-owner', 'full_name' => 'Synthetic Student',
            'email' => 'synthetic@g.batstate-u.edu.ph', 'account_status' => 'active', 'role' => 'student']);
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'email', 'college', 'program', 'year_level', 'subsection', 'status', 'requirements', 'remarks'] as $column) {
                $table->text($column)->nullable();
            }
            $table->decimal('gwa', 4, 2)->nullable();
            $table->boolean('gwa_verified')->default(false);
            $table->json('score_evaluation')->nullable();
            $table->json('document_review')->nullable();
            $table->json('committee_members')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->text('body');
            $table->string('priority')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        OfficeAnnouncement::query()->create(['title' => 'TOSA call', 'type' => 'Call for TOSA',
            'body' => 'Submit the required documents.', 'priority' => 'normal', 'expires_at' => now()->addDay()]);
        Schema::connection('mysql')->create('office_templates', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'category', 'format', 'size', 'description', 'file_path', 'original_name'] as $column) {
                $table->text($column)->nullable();
            }
            $table->integer('downloads')->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_31_000001_create_tosa_workspace_tables.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function student(): UserAccount
    {
        return UserAccount::query()->findOrFail(1);
    }

    private function referenceBytes(string $key): string
    {
        return file_get_contents(base_path('TOSA/'.TosaRequirements::REFERENCES[$key]['filename']));
    }

    private function evidenceBytes(string $format = 'pdf'): string
    {
        if ($format !== 'docx') {
            return $this->referenceBytes('guidelines');
        }
        if ($this->filledDocx !== null) {
            return $this->filledDocx;
        }
        $temp = tempnam(sys_get_temp_dir(), 'tosa-fixture-');
        try {
            file_put_contents($temp, $this->referenceBytes('application_form'));
            $zip = new \ZipArchive;
            if ($zip->open($temp) !== true) {
                throw new RuntimeException('Could not prepare the synthetic DOCX fixture.');
            }
            $xml = $zip->getFromName('word/document.xml');
            $zip->addFromString('word/document.xml', str_replace('</w:body>', '<w:p><w:r><w:t>TEST synthetic candidate details</w:t></w:r></w:p></w:body>', $xml));
            $zip->close();
            return $this->filledDocx = file_get_contents($temp);
        } finally {
            @unlink($temp);
        }
    }

    private function evidenceUpload(string $key, string $format = 'pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('candidate-'.$key.'.'.$format, $this->evidenceBytes($format));
    }

    private function packet(string $applicationFormat = 'docx'): array
    {
        $documents = [];
        foreach (self::SECTIONS as $key) {
            $documents[$key] = $this->evidenceUpload($key, $key === 'application_form' ? $applicationFormat : 'pdf');
        }

        return $documents;
    }

    private function completeApplication(): TosaApplicant
    {
        $requirements = [];
        foreach (self::SECTIONS as $key) {
            $format = $key === 'application_form' ? 'docx' : 'pdf';
            $path = 'tosa/fixture-owner/'.$key.'.'.$format;
            Storage::disk('local')->put($path, $this->evidenceBytes($format));
            $requirements[$key] = ['path' => $path, 'name' => 'candidate-'.$key.'.'.$format];
        }

        return TosaApplicant::query()->create(['sr_code' => 'fixture-owner', 'status' => 'draft',
            'subsection' => 'pending', 'gwa' => 1.25, 'gwa_verified' => true,
            'remarks' => 'Prior manual review', 'requirements' => $requirements])->refresh();
    }

    private function assertSubmissionRejected(TosaApplicant $applicant): void
    {
        $before = $applicant->fresh()->getRawOriginal();
        $this->post(route('portal.tosa.submit'))->assertRedirect()->assertSessionHasErrors('tosa');
        $this->assertSame($before, $applicant->fresh()->getRawOriginal());
    }

    private function assertSavedPacketUnchanged(TosaApplicant $applicant, array $before, array $files): void
    {
        $this->assertSame($before, $applicant->fresh()->getRawOriginal());
        $disk = Storage::disk('local');
        $currentPaths = $disk->allFiles('tosa/fixture-owner');
        sort($currentPaths);
        $expectedPaths = array_keys($files);
        sort($expectedPaths);
        $this->assertSame($expectedPaths, $currentPaths);
        foreach ($files as $path => $bytes) {
            $this->assertSame($bytes, $disk->get($path));
        }
    }

    private function savedBytes(TosaApplicant $applicant): array
    {
        $files = [];
        foreach (self::SECTIONS as $key) {
            $path = $applicant->requirementPath($key);
            $files[$path] = Storage::disk('local')->get($path);
        }

        return $files;
    }

    public function test_new_packet_requires_an_active_tosa_call_not_a_general_notice(): void
    {
        OfficeAnnouncement::query()->update(['type' => 'General Announcement']);
        $this->actingAs($this->student(), 'student')->post(route('portal.tosa.submit'), ['documents' => $this->packet()])
            ->assertRedirect()->assertSessionHasErrors('tosa');
        $this->assertSame(0, TosaApplicant::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_returned_packet_can_be_resubmitted_after_call_expires_and_requires_new_review(): void
    {
        $call = OfficeAnnouncement::query()->firstOrFail();
        $this->assertSame('tosa', $call->studentPublication()['category']);
        $this->actingAs($this->student(), 'student')->post(route('portal.tosa.submit'), ['documents' => $this->packet()])
            ->assertRedirect()->assertSessionHasNoErrors();
        $applicant = TosaApplicant::query()->sole();
        $this->assertDatabaseHas('tosa_history_events', [
            'action' => 'submitted', 'actor_type' => 'student', 'applicant_id' => $applicant->id,
        ], 'mysql');
        $files = $this->savedBytes($applicant);
        $applicant->forceFill([
            'subsection' => 'returned', 'gwa' => 1.25, 'gwa_verified' => true,
            'document_review' => ['documents' => ['academic' => ['verified_by' => 1]]],
            'committee_members' => ['members' => [5], 'assigned_by' => 1, 'assigned_at' => now()->toIso8601String()],
            'score_evaluation' => ['status' => 'finalized'],
        ])->save();
        $call->update(['expires_at' => now()->subMinute()]);
        $this->post(route('portal.tosa.submit'))->assertRedirect()->assertSessionHasNoErrors();
        $applicant->refresh();
        $this->assertDatabaseHas('tosa_history_events', [
            'action' => 'resubmitted', 'actor_type' => 'student', 'applicant_id' => $applicant->id,
        ], 'mysql');
        $this->assertSame('screening', $applicant->subsection);
        $this->assertNull($applicant->document_review);
        $this->assertSame(['status' => 'finalized'], $applicant->score_evaluation);
        $this->assertSame(1.25, $applicant->gwa);
        $this->assertTrue($applicant->gwa_verified);
        $this->assertSame([5], $applicant->committee_members['members']);
        $this->assertSame($files, $this->savedBytes($applicant));
    }

    public function test_one_submit_saves_all_seven_private_originals_and_publishes_one_screening_row(): void
    {
        $this->actingAs($this->student(), 'student');
        $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])->assertRedirect()->assertSessionHasNoErrors();
        $applicant = TosaApplicant::query()->sole();
        $this->assertSame('submitted', $applicant->status);
        $this->assertSame('screening', $applicant->subsection);
        $this->assertCount(7, Storage::disk('local')->allFiles('tosa/fixture-owner'));
        foreach (self::SECTIONS as $key) {
            $path = $applicant->requirementPath($key);
            $format = $key === 'application_form' ? 'docx' : 'pdf';
            $mime = $format === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/pdf';
            $this->assertSame($this->evidenceBytes($format), Storage::disk('local')->get($path));
            Storage::disk('public')->assertMissing($path);
            $preview = $this->get(route('portal.tosa.documents.show', ['key' => $key]))->assertOk()
                ->assertHeader('Content-Type', $mime)->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringStartsWith($format === 'docx' ? 'attachment;' : 'inline;', $preview->headers->get('Content-Disposition'));
            $this->assertTrue($preview->headers->hasCacheControlDirective('private'));
            $this->assertTrue($preview->headers->hasCacheControlDirective('no-store'));
            $download = $this->get(route('portal.tosa.documents.show', ['key' => $key, 'download' => 1]))
                ->assertOk()->assertDownload('candidate-'.$key.'.'.$format);
            $this->assertSame($this->evidenceBytes($format), file_get_contents($download->baseResponse->getFile()->getPathname()));
        }
        $before = $applicant->fresh()->getRawOriginal();
        $files = $this->savedBytes($applicant);
        $this->assertSubmissionRejected($applicant);
        $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])
            ->assertRedirect()->assertSessionHasErrors('tosa');
        $this->assertSavedPacketUnchanged($applicant, $before, $files);
        $this->assertSame(1, TosaApplicant::query()->count());
    }

    public function test_full_nomination_docx_packet_has_actual_mime_and_download_only_behavior(): void
    {
        $this->actingAs($this->student(), 'student');
        $this->post(route('portal.tosa.submit'), ['documents' => $this->packet('docx')])->assertRedirect()->assertSessionHasNoErrors();
        foreach ([[], ['download' => 1], ['preview' => 1]] as $query) {
            $response = $this->get(route('portal.tosa.documents.show', ['key' => 'application_form', ...$query]))
                ->assertOk()->assertDownload('candidate-application_form.docx')
                ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $this->assertSame($this->evidenceBytes('docx'), file_get_contents($response->baseResponse->getFile()->getPathname()));
        }
        $this->assertSame('screening', TosaApplicant::query()->sole()->subsection);
    }

    public function test_pdf_and_untouched_application_templates_cannot_be_submitted(): void
    {
        $this->actingAs($this->student(), 'student');
        $blank = $this->referenceBytes('application_form');
        $temp = tempnam(sys_get_temp_dir(), 'tosa-blank-');
        try {
            file_put_contents($temp, $blank);
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($temp) === true);
            $zip->addFromString('docProps/test-fixture.txt', 'Repacked without completing the form');
            $zip->close();
            $repacked = file_get_contents($temp);
        } finally {
            @unlink($temp);
        }
        foreach ([$blank, $repacked] as $bytes) {
            $documents = $this->packet();
            $documents['application_form'] = UploadedFile::fake()->createWithContent('blank.docx', $bytes);
            $this->post(route('portal.tosa.submit'), ['documents' => $documents])
                ->assertRedirect()->assertSessionHasErrors('documents.application_form');
        }
        $documents = $this->packet('pdf');
        $this->post(route('portal.tosa.submit'), ['documents' => $documents])
            ->assertRedirect()->assertSessionHasErrors('documents.application_form');
        $this->assertSame(0, TosaApplicant::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $applicant = $this->completeApplication();
        Storage::disk('local')->put($applicant->requirementPath('application_form'), $blank);
        $this->assertSubmissionRejected($applicant);
    }

    public function test_incomplete_first_packet_creates_no_row_or_partial_files(): void
    {
        $this->actingAs($this->student(), 'student');
        foreach (self::SECTIONS as $missing) {
            $documents = $this->packet();
            unset($documents[$missing]);
            $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('tosa');
            $this->assertSame(0, TosaApplicant::query()->count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
        $this->post(route('portal.tosa.submit'))->assertRedirect()->assertSessionHasErrors('tosa');
        $this->assertSame(0, TosaApplicant::query()->count());
    }

    public function test_unknown_reference_keys_nonfiles_empty_wrong_formats_and_oversized_packets_write_nothing(): void
    {
        $this->actingAs($this->student(), 'student');
        foreach (['front_page', 'guidelines', 'computation', 'submission_guide', 'scholastic', 'certificates', 'application_docs', 'other'] as $key) {
            $documents = $this->packet();
            $documents[$key] = $this->evidenceUpload('cv');
            $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents');
        }
        foreach (self::SECTIONS as $key) {
            foreach (['', 'not a PDF', $this->referenceBytes('front_page')] as $bytes) {
                $documents = $this->packet();
                $documents[$key] = UploadedFile::fake()->createWithContent($key.'.pdf', $bytes);
                $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents.'.$key);
            }
        }
        foreach (['table_of_contents', 'cv', 'good_moral', 'academic', 'leadership', 'social_responsibility'] as $key) {
            $documents = $this->packet();
            $documents[$key] = $this->evidenceUpload($key, 'docx');
            $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents.'.$key);
        }
        $documents = $this->packet();
        $documents['application_form'] = UploadedFile::fake()->createWithContent('renamed.docx', $this->referenceBytes('computation'));
        $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents.application_form');
        $documents = $this->packet();
        $documents['cv'] = 'not a file';
        $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents.cv');
        $documents = $this->packet();
        $documents['academic']->size(25601);
        $this->post(route('portal.tosa.submit'), ['documents' => $documents])->assertRedirect()->assertSessionHasErrors('documents.academic');
        $this->assertSame(0, TosaApplicant::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get(route('portal.tosa.templates.show', ['key' => 'cv']))->assertNotFound();
    }

    public function test_sparse_replacement_uses_saved_originals_resets_review_and_deletes_only_replaced_owned_file(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $oldForm = $applicant->requirementPath('application_form');
        $cv = $applicant->requirementPath('cv');
        $legacyPath = 'tosa/fixture-owner/scholastic.pdf';
        Storage::disk('local')->put($legacyPath, $this->evidenceBytes());
        $requirements = $applicant->requirements;
        $requirements['scholastic'] = ['path' => $legacyPath];
        $applicant->update(['requirements' => $requirements]);
        $applicant->status = 'submitted';
        $applicant->subsection = 'returned';
        $applicant->score_evaluation = ['status' => 'historical'];
        $applicant->document_review = ['documents' => ['application_form' => ['path' => $oldForm]]];
        $applicant->save();
        $this->post(route('portal.tosa.submit'), ['documents' => ['application_form' => $this->evidenceUpload('application_form', 'docx')]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $applicant->refresh();
        $this->assertSame('submitted', $applicant->status);
        $this->assertSame('screening', $applicant->subsection);
        $this->assertSame(1.25, $applicant->gwa);
        $this->assertTrue($applicant->gwa_verified);
        $this->assertSame('Prior manual review', $applicant->remarks);
        $this->assertSame(['status' => 'historical'], $applicant->score_evaluation);
        $this->assertNull($applicant->document_review);
        Storage::disk('local')->assertMissing($oldForm);
        Storage::disk('local')->assertExists($cv);
        Storage::disk('local')->assertExists($legacyPath);
        $this->assertSame($this->evidenceBytes(), Storage::disk('local')->get($cv));
        $this->get(route('portal.tosa.documents.show', ['key' => 'application_form']))->assertOk()->assertDownload('candidate-application_form.docx');
    }

    public function test_replacement_preserves_original_bytes_still_referenced_by_unknown_legacy_metadata(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $oldCv = $applicant->requirementPath('cv');
        $requirements = $applicant->requirements;
        $requirements['certificates'] = ['path' => $oldCv];
        $applicant->update(['requirements' => $requirements]);
        $this->post(route('portal.tosa.submit'), ['documents' => ['cv' => $this->evidenceUpload('cv')]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotSame($oldCv, $applicant->fresh()->requirementPath('cv'));
        Storage::disk('local')->assertExists($oldCv);
        $this->assertSame($this->evidenceBytes(), Storage::disk('local')->get($oldCv));
        $this->get(route('portal.tosa.documents.show', ['key' => 'certificates']))->assertNotFound();
    }

    public function test_storage_false_or_throw_after_partial_write_rolls_back_packet_and_cleans_all_new_paths(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        foreach (['false', 'throw', 'corrupt'] as $failure) {
            $applicant->refresh();
            $before = $applicant->getRawOriginal();
            $files = $this->savedBytes($applicant);
            $factory = Storage::getFacadeRoot();
            $disk = Storage::disk('local');
            $failingDisk = Mockery::mock($disk);
            $writes = 0;
            $failingDisk->shouldReceive('putFileAs')->twice()->andReturnUsing(function ($directory, $file, $filename, $options) use ($disk, $failure, &$writes) {
                if (++$writes === 1) {
                    return $disk->putFileAs($directory, $file, $filename, $options);
                }
                $disk->put($directory.'/'.$filename, 'partial packet bytes');
                if ($failure === 'throw') {
                    throw new RuntimeException('Synthetic storage interruption');
                }

                return $failure === 'corrupt' ? $directory.'/'.$filename : false;
            });
            Storage::shouldReceive('disk')->with('local')->andReturn($failingDisk);
            try {
                $this->post(route('portal.tosa.submit'), ['documents' => ['cv' => $this->evidenceUpload('cv'), 'academic' => $this->evidenceUpload('academic')]])
                    ->assertRedirect()->assertSessionHasErrors('tosa');
            } finally {
                Storage::swap($factory);
            }
            $this->assertSavedPacketUnchanged($applicant, $before, $files);
        }
    }

    public function test_database_failure_or_cancelled_save_preserves_old_packet_and_removes_new_files(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $event = 'eloquent.saving: '.TosaApplicant::class;
        foreach (['throw', 'cancel'] as $failure) {
            $before = $applicant->fresh()->getRawOriginal();
            $files = $this->savedBytes($applicant);
            Event::listen($event, static function () use ($failure) {
                if ($failure === 'throw') {
                    throw new RuntimeException('Synthetic database interruption');
                }

                return false;
            });
            try {
                $this->post(route('portal.tosa.submit'), ['documents' => ['cv' => $this->evidenceUpload('cv'), 'application_form' => $this->evidenceUpload('application_form', 'docx')]])
                    ->assertRedirect()->assertSessionHasErrors('tosa');
            } finally {
                Event::forget($event);
            }
            $this->assertSavedPacketUnchanged($applicant, $before, $files);
        }
    }

    public function test_failed_first_commit_creates_no_draft_and_leaves_no_packet_files(): void
    {
        $this->actingAs($this->student(), 'student');
        $event = 'eloquent.saving: '.TosaApplicant::class;
        Event::listen($event, static function (): void {
            throw new RuntimeException('Synthetic first-save failure');
        });
        try {
            $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])->assertRedirect()->assertSessionHasErrors('tosa');
        } finally {
            Event::forget($event);
        }
        $this->assertSame(0, TosaApplicant::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_each_unselected_saved_section_needs_readable_nonempty_private_bytes(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $complete = $applicant->requirements;
        foreach (self::SECTIONS as $key) {
            $requirements = $complete;
            unset($requirements[$key]);
            $applicant->update(['requirements' => $requirements]);
            $this->assertSubmissionRejected($applicant);
            $applicant->update(['requirements' => $complete]);
            $path = $applicant->requirementPath($key);
            Storage::disk('local')->delete($path);
            Storage::disk('public')->put($path, $this->evidenceBytes($key === 'application_form' ? 'docx' : 'pdf'));
            $this->assertSubmissionRejected($applicant);
            foreach (['', 'not a PDF', $this->referenceBytes('front_page')] as $invalid) {
                Storage::disk('local')->put($path, $invalid);
                $this->assertSubmissionRejected($applicant);
                $this->get(route('portal.tosa.documents.show', ['key' => $key]))->assertNotFound();
            }
            Storage::disk('local')->put($path, $this->evidenceBytes($key === 'application_form' ? 'docx' : 'pdf'));
        }
    }

    public function test_foreign_malformed_and_non_regular_saved_paths_do_not_count_or_get_deleted(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $complete = $applicant->requirements;
        $foreign = 'tosa/fixture-foreign/academic.pdf';
        Storage::disk('local')->put($foreign, $this->evidenceBytes());
        Storage::disk('local')->makeDirectory('tosa/fixture-owner/directory.pdf');
        foreach ([null, [], ['name' => 'academic.pdf'], ['path' => 42], ['path' => $foreign],
            ['path' => 'tosa/fixture-owner/../fixture-foreign/academic.pdf'], ['path' => 'archive/private.pdf'],
            ['path' => '../../.env'], ['path' => 'tosa/fixture-owner/directory.pdf'],
            ['path' => 'tosa/fixture-owner/academic.pdf:stream'], ['path' => 'tosa/fixture-owner\\academic.pdf'],
            ['path' => "tosa/fixture-owner/academic.pdf\0"]] as $metadata) {
            $requirements = $complete;
            $requirements['academic'] = $metadata;
            $applicant->update(['requirements' => $requirements]);
            $this->assertSubmissionRejected($applicant);
            $this->get(route('portal.tosa.documents.show', ['key' => 'academic']))->assertNotFound();
        }
        $requirements = $complete;
        $requirements['academic'] = ['path' => $foreign];
        $applicant->update(['requirements' => $requirements]);
        $this->post(route('portal.tosa.submit'), ['documents' => ['academic' => $this->evidenceUpload('academic')]])
            ->assertRedirect()->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($foreign);
        $this->assertSame($this->evidenceBytes(), Storage::disk('local')->get($foreign));
        Auth::guard('student')->logout();
        $this->get(route('portal.tosa.documents.show', ['key' => 'academic']))->assertRedirect('/');
    }

    public function test_five_reference_files_and_old_checklist_never_substitute_for_evidence_bundles(): void
    {
        $this->actingAs($this->student(), 'student');
        $requirements = [];
        foreach (TosaRequirements::REFERENCES as $key => $reference) {
            $path = 'tosa/fixture-owner/'.$key.'.'.$reference['extensions'][0];
            Storage::disk('local')->put($path, $this->referenceBytes($key));
            $requirements[$key] = ['path' => $path];
        }
        $applicant = TosaApplicant::query()->create(['sr_code' => 'fixture-owner', 'status' => 'draft',
            'subsection' => 'pending', 'requirements' => $requirements]);
        $this->assertSubmissionRejected($applicant);
        foreach (['cv', 'good_moral', 'scholastic', 'certificates', 'application_docs'] as $key) {
            $path = 'tosa/fixture-owner/'.$key.'.pdf';
            Storage::disk('local')->put($path, $this->evidenceBytes());
            $requirements[$key] = ['path' => $path];
        }
        $applicant->update(['requirements' => $requirements]);
        $this->assertSubmissionRejected($applicant);
        foreach (['front_page', 'guidelines', 'computation', 'submission_guide', 'scholastic', 'certificates', 'application_docs'] as $key) {
            Storage::disk('local')->assertExists($requirements[$key]['path']);
        }
    }

    public function test_submitted_non_revision_stages_lock_whole_packet_without_touching_originals(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $states = array_map(fn ($stage) => ['status' => 'submitted', 'subsection' => $stage],
            ['pending', 'screening', 'interview', 'rejected', 'accepted']);
        $states[] = ['status' => 'accepted', 'subsection' => 'returned'];
        $states[] = ['status' => 'draft', 'subsection' => 'accepted'];
        foreach ($states as $state) {
            $applicant->update($state);
            $before = $applicant->getRawOriginal();
            $files = $this->savedBytes($applicant);
            $this->assertSubmissionRejected($applicant);
            $this->post(route('portal.tosa.submit'), ['documents' => ['cv' => $this->evidenceUpload('cv')]])
                ->assertRedirect()->assertSessionHasErrors('tosa');
            $this->assertSavedPacketUnchanged($applicant, $before, $files);
            $this->get(route('portal.tosa.documents.show', ['key' => 'cv']))->assertOk();
        }
    }

    public function test_revision_allows_corrected_packet_once_then_relocks_without_losing_unchanged_originals(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $applicant->update(['status' => 'submitted', 'subsection' => 'returned', 'remarks' => 'Correct the nomination.']);
        $before = $applicant->requirements;
        $oldForm = $applicant->requirementPath('application_form');
        $this->post(route('portal.tosa.submit'), ['documents' => ['application_form' => $this->evidenceUpload('application_form', 'docx')]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $applicant->refresh();
        $this->assertSame('submitted', $applicant->status);
        $this->assertSame('screening', $applicant->subsection);
        $this->assertSame(1.25, $applicant->gwa);
        $this->assertTrue($applicant->gwa_verified);
        $this->assertSame('Correct the nomination.', $applicant->remarks);
        Storage::disk('local')->assertMissing($oldForm);
        $this->get(route('portal.tosa.documents.show', ['key' => 'application_form']))
            ->assertOk()->assertDownload('candidate-application_form.docx');
        foreach (self::SECTIONS as $key) {
            if ($key !== 'application_form') {
                $this->assertSame($before[$key], $applicant->requirements[$key]);
                $this->assertSame($this->evidenceBytes(), Storage::disk('local')->get($applicant->requirementPath($key)));
            }
        }
        $saved = $applicant->getRawOriginal();
        $files = $this->savedBytes($applicant);
        $this->post(route('portal.tosa.submit'), ['documents' => ['cv' => $this->evidenceUpload('cv')]])
            ->assertRedirect()->assertSessionHasErrors('tosa');
        $this->assertSavedPacketUnchanged($applicant, $saved, $files);
        $this->assertSame(1, TosaApplicant::query()->count());
    }

    public function test_real_identity_row_is_required_and_unsafe_owner_codes_are_rejected_before_writes(): void
    {
        $student = $this->student();
        $this->actingAs($student, 'student');
        $student->delete();
        $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])->assertForbidden();
        $this->assertSame(0, TosaApplicant::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $student->exists = false;
        $student->sr_code = '../foreign';
        $student->save();
        $this->actingAs($student, 'student');
        $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])->assertForbidden();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_escaping_symlinks_do_not_count_and_owner_directory_cannot_receive_new_packet(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $path = $applicant->requirementPath('academic');
        Storage::disk('local')->delete($path);
        $foreign = 'tosa/fixture-foreign/academic.pdf';
        Storage::disk('local')->put($foreign, $this->evidenceBytes());
        $link = Storage::disk('local')->path($path);
        if (! @symlink(Storage::disk('local')->path($foreign), $link)) {
            $this->markTestSkipped('Creating symbolic links is not permitted on this host.');
        }
        try {
            $this->assertSubmissionRejected($applicant);
            $this->get(route('portal.tosa.documents.show', ['key' => 'academic']))->assertNotFound();
            $this->post(route('portal.tosa.submit'), ['documents' => ['academic' => $this->evidenceUpload('academic')]])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($this->evidenceBytes(), Storage::disk('local')->get($foreign));
        } finally {
            unlink($link);
        }
        // The replacement submitted and locked the packet; revision must be open
        // before the separate directory-symlink guard can be exercised.
        $applicant->refresh()->forceFill(['subsection' => 'returned'])->save();
        foreach (self::SECTIONS as $key) {
            Storage::disk('local')->put('tosa/fixture-foreign/'.$key.'.pdf', $this->evidenceBytes());
        }
        Storage::disk('local')->deleteDirectory('tosa/fixture-owner');
        $link = Storage::disk('local')->path('tosa/fixture-owner');
        if (! @symlink(Storage::disk('local')->path('tosa/fixture-foreign'), $link)) {
            $this->markTestSkipped('Creating directory symbolic links is not permitted on this host.');
        }
        try {
            $this->post(route('portal.tosa.submit'), ['documents' => $this->packet()])->assertForbidden();
        } finally {
            PHP_OS_FAMILY === 'Windows' ? rmdir($link) : unlink($link);
        }
    }

    public function test_unreadable_saved_evidence_cannot_submit(): void
    {
        $this->actingAs($this->student(), 'student');
        $applicant = $this->completeApplication();
        $absolute = $applicant->requirementFile('cv');
        $permissions = fileperms($absolute) & 0777;
        chmod($absolute, 0000);
        try {
            clearstatcache(true, $absolute);
            if (is_readable($absolute)) {
                $this->markTestSkipped('This host cannot make the fixture unreadable for the current user.');
            }
            $this->assertSubmissionRejected($applicant);
        } finally {
            chmod($absolute, $permissions);
        }
    }

    public function test_doc_reference_preview_is_private_and_releases_generated_files_after_streaming(): void
    {
        $this->actingAs($this->student(), 'student');
        $disk = Storage::disk('local');
        $disk->put('reference-preview/output.pdf', $this->referenceBytes('guidelines'));
        $result = ['path' => $disk->path('reference-preview/output.pdf'), 'directory' => $disk->path('reference-preview')];
        $converter = $this->mock(OfficeDocumentConverter::class);
        $converter->shouldReceive('toPdf')->andReturn($result);
        $converter->shouldReceive('release')->andReturnUsing(function () use ($disk): void {
            $disk->deleteDirectory('reference-preview');
        });

        $response = $this->get(route('portal.tosa.templates.show', ['key' => 'front_page', 'preview' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
        $disk->assertMissing('reference-preview/output.pdf');
        $this->assertDirectoryDoesNotExist($result['directory']);

        $download = $this->get(route('portal.tosa.templates.show', ['key' => 'front_page']))
            ->assertOk()->assertDownload('Front Page.doc')->assertHeader('Content-Type', 'application/msword');
        $this->assertSame($this->referenceBytes('front_page'), file_get_contents($download->baseResponse->getFile()->getPathname()));
        $this->assertSame(0, TosaApplicant::query()->count());
    }

    public function test_failed_doc_reference_conversion_keeps_original_download_available(): void
    {
        $this->actingAs($this->student(), 'student');
        $converter = $this->mock(OfficeDocumentConverter::class);
        $converter->shouldReceive('toPdf')->andThrow(new RuntimeException('The isolated converter is unavailable.'));
        $response = $this->get(route('portal.tosa.templates.show', ['key' => 'submission_guide', 'preview' => 1]))
            ->assertStatus(503)->assertJsonStructure(['message'])->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));

        $download = $this->get(route('portal.tosa.templates.show', ['key' => 'submission_guide']))
            ->assertOk()->assertDownload('TOSA Submission Guide.doc');
        $this->assertSame($this->referenceBytes('submission_guide'), file_get_contents($download->baseResponse->getFile()->getPathname()));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, TosaApplicant::query()->count());
    }

    public function test_reference_downloads_use_latest_matching_native_override_without_altering_bundle(): void
    {
        $this->actingAs($this->student(), 'student');
        $hash = hash_file('sha256', base_path('TOSA/Front Page.doc'));
        foreach (['first.doc' => 'front_page', 'latest.doc' => 'submission_guide'] as $name => $sourceKey) {
            $path = 'tosa-templates/'.$name;
            Storage::disk('public')->put($path, $this->referenceBytes($sourceKey));
            OfficeTemplate::query()->create(['category' => 'TOSA', 'name' => 'Front Page', 'original_name' => $name, 'file_path' => $path]);
        }
        Storage::disk('public')->put('tosa-templates/wrong.pdf', $this->referenceBytes('guidelines'));
        OfficeTemplate::query()->create(['category' => 'TOSA', 'name' => 'Front Page', 'original_name' => 'wrong.pdf', 'file_path' => 'tosa-templates/wrong.pdf']);
        $response = $this->get(route('portal.tosa.templates.show', ['key' => 'front_page']))->assertOk()->assertDownload('latest.doc');
        $this->assertSame($this->referenceBytes('submission_guide'), file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertSame($hash, hash_file('sha256', base_path('TOSA/Front Page.doc')));
        foreach (['guidelines', 'application_form', 'computation', 'submission_guide'] as $key) {
            $response = $this->get(route('portal.tosa.templates.show', ['key' => $key]))->assertOk()
                ->assertHeader('Content-Type', TosaRequirements::mimeType($key));
            $this->assertSame($this->referenceBytes($key), file_get_contents($response->baseResponse->getFile()->getPathname()));
        }
        Auth::guard('student')->logout();
        $this->get(route('portal.tosa.templates.show', ['key' => 'front_page']))->assertRedirect('/');
    }
}

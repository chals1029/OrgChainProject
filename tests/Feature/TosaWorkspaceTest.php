<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\TosaApplicant;
use App\Models\TosaShortlist;
use App\Services\TosaRequirements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class TosaWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config(['database.default' => 'mysql', 'database.connections.mysql' => $memory, 'database.connections.voting' => $memory]);
        DB::purge('mysql');
        Storage::fake('local');
        Schema::connection('mysql')->create('tosa_applicants', function (Blueprint $table): void {
            $table->id();
            foreach (['sr_code', 'full_name', 'program', 'status', 'subsection', 'requirements'] as $column) {
                $table->text($column)->nullable();
            }
            $table->json('document_review')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('office_users', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'email', 'username', 'password', 'office_role', 'tosa_clearance', 'remember_token'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->integer('auth_version')->default(0);
            $table->unsignedBigInteger('student_organization_id')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_31_000001_create_tosa_workspace_tables.php'))->up();
        (require database_path('migrations/2026_11_05_000001_add_tosa_published_decisions.php'))->up();
        (require database_path('migrations/2026_11_06_000001_add_tosa_shortlist_captions.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function officer(string $role = 'oso'): OfficeUser
    {
        return OfficeUser::query()->create([
            'name' => 'Synthetic reviewer', 'office_role' => $role, 'is_active' => true,
            'tosa_clearance' => $role === 'ovcaa' ? 'Level 1 Read-only' : 'No Access',
        ]);
    }

    private function applicant(int $number, bool $verified = true): TosaApplicant
    {
        $srCode = 'workspace-'.$number;
        $requirements = [];
        $reviews = [];
        $pdf = file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf'));
        $docx = file_get_contents(base_path('TOSA/TOSA Application Form 2025.docx'));
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            $format = $key === 'application_form' ? 'docx' : 'pdf';
            $path = 'tosa/'.$srCode.'/'.$key.'.'.$format;
            Storage::disk('local')->put($path, $format === 'docx' ? $docx : $pdf);
            $requirements[$key] = ['path' => $path, 'name' => $key.'.'.$format];
            $reviews[$key] = ['path' => $path, 'verified_by' => 1, 'verified_at' => now()->toIso8601String()];
        }
        $applicant = TosaApplicant::query()->create([
            'sr_code' => $srCode, 'full_name' => 'Synthetic Applicant '.$number, 'program' => 'Test Program',
            'status' => 'submitted', 'subsection' => 'screening', 'requirements' => $requirements,
        ]);
        if ($verified) {
            $applicant->forceFill(['document_review' => ['documents' => $reviews]])->save();
        }

        return $applicant;
    }

    public function test_zip_contains_private_originals_and_reports_unavailable_files(): void
    {
        $applicant = $this->applicant(1);
        $url = route('office.tosa.package.download', $applicant);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($this->officer('ovcaa'), 'office')->getJson($url)->assertForbidden();
        $this->actingAs($this->officer(), 'office');
        Storage::disk('local')->delete($applicant->requirementPath('academic'));
        $response = $this->get($url)->assertOk()->assertDownload($applicant->full_name.'.zip')->assertHeader('Content-Type', 'application/zip');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        try {
            $this->assertTrue($zip->open($path));
            $this->assertSame(7, $zip->numFiles);
            $this->assertStringContainsString('Academic Evidence', $zip->getFromName('unavailable-documents.txt'));
            $this->assertSame(file_get_contents(base_path('TOSA/Guidelines TOSA 2025.pdf')), $zip->getFromName('A-table-of-contents.pdf'));
        } finally {
            $zip->close();
            @unlink($path);
        }
        foreach (TosaRequirements::SUBMISSIONS as $key => $definition) {
            Storage::disk('local')->delete($applicant->requirementPath($key));
        }
        $this->getJson($url)->assertUnprocessable();
    }

    public function test_only_oso_can_publish_the_current_ten_reviewed_applicants(): void
    {
        $office = $this->officer();
        $applicants = collect(range(1, 10))->map(fn (int $number) => $this->applicant($number, false));
        $reviewedOther = $this->applicant(11);
        $pendingOther = $this->applicant(12, false);
        $ids = $applicants->pluck('id')->all();
        $url = route('office.tosa.top10.save');
        $this->actingAs($this->officer('ovcaa'), 'office')
            ->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids])->assertForbidden();
        $this->actingAs($office, 'office');
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => array_slice($ids, 0, 9)])->assertUnprocessable();
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => [...array_slice($ids, 0, 9), $ids[0]]])->assertUnprocessable();
        foreach ($applicants as $applicant) {
            $this->postJson(route('office.tosa.review', $applicant), [])->assertOk();
            $this->postJson(route('office.tosa.top10.add', $applicant), [])->assertOk();
        }
        $this->assertNull(TosaShortlist::query()->findOrFail(1)->decisionFor($applicants[0]->fresh(), true));
        $this->assertNull(TosaShortlist::query()->findOrFail(1)->published_entries);
        $this->assertSame([], TosaShortlist::query()->findOrFail(1)->applicantIdsForDecision('qualified'));
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids, 'caption_qualified' => 'Selected caption', 'caption_not_qualified' => 'Other caption'])->assertOk();
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $this->assertSame('qualified', $shortlist->decisionFor($applicants[0]->fresh(), true));
        $this->assertSame('not_qualified', $shortlist->decisionFor($reviewedOther->fresh(), true));
        $this->assertNull($shortlist->decisionFor($pendingOther->fresh(), false));
        $this->assertSame('Synthetic Applicant 1', $shortlist->published_decisions[$applicants[0]->id]['name']);
        $this->assertSame('Synthetic Applicant 11', $shortlist->published_decisions[$reviewedOther->id]['name']);
        $this->assertSame('Selected caption', $shortlist->published_decisions[$applicants[0]->id]['caption']);
        $this->assertSame('Other caption', $shortlist->published_decisions[$reviewedOther->id]['caption']);
        $this->assertArrayNotHasKey($pendingOther->id, $shortlist->published_decisions);
        $this->assertEqualsCanonicalizing($ids, $shortlist->applicantIdsForDecision('qualified'));
        $this->assertSame([$reviewedOther->id], $shortlist->applicantIdsForDecision('not_qualified'));
        $this->postJson(route('office.tosa.review', $pendingOther), [])->assertOk();
        $this->assertNull($shortlist->decisionFor($pendingOther->fresh(), true));
        $this->assertSame([$reviewedOther->id], $shortlist->applicantIdsForDecision('not_qualified'));
        $published = $shortlist->published_entries;
        $this->assertCount(10, $published);
        $this->assertSame('Synthetic Applicant 1', $published[0]['name']);
        $this->assertArrayNotHasKey('sr_code', $published[0]);
        $this->assertArrayNotHasKey('email', $published[0]);
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'top10_published', 'actor_id' => $office->id], 'mysql');
        $this->postJson(route('office.tosa.top10.remove', $ids[0]), [])->assertForbidden();
        $this->postJson(route('office.tosa.top10.add', $reviewedOther), [])->assertForbidden();
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids, 'caption_qualified' => 'Selected caption', 'caption_not_qualified' => 'Other caption'])->assertForbidden();
        $this->postJson($url, ['intent' => 'unpublish'])->assertOk();
        $this->assertNull($shortlist->fresh()->published_entries);
        $this->assertNull($shortlist->fresh()->decisionFor($applicants[0]->fresh(), true));
        $this->postJson(route('office.tosa.top10.remove', $ids[0]), [])->assertOk()->assertJsonPath('removed', true);
        $replacement = $reviewedOther;
        $this->postJson(route('office.tosa.top10.add', $replacement), [])->assertOk()->assertJsonPath('draft_count', 10);
        $currentIds = [...array_slice($ids, 1), $replacement->id];
        $captions = ['caption_qualified' => 'Selected caption', 'caption_not_qualified' => 'Other caption'];
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids] + $captions)->assertUnprocessable();
        $this->assertSame($currentIds, $shortlist->fresh()->draft_ids);
        $applicants[1]->update(['subsection' => 'returned']);
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $currentIds] + $captions)->assertUnprocessable();
        $applicants[1]->update(['subsection' => 'screening']);
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $currentIds] + $captions)->assertOk();
        $this->postJson(route('office.tosa.top10.remove', $currentIds[0]), [])->assertForbidden();
        $this->assertSame('not_qualified', $shortlist->fresh()->decisionFor($applicants[0]->fresh(), true));
        $this->assertSame('qualified', $shortlist->fresh()->decisionFor($replacement->fresh(), true));
        $review = $replacement->fresh()->document_review;
        $review['documents']['academic']['verified_at'] = '2030-01-01T00:00:00+00:00';
        $replacement->forceFill(['document_review' => $review])->save();
        $this->assertNull($shortlist->fresh()->decisionFor($replacement->fresh(), true));
        $this->assertNotContains($replacement->id, $shortlist->fresh()->applicantIdsForDecision('qualified'));
        $this->postJson($url, ['intent' => 'unpublish'])->assertOk();
        $this->assertNull($shortlist->fresh()->published_entries);
        $this->assertSame([], $shortlist->fresh()->applicantIdsForDecision('qualified'));
    }

    public function test_removing_a_draft_applicant_requires_unpublishing_and_preserves_other_entries(): void
    {
        $office = $this->officer();
        $first = $this->applicant(1);
        $second = $this->applicant(2);
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $shortlist->update(['draft_ids' => [$first->id, $second->id], 'published_entries' => [['name' => 'Previously Published']], 'published_at' => now()]);
        $url = route('office.tosa.top10.remove', $first->id);
        $this->postJson($url, [])->assertUnauthorized();
        $this->actingAs($this->officer('ovcaa'), 'office')->postJson($url, [])->assertForbidden();
        $this->actingAs($office, 'office');
        $this->postJson($url, ['applicant_ids' => []])->assertUnprocessable();
        $this->postJson($url, [])->assertForbidden();
        $this->postJson(route('office.tosa.top10.save'), ['intent' => 'unpublish'])->assertOk();
        $this->postJson($url, [])->assertOk()->assertJsonPath('removed', true);
        $this->assertSame([$second->id], $shortlist->fresh()->draft_ids);
        $this->assertTrue(app(\App\Services\TosaWorkflowService::class)->isVerified($first->fresh()));
        $this->assertTrue(Storage::disk('local')->exists($first->requirementPath('application_form')));
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'top10_removed', 'actor_id' => $office->id, 'applicant_id' => $first->id], 'mysql');
        $this->postJson($url, [])->assertOk()->assertJsonPath('removed', false);
    }
}

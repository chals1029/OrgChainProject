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
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/zip');
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

    public function test_only_oso_can_publish_exactly_ten_reviewed_applicants(): void
    {
        $office = $this->officer();
        $applicants = collect(range(1, 10))->map(fn (int $number) => $this->applicant($number));
        $ids = $applicants->pluck('id')->all();
        $url = route('office.tosa.top10.save');
        $this->actingAs($this->officer('ovcaa'), 'office')
            ->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids])->assertForbidden();
        $this->actingAs($office, 'office');
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => array_slice($ids, 0, 9)])->assertUnprocessable();
        $this->postJson($url, ['intent' => 'save', 'applicant_ids' => [...$ids, $ids[0]]])->assertUnprocessable();
        $this->postJson($url, ['intent' => 'save', 'applicant_ids' => $ids])->assertOk();
        $this->assertNull(TosaShortlist::query()->findOrFail(1)->published_entries);
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids])->assertOk();
        $shortlist = TosaShortlist::query()->findOrFail(1);
        $published = $shortlist->published_entries;
        $this->assertCount(10, $published);
        $this->assertSame('Synthetic Applicant 1', $published[0]['name']);
        $this->assertArrayNotHasKey('sr_code', $published[0]);
        $this->assertArrayNotHasKey('email', $published[0]);
        $this->assertDatabaseHas('tosa_history_events', ['action' => 'top10_published', 'actor_id' => $office->id], 'mysql');

        $this->postJson($url, ['intent' => 'save', 'applicant_ids' => array_slice($ids, 0, 9)])->assertOk();
        $this->assertSame($published, $shortlist->fresh()->published_entries);
        $replacement = $this->applicant(11, false);
        $this->postJson(route('office.tosa.top10.add', $replacement), [])->assertOk()->assertJsonPath('draft_count', 10);
        $this->assertSame($published, $shortlist->fresh()->published_entries);
        $applicants->first()->update(['subsection' => 'returned']);
        $this->postJson($url, ['intent' => 'publish', 'applicant_ids' => $ids])->assertUnprocessable();
        $this->assertSame($published, $shortlist->fresh()->published_entries);
        $this->postJson($url, ['intent' => 'unpublish'])->assertOk();
        $this->assertNull($shortlist->fresh()->published_entries);
    }
}

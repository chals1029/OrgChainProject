<?php

namespace Tests\Feature;

use App\Models\ArchiveDocument;
use App\Models\ExpenseReceiptReview;
use App\Models\InCampusActivitySubmission;
use App\Models\OrgActivityAccomplishment;
use App\Models\OrgRenewalDocument;
use App\Models\OrgReportDocument;
use App\Models\TosaApplicant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('mysql');
        Storage::fake('public');
        Storage::fake('local');
        foreach ([
            'in_campus_activity_submissions' => ['attachments'],
            'org_renewal_documents' => ['file_path', 'review_status'],
            'org_report_documents' => ['file_path', 'accomplishment_summary'],
            'tosa_applicants' => ['sr_code', 'requirements'],
            'org_activity_accomplishments' => ['evidence', 'financial_snapshot'],
            'archive_documents' => ['file_path', 'file_disk'],
            'expense_receipt_reviews' => ['receipt_path', 'receipt_disk', 'receipt_attachments'],
        ] as $table => $columns) {
            Schema::connection('mysql')->create($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->id();
                foreach ($columns as $column) $blueprint->text($column)->nullable();
                $blueprint->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    public function test_dry_run_then_migration_preserves_all_referenced_and_orphan_bytes_and_is_idempotent(): void
    {
        $paths = ['in-campus-activities/7/one.pdf', 'off-campus-activities/8/two.pdf', 'renewal-documents/three.pdf',
            'semester-reports/ar/four.docx', 'tosa/fixture-student/cv.pdf', 'archive/five.pdf',
            'receipts/six.jpg', 'expense-receipts/7/seven.jpg', 'accomplishment-evidence/7/eight.jpg'];
        foreach ($paths as $index => $path) Storage::disk('public')->put($path, 'confidential fixture '.$index);
        Storage::disk('public')->put('renewal-documents/orphan.pdf', 'orphan bytes');
        Storage::disk('public')->put('financial-smoke/orphan.xlsx', 'financial orphan bytes');
        foreach (['templates/blank.docx', 'renewal-templates/blank.pdf', 'tosa-templates/blank.pdf', 'office-settings/logo.png', 'community/photo.png'] as $path) {
            Storage::disk('public')->put($path, 'public asset bytes');
        }
        InCampusActivitySubmission::query()->forceCreate(['attachments' => ['proposal' => ['path' => $paths[0]], 'travel' => ['path' => $paths[1]]]]);
        OrgRenewalDocument::query()->forceCreate(['file_path' => $paths[2]]);
        OrgReportDocument::query()->forceCreate(['file_path' => $paths[3], 'accomplishment_summary' => ['reports' => [['evidence' => [['path' => $paths[8]]]]]]]);
        TosaApplicant::query()->forceCreate(['sr_code' => 'fixture-student', 'requirements' => ['cv' => ['path' => $paths[4]]]]);
        OrgActivityAccomplishment::query()->forceCreate(['evidence' => [['path' => $paths[8]]], 'financial_snapshot' => ['receipt_attachments' => [['path' => $paths[7]]]]]);
        $archive = ArchiveDocument::query()->forceCreate(['file_path' => $paths[5], 'file_disk' => 'public']);
        $receipt = ExpenseReceiptReview::query()->forceCreate(['receipt_path' => $paths[6], 'receipt_disk' => 'public',
            'receipt_attachments' => [['path' => $paths[6], 'disk' => 'public'], ['path' => $paths[7], 'disk' => 'public']]]);
        $original = collect($paths)->mapWithKeys(fn ($path) => [$path => Storage::disk('public')->get($path)])->all();
        $this->assertSame(0, Artisan::call('documents:privatize', ['--dry-run' => true]));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame('public', $archive->fresh()->file_disk);
        Storage::disk('public')->assertExists($paths);
        $this->assertSame(0, Artisan::call('documents:privatize'));
        foreach ($original as $path => $bytes) {
            $this->assertSame($bytes, Storage::disk('local')->get($path));
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertSame('orphan bytes', Storage::disk('local')->get('renewal-documents/orphan.pdf'));
        Storage::disk('public')->assertMissing('renewal-documents/orphan.pdf');
        $this->assertSame('financial orphan bytes', Storage::disk('local')->get('financial-smoke/orphan.xlsx'));
        Storage::disk('public')->assertMissing('financial-smoke/orphan.xlsx');
        $this->assertSame('local', $archive->fresh()->file_disk);
        $this->assertSame('local', $receipt->fresh()->receipt_disk);
        $this->assertSame(['local', 'local'], array_column($receipt->fresh()->receipt_attachments, 'disk'));
        $this->assertCount(5, Storage::disk('public')->allFiles());
        $this->assertSame(0, Artisan::call('documents:privatize'));
        foreach (array_keys($original) as $path) $this->assertStringNotContainsString($path, Artisan::output());
    }

    public function test_collision_and_missing_bytes_fail_visibly_without_overwriting_deleting_or_switching_metadata(): void
    {
        Storage::disk('public')->put('archive/conflict.pdf', 'public original');
        Storage::disk('local')->put('archive/conflict.pdf', 'different private original');
        $archive = ArchiveDocument::query()->forceCreate(['file_path' => 'archive/conflict.pdf', 'file_disk' => 'public']);
        $missing = OrgRenewalDocument::query()->forceCreate(['file_path' => 'renewal-documents/missing.pdf']);
        foreach ([['--dry-run' => true], []] as $arguments) {
            $this->assertSame(1, Artisan::call('documents:privatize', $arguments));
            $this->assertSame('public original', Storage::disk('public')->get($archive->file_path));
            $this->assertSame('different private original', Storage::disk('local')->get($archive->file_path));
            $this->assertSame('public', $archive->fresh()->file_disk);
            $this->assertTrue($missing->fresh()->exists);
            $this->assertStringNotContainsString($archive->file_path, Artisan::output());
        }
    }

    public function test_verified_preexisting_copy_resumes_and_unsafe_metadata_never_removes_sources(): void
    {
        $path = 'receipts/resumed.pdf';
        foreach (['public', 'local'] as $disk) Storage::disk($disk)->put($path, 'same preserved bytes');
        $receipt = ExpenseReceiptReview::query()->forceCreate(['receipt_path' => $path, 'receipt_disk' => 'public']);
        $this->assertSame(0, Artisan::call('documents:privatize'));
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('same preserved bytes', Storage::disk('local')->get($path));
        $this->assertSame('local', $receipt->fresh()->receipt_disk);
        $archive = ArchiveDocument::query()->forceCreate(['file_path' => '../outside.pdf', 'file_disk' => 'public']);
        Storage::disk('public')->put('renewal-documents/safe.pdf', 'safe preserved bytes');
        $this->assertSame(1, Artisan::call('documents:privatize'));
        Storage::disk('public')->assertExists('renewal-documents/safe.pdf');
        $this->assertSame('public', $archive->fresh()->file_disk);
    }

    public function test_allow_missing_retires_available_sources_without_claiming_missing_records_are_fixed(): void
    {
        $available = ArchiveDocument::query()->forceCreate(['file_path' => 'archive/available.pdf', 'file_disk' => 'public']);
        $missing = ArchiveDocument::query()->forceCreate(['file_path' => 'archive/missing.pdf', 'file_disk' => 'public']);
        Storage::disk('public')->put($available->file_path, 'original confidential bytes');
        $this->assertSame(1, Artisan::call('documents:privatize', ['--allow-missing' => true, '--diagnostics' => true]));
        $this->assertSame('original confidential bytes', Storage::disk('local')->get($available->file_path));
        Storage::disk('public')->assertMissing($available->file_path);
        $this->assertSame('local', $available->fresh()->file_disk);
        $this->assertSame('public', $missing->fresh()->file_disk);
        Storage::disk('local')->assertMissing($missing->file_path);
        $this->assertStringNotContainsString($missing->file_path, Artisan::output());
        $manifest = json_decode(Storage::disk('local')->get('migration-diagnostics/confidential-documents.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $manifest['counts']['missing']);
        $this->assertSame($missing->file_path, $manifest['failures'][0]['path']);
        Storage::disk('public')->assertMissing('migration-diagnostics/confidential-documents.json');
    }

    public function test_allow_missing_never_overrides_collisions(): void
    {
        $path = 'archive/collision.pdf';
        Storage::disk('public')->put($path, 'public original');
        Storage::disk('local')->put($path, 'private different original');
        $archive = ArchiveDocument::query()->forceCreate(['file_path' => $path, 'file_disk' => 'public']);
        $this->assertSame(1, Artisan::call('documents:privatize', ['--allow-missing' => true]));
        $this->assertSame('public original', Storage::disk('public')->get($path));
        $this->assertSame('private different original', Storage::disk('local')->get($path));
        $this->assertSame('public', $archive->fresh()->file_disk);
    }
}

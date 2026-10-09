<?php

namespace Tests\Feature;

use App\Models\ActivityComplianceDoc;
use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use App\Models\InCampusActivitySubmission;
use App\Models\OfficeUser;
use App\Models\OrgActivity;
use App\Models\OrgReportStatus;
use App\Models\StudentOrganization;
use App\Services\DocxBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ArchiveHierarchyTest extends TestCase
{
    use UsesLaragonDatabase;

    private StudentOrganization $organization;
    private StudentOrganization $foreignOrganization;
    private OfficeUser $so;
    private OfficeUser $oso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        foreach (['mysql', 'orgchain'] as $connection) {
            DB::connection($connection)->beginTransaction();
        }
        Storage::fake('local');
        Storage::fake('public');
        $this->organization = StudentOrganization::query()->create([
            'name' => 'Archive Owned Fixture '.Str::uuid(), 'college' => 'Engineering', 'is_active' => true,
        ]);
        $this->foreignOrganization = StudentOrganization::query()->create([
            'name' => 'Archive Foreign Fixture '.Str::uuid(), 'college' => 'Sciences', 'is_active' => true,
        ]);
        $this->so = $this->officeUser('so', $this->organization);
        $this->oso = $this->officeUser('oso');
    }

    protected function tearDown(): void
    {
        try {
            foreach (['mysql', 'orgchain'] as $connection) {
                while (DB::connection($connection)->transactionLevel() > 0) {
                    DB::connection($connection)->rollBack();
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_so_empty_archive_has_no_demo_documents_folders_or_storage(): void
    {
        $this->actingAs($this->so, 'office')->get(route('office.archive'))
            ->assertOk()->assertViewHas('assignedArchiveOrganization', $this->organization->name)
            ->assertViewHas('folders', fn ($folders) => $folders->isEmpty())
            ->assertViewHas('documents', fn ($documents) => $documents->isEmpty())
            ->assertViewHas('totalDocuments', 0)->assertViewHas('totalFolders', 0)
            ->assertViewHas('storageUsedFormatted', '0 B');
    }

    public function test_so_root_nested_recents_picker_counts_and_ancestry_are_owned(): void
    {
        $root = $this->folder('Own root');
        $child = $this->folder('Own child', parent: $root);
        $foreignRoot = $this->folder('Foreign root', $this->foreignOrganization);
        $foreignChild = $this->folder('Foreign child', $this->foreignOrganization, $root);
        $malformed = $this->folder('Misowned ancestry', parent: $foreignRoot);
        $ownDocument = $this->document($child, 'Owned document', 'own bytes');
        $this->document($foreignRoot, 'Foreign recent document', 'foreign secret');
        $this->document($foreignChild, 'Foreign nested document', 'foreign nested secret');
        $this->document($malformed, 'Misowned document', 'misowned secret');

        $rootResponse = $this->actingAs($this->so, 'office')->get(route('office.archive'))->assertOk();
        $rootResponse->assertViewHas('folders', fn ($folders) => $folders->pluck('id')->all() === [$root->id]);
        $rootResponse->assertViewHas('folders', fn ($folders) => $folders->first()['subfolders'] === 1);
        $rootResponse->assertViewHas('documents', fn ($documents) => $documents->pluck('id')->all() === [$ownDocument->id]);
        $rootResponse->assertViewHas('allSavedFolders', fn ($folders) => $folders->pluck('id')->sort()->values()->all() === [$root->id, $child->id]);
        $rootResponse->assertViewHas('totalDocuments', 1)->assertViewHas('totalFolders', 2);
        $rootResponse->assertViewHas('storageUsedFormatted', '9 B');
        $rootResponse->assertDontSee('Foreign root')->assertDontSee('Foreign recent document')->assertDontSee('Misowned ancestry');

        $this->get(route('office.archive', ['folder_id' => $root->id]))->assertOk()
            ->assertViewHas('folders', fn ($folders) => $folders->pluck('id')->all() === [$child->id]);
        $this->get(route('office.archive', ['folder_id' => $child->id]))->assertOk()
            ->assertViewHas('parentFolderId', $root->id)
            ->assertViewHas('breadcrumbs', fn ($crumbs) => array_column($crumbs, 'id') === [null, $root->id, $child->id])
            ->assertViewHas('documents', fn ($documents) => $documents->pluck('id')->all() === [$ownDocument->id]);
        foreach (['view', 'download'] as $action) {
            $response = $this->get(route('office.archive.documents.'.$action, $ownDocument))->assertOk();
            $this->assertSame('own bytes', $this->body($response));
        }
        foreach ([$foreignRoot->id, $foreignChild->id, $malformed->id, 999999999, 'demo-1', 'activity-invalid'] as $id) {
            $this->get(route('office.archive', ['folder_id' => $id]))->assertNotFound();
        }
    }

    public function test_so_creates_owned_hierarchy_and_preserves_genuine_docx_privately_without_submitting_reports(): void
    {
        $this->actingAs($this->so, 'office')->post(route('office.archive.folders.store'), [
            'name' => 'Created root', 'organization_name' => $this->foreignOrganization->name,
            'semester' => '1st Semester', 'color' => 'red',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $root = ArchiveFolder::query()->where('name', 'Created root')->where('organization_name', $this->organization->name)->sole();
        $this->post(route('office.archive.folders.store'), [
            'name' => 'Created child', 'parent_id' => $root->id,
            'organization_name' => $this->foreignOrganization->name,
        ])->assertRedirect(route('office.archive', ['folder_id' => $root->id]))->assertSessionHasNoErrors();
        $child = $root->children()->sole();
        $this->assertSame($this->organization->name, $child->organization_name);
        $this->assertSame('1st Semester', $child->semester);
        $this->post(route('office.archive.folders.store'), ['name' => 'Created grandchild', 'parent_id' => $child->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $grandchild = $child->children()->sole();
        $this->assertSame([$root->id, $child->id, $grandchild->id], array_column($grandchild->getBreadcrumbs(), 'id'));

        $bytes = $this->docxBytes();
        $reportCount = OrgReportStatus::query()->count();
        $this->post(route('office.archive.documents.store'), [
            'archive_folder_id' => $grandchild->id, 'name' => 'Meeting minutes',
            'document' => UploadedFile::fake()->createWithContent('Original Minutes.docx', $bytes),
        ])->assertRedirect(route('office.archive', ['folder_id' => $grandchild->id]))->assertSessionHasNoErrors();
        $document = $grandchild->documents()->sole();
        $this->assertSame('local', $document->file_disk);
        $this->assertSame('Original Minutes.docx', $document->original_name);
        $this->assertSame(strlen($bytes), (int) $document->file_size);
        $this->assertSame($bytes, Storage::disk('local')->get($document->file_path));
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertSame($reportCount, OrgReportStatus::query()->count());
        $this->get(route('office.archive', ['folder_id' => $grandchild->id]))->assertOk()
            ->assertViewHas('documents', fn ($documents) => $documents->first()['url'] === route('office.archive.documents.view', $document)
                && $documents->first()['download_url'] === route('office.archive.documents.download', $document));
        $view = $this->get(route('office.archive.documents.view', $document))->assertOk();
        $this->assertStringStartsWith('inline;', (string) $view->headers->get('Content-Disposition'));
        $this->assertSame($bytes, $this->body($view));
        $download = $this->get(route('office.archive.documents.download', $document))->assertOk()->assertDownload('Original Minutes.docx');
        $this->assertSame($bytes, $this->body($download));
    }

    public function test_foreign_parent_folder_upload_and_file_ids_fail_before_any_file_is_written(): void
    {
        $foreign = $this->folder('Foreign parent', $this->foreignOrganization);
        $document = $this->document($foreign, 'Foreign document', 'secret bytes');
        $this->actingAs($this->so, 'office');
        foreach ([$foreign->id, 0, 999999999] as $id) {
            $this->post(route('office.archive.folders.store'), ['name' => 'Forged child', 'parent_id' => $id])->assertNotFound();
            $this->post(route('office.archive.documents.store'), [
                'archive_folder_id' => $id, 'document' => UploadedFile::fake()->createWithContent('forged.docx', $this->docxBytes()),
            ])->assertNotFound();
        }
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->assertDatabaseMissing('archive_folders', ['name' => 'Forged child']);
        $this->get(route('office.archive.documents.view', $document))->assertNotFound();
        $this->get(route('office.archive.documents.download', $document))->assertNotFound();
        $this->assertSame('secret bytes', Storage::disk('public')->get($document->file_path));
    }

    public function test_foreign_so_private_upload_cannot_be_read_by_another_organization(): void
    {
        $foreignFolder = $this->folder('Foreign private root', $this->foreignOrganization);
        $bytes = $this->docxBytes();
        $foreignSo = $this->officeUser('so', $this->foreignOrganization);
        $this->actingAs($foreignSo, 'office')->post(route('office.archive.documents.store'), [
            'archive_folder_id' => $foreignFolder->id,
            'document' => UploadedFile::fake()->createWithContent('Foreign private.docx', $bytes),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $document = $foreignFolder->documents()->sole();
        Storage::disk('public')->assertMissing($document->file_path);
        $this->actingAs($this->so, 'office');
        foreach (['view', 'download'] as $action) {
            $this->get(route('office.archive.documents.'.$action, $document))->assertNotFound();
        }
        $this->assertSame($bytes, Storage::disk('local')->get($document->file_path));
    }

    public function test_unassigned_and_unsupported_roles_fail_closed_for_all_archive_endpoints(): void
    {
        $folder = $this->folder('Protected root');
        $document = $this->document($folder, 'Protected document', 'protected');
        [, $activityDocument] = $this->activityDocument($this->organization);
        foreach (['so', 'sdo', 'ovcaa', 'oc'] as $role) {
            $this->actingAs($this->officeUser($role), 'office');
            $this->get(route('office.archive'))->assertForbidden();
            $this->get(route('office.archive', ['folder_id' => $folder->id]))->assertForbidden();
            $this->post(route('office.archive.folders.store'), ['name' => 'Forbidden folder'])->assertForbidden();
            $this->post(route('office.archive.documents.store'), [
                'archive_folder_id' => $folder->id, 'document' => UploadedFile::fake()->createWithContent('report.docx', $this->docxBytes()),
            ])->assertForbidden();
            foreach (['view', 'download'] as $action) {
                $this->get(route('office.archive.documents.'.$action, $document))->assertForbidden();
                $this->get(route('office.archive.activity-documents.'.$action, $activityDocument))->assertForbidden();
            }
        }
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_archive_upload_retains_twenty_megabyte_limit(): void
    {
        $folder = $this->folder('Size limit root');
        $this->actingAs($this->so, 'office')->post(route('office.archive.documents.store'), [
            'archive_folder_id' => $folder->id,
            'document' => UploadedFile::fake()->create('oversized.pdf', 20481, 'application/pdf'),
        ])->assertSessionHasErrors('document');
        $this->assertSame(0, $folder->documents()->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_renamed_pdf_or_invalid_docx_is_rejected_without_storage_or_metadata(): void
    {
        $folder = $this->folder('Validation root');
        $this->actingAs($this->so, 'office');
        foreach (["%PDF-1.4\nRenamed PDF", 'not a Word document'] as $bytes) {
            $this->post(route('office.archive.documents.store'), [
                'archive_folder_id' => $folder->id, 'document' => UploadedFile::fake()->createWithContent('fake.docx', $bytes),
            ])->assertSessionHasErrors('document');
        }
        $this->assertSame(0, $folder->documents()->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_failed_database_insert_removes_new_private_file(): void
    {
        $folder = $this->folder('Failure root');
        $originalDispatcher = ArchiveDocument::getEventDispatcher();
        $dispatcher = clone $originalDispatcher;
        ArchiveDocument::setEventDispatcher($dispatcher);
        $dispatcher->listen('eloquent.creating: '.ArchiveDocument::class, static function (): never {
            throw new \RuntimeException('Owned archive insert failure');
        });
        try {
            $this->withoutExceptionHandling()->actingAs($this->so, 'office')->post(route('office.archive.documents.store'), [
                'archive_folder_id' => $folder->id, 'document' => UploadedFile::fake()->createWithContent('minutes.docx', $this->docxBytes()),
            ]);
            $this->fail('The database insert must fail.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Owned archive insert failure', $error->getMessage());
        } finally {
            ArchiveDocument::setEventDispatcher($originalDispatcher);
        }
        $this->assertSame(0, $folder->documents()->count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_oso_retains_broad_hierarchy_upload_and_legacy_public_document_bytes(): void
    {
        $root = $this->folder('Legacy verified report', $this->foreignOrganization);
        $legacy = $this->document($root, 'Legacy archive report', 'original public bytes');
        $this->actingAs($this->oso, 'office')->get(route('office.archive'))->assertOk()
            ->assertViewHas('assignedArchiveOrganization', null)
            ->assertViewHas('allSavedFolders', fn ($folders) => $folders->contains('id', $root->id));
        $this->post(route('office.archive.folders.store'), [
            'name' => 'OSO nested folder', 'parent_id' => $root->id, 'organization_name' => $this->organization->name,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $child = $root->children()->sole();
        $this->assertSame($this->foreignOrganization->name, $child->organization_name);
        $this->post(route('office.archive.documents.store'), [
            'archive_folder_id' => $child->id, 'document' => UploadedFile::fake()->createWithContent('OSO.docx', $this->docxBytes()),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('local', $child->documents()->sole()->file_disk);
        foreach (['view', 'download'] as $action) {
            $response = $this->get(route('office.archive.documents.'.$action, $legacy))->assertOk();
            $this->assertSame('original public bytes', $this->body($response));
        }
        $this->assertSame('public', $legacy->fresh()->file_disk);
        $this->assertSame('original public bytes', Storage::disk('public')->get($legacy->file_path));
        $legacyMixedFolder = $this->folder('Legacy mixed organization child', $this->organization, $root);
        $legacyMixedDocument = $this->document($legacyMixedFolder, 'Legacy mixed report', 'legacy mixed bytes');
        $this->get(route('office.archive', ['folder_id' => $legacyMixedFolder->id]))->assertOk();
        $this->assertSame('legacy mixed bytes', $this->body(
            $this->get(route('office.archive.documents.view', $legacyMixedDocument))->assertOk()
        ));
        Storage::disk('public')->delete($legacy->file_path);
        foreach (['view', 'download'] as $action) {
            $this->get(route('office.archive.documents.'.$action, $legacy))->assertNotFound();
        }
    }

    public function test_activity_archive_uses_real_attachment_metadata_counts_and_owned_bytes(): void
    {
        [$ownActivity, $ownDocument] = $this->activityDocument($this->organization);
        [$foreignActivity, $foreignDocument] = $this->activityDocument($this->foreignOrganization);
        ActivityComplianceDoc::query()->create([
            'org_activity_id' => $ownActivity->id, 'doc_key' => 'missing', 'title' => 'No file document', 'status' => 'approved',
        ]);
        $this->actingAs($this->so, 'office')->get(route('office.archive'))->assertOk()
            ->assertViewHas('totalDocuments', 1)->assertViewHas('totalFolders', 1)
            ->assertViewHas('storageUsedFormatted', '21 B')
            ->assertViewHas('folders', fn ($folders) => $folders->first()['documents'] === 1);
        $this->get(route('office.archive', ['folder_id' => 'activity-'.$ownActivity->id]))->assertOk()
            ->assertViewHas('documents', fn ($documents) => $documents->count() === 1
                && $documents->first()['original_name'] === 'Original proposal.pdf'
                && $documents->first()['download_url'] === route('office.archive.activity-documents.download', $ownDocument));
        $this->get(route('office.archive', ['folder_id' => 'activity-'.$foreignActivity->id]))->assertNotFound();
        foreach (['view', 'download'] as $action) {
            $response = $this->get(route('office.archive.activity-documents.'.$action, $ownDocument))->assertOk();
            $this->assertSame('%PDF-1.4 actual bytes', $this->body($response));
            $this->get(route('office.archive.activity-documents.'.$action, $foreignDocument))->assertNotFound();
        }
        $this->actingAs($this->oso, 'office')->get(route('office.archive.activity-documents.download', $foreignDocument))
            ->assertOk()->assertDownload('Original proposal.pdf');
    }

    public function test_archive_document_disk_and_regular_file_boundary_cannot_escape_storage(): void
    {
        $folder = $this->folder('File boundary root');
        $document = $this->document($folder, 'Directory document', 'bytes');
        Storage::disk('public')->makeDirectory('archive-directory');
        $document->update(['file_path' => 'archive-directory']);
        $this->actingAs($this->so, 'office');
        foreach (['view', 'download'] as $action) {
            $this->get(route('office.archive.documents.'.$action, $document))->assertNotFound();
        }
        $document->update(['file_disk' => 'unknown']);
        $this->get(route('office.archive.documents.view', $document))->assertNotFound();
        $document->update(['file_disk' => 'local', 'file_path' => '../../.env']);
        $this->get(route('office.archive.documents.download', $document))->assertNotFound();
    }

    private function officeUser(string $role, ?StudentOrganization $organization = null): OfficeUser
    {
        $suffix = Str::uuid()->toString();
        return OfficeUser::query()->create([
            'name' => 'Owned Archive '.strtoupper($role), 'email' => 'archive-'.$suffix.'@example.test',
            'username' => 'archive_'.$suffix, 'password' => 'ArchiveFixture!2026',
            'office_role' => $role, 'student_organization_id' => $organization?->id,
            'office_title' => strtoupper($role).' Desk', 'is_active' => true,
        ]);
    }

    private function folder(string $name, ?StudentOrganization $organization = null, ?ArchiveFolder $parent = null): ArchiveFolder
    {
        return ArchiveFolder::query()->create([
            'name' => $name, 'organization_name' => ($organization ?? $this->organization)->name,
            'parent_id' => $parent?->id, 'semester' => '2nd Semester', 'color' => 'blue',
        ]);
    }

    private function document(ArchiveFolder $folder, string $name, string $bytes): ArchiveDocument
    {
        $path = 'archive-fixtures/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($path, $bytes);
        // Deliberately omit file_disk: verified legacy archives must use the migration's public default.
        return ArchiveDocument::query()->create([
            'archive_folder_id' => $folder->id, 'name' => $name, 'original_name' => $name.'.pdf',
            'file_path' => $path, 'mime_type' => 'application/pdf', 'file_size' => strlen($bytes), 'uploaded_by' => 'Fixture author',
        ])->fresh();
    }

    private function activityDocument(StudentOrganization $organization): array
    {
        $activity = OrgActivity::query()->create([
            'title' => 'Archive activity '.Str::uuid(), 'organization_name' => $organization->name,
            'college' => $organization->college, 'status' => 'upcoming', 'workflow_status' => 'oc_approved',
        ]);
        $path = 'archive-activity-fixtures/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 actual bytes');
        $submission = InCampusActivitySubmission::query()->create([
            'org_activity_id' => $activity->id, 'organization_name' => $organization->name,
            'college' => $organization->college, 'activity_type' => 'in_campus', 'status' => 'submitted',
            'workflow_status' => 'oc_approved', 'rationale' => 'Fixture rationale', 'objectives' => 'Fixture objectives',
            'participants' => 'Fixture participants', 'safety_plan' => 'Fixture safety',
            'attachments' => ['project_proposal' => ['path' => $path, 'name' => 'Original proposal.pdf']],
        ]);
        $document = ActivityComplianceDoc::query()->create([
            'org_activity_id' => $activity->id, 'submission_id' => $submission->id,
            'doc_key' => 'project_proposal', 'title' => 'Actual proposal', 'status' => 'approved',
        ]);
        return [$activity, $document];
    }

    private function docxBytes(): string
    {
        return (new DocxBuilder())->title('Original organization meeting minutes')->p('Real Word content for archive preservation.')->build();
    }

    private function body(TestResponse $response): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
    }
}

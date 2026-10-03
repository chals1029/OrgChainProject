<?php

namespace Tests\Feature;

use App\Models\ArchiveDocument;
use App\Models\ArchiveFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class ArchiveHierarchyTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
    }

    public function test_archive_vault_root_displays_for_oso_user(): void
    {
        $oso = $this->ensureOfficeUser('oso');

        $response = $this->actingAs($oso, 'office')->get('/office-desk/archive');

        $response->assertOk();
        $response->assertSee('Archive Vault');
        $response->assertSee('Total Documents');
        $response->assertSee('Archived Folders');
    }

    public function test_oso_can_create_root_folder_and_nested_subfolder_hierarchy(): void
    {
        Storage::fake('public');
        $oso = $this->ensureOfficeUser('oso');
        $unique = uniqid();

        // 1. Create a root folder (parent_id = null)
        $rootName = "Root College {$unique}";
        $rootResponse = $this->actingAs($oso, 'office')
            ->post('/office-desk/archive/folders', [
                'name' => $rootName,
                'organization_name' => 'College of Informatics',
                'semester' => '1st Semester',
                'color' => 'red',
            ]);

        $rootResponse->assertRedirect();
        $rootFolder = ArchiveFolder::query()->where('name', $rootName)->first();
        $this->assertNotNull($rootFolder);
        $this->assertNull($rootFolder->parent_id);

        try {
            // 2. Create a 1st-level nested subfolder inside the root folder
            $subfolderName = "AY 2025-2026 {$unique}";
            $subResponse = $this->actingAs($oso, 'office')
                ->post('/office-desk/archive/folders', [
                    'name' => $subfolderName,
                    'parent_id' => $rootFolder->id,
                ]);

            $subResponse->assertRedirect('/office-desk/archive?folder_id='.$rootFolder->id);
            $subFolder = ArchiveFolder::query()->where('name', $subfolderName)->first();
            $this->assertNotNull($subFolder);
            $this->assertSame($rootFolder->id, $subFolder->parent_id);
            $this->assertSame('College of Informatics', $subFolder->organization_name); // inherited
            $this->assertSame('1st Semester', $subFolder->semester); // inherited

            // 3. Create a 2nd-level nested subfolder inside the 1st subfolder
            $nestedName = "Liquidation Receipts {$unique}";
            $nestedResponse = $this->actingAs($oso, 'office')
                ->post('/office-desk/archive/folders', [
                    'name' => $nestedName,
                    'parent_id' => $subFolder->id,
                ]);

            $nestedResponse->assertRedirect('/office-desk/archive?folder_id='.$subFolder->id);
            $nestedFolder = ArchiveFolder::query()->where('name', $nestedName)->first();
            $this->assertNotNull($nestedFolder);
            $this->assertSame($subFolder->id, $nestedFolder->parent_id);

            // 4. Upload a document directly into the 2nd-level nested folder
            $docName = "Annual Report {$unique}";
            $uploadResponse = $this->actingAs($oso, 'office')
                ->post('/office-desk/archive/documents', [
                    'archive_folder_id' => $nestedFolder->id,
                    'name' => $docName,
                    'document' => UploadedFile::fake()->create('report.pdf', 500, 'application/pdf'),
                ]);

            $uploadResponse->assertRedirect('/office-desk/archive?folder_id='.$nestedFolder->id);
            $doc = ArchiveDocument::query()->where('name', $docName)->first();
            $this->assertNotNull($doc);
            $this->assertSame($nestedFolder->id, $doc->archive_folder_id);

            // 5. Visit the nested folder view and verify GDrive-style breadcrumbs and contents
            $viewResponse = $this->actingAs($oso, 'office')
                ->get('/office-desk/archive?folder_id='.$nestedFolder->id);

            $viewResponse->assertOk();
            $viewResponse->assertSee($rootName);
            $viewResponse->assertSee($subfolderName);
            $viewResponse->assertSee($nestedName);
            $viewResponse->assertSee($docName);

            // 6. Test Model helpers
            $crumbs = $nestedFolder->getBreadcrumbs();
            $this->assertCount(3, $crumbs);
            $this->assertSame($rootFolder->id, $crumbs[0]['id']);
            $this->assertSame($subFolder->id, $crumbs[1]['id']);
            $this->assertSame($nestedFolder->id, $crumbs[2]['id']);

            $descendantIds = $rootFolder->getAllDescendantIds();
            $this->assertContains($subFolder->id, $descendantIds);
            $this->assertContains($nestedFolder->id, $descendantIds);

        } finally {
            // 7. Test Cascade Delete: deleting root folder removes all subfolders and documents
            $rootFolder->delete();
            $this->assertDatabaseMissing('archive_folders', ['name' => $rootName]);
            $this->assertDatabaseMissing('archive_folders', ['name' => $subfolderName]);
            $this->assertDatabaseMissing('archive_folders', ['name' => $nestedName]);
            $this->assertDatabaseMissing('archive_documents', ['name' => $docName]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\OfficeUser;
use App\Models\OrgRenewalDocument;
use App\Models\OrgRenewalSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\UsesLaragonDatabase;
use Tests\TestCase;

class OfficeDocumentPreviewTest extends TestCase
{
    use UsesLaragonDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useLaragonDatabase();
        Storage::fake('local');
    }

    #[DataProvider('invalidWordFiles')]
    public function test_invalid_native_word_uploads_cannot_create_documents_or_packets(string $name, string $bytes): void
    {
        $documents = OrgRenewalDocument::count();
        $packets = OrgRenewalSubmission::count();
        $files = Storage::disk('local')->allFiles();

        $this->actingAs($this->ensureOfficeUser('so'), 'office')
            ->postJson(route('office.documents.preview.convert'), [
                'file' => UploadedFile::fake()->createWithContent($name, $bytes),
            ])
            ->assertUnprocessable()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame($documents, OrgRenewalDocument::count());
        $this->assertSame($packets, OrgRenewalSubmission::count());
        $this->assertSame($files, Storage::disk('local')->allFiles());
    }

    public static function invalidWordFiles(): array
    {
        return [
            'empty' => ['empty.doc', ''],
            'renamed PDF' => ['renamed.doc', "%PDF-1.4\n1 0 obj <<>> endobj\n%%EOF"],
            'renamed HTML' => ['renamed.doc', '<html><body>Not a Word document</body></html>'],
            'spoofed compound header' => ['spoofed.doc', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\0", 504)],
            'wrong extension' => ['document.docx', 'Not a legacy Word file'],
        ];
    }

    #[DataProvider('activeDesks')]
    public function test_active_office_desks_reach_native_word_validation_without_persisting_uploads(string $role): void
    {
        $documents = OrgRenewalDocument::count();
        $packets = OrgRenewalSubmission::count();
        $files = Storage::disk('local')->allFiles();
        $this->actingAs($this->ensureOfficeUser($role), 'office')
            ->postJson(route('office.documents.preview.convert'), [
                'file' => UploadedFile::fake()->createWithContent('document.doc', 'Not genuine Word bytes'),
            ])->assertUnprocessable()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($documents, OrgRenewalDocument::count());
        $this->assertSame($packets, OrgRenewalSubmission::count());
        $this->assertSame($files, Storage::disk('local')->allFiles());
    }

    public static function activeDesks(): array
    {
        return [['so'], ['oso'], ['sdo'], ['ovcaa'], ['oc']];
    }

    #[DataProvider('activeDesks')]
    public function test_inactive_office_desks_cannot_request_temporary_conversion(string $role): void
    {
        $office = OfficeUser::query()->create([
            'name' => 'Inactive Preview Desk',
            'username' => 'inactive_preview_'.$role,
            'email' => 'inactive.preview.'.$role.'@example.test',
            'password' => 'FixtureOfficeOnly@2026!',
            'office_role' => $role,
            'office_title' => 'Inactive Preview Desk',
            'is_active' => false,
            'must_change_password' => false,
        ]);
        try {
            $this->actingAs($office, 'office')
                ->postJson(route('office.documents.preview.convert'), [
                    'file' => UploadedFile::fake()->createWithContent('document.doc', 'Private source bytes'),
                ])->assertUnauthorized();
            $this->assertSame([], Storage::disk('local')->allFiles());
        } finally {
            $office->delete();
        }
    }

    #[DataProvider('forbiddenDesks')]
    public function test_non_review_office_roles_cannot_request_temporary_conversion(string $role): void
    {
        $this->actingAs($this->ensureOfficeUser($role), 'office')
            ->postJson(route('office.documents.preview.convert'), [
                'file' => UploadedFile::fake()->createWithContent('document.doc', 'Private source bytes'),
            ])->assertForbidden();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public static function forbiddenDesks(): array
    {
        return [['college_reviewer'], ['unsupported']];
    }

    public function test_unauthenticated_requests_cannot_convert_private_source_bytes(): void
    {
        $this->postJson(route('office.documents.preview.convert'), [
            'file' => UploadedFile::fake()->createWithContent('document.doc', 'Private source bytes'),
        ])->assertUnauthorized();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}

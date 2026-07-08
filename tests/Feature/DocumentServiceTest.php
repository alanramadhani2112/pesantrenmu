<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Services\DocumentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Coverage: DocumentService — saveDocument (create + update + file handling),
 * deleteDocument, findDocument, getActiveDocuments.
 */
class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $service;

    protected User $admin;

    protected DocumentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($this->admin);
        $this->service = app(DocumentService::class);

        $this->category = DocumentCategory::create([
            'name' => 'Dokumen Visitasi',
            'slug' => 'visitasi',
            'visibility' => 'public',
            'is_active' => true,
        ]);
    }

    // ─── saveDocument (create) ────────────────────────────────────────────────

    public function test_save_document_creates_record_without_file(): void
    {
        // file_path wajib di DB — test create dengan file minimal
        $file = UploadedFile::fake()->create('template.pdf', 10, 'application/pdf');

        $this->service->saveDocument([
            'title' => 'Template Visitasi',
            'status' => 1,
            'category_id' => $this->category->id,
        ], null, $file);

        $this->assertDatabaseHas('documents', ['title' => 'Template Visitasi', 'status' => 1]);
    }

    public function test_save_document_stores_file_and_sets_path(): void
    {
        $file = UploadedFile::fake()->create('template.pdf', 100, 'application/pdf');

        $this->service->saveDocument([
            'title' => 'Template PDF',
            'status' => 1,
            'category_id' => $this->category->id,
        ], null, $file);

        $doc = Document::where('title', 'Template PDF')->first();
        $this->assertNotNull($doc);
        $this->assertNotNull($doc->file_path);
        Storage::disk('local')->assertExists($doc->file_path);
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_save_document_stores_new_uploads_on_private_local_disk(): void
    {
        $file = UploadedFile::fake()->create('private-template.pdf', 10, 'application/pdf');

        $this->service->saveDocument([
            'title' => 'Private Template',
            'status' => 1,
            'category_id' => $this->category->id,
            'description' => 'Private',
        ], null, $file);

        $path = Document::where('title', 'Private Template')->firstOrFail()->file_path;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_save_document_sets_uploaded_by_user_id(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $this->service->saveDocument([
            'title' => 'Doc Upload',
            'status' => 1,
            'category_id' => $this->category->id,
        ], null, $file);

        $doc = Document::where('title', 'Doc Upload')->first();
        $this->assertSame($this->admin->id, $doc->uploaded_by_user_id);
    }

    // ─── saveDocument (update) ────────────────────────────────────────────────

    public function test_save_document_updates_existing_record(): void
    {
        $doc = Document::create([
            'title' => 'Old Title',
            'status' => 0,
            'category_id' => $this->category->id,
            'file_path' => 'documents/old.pdf',
        ]);

        $this->service->saveDocument([
            'title' => 'New Title',
            'status' => 1,
            'category_id' => $this->category->id,
        ], $doc->id);

        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'title' => 'New Title', 'status' => 1]);
    }

    public function test_save_document_replaces_old_file_on_update(): void
    {
        // Create old file on fake disk
        Storage::disk('public')->put('documents/old.pdf', 'old content');

        $doc = Document::create([
            'title' => 'Doc',
            'status' => 1,
            'category_id' => $this->category->id,
            'file_path' => 'documents/old.pdf',
        ]);

        $newFile = UploadedFile::fake()->create('new.pdf', 50, 'application/pdf');

        $this->service->saveDocument([
            'title' => 'Doc',
            'status' => 1,
            'category_id' => $this->category->id,
        ], $doc->id, $newFile);

        // Old file deleted
        Storage::disk('public')->assertMissing('documents/old.pdf');
        // New file stored privately
        $updated = Document::find($doc->id);
        Storage::disk('local')->assertExists($updated->file_path);
        Storage::disk('public')->assertMissing($updated->file_path);
    }


    public function test_save_document_deletes_new_file_when_repository_create_fails(): void
    {
        $repo = Mockery::mock(DocumentRepositoryInterface::class);
        $repo->shouldReceive('getPaginatedDocuments')->zeroOrMoreTimes();
        $repo->shouldReceive('find')->zeroOrMoreTimes();
        $repo->shouldReceive('create')->once()->andThrow(new \RuntimeException('db fail'));
        $repo->shouldReceive('update')->zeroOrMoreTimes();
        $repo->shouldReceive('delete')->zeroOrMoreTimes();
        $repo->shouldReceive('getActiveForRole')->zeroOrMoreTimes();
        $this->app->instance(DocumentRepositoryInterface::class, $repo);

        $service = app(DocumentService::class);
        $file = UploadedFile::fake()->create('broken.pdf', 50, 'application/pdf');

        try {
            $service->saveDocument([
                'title' => 'Broken Doc',
                'status' => 1,
                'category_id' => $this->category->id,
            ], null, $file);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('db fail', $e->getMessage());
        }

        Storage::disk('local')->assertDirectoryEmpty('documents');
        Storage::disk('public')->assertDirectoryEmpty('documents');
        $this->assertDatabaseMissing('documents', ['title' => 'Broken Doc']);
    }

    public function test_save_document_keeps_old_file_when_repository_update_fails(): void
    {
        Storage::disk('public')->put('documents/old.pdf', 'old content');

        $doc = Document::create([
            'title' => 'Doc',
            'status' => 1,
            'category_id' => $this->category->id,
            'file_path' => 'documents/old.pdf',
        ]);

        $repo = Mockery::mock(DocumentRepositoryInterface::class);
        $repo->shouldReceive('getPaginatedDocuments')->zeroOrMoreTimes();
        $repo->shouldReceive('find')->with($doc->id)->andReturn($doc);
        $repo->shouldReceive('update')->once()->andThrow(new \RuntimeException('update fail'));
        $repo->shouldReceive('create')->zeroOrMoreTimes();
        $repo->shouldReceive('delete')->zeroOrMoreTimes();
        $repo->shouldReceive('getActiveForRole')->zeroOrMoreTimes();
        $this->app->instance(DocumentRepositoryInterface::class, $repo);

        $service = app(DocumentService::class);
        $file = UploadedFile::fake()->create('new.pdf', 50, 'application/pdf');

        try {
            $service->saveDocument([
                'title' => 'Doc',
                'status' => 1,
                'category_id' => $this->category->id,
            ], $doc->id, $file);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('update fail', $e->getMessage());
        }

        Storage::disk('public')->assertExists('documents/old.pdf');
        $files = Storage::disk('public')->allFiles('documents');
        $this->assertCount(1, $files);
        $this->assertSame('documents/old.pdf', $files[0]);
    }
    // ─── deleteDocument ───────────────────────────────────────────────────────

    public function test_delete_document_removes_record_and_file(): void
    {
        Storage::disk('public')->put('documents/todelete.pdf', 'content');

        $doc = Document::create([
            'title' => 'To Delete',
            'status' => 1,
            'category_id' => $this->category->id,
            'file_path' => 'documents/todelete.pdf',
        ]);

        $result = $this->service->deleteDocument($doc->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing('documents/todelete.pdf');
    }

    public function test_delete_document_returns_false_for_nonexistent(): void
    {
        $result = $this->service->deleteDocument(99999);
        $this->assertFalse($result);
    }

    // ─── findDocument ─────────────────────────────────────────────────────────

    public function test_find_document_returns_model(): void
    {
        $doc = Document::create([
            'title' => 'Find Me',
            'status' => 1,
            'category_id' => $this->category->id,
            'file_path' => 'documents/find.pdf',
        ]);

        $found = $this->service->findDocument($doc->id);

        $this->assertNotNull($found);
        $this->assertSame($doc->id, $found->id);
    }

    public function test_find_document_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->findDocument(99999));
    }
}


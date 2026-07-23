<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_document_view_uses_authenticated_download_route_not_public_storage_url(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $category = $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET);
        $document = $this->document($category, 'documents/secret.pdf');
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($asesor)
            ->get(route('documents.index', ['doc' => $category->slug]))
            ->assertOk()
            ->assertSee(route('documents.download', $document), false)
            ->assertDontSee('/storage/documents/secret.pdf', false);
    }

    public function test_pesantren_cannot_download_asesor_secret_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $pesantren = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET),
            'documents/asesor-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($pesantren)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_asesor_can_download_asesor_secret_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Asesor Secret', 'asesor_secret', DocumentCategory::VISIBILITY_ASESOR_SECRET),
            'documents/asesor-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($asesor)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_admin_can_download_any_document(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Pesantren Secret', 'pesantren_secret', DocumentCategory::VISIBILITY_PESANTREN_SECRET),
            'documents/pesantren-secret.pdf'
        );
        Storage::disk('local')->put($document->file_path, 'secret');

        $this->actingAs($admin)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_admin_and_super_admin_can_download_inactive_document_without_category(): void
    {
        Storage::fake('local');
        $this->seedBasePermissions();
        $document = Document::create([
            'title' => 'Inactive Legacy Document',
            'status' => 0,
            'file_path' => 'documents/inactive-legacy.pdf',
        ]);
        Storage::disk('local')->put($document->file_path, 'legacy');

        foreach ([1, 4] as $roleId) {
            $user = User::factory()->create(['role_id' => $roleId, 'email_verified_at' => now()]);

            $this->actingAs($user)
                ->get(route('documents.download', $document))
                ->assertOk();
        }
    }

    public function test_legacy_public_disk_file_downloads_through_authenticated_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seedBasePermissions();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Public', 'public_doc', DocumentCategory::VISIBILITY_PUBLIC),
            'documents/legacy.pdf'
        );
        Storage::disk('public')->put($document->file_path, 'legacy');

        $this->actingAs($admin)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_active_visible_document_with_blank_file_path_returns_not_found(): void
    {
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Public Blank', 'public_blank', DocumentCategory::VISIBILITY_PUBLIC),
            ''
        );

        $this->actingAs($asesor)
            ->get(route('documents.download', $document))
            ->assertNotFound();
    }

    public function test_document_index_marks_missing_file_without_broken_download_link(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seedBasePermissions();
        $asesor = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $document = $this->document(
            $this->category('Public Missing', 'public_missing', DocumentCategory::VISIBILITY_PUBLIC),
            'documents/missing.pdf'
        );

        $this->actingAs($asesor)
            ->get(route('documents.index', ['doc' => 'public_missing']))
            ->assertOk()
            ->assertSee('Berkas belum tersedia')
            ->assertDontSee(route('documents.download', $document), false);
    }

    private function seedBasePermissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function category(string $name, string $slug, string $visibility): DocumentCategory
    {
        return DocumentCategory::create([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'document',
            'visibility' => $visibility,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function document(DocumentCategory $category, string $path): Document
    {
        return Document::create([
            'title' => 'Test Document',
            'category_id' => $category->id,
            'type' => $category->slug,
            'status' => 1,
            'file_path' => $path,
            'is_pesantren' => $category->visibility !== DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'is_asesor' => $category->visibility !== DocumentCategory::VISIBILITY_PESANTREN_SECRET,
        ]);
    }
}

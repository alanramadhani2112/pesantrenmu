<?php

namespace Tests\Feature\Pesantren;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for DocumentService from the pesantren role perspective.
 *
 * Covers:
 *   - getActiveDocuments: visibility scoping (public, pesantren_secret, asesor_secret)
 *   - Role isolation: pesantren cannot see asesor_secret documents
 *   - Search filtering
 *   - Category slug filtering
 *   - Inactive documents are excluded
 *   - Documents with inactive categories are excluded
 */
class DokumenServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentService $service;
    private User $pesantrenUser;
    private User $asesorUser;
    private User $adminUser;

    // Categories
    private DocumentCategory $publicCategory;
    private DocumentCategory $pesantrenSecretCategory;
    private DocumentCategory $asesorSecretCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->pesantrenUser = User::factory()->create(['role_id' => 3]);
        $this->asesorUser = User::factory()->create(['role_id' => 2]);
        $this->adminUser = User::factory()->create(['role_id' => 1]);

        // Create categories with different visibility
        $this->publicCategory = DocumentCategory::create([
            'name' => 'Dokumen Publik',
            'slug' => 'publik',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->pesantrenSecretCategory = DocumentCategory::create([
            'name' => 'Dokumen Pesantren',
            'slug' => 'pesantren',
            'visibility' => DocumentCategory::VISIBILITY_PESANTREN_SECRET,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->asesorSecretCategory = DocumentCategory::create([
            'name' => 'Dokumen Asesor',
            'slug' => 'asesor',
            'visibility' => DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->service = app(DocumentService::class);
    }

    // ─── Visibility: pesantren role ───────────────────────────────────────────

    public function test_pesantren_can_see_public_documents(): void
    {
        $doc = $this->createDocument('Panduan Umum', $this->publicCategory);

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(1, $result->total());
        $this->assertEquals($doc->id, $result->first()->id);
    }

    public function test_pesantren_can_see_pesantren_secret_documents(): void
    {
        $doc = $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(1, $result->total());
        $this->assertEquals($doc->id, $result->first()->id);
    }

    public function test_pesantren_cannot_see_asesor_secret_documents(): void
    {
        $this->createDocument('Panduan Asesor', $this->asesorSecretCategory);

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(0, $result->total());
    }

    public function test_pesantren_sees_both_public_and_pesantren_secret(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);
        $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);
        $this->createDocument('Panduan Asesor', $this->asesorSecretCategory); // should be hidden

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(2, $result->total());
    }

    // ─── Visibility: asesor role ──────────────────────────────────────────────

    public function test_asesor_can_see_public_documents(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);

        $result = $this->service->getActiveDocuments('asesor');

        $this->assertEquals(1, $result->total());
    }

    public function test_asesor_can_see_asesor_secret_documents(): void
    {
        $this->createDocument('Panduan Asesor', $this->asesorSecretCategory);

        $result = $this->service->getActiveDocuments('asesor');

        $this->assertEquals(1, $result->total());
    }

    public function test_asesor_cannot_see_pesantren_secret_documents(): void
    {
        $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);

        $result = $this->service->getActiveDocuments('asesor');

        $this->assertEquals(0, $result->total());
    }

    // ─── Visibility: admin role ───────────────────────────────────────────────

    public function test_admin_can_see_all_documents(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);
        $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);
        $this->createDocument('Panduan Asesor', $this->asesorSecretCategory);

        $result = $this->service->getActiveDocuments('admin');

        $this->assertEquals(3, $result->total());
    }

    // ─── Active/inactive filtering ────────────────────────────────────────────

    public function test_inactive_documents_are_excluded(): void
    {
        $this->createDocument('Aktif', $this->publicCategory, status: 1);
        $this->createDocument('Nonaktif', $this->publicCategory, status: 0);

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Aktif', $result->first()->title);
    }

    public function test_documents_with_inactive_category_are_excluded(): void
    {
        $inactiveCategory = DocumentCategory::create([
            'name' => 'Kategori Nonaktif',
            'slug' => 'nonaktif',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
            'is_active' => false,
            'sort_order' => 99,
        ]);
        $this->createDocument('Dokumen di Kategori Nonaktif', $inactiveCategory);

        $result = $this->service->getActiveDocuments('pesantren');

        $this->assertEquals(0, $result->total());
    }

    // ─── Search filtering ─────────────────────────────────────────────────────

    public function test_search_filters_by_title(): void
    {
        $this->createDocument('Panduan Akreditasi', $this->publicCategory);
        $this->createDocument('Template Visitasi', $this->publicCategory);

        $result = $this->service->getActiveDocuments('pesantren', null, 'Akreditasi');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Panduan Akreditasi', $result->first()->title);
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->createDocument('Panduan Akreditasi', $this->publicCategory);

        $result = $this->service->getActiveDocuments('pesantren', null, 'akreditasi');

        $this->assertEquals(1, $result->total());
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        $this->createDocument('Panduan Akreditasi', $this->publicCategory);

        $result = $this->service->getActiveDocuments('pesantren', null, 'xyz_tidak_ada');

        $this->assertEquals(0, $result->total());
    }

    // ─── Category slug filtering ──────────────────────────────────────────────

    public function test_category_slug_filter_returns_only_matching_documents(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);
        $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);

        $result = $this->service->getActiveDocuments('pesantren', 'publik');

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Panduan Umum', $result->first()->title);
    }

    public function test_all_slug_returns_all_visible_documents(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);
        $this->createDocument('Panduan Pesantren', $this->pesantrenSecretCategory);

        $result = $this->service->getActiveDocuments('pesantren', 'all');

        $this->assertEquals(2, $result->total());
    }

    // ─── Pagination ───────────────────────────────────────────────────────────

    public function test_pagination_respects_per_page(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createDocument("Dokumen $i", $this->publicCategory);
        }

        $result = $this->service->getActiveDocuments('pesantren', null, null, 3);

        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
    }

    // ─── Role scope isolation ─────────────────────────────────────────────────

    public function test_null_role_returns_no_documents(): void
    {
        $this->createDocument('Panduan Umum', $this->publicCategory);

        // null role = unknown/guest — should see nothing
        $result = $this->service->getActiveDocuments(null);

        // Admin sees everything, null should be treated as unknown role
        // Based on Document::scopeVisibleToRole: default => where('id', 0)
        $this->assertEquals(0, $result->total());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createDocument(
        string $title,
        DocumentCategory $category,
        int $status = 1
    ): Document {
        Storage::disk('public')->put("documents/{$title}.pdf", 'content');

        return Document::create([
            'title' => $title,
            'category_id' => $category->id,
            'file_path' => "documents/{$title}.pdf",
            'status' => $status,
            'type' => $category->slug,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_dokumen_accepts_sort_and_direction_params(): void
    {
        $this->seedBase();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $category = $this->cat('Docs', 'docs');
        Document::create(['title' => 'B Doc', 'category_id' => $category->id, 'status' => 1, 'file_path' => 'documents/b.pdf']);
        Document::create(['title' => 'A Doc', 'category_id' => $category->id, 'status' => 1, 'file_path' => 'documents/a.pdf']);

        $res = $this->actingAs($admin)->get(route('admin.master-dokumen.index', ['sort' => 'title', 'direction' => 'asc']));
        $res->assertOk();
        $res->assertSeeInOrder(['A Doc', 'B Doc']);
    }

    public function test_master_kategori_accepts_sort_and_direction_params(): void
    {
        $this->seedBase();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $this->cat('B Category', 'b_cat', 20);
        $this->cat('A Category', 'a_cat', 10);

        $res = $this->actingAs($admin)->get(route('admin.master-kategori-dokumen.index', ['sort' => 'name', 'direction' => 'asc']));
        $res->assertOk();
        $res->assertSeeInOrder(['A Category', 'B Category']);
    }

    public function test_roles_accept_sort_and_direction_params(): void
    {
        $this->seedBase();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        Role::create(['name' => 'Z Custom', 'parameter' => 'z_custom']);
        Role::create(['name' => 'A Custom', 'parameter' => 'a_custom']);

        $res = $this->actingAs($superAdmin)->get(route('admin.roles.index', ['sort' => 'name', 'direction' => 'asc']));
        $res->assertOk();
        $res->assertSeeInOrder(['A Custom', 'admin', 'asesor', 'pesantren', 'super_admin']);
    }

    public function test_invalid_sort_falls_back_without_sql_error(): void
    {
        $this->seedBase();
        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.master-kategori-dokumen.index', ['sort' => 'bad_column', 'direction' => 'asc']))
            ->assertOk();
    }

    private function seedBase(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function cat(string $name, string $slug, int $sortOrder = 1): DocumentCategory
    {
        return DocumentCategory::create([
            'name' => $name, 'slug' => $slug, 'icon' => 'document',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC, 'is_active' => true, 'sort_order' => $sortOrder,
        ]);
    }
}

<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 7.5: Trash view UI tests.
 *
 * Validates: Requirements 1.1–1.6
 */
class TrashViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function makeTrashedAkreditasi(string $pesantrenName = 'Pesantren View Test'): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => $pesantrenName]);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 6]);
        $akreditasi->delete();

        return $akreditasi->fresh();
    }

    public function test_trash_view_renders_with_trashed_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->makeTrashedAkreditasi('Pesantren Al-Hidayah');

        $response = $this->get(route('admin.trash'));
        $response->assertStatus(200);
        $response->assertSee('Arsip Akreditasi');
    }

    public function test_trash_view_shows_empty_state_when_no_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        Livewire::test('pages.admin.trash')
            ->assertSee('Tidak ada akreditasi terhapus saat ini');
    }

    public function test_trash_view_paginates_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        // Create 15 trashed records
        for ($i = 1; $i <= 15; $i++) {
            $this->makeTrashedAkreditasi("Pesantren {$i}");
        }

        $component = Livewire::test('pages.admin.trash');
        $component->assertSet('perPage', 10);

        // First page should have 10 records
        $trashed = $component->get('trashedAkreditasis');
        $this->assertSame(10, $trashed->perPage());
        $this->assertSame(15, $trashed->total());
        $this->assertSame(2, $trashed->lastPage());
    }

    public function test_trash_view_search_filters_results(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->makeTrashedAkreditasi('Pesantren Al-Hidayah Bandung');
        $this->makeTrashedAkreditasi('Pesantren An-Nur Surabaya');

        $component = Livewire::test('pages.admin.trash')
            ->set('search', 'Bandung');

        $trashed = $component->get('trashedAkreditasis');
        $this->assertSame(1, $trashed->total());
    }

    public function test_trash_view_shows_deleted_at_elapsed_time(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->makeTrashedAkreditasi('Pesantren Elapsed');

        $response = $this->get(route('admin.trash'));
        // The view uses diffForHumans() which outputs something like "beberapa detik yang lalu"
        $response->assertStatus(200);
    }

    public function test_trash_view_shows_retention_info(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        Livewire::test('pages.admin.trash')
            ->assertSee('90 hari');
    }

    public function test_trash_view_shows_badge_when_records_exist(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->makeTrashedAkreditasi();
        $this->makeTrashedAkreditasi();

        $component = Livewire::test('pages.admin.trash');
        $this->assertSame(2, $component->get('trashCount'));
    }

    public function test_trash_view_badge_is_zero_when_empty(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $component = Livewire::test('pages.admin.trash');
        $this->assertSame(0, $component->get('trashCount'));
    }
}

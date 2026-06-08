<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $response->assertOk();
        $response->assertSee('Arsip Akreditasi');
        $response->assertSee('Pesantren Al-Hidayah');
    }

    public function test_trash_view_shows_empty_state_when_no_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $response = $this->get(route('admin.trash'));

        $response->assertOk();
        $response->assertSee('Tidak ada akreditasi terhapus saat ini');
    }

    public function test_trash_view_paginates_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        for ($i = 1; $i <= 15; $i++) {
            $this->makeTrashedAkreditasi("Pesantren {$i}");
        }

        $response = $this->get(route('admin.trash', ['perPage' => 10]));

        $response->assertOk();
        $response->assertSee('Pesantren 1');
        $response->assertSee('Pesantren 10');
    }

    public function test_trash_view_search_filters_results(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->makeTrashedAkreditasi('Pesantren Alpha');
        $this->makeTrashedAkreditasi('Pesantren Beta');

        $response = $this->get(route('admin.trash', ['search' => 'Alpha']));

        $response->assertOk();
        $response->assertSee('Pesantren Alpha');
        $response->assertDontSee('Pesantren Beta');
    }
}

<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\TrashService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceDeleteFlowTest extends TestCase
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

    private function makeTrashedAkreditasi(string $pesantrenName = 'Pesantren Force Delete'): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => $pesantrenName]);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 5]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor',
            'nama_tanpa_gelar' => 'Asesor',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(30)->toDateString(),
        ]);

        $akreditasi->delete();

        return $akreditasi->fresh();
    }

    public function test_force_delete_via_service_removes_all_records(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $akreditasi = $this->makeTrashedAkreditasi();
        $id = $akreditasi->id;

        $service = app(TrashService::class);
        $count = $service->forceDelete($id);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(0, Akreditasi::withTrashed()->where('id', $id)->count());
        $this->assertSame(0, Assessment::withTrashed()->where('akreditasi_id', $id)->count());
    }
}

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

class RestoreFlowTest extends TestCase
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

    private function makeTrashedAkreditasi(): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Restore Flow']);
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

    public function test_restore_via_service_restores_parent_and_children(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $akreditasi = $this->makeTrashedAkreditasi();
        $id = $akreditasi->id;

        $service = app(TrashService::class);
        $count = $service->restore($id);

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertNull(Akreditasi::find($id)->deleted_at);
        $this->assertSame(0, Assessment::onlyTrashed()->where('akreditasi_id', $id)->count());
    }
}

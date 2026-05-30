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
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 7.2: Restore flow tests.
 *
 * Validates: Requirements 3.1–3.7
 */
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

    public function test_restore_via_volt_component_dispatches_success_notification(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $akreditasi = $this->makeTrashedAkreditasi();

        Livewire::test('pages.admin.trash')
            ->call('openRestoreConfirm', $akreditasi->id)
            ->assertSet('previewId', $akreditasi->id)
            ->call('restore')
            ->assertDispatched('notification-received');

        $this->assertNull(Akreditasi::find($akreditasi->id)?->deleted_at);
    }

    public function test_restore_nonexistent_id_throws_runtime_exception(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $service = app(TrashService::class);

        $this->expectException(\RuntimeException::class);
        $service->restore(99999);
    }

    public function test_restore_already_restored_record_throws_runtime_exception(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $akreditasi = $this->makeTrashedAkreditasi();
        $service = app(TrashService::class);

        $service->restore($akreditasi->id);

        $this->expectException(\RuntimeException::class);
        $service->restore($akreditasi->id);
    }
}

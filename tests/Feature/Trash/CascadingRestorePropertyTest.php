<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\TrashService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature: soft-delete-restore-flow
 * Property 3: Cascading restore recovers parent and all children.
 *
 * For any soft-deleted akreditasi with any number of soft-deleted child records,
 * after a successful restore operation, the akreditasi and ALL associated child
 * records SHALL have deleted_at = null.
 */
class CascadingRestorePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected TrashService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
        $this->service = app(TrashService::class);
    }

    private function makeAndDeleteAkreditasi(int $edpmCount, int $catatanCount): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Restore Test']);

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

        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen']);
        for ($i = 0; $i < $edpmCount; $i++) {
            $butir = MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => '1.' . ($i + 1),
                'butir_pernyataan' => 'Butir ' . ($i + 1),
            ]);
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => '3',
            ]);
        }

        for ($i = 0; $i < $catatanCount; $i++) {
            $k = MasterEdpmKomponen::create(['nama' => 'Komponen Catatan ' . $i]);
            AkreditasiEdpmCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $user->id,
                'komponen_id' => $k->id,
                'catatan' => 'Catatan ' . $i,
            ]);
        }

        $akreditasi->delete();
        return $akreditasi->fresh();
    }

    public static function childVariationProvider(): array
    {
        $faker = \Faker\Factory::create();
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            $cases[] = [
                $faker->numberBetween(0, 5),
                $faker->numberBetween(0, 5),
            ];
        }
        return $cases;
    }

    /**     */
#[DataProvider('childVariationProvider')]
public function test_property_3_cascading_restore_recovers_all_children(
        int $edpmCount,
        int $catatanCount
    ): void {
        $akreditasi = $this->makeAndDeleteAkreditasi($edpmCount, $catatanCount);
        $id = $akreditasi->id;

        // Confirm all are soft-deleted
        $this->assertNotNull(Akreditasi::onlyTrashed()->find($id)?->deleted_at);

        $this->service->restore($id);

        // Parent restored
        $restored = Akreditasi::find($id);
        $this->assertNotNull($restored, 'Akreditasi should exist after restore');
        $this->assertNull($restored->deleted_at, 'Parent deleted_at should be null');

        // All children restored
        $this->assertSame(
            0,
            Assessment::onlyTrashed()->where('akreditasi_id', $id)->count(),
            'No assessments should remain soft-deleted'
        );
        $this->assertSame(
            0,
            AkreditasiEdpm::onlyTrashed()->where('akreditasi_id', $id)->count(),
            'No EDPM records should remain soft-deleted'
        );
        $this->assertSame(
            0,
            AkreditasiEdpmCatatan::onlyTrashed()->where('akreditasi_id', $id)->count(),
            'No EDPM catatan should remain soft-deleted'
        );
    }
}

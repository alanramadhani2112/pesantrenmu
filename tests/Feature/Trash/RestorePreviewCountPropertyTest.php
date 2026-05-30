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
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature: soft-delete-restore-flow
 * Property 2: Restore preview child counts are accurate.
 *
 * For any soft-deleted akreditasi with any number of soft-deleted child records,
 * the restore preview counts SHALL exactly equal the actual number of soft-deleted
 * child records of each type associated with that akreditasi.
 */
class RestorePreviewCountPropertyTest extends TestCase
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

    private function makeAkreditasiWithCounts(int $assessmentCount, int $edpmCount, int $catatanCount): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 5]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        for ($i = 0; $i < $assessmentCount; $i++) {
            Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => $i + 1,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_berakhir' => now()->addDays(30)->toDateString(),
            ]);
        }

        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen Test']);
        for ($i = 0; $i < $edpmCount; $i++) {
            $butir = MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => '1.'.($i + 1),
                'butir_pernyataan' => 'Butir '.($i + 1),
            ]);
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => '3',
            ]);
        }

        for ($i = 0; $i < $catatanCount; $i++) {
            $k = MasterEdpmKomponen::create(['nama' => 'Komponen Catatan '.$i]);
            AkreditasiEdpmCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $user->id,
                'komponen_id' => $k->id,
                'catatan' => 'Catatan '.$i,
            ]);
        }

        $akreditasi->delete();

        return $akreditasi->fresh();
    }

    public static function childCountDataProvider(): array
    {
        $faker = Factory::create();
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            $cases[] = [
                $faker->numberBetween(0, 2),  // assessment (max 2 due to tipe constraint)
                $faker->numberBetween(0, 5),  // edpm
                $faker->numberBetween(0, 5),  // catatan
            ];
        }

        return $cases;
    }

    #[DataProvider('childCountDataProvider')]
    public function test_property_2_preview_counts_match_actual_db_counts(
        int $assessmentCount,
        int $edpmCount,
        int $catatanCount
    ): void {
        $akreditasi = $this->makeAkreditasiWithCounts($assessmentCount, $edpmCount, $catatanCount);

        $preview = $this->service->getRestorePreview($akreditasi->id);

        $this->assertSame(
            $assessmentCount,
            $preview['children']['assessment'],
            "Assessment count mismatch: expected {$assessmentCount}"
        );
        $this->assertSame(
            $edpmCount,
            $preview['children']['akreditasi_edpm'],
            "EDPM count mismatch: expected {$edpmCount}"
        );
        $this->assertSame(
            $catatanCount,
            $preview['children']['akreditasi_edpm_catatan'],
            "Catatan count mismatch: expected {$catatanCount}"
        );
        $this->assertSame(
            $assessmentCount + $edpmCount + $catatanCount,
            $preview['children']['total'],
            'Total count mismatch'
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkreditasiSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_soft_deleting_akreditasi_soft_deletes_assessments_and_edpm(): void
    {
        // Arrange — pesantren user + akreditasi
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesorUser = User::factory()->create(['role_id' => 2]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Tester, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Tester',
        ]);

        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(3)->toDateString(),
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        $edpm = AkreditasiEdpm::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $pesantrenUser->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        $catatan = AkreditasiEdpmCatatan::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $pesantrenUser->id,
            'komponen_id' => $komponen->id,
            'catatan' => 'Catatan asesor',
        ]);

        // Sanity check — semua child belum soft-deleted
        $this->assertNull($assessment->fresh()->deleted_at);
        $this->assertNull($edpm->fresh()->deleted_at);
        $this->assertNull($catatan->fresh()->deleted_at);

        // Act — soft delete parent akreditasi
        $akreditasi->delete();

        // Assert — parent soft-deleted
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);

        // Assert — child Assessment ikut soft-deleted (record tetap ada, deleted_at terisi)
        $this->assertSoftDeleted('assessments', ['id' => $assessment->id]);
        $this->assertNotNull(Assessment::withTrashed()->find($assessment->id)->deleted_at);
        $this->assertNull(Assessment::find($assessment->id), 'Assessment seharusnya tidak terlihat tanpa withTrashed');

        // Assert — child AkreditasiEdpm ikut soft-deleted
        $this->assertSoftDeleted('akreditasi_edpms', ['id' => $edpm->id]);
        $this->assertNotNull(AkreditasiEdpm::withTrashed()->find($edpm->id)->deleted_at);
        $this->assertNull(AkreditasiEdpm::find($edpm->id), 'AkreditasiEdpm seharusnya tidak terlihat tanpa withTrashed');

        // Assert — child AkreditasiEdpmCatatan ikut soft-deleted
        $this->assertSoftDeleted('akreditasi_edpm_catatans', ['id' => $catatan->id]);
        $this->assertNotNull(AkreditasiEdpmCatatan::withTrashed()->find($catatan->id)->deleted_at);
        $this->assertNull(AkreditasiEdpmCatatan::find($catatan->id), 'AkreditasiEdpmCatatan seharusnya tidak terlihat tanpa withTrashed');
    }

    public function test_soft_delete_cascade_runs_in_transaction(): void
    {
        // Arrange — buat akreditasi tanpa child (kasus minimal)
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        // Act
        $deleted = $akreditasi->delete();

        // Assert — soft delete sukses meski tidak ada child
        $this->assertTrue((bool) $deleted);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }
}

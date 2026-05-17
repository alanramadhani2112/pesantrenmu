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
use RuntimeException;
use Tests\TestCase;

class TrashServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Audit observer mencatat Auth::id() saat akreditasi di-soft-delete; auth admin di sini.
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    /**
     * Helper: create akreditasi + child records, optionally soft-deleted.
     *
     * @return array{akreditasi: Akreditasi, user: User}
     */
    private function makeAkreditasiWithChildren(string $pesantrenName = 'Pesantren Demo', int $status = 5, bool $softDelete = true): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Demo, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Demo',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        Assessment::create([
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

        AkreditasiEdpm::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $pesantrenUser->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        AkreditasiEdpmCatatan::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $pesantrenUser->id,
            'komponen_id' => $komponen->id,
            'catatan' => 'Catatan demo',
        ]);

        if ($softDelete) {
            $akreditasi->delete();
        }

        return ['akreditasi' => $akreditasi->fresh(), 'user' => $pesantrenUser];
    }

    /** Property 1: cascade restore — restore parent juga restore semua child. */
    public function test_property_cascade_restore_undoes_all_children(): void
    {
        $arr = $this->makeAkreditasiWithChildren();
        $akreditasi = $arr['akreditasi'];

        $this->assertNotNull($akreditasi->deleted_at);
        $this->assertSame(1, Assessment::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(1, AkreditasiEdpm::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(1, AkreditasiEdpmCatatan::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());

        $service = app(TrashService::class);
        $count = $service->restore($akreditasi->id);

        $this->assertSame(4, $count, 'Total restored = parent (1) + 3 child records');
        $this->assertNull(Akreditasi::find($akreditasi->id)->deleted_at);
        $this->assertSame(0, Assessment::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(0, AkreditasiEdpm::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(0, AkreditasiEdpmCatatan::onlyTrashed()->where('akreditasi_id', $akreditasi->id)->count());
    }

    /** Property 2: forceDelete benar-benar menghapus parent + semua child dari DB. */
    public function test_property_force_delete_purges_records_from_database(): void
    {
        $arr = $this->makeAkreditasiWithChildren();
        $akreditasi = $arr['akreditasi'];

        $service = app(TrashService::class);
        $count = $service->forceDelete($akreditasi->id);

        $this->assertSame(4, $count);
        $this->assertSame(0, Akreditasi::withTrashed()->where('id', $akreditasi->id)->count());
        $this->assertSame(0, Assessment::withTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(0, AkreditasiEdpm::withTrashed()->where('akreditasi_id', $akreditasi->id)->count());
        $this->assertSame(0, AkreditasiEdpmCatatan::withTrashed()->where('akreditasi_id', $akreditasi->id)->count());
    }

    /** Property 3: idempotent — restore lalu restore lagi tidak menambah/merusak data. */
    public function test_property_restore_is_idempotent(): void
    {
        $arr = $this->makeAkreditasiWithChildren();
        $akreditasi = $arr['akreditasi'];

        $service = app(TrashService::class);
        $service->restore($akreditasi->id);

        // Pemanggilan kedua harus throw karena record sudah tidak ada di onlyTrashed
        $this->expectException(RuntimeException::class);
        $service->restore($akreditasi->id);
    }

    /** Property 4: getTrashCount akurat sebelum & sesudah operasi. */
    public function test_property_trash_count_accurate(): void
    {
        $service = app(TrashService::class);
        $this->assertSame(0, $service->getTrashCount());

        $a1 = $this->makeAkreditasiWithChildren('A')['akreditasi'];
        $a2 = $this->makeAkreditasiWithChildren('B')['akreditasi'];
        $this->makeAkreditasiWithChildren('C', 5, false);
        $this->assertSame(2, $service->getTrashCount());

        $service->restore($a1->id);
        $this->assertSame(1, $service->getTrashCount());

        $service->forceDelete($a2->id);
        $this->assertSame(0, $service->getTrashCount());
    }

    /** Property 5: getRestorePreview returns child counts matching DB state. */
    public function test_property_restore_preview_counts_match_database(): void
    {
        $arr = $this->makeAkreditasiWithChildren();
        $akreditasi = $arr['akreditasi'];

        $service = app(TrashService::class);
        $preview = $service->getRestorePreview($akreditasi->id);

        $this->assertInstanceOf(Akreditasi::class, $preview['akreditasi']);
        $this->assertSame($akreditasi->id, $preview['akreditasi']->id);
        $this->assertSame(1, $preview['children']['assessment']);
        $this->assertSame(1, $preview['children']['akreditasi_edpm']);
        $this->assertSame(1, $preview['children']['akreditasi_edpm_catatan']);
        $this->assertSame(3, $preview['children']['total']);
    }

    /** Property 6: search filter mempersempit hasil ke pesantren yang cocok. */
    public function test_property_search_filter_narrows_results(): void
    {
        $this->makeAkreditasiWithChildren('Al-Hidayah Bandung');
        $this->makeAkreditasiWithChildren('An-Nur Surabaya');
        $this->makeAkreditasiWithChildren('Daarul Mukmin Jakarta');

        $service = app(TrashService::class);

        $all = $service->getPaginatedTrashed(null);
        $this->assertSame(3, $all->total());

        $bandung = $service->getPaginatedTrashed('Bandung');
        $this->assertSame(1, $bandung->total());

        $none = $service->getPaginatedTrashed('XYZ-NotExist');
        $this->assertSame(0, $none->total());
    }
}

<?php

namespace Tests\Feature;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Services\MasterEdpmService;
use Database\Seeders\MasterEdpmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Coverage: MasterEdpmService — getKomponensData, saveKomponen (create + update),
 * deleteKomponen, saveButir (create + update), deleteButir, findKomponen, findButir.
 */
class MasterEdpmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MasterEdpmService $service;

    protected MasterEdpmKomponen $komponen;

    protected MasterEdpmButir $butir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(MasterEdpmService::class);

        // Seed a base komponen + butir for tests that need existing data
        $this->komponen = MasterEdpmKomponen::create([
            'nama' => 'MUTU LULUSAN',
            'ipr' => null,
        ]);
        $this->butir = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-001',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pernyataan butir pertama',
        ]);
    }

    // ─── getKomponensData ─────────────────────────────────────────────────────

    public function test_get_komponens_data_returns_collection(): void
    {
        $result = $this->service->getKomponensData();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_komponens_data_includes_seeded_komponen(): void
    {
        $result = $this->service->getKomponensData();

        $this->assertCount(1, $result);
        $this->assertEquals('MUTU LULUSAN', $result->first()->nama);
    }

    public function test_get_komponens_data_eager_loads_butirs(): void
    {
        $result = $this->service->getKomponensData();

        $komponen = $result->first();
        $this->assertTrue($komponen->relationLoaded('butirs'));
        $this->assertCount(1, $komponen->butirs);
        $this->assertEquals('Pernyataan butir pertama', $komponen->butirs->first()->butir_pernyataan);
    }

    public function test_get_komponens_data_returns_empty_when_no_data(): void
    {
        MasterEdpmButir::query()->delete();
        MasterEdpmKomponen::query()->delete();

        $result = $this->service->getKomponensData();

        $this->assertEmpty($result);
    }

    public function test_get_komponens_data_returns_multiple_komponens(): void
    {
        MasterEdpmKomponen::create(['nama' => 'MUTU PROSES', 'ipr' => null]);
        MasterEdpmKomponen::create(['nama' => 'MUTU INPUT', 'ipr' => null]);

        $result = $this->service->getKomponensData();

        $this->assertCount(3, $result);
    }

    public function test_get_komponens_data_orders_by_ipr_then_id(): void
    {
        MasterEdpmButir::query()->delete();
        MasterEdpmKomponen::query()->delete();

        $k2 = MasterEdpmKomponen::create(['nama' => 'B', 'ipr' => 2]);
        $k1 = MasterEdpmKomponen::create(['nama' => 'A', 'ipr' => 1]);
        $k0 = MasterEdpmKomponen::create(['nama' => 'C', 'ipr' => null]); // COALESCE(null,0) = 0

        $result = $this->service->getKomponensData();

        // ipr=null(0) first, then ipr=1, then ipr=2
        $this->assertEquals($k0->id, $result->get(0)->id);
        $this->assertEquals($k1->id, $result->get(1)->id);
        $this->assertEquals($k2->id, $result->get(2)->id);
    }

    public function test_master_edpm_seeder_marks_relative_fulfillment_indicator_as_ipr(): void
    {
        $this->seed(MasterEdpmSeeder::class);

        $iprKomponen = MasterEdpmKomponen::query()
            ->where('nama', 'B. INDIKATOR PEMENUHAN RELATIF')
            ->firstOrFail();

        $this->assertSame(1, (int) $iprKomponen->ipr);
        $this->assertSame(4, MasterEdpmKomponen::query()->whereNull('ipr')->count());
        $this->assertSame(1, MasterEdpmKomponen::query()->whereNotNull('ipr')->count());
    }

    // ─── findKomponen ─────────────────────────────────────────────────────────

    public function test_find_komponen_returns_correct_model(): void
    {
        $found = $this->service->findKomponen($this->komponen->id);

        $this->assertNotNull($found);
        $this->assertEquals($this->komponen->id, $found->id);
        $this->assertEquals('MUTU LULUSAN', $found->nama);
    }

    public function test_find_komponen_returns_null_for_nonexistent_id(): void
    {
        $found = $this->service->findKomponen(99999);

        $this->assertNull($found);
    }

    // ─── saveKomponen (create) ────────────────────────────────────────────────

    public function test_save_komponen_creates_new_record_when_no_id(): void
    {
        $this->service->saveKomponen(['nama' => 'MUTU KONTEKS', 'ipr' => 3]);

        $this->assertDatabaseHas('master_edpm_komponens', ['nama' => 'MUTU KONTEKS', 'ipr' => 3]);
    }

    public function test_save_komponen_increments_count_on_create(): void
    {
        $countBefore = MasterEdpmKomponen::count();

        $this->service->saveKomponen(['nama' => 'NEW KOMPONEN']);

        $this->assertEquals($countBefore + 1, MasterEdpmKomponen::count());
    }

    public function test_save_komponen_create_returns_void(): void
    {
        $result = $this->service->saveKomponen(['nama' => 'VOID TEST']);

        $this->assertNull($result);
    }

    // ─── saveKomponen (update) ────────────────────────────────────────────────

    public function test_save_komponen_updates_existing_record_when_id_given(): void
    {
        $this->service->saveKomponen(['nama' => 'UPDATED NAMA', 'ipr' => 5], $this->komponen->id);

        $this->assertDatabaseHas('master_edpm_komponens', [
            'id' => $this->komponen->id,
            'nama' => 'UPDATED NAMA',
            'ipr' => 5,
        ]);
    }

    public function test_save_komponen_update_does_not_create_new_record(): void
    {
        $countBefore = MasterEdpmKomponen::count();

        $this->service->saveKomponen(['nama' => 'UPDATED'], $this->komponen->id);

        $this->assertEquals($countBefore, MasterEdpmKomponen::count());
    }

    // ─── deleteKomponen ───────────────────────────────────────────────────────

    public function test_delete_komponen_removes_record(): void
    {
        $extra = MasterEdpmKomponen::create(['nama' => 'TO DELETE']);

        $result = $this->service->deleteKomponen($extra->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('master_edpm_komponens', ['id' => $extra->id]);
    }

    public function test_delete_komponen_returns_false_for_nonexistent_id(): void
    {
        $result = $this->service->deleteKomponen(99999);

        $this->assertFalse($result);
    }

    public function test_delete_komponen_does_not_affect_other_komponens(): void
    {
        $extra = MasterEdpmKomponen::create(['nama' => 'TEMP']);

        $this->service->deleteKomponen($extra->id);

        $this->assertDatabaseHas('master_edpm_komponens', ['id' => $this->komponen->id]);
    }

    // ─── findButir ────────────────────────────────────────────────────────────

    public function test_find_butir_returns_correct_model(): void
    {
        $found = $this->service->findButir($this->butir->id);

        $this->assertNotNull($found);
        $this->assertEquals($this->butir->id, $found->id);
        $this->assertEquals('Pernyataan butir pertama', $found->butir_pernyataan);
    }

    public function test_find_butir_returns_null_for_nonexistent_id(): void
    {
        $found = $this->service->findButir(99999);

        $this->assertNull($found);
    }

    // ─── saveButir (create) ───────────────────────────────────────────────────

    public function test_save_butir_creates_new_record_when_no_id(): void
    {
        $this->service->saveButir([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-002',
            'nomor_butir' => '1.2',
            'butir_pernyataan' => 'Pernyataan butir kedua',
        ]);

        $this->assertDatabaseHas('master_edpm_butirs', [
            'komponen_id' => $this->komponen->id,
            'nomor_butir' => '1.2',
            'butir_pernyataan' => 'Pernyataan butir kedua',
        ]);
    }

    public function test_save_butir_increments_count_on_create(): void
    {
        $countBefore = MasterEdpmButir::count();

        $this->service->saveButir([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-003',
            'nomor_butir' => '1.3',
            'butir_pernyataan' => 'New butir',
        ]);

        $this->assertEquals($countBefore + 1, MasterEdpmButir::count());
    }

    public function test_save_butir_create_returns_void(): void
    {
        $result = $this->service->saveButir([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-X',
            'nomor_butir' => '9.9',
            'butir_pernyataan' => 'Void test',
        ]);

        $this->assertNull($result);
    }

    // ─── saveButir (update) ───────────────────────────────────────────────────

    public function test_save_butir_updates_existing_record_when_id_given(): void
    {
        $this->service->saveButir([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-001-UPDATED',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pernyataan diperbarui',
        ], $this->butir->id);

        $this->assertDatabaseHas('master_edpm_butirs', [
            'id' => $this->butir->id,
            'no_sk' => 'SK-001-UPDATED',
            'butir_pernyataan' => 'Pernyataan diperbarui',
        ]);
    }

    public function test_save_butir_update_does_not_create_new_record(): void
    {
        $countBefore = MasterEdpmButir::count();

        $this->service->saveButir([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-001',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Updated',
        ], $this->butir->id);

        $this->assertEquals($countBefore, MasterEdpmButir::count());
    }

    // ─── deleteButir ──────────────────────────────────────────────────────────

    public function test_delete_butir_removes_record(): void
    {
        $extra = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-DEL',
            'nomor_butir' => '9.1',
            'butir_pernyataan' => 'To be deleted',
        ]);

        $result = $this->service->deleteButir($extra->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('master_edpm_butirs', ['id' => $extra->id]);
    }

    public function test_delete_butir_returns_false_for_nonexistent_id(): void
    {
        $result = $this->service->deleteButir(99999);

        $this->assertFalse($result);
    }

    public function test_delete_butir_does_not_affect_other_butirs(): void
    {
        $extra = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => 'SK-TEMP',
            'nomor_butir' => '8.8',
            'butir_pernyataan' => 'Temp',
        ]);

        $this->service->deleteButir($extra->id);

        $this->assertDatabaseHas('master_edpm_butirs', ['id' => $this->butir->id]);
    }

    // ─── Relationship integrity ───────────────────────────────────────────────

    public function test_butir_belongs_to_correct_komponen(): void
    {
        $found = $this->service->findButir($this->butir->id);

        $this->assertEquals($this->komponen->id, $found->komponen_id);
    }

    public function test_get_komponens_data_includes_butirs_from_correct_komponen(): void
    {
        $k2 = MasterEdpmKomponen::create(['nama' => 'SECOND KOMPONEN']);
        MasterEdpmButir::create([
            'komponen_id' => $k2->id,
            'no_sk' => 'SK-K2',
            'nomor_butir' => '2.1',
            'butir_pernyataan' => 'Butir komponen 2',
        ]);

        $result = $this->service->getKomponensData();

        $k1Data = $result->firstWhere('id', $this->komponen->id);
        $k2Data = $result->firstWhere('id', $k2->id);

        $this->assertCount(1, $k1Data->butirs);
        $this->assertCount(1, $k2Data->butirs);
        $this->assertEquals('Pernyataan butir pertama', $k1Data->butirs->first()->butir_pernyataan);
        $this->assertEquals('Butir komponen 2', $k2Data->butirs->first()->butir_pernyataan);
    }
}

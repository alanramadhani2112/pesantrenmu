<?php

namespace Tests\Feature\Pesantren;

use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PesantrenService SDM-related methods.
 *
 * Covers:
 *   - getSdm: returns existing records keyed by tingkat
 *   - updateSdm: happy path, lock guard, upsert behaviour
 *   - checkDataCompleteness: SDM presence check
 */
class SdmServiceTest extends TestCase
{
    use RefreshDatabase;

    private PesantrenService $service;

    private User $user;

    private Pesantren $pesantren;

    private PesantrenUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => false,
        ]);
        $this->unit = PesantrenUnit::create([
            'pesantren_id' => $this->pesantren->id,
            'unit' => 'sd',
            'jumlah_rombel' => 3,
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── getSdm ──────────────────────────────────────────────────────────────

    public function test_get_sdm_returns_empty_collection_when_no_records(): void
    {
        $result = $this->service->getSdm($this->user->id);

        $this->assertEmpty($result);
    }

    public function test_get_sdm_returns_records_keyed_by_tingkat(): void
    {
        SdmPesantren::create([
            'user_id' => $this->user->id,
            'pesantren_unit_id' => $this->unit->id,
            'tingkat' => 'sd',
            'santri_l' => 50,
            'santri_p' => 45,
        ]);

        $result = $this->service->getSdm($this->user->id);

        $this->assertTrue($result->has('sd'));
        $this->assertEquals(50, $result['sd']->santri_l);
        $this->assertEquals(45, $result['sd']->santri_p);
    }

    public function test_get_sdm_returns_all_units_for_user(): void
    {
        $unit2 = PesantrenUnit::create([
            'pesantren_id' => $this->pesantren->id,
            'unit' => 'smp',
            'jumlah_rombel' => 2,
        ]);

        SdmPesantren::create(['user_id' => $this->user->id, 'pesantren_unit_id' => $this->unit->id, 'tingkat' => 'sd', 'santri_l' => 10]);
        SdmPesantren::create(['user_id' => $this->user->id, 'pesantren_unit_id' => $unit2->id, 'tingkat' => 'smp', 'santri_l' => 20]);

        $result = $this->service->getSdm($this->user->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has('sd'));
        $this->assertTrue($result->has('smp'));
    }

    // ─── updateSdm: happy path ────────────────────────────────────────────────

    public function test_update_sdm_creates_new_record_when_none_exists(): void
    {
        $this->assertDatabaseCount('sdm_pesantrens', 0);

        $result = $this->service->updateSdm($this->user->id, 'sd', [
            'pesantren_unit_id' => $this->unit->id,
            'santri_l' => 30,
            'santri_p' => 25,
            'ustadz_dirosah_l' => 5,
            'ustadz_dirosah_p' => 3,
            'ustadz_non_dirosah_l' => 2,
            'ustadz_non_dirosah_p' => 1,
            'pamong_l' => 4,
            'pamong_p' => 2,
            'musyrif_l' => 6,
            'musyrif_p' => 4,
            'tendik_l' => 3,
            'tendik_p' => 2,
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('sdm_pesantrens', [
            'user_id' => $this->user->id,
            'tingkat' => 'sd',
            'santri_l' => 30,
            'santri_p' => 25,
        ]);
    }

    public function test_update_sdm_updates_existing_record(): void
    {
        SdmPesantren::create([
            'user_id' => $this->user->id,
            'pesantren_unit_id' => $this->unit->id,
            'tingkat' => 'sd',
            'santri_l' => 10,
            'santri_p' => 8,
        ]);

        $this->service->updateSdm($this->user->id, 'sd', [
            'pesantren_unit_id' => $this->unit->id,
            'santri_l' => 50,
            'santri_p' => 45,
        ]);

        $this->assertDatabaseHas('sdm_pesantrens', [
            'user_id' => $this->user->id,
            'tingkat' => 'sd',
            'santri_l' => 50,
            'santri_p' => 45,
        ]);
        // No duplicate rows
        $this->assertDatabaseCount('sdm_pesantrens', 1);
    }

    public function test_update_sdm_does_not_create_duplicate_rows(): void
    {
        // Call updateSdm twice for the same tingkat
        $data = [
            'pesantren_unit_id' => $this->unit->id,
            'santri_l' => 10,
            'santri_p' => 8,
        ];

        $this->service->updateSdm($this->user->id, 'sd', $data);
        $this->service->updateSdm($this->user->id, 'sd', array_merge($data, ['santri_l' => 20]));

        $this->assertDatabaseCount('sdm_pesantrens', 1);
        $this->assertDatabaseHas('sdm_pesantrens', ['santri_l' => 20]);
    }

    // ─── updateSdm: lock guard ────────────────────────────────────────────────

    public function test_update_sdm_returns_false_when_locked_and_no_active_rejection(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        $result = $this->service->updateSdm($this->user->id, 'sd', [
            'pesantren_unit_id' => $this->unit->id,
            'santri_l' => 99,
        ]);

        $this->assertFalse($result);
    }

    public function test_update_sdm_does_not_modify_db_when_locked(): void
    {
        SdmPesantren::create([
            'user_id' => $this->user->id,
            'pesantren_unit_id' => $this->unit->id,
            'tingkat' => 'sd',
            'santri_l' => 10,
        ]);
        $this->pesantren->update(['is_locked' => true]);

        $this->service->updateSdm($this->user->id, 'sd', [
            'pesantren_unit_id' => $this->unit->id,
            'santri_l' => 999,
        ]);

        $this->assertDatabaseHas('sdm_pesantrens', [
            'user_id' => $this->user->id,
            'tingkat' => 'sd',
            'santri_l' => 10, // unchanged
        ]);
    }

    // ─── checkDataCompleteness: SDM ──────────────────────────────────────────

    public function test_completeness_reports_missing_sdm_when_no_records(): void
    {
        $missing = $this->service->checkDataCompleteness($this->user->id);

        $this->assertTrue(collect($missing)->contains(fn ($m) => str_contains($m, 'SDM')));
    }

    public function test_completeness_passes_sdm_check_when_at_least_one_record_exists(): void
    {
        SdmPesantren::create([
            'user_id' => $this->user->id,
            'pesantren_unit_id' => $this->unit->id,
            'tingkat' => 'sd',
            'santri_l' => 1,
        ]);

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $sdmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'SDM'));
        $this->assertEmpty($sdmMissing);
    }

    // ─── Isolation: different users don't see each other's SDM ───────────────

    public function test_get_sdm_is_scoped_to_user(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3]);
        $otherPesantren = Pesantren::create(['user_id' => $otherUser->id, 'nama_pesantren' => 'Other']);
        $otherUnit = PesantrenUnit::create(['pesantren_id' => $otherPesantren->id, 'unit' => 'sd', 'jumlah_rombel' => 1]);

        SdmPesantren::create([
            'user_id' => $otherUser->id,
            'pesantren_unit_id' => $otherUnit->id,
            'tingkat' => 'sd',
            'santri_l' => 100,
        ]);

        $result = $this->service->getSdm($this->user->id);

        $this->assertEmpty($result);
    }
}

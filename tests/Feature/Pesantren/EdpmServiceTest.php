<?php

namespace Tests\Feature\Pesantren;

use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PesantrenService EDPM-related methods.
 *
 * Covers:
 *   - getEdpmData: returns komponens, existing evaluasis, existing catatans
 *   - saveEdpmEvaluation: happy path, lock guard, transaction atomicity
 *   - saveEdpmDraft: happy path, lock guard, only saves non-empty values
 *   - checkDataCompleteness: EDPM completeness gate
 */
class EdpmServiceTest extends TestCase
{
    use RefreshDatabase;

    private PesantrenService $service;

    private User $user;

    private Pesantren $pesantren;

    private MasterEdpmKomponen $komponen;

    private MasterEdpmButir $butir1;

    private MasterEdpmButir $butir2;

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

        // Create minimal master EDPM data for tests
        $this->komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN']);
        $this->butir1 = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Butir pernyataan 1',
        ]);
        $this->butir2 = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => '2',
            'nomor_butir' => '2',
            'butir_pernyataan' => 'Butir pernyataan 2',
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── getEdpmData ─────────────────────────────────────────────────────────

    public function test_get_edpm_data_returns_komponens(): void
    {
        $data = $this->service->getEdpmData($this->user->id);

        $this->assertArrayHasKey('komponens', $data);
        $this->assertCount(1, $data['komponens']);
        $this->assertEquals('MUTU LULUSAN', $data['komponens']->first()->nama);
    }

    public function test_get_edpm_data_returns_empty_collections_when_no_existing_data(): void
    {
        $data = $this->service->getEdpmData($this->user->id);

        $this->assertArrayHasKey('existingEdpms', $data);
        $this->assertArrayHasKey('existingCatatans', $data);
        $this->assertEmpty($data['existingEdpms']);
        $this->assertEmpty($data['existingCatatans']);
    }

    public function test_get_edpm_data_returns_existing_evaluasis_keyed_by_butir_id(): void
    {
        Edpm::create([
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '3',
            'link' => 'https://example.com/bukti1',
        ]);

        $data = $this->service->getEdpmData($this->user->id);

        $this->assertTrue($data['existingEdpms']->has($this->butir1->id));
        $this->assertEquals('3', $data['existingEdpms'][$this->butir1->id]->isian);
        $this->assertEquals('https://example.com/bukti1', $data['existingEdpms'][$this->butir1->id]->link);
    }

    public function test_get_edpm_data_returns_existing_catatans_keyed_by_komponen_id(): void
    {
        EdpmCatatan::create([
            'user_id' => $this->user->id,
            'komponen_id' => $this->komponen->id,
            'catatan' => 'Catatan komponen 1',
        ]);

        $data = $this->service->getEdpmData($this->user->id);

        $this->assertTrue($data['existingCatatans']->has($this->komponen->id));
        $this->assertEquals('Catatan komponen 1', $data['existingCatatans'][$this->komponen->id]);
    }

    // ─── saveEdpmEvaluation: happy path ──────────────────────────────────────

    public function test_save_edpm_evaluation_persists_evaluasis_and_links(): void
    {
        $evaluasis = [$this->butir1->id => '3', $this->butir2->id => '4'];
        $links = [
            $this->butir1->id => 'https://example.com/bukti1',
            $this->butir2->id => 'https://example.com/bukti2',
        ];
        $catatans = [$this->komponen->id => 'Catatan evaluasi'];

        $result = $this->service->saveEdpmEvaluation($this->user->id, $evaluasis, $links, $catatans);

        $this->assertTrue($result);
        $this->assertDatabaseHas('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '3',
            'link' => 'https://example.com/bukti1',
        ]);
        $this->assertDatabaseHas('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir2->id,
            'isian' => '4',
            'link' => 'https://example.com/bukti2',
        ]);
    }

    public function test_save_edpm_evaluation_persists_catatans(): void
    {
        $evaluasis = [$this->butir1->id => '2'];
        $links = [$this->butir1->id => 'https://example.com'];
        $catatans = [$this->komponen->id => 'Catatan kinerja komponen'];

        $this->service->saveEdpmEvaluation($this->user->id, $evaluasis, $links, $catatans);

        $this->assertDatabaseHas('edpm_catatans', [
            'user_id' => $this->user->id,
            'komponen_id' => $this->komponen->id,
            'catatan' => 'Catatan kinerja komponen',
        ]);
    }

    public function test_save_edpm_evaluation_updates_existing_record(): void
    {
        Edpm::create([
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '1',
            'link' => 'https://old.com',
        ]);

        $this->service->saveEdpmEvaluation(
            $this->user->id,
            [$this->butir1->id => '4'],
            [$this->butir1->id => 'https://new.com'],
            []
        );

        $this->assertDatabaseHas('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '4',
            'link' => 'https://new.com',
        ]);
        $this->assertDatabaseCount('edpms', 1); // no duplicate
    }

    public function test_save_edpm_evaluation_converts_empty_string_to_null(): void
    {
        $this->service->saveEdpmEvaluation(
            $this->user->id,
            [$this->butir1->id => ''],
            [$this->butir1->id => ''],
            []
        );

        $edpm = Edpm::where('user_id', $this->user->id)->where('butir_id', $this->butir1->id)->first();
        $this->assertNotNull($edpm);
        $this->assertNull($edpm->isian);
        $this->assertNull($edpm->link);
    }

    // ─── saveEdpmEvaluation: lock guard ──────────────────────────────────────

    public function test_save_edpm_evaluation_returns_false_when_locked_and_no_active_rejection(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        $result = $this->service->saveEdpmEvaluation(
            $this->user->id,
            [$this->butir1->id => '3'],
            [$this->butir1->id => 'https://example.com'],
            []
        );

        $this->assertFalse($result);
        $this->assertDatabaseCount('edpms', 0);
    }

    public function test_save_edpm_evaluation_does_not_modify_db_when_locked(): void
    {
        Edpm::create([
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '2',
            'link' => 'https://original.com',
        ]);
        $this->pesantren->update(['is_locked' => true]);

        $this->service->saveEdpmEvaluation(
            $this->user->id,
            [$this->butir1->id => '4'],
            [$this->butir1->id => 'https://override.com'],
            []
        );

        $this->assertDatabaseHas('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '2', // unchanged
        ]);
    }

    // ─── saveEdpmDraft ────────────────────────────────────────────────────────

    public function test_save_edpm_draft_persists_non_empty_values(): void
    {
        $result = $this->service->saveEdpmDraft(
            $this->user->id,
            [$this->butir1->id => '3', $this->butir2->id => ''],
            [$this->butir1->id => 'https://example.com', $this->butir2->id => ''],
            [$this->komponen->id => 'Catatan draft']
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '3',
        ]);
        // butir2 is empty — should not be saved
        $this->assertDatabaseMissing('edpms', [
            'user_id' => $this->user->id,
            'butir_id' => $this->butir2->id,
        ]);
    }

    public function test_save_edpm_draft_skips_empty_catatans(): void
    {
        $this->service->saveEdpmDraft(
            $this->user->id,
            [],
            [],
            [$this->komponen->id => ''] // empty catatan
        );

        $this->assertDatabaseCount('edpm_catatans', 0);
    }

    public function test_save_edpm_draft_returns_false_when_locked_and_no_active_rejection(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        $result = $this->service->saveEdpmDraft(
            $this->user->id,
            [$this->butir1->id => '3'],
            [$this->butir1->id => 'https://example.com'],
            []
        );

        $this->assertFalse($result);
        $this->assertDatabaseCount('edpms', 0);
    }

    // ─── checkDataCompleteness: EDPM ─────────────────────────────────────────

    public function test_completeness_reports_edpm_incomplete_when_no_evaluasis(): void
    {
        $missing = $this->service->checkDataCompleteness($this->user->id);

        $edpmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'EDPM'));
        $this->assertNotEmpty($edpmMissing);
    }

    public function test_completeness_reports_edpm_incomplete_when_partial_evaluasis(): void
    {
        // Only fill butir1, not butir2
        Edpm::create([
            'user_id' => $this->user->id,
            'butir_id' => $this->butir1->id,
            'isian' => '3',
            'link' => 'https://example.com',
        ]);

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $edpmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'EDPM'));
        $this->assertNotEmpty($edpmMissing);
    }

    public function test_completeness_passes_edpm_check_when_all_butirs_filled(): void
    {
        // Fill all butirs
        Edpm::create(['user_id' => $this->user->id, 'butir_id' => $this->butir1->id, 'isian' => '3', 'link' => 'https://example.com']);
        Edpm::create(['user_id' => $this->user->id, 'butir_id' => $this->butir2->id, 'isian' => '4', 'link' => 'https://example.com']);

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $edpmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'EDPM'));
        $this->assertEmpty($edpmMissing);
    }

    // ─── Isolation: different users don't see each other's EDPM ──────────────

    public function test_get_edpm_data_is_scoped_to_user(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3]);
        Edpm::create([
            'user_id' => $otherUser->id,
            'butir_id' => $this->butir1->id,
            'isian' => '4',
            'link' => 'https://other.com',
        ]);

        $data = $this->service->getEdpmData($this->user->id);

        $this->assertEmpty($data['existingEdpms']);
    }

    public function test_save_edpm_evaluation_is_scoped_to_user(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $otherUser->id, 'nama_pesantren' => 'Other']);

        $this->service->saveEdpmEvaluation(
            $this->user->id,
            [$this->butir1->id => '3'],
            [$this->butir1->id => 'https://example.com'],
            []
        );

        // Other user's data should not be affected
        $this->assertDatabaseMissing('edpms', [
            'user_id' => $otherUser->id,
        ]);
    }
}

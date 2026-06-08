<?php

namespace Tests\Feature\Pesantren;

use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for PM-1 and PM-2: orphan file race condition fix.
 *
 * The fix in profile.blade.php and ipm.blade.php ensures:
 *   - New files are stored to disk BEFORE the DB update attempt.
 *   - Old files are deleted ONLY after the DB update succeeds.
 *   - If the DB update fails (e.g. section locked), new files are deleted
 *     and old files remain intact — no data loss.
 *
 * These tests exercise the service layer directly (updateProfile / updateIpm)
 * to verify the lock-guard behaviour that the Blade upload flow relies on.
 * The upload rollback logic is tested via the service returning false on
 * locked pesantren.
 */
class FileUploadRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Pesantren $pesantren;

    private PesantrenService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->user = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => false,
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── PM-1: Profile — service returns false when locked ───────────────────

    /**
     * When updateProfile() returns false (locked), the profile submit flow
     * must delete the newly stored file and keep the old one.
     * This test verifies the service correctly returns false so the UI layer
     * can trigger its rollback branch.
     */
    public function test_update_profile_returns_false_when_locked_so_component_can_rollback(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        // Simulate: new file already stored to disk (as the component does)
        Storage::disk('public')->put('pesantren_docs/new_nsp.pdf', 'new content');
        Storage::disk('public')->put('pesantren_docs/old_nsp.pdf', 'old content');

        $result = $this->service->updateProfile($this->user->id, [
            'nama_pesantren' => 'Updated',
            'sertifikat_nsp' => 'pesantren_docs/new_nsp.pdf',
        ], []);

        // Service returns false → component should delete new file and keep old
        $this->assertFalse($result);

        // DB must NOT be updated
        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Test', // unchanged
        ]);
    }

    public function test_update_profile_returns_true_when_unlocked_so_component_deletes_old_file(): void
    {
        $data = [
            'nama_pesantren' => 'Updated Name',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Test',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Test',
            'layanan_satuan_pendidikan' => ['sd'],
            'sertifikat_nsp' => 'pesantren_docs/new_nsp.pdf',
        ];

        $result = $this->service->updateProfile($this->user->id, $data, []);

        // Service returns true → component should delete old file
        $this->assertTrue($result);
        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $this->user->id,
            'sertifikat_nsp' => 'pesantren_docs/new_nsp.pdf',
        ]);
    }

    // ─── PM-1: Profile — Storage rollback simulation ─────────────────────────

    public function test_new_file_is_deleted_when_profile_update_fails(): void
    {
        $this->pesantren->update([
            'is_locked' => true,
            'sertifikat_nsp' => 'pesantren_docs/old_nsp.pdf',
        ]);

        // Simulate what the upload flow does:
        // 1. Store new file to disk
        Storage::disk('public')->put('pesantren_docs/old_nsp.pdf', 'old content');
        $newPath = 'pesantren_docs/new_nsp_'.uniqid().'.pdf';
        Storage::disk('public')->put($newPath, 'new content');

        // 2. Attempt DB update — returns false (locked)
        $data = ['sertifikat_nsp' => $newPath];
        $result = $this->service->updateProfile($this->user->id, $data, []);

        // 3. Component rollback: delete new file since DB failed
        if (! $result) {
            Storage::disk('public')->delete($newPath);
        }

        // Assert: new file cleaned up, old file intact
        Storage::disk('public')->assertMissing($newPath);
        Storage::disk('public')->assertExists('pesantren_docs/old_nsp.pdf');
    }

    public function test_old_file_is_deleted_only_after_successful_profile_update(): void
    {
        Storage::disk('public')->put('pesantren_docs/old_nsp.pdf', 'old content');
        $this->pesantren->update(['sertifikat_nsp' => 'pesantren_docs/old_nsp.pdf']);

        $newPath = 'pesantren_docs/new_nsp_'.uniqid().'.pdf';
        Storage::disk('public')->put($newPath, 'new content');

        $data = [
            'nama_pesantren' => 'Updated',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Test',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Test',
            'layanan_satuan_pendidikan' => ['sd'],
            'sertifikat_nsp' => $newPath,
        ];

        $result = $this->service->updateProfile($this->user->id, $data, []);

        // Simulate component: delete old file only after success
        if ($result) {
            Storage::disk('public')->delete('pesantren_docs/old_nsp.pdf');
        }

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing('pesantren_docs/old_nsp.pdf');
        Storage::disk('public')->assertExists($newPath);
    }

    // ─── PM-2: IPM — service returns false when locked ───────────────────────

    public function test_update_ipm_returns_false_when_locked_so_component_can_rollback(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        Ipm::create(['user_id' => $this->user->id, 'nsp_file' => 'ipm_docs/old_nsp.pdf']);

        $result = $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'ipm_docs/new_nsp.pdf',
        ]);

        $this->assertFalse($result);
        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'ipm_docs/old_nsp.pdf', // unchanged
        ]);
    }

    public function test_update_ipm_returns_true_when_unlocked_so_component_deletes_old_file(): void
    {
        Ipm::create(['user_id' => $this->user->id, 'nsp_file' => 'ipm_docs/old_nsp.pdf']);

        $result = $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'ipm_docs/new_nsp.pdf',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'ipm_docs/new_nsp.pdf',
        ]);
    }

    // ─── PM-2: IPM — Storage rollback simulation ─────────────────────────────

    public function test_new_ipm_file_is_deleted_when_update_fails(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        Storage::disk('public')->put('ipm_docs/old_nsp.pdf', 'old content');
        Ipm::create(['user_id' => $this->user->id, 'nsp_file' => 'ipm_docs/old_nsp.pdf']);

        // Simulate component: store new file first
        $newPath = 'ipm_docs/new_nsp_'.uniqid().'.pdf';
        Storage::disk('public')->put($newPath, 'new content');

        // Attempt DB update — returns false (locked)
        $result = $this->service->updateIpm($this->user->id, ['nsp_file' => $newPath]);

        // Component rollback: delete new file
        if (! $result) {
            Storage::disk('public')->delete($newPath);
        }

        Storage::disk('public')->assertMissing($newPath);
        Storage::disk('public')->assertExists('ipm_docs/old_nsp.pdf');
    }

    public function test_old_ipm_file_is_deleted_only_after_successful_update(): void
    {
        Storage::disk('public')->put('ipm_docs/old_nsp.pdf', 'old content');
        Ipm::create(['user_id' => $this->user->id, 'nsp_file' => 'ipm_docs/old_nsp.pdf']);

        $newPath = 'ipm_docs/new_nsp_'.uniqid().'.pdf';
        Storage::disk('public')->put($newPath, 'new content');

        $result = $this->service->updateIpm($this->user->id, ['nsp_file' => $newPath]);

        // Component: delete old file only after success
        if ($result) {
            Storage::disk('public')->delete('ipm_docs/old_nsp.pdf');
        }

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing('ipm_docs/old_nsp.pdf');
        Storage::disk('public')->assertExists($newPath);
    }

    // ─── Transaction atomicity ────────────────────────────────────────────────

    public function test_update_profile_units_are_atomic_with_profile_data(): void
    {
        $data = [
            'nama_pesantren' => 'Updated Name',
            'ns_pesantren' => '510099999999',
            'alamat' => 'Jl. Test',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '2000',
            'nama_mudir' => 'KH. Test',
            'layanan_satuan_pendidikan' => ['sd', 'smp'],
        ];

        $units = [
            ['unit' => 'sd', 'jumlah_rombel' => 3],
            ['unit' => 'smp', 'jumlah_rombel' => 2],
        ];

        $result = $this->service->updateProfile($this->user->id, $data, $units);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pesantrens', ['nama_pesantren' => 'Updated Name']);
        $this->assertDatabaseHas('pesantren_units', ['unit' => 'sd', 'jumlah_rombel' => 3]);
        $this->assertDatabaseHas('pesantren_units', ['unit' => 'smp', 'jumlah_rombel' => 2]);
    }
}

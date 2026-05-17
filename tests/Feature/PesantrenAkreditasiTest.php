<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PesantrenAkreditasiTest extends TestCase
{
    use RefreshDatabase;

    protected PesantrenService $pesantrenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->pesantrenService = app(PesantrenService::class);
        Notification::fake();
    }

    /**
     * Helper: create a pesantren user with a Pesantren record.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test ' . $user->id,
            'is_locked' => true,
        ]);

        return $user;
    }

    /**
     * Task 9.4: deleteSubmission returns false for non-status-6 akreditasi.
     *
     * Requirements: 2.12
     */
    public function test_pesantren_cannot_delete_akreditasi_with_status_other_than_pengajuan(): void
    {
        $user = $this->createPesantrenUser();

        // Create akreditasi at status 5 (Assessment) — not deletable
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $result = $this->pesantrenService->deleteSubmission($akreditasi->id, $user->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id]);
    }

    /**
     * Task 9.4: deleteSubmission handles null pesantren gracefully (no exception).
     *
     * A user without a Pesantren record should still be able to delete their
     * own status-6 akreditasi without throwing an error.
     *
     * Requirements: 2.13
     */
    public function test_pesantren_with_null_pesantren_record_can_delete_safely(): void
    {
        // Create a user WITHOUT a Pesantren record
        $user = User::factory()->create(['role_id' => 3]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6, // Pengajuan — deletable
        ]);

        // Authenticate so the audit log observer can resolve Auth::id()
        $this->actingAs($user);

        // Should not throw; null pesantren is handled via null-safe operator
        $result = $this->pesantrenService->deleteSubmission($akreditasi->id, $user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    /**
     * Task 9.4: deleteSubmission returns false when the akreditasi belongs to another user.
     *
     * Requirements: 2.12
     */
    public function test_pesantren_cannot_delete_akreditasi_owned_by_another_user(): void
    {
        $owner = $this->createPesantrenUser();
        $attacker = $this->createPesantrenUser();

        // Akreditasi belongs to owner, not attacker
        $akreditasi = Akreditasi::create([
            'user_id' => $owner->id,
            'status' => 6,
        ]);

        $result = $this->pesantrenService->deleteSubmission($akreditasi->id, $attacker->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id]);
    }
}

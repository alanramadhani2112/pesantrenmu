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

class PesantrenUploadTest extends TestCase
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
     * Task 3.4: A pesantren user cannot upload kartu kendali for an akreditasi
     * owned by another user — uploadKartuKendali returns false.
     */
    public function test_pesantren_cannot_upload_kartu_kendali_for_other_user_akreditasi(): void
    {
        // Arrange — owner of the akreditasi
        $owner = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $owner->id,
            'nama_pesantren' => 'Pesantren Pemilik',
        ]);

        // Admin user for notifications
        User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $owner->id,
            'status' => 2, // Pasca Visitasi — the only status that allows upload
        ]);

        // Attacker — a different pesantren user
        $attacker = User::factory()->create(['role_id' => 3]);

        // Act — attacker tries to upload for owner's akreditasi
        $result = $this->pesantrenService->uploadKartuKendali(
            $akreditasi->id,
            $attacker->id,
            'akreditasi/kartu-kendali/fake.pdf'
        );

        // Assert — upload must be rejected
        $this->assertFalse($result);

        // Verify the kartu_kendali field was NOT updated
        $this->assertNull($akreditasi->fresh()->kartu_kendali);
    }
}

<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $pesantren;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);

        $this->pesantren = User::where('email', 'bf.pesantren@test.local')->firstOrFail();
        $this->superAdmin = User::where('email', 'bf.superadmin@test.local')->firstOrFail();
    }

    public function test_pesantren_can_submit_banding_for_rejected_assessed_akreditasi(): void
    {
        $akreditasi = $this->scenario('BF-BANDING-001');

        $this->actingAs($this->pesantren)
            ->post(route('pesantren.akreditasi.banding'), [
                'id' => $akreditasi->id,
                'alasan' => str_repeat('Alasan banding E2E valid. ', 3),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_BANDING, (int) $akreditasi->fresh()->status);
        $this->assertDatabaseHas('bandings', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $this->pesantren->id,
            'status' => 'pending',
        ]);
    }

    public function test_pesantren_cannot_submit_banding_with_short_reason(): void
    {
        $akreditasi = $this->scenario('BF-BANDING-001');

        $this->actingAs($this->pesantren)
            ->post(route('pesantren.akreditasi.banding'), [
                'id' => $akreditasi->id,
                'alasan' => 'Terlalu pendek.',
            ])
            ->assertSessionHasErrors('alasan');

        $this->assertSame(Akreditasi::STATUS_DITOLAK, (int) $akreditasi->fresh()->status);
        $this->assertDatabaseMissing('bandings', [
            'akreditasi_id' => $akreditasi->id,
        ]);
    }

    public function test_pesantren_cannot_submit_duplicate_banding(): void
    {
        $akreditasi = $this->scenario('BF-NEG-006');
        $existingCount = Banding::where('akreditasi_id', $akreditasi->id)->count();

        $this->actingAs($this->pesantren)
            ->post(route('pesantren.akreditasi.banding'), [
                'id' => $akreditasi->id,
                'alasan' => str_repeat('Alasan banding duplikat harus ditolak. ', 2),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Akreditasi::STATUS_DITOLAK, (int) $akreditasi->fresh()->status);
        $this->assertSame($existingCount, Banding::where('akreditasi_id', $akreditasi->id)->count());
    }

    public function test_super_admin_can_assign_and_accept_banding(): void
    {
        $banding = $this->bandingScenario('BF-BANDING-002');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.banding-detail', $banding->id))
            ->assertOk()
            ->assertSee('Detail Banding')
            ->assertSee('Alasan Banding');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.banding.assign-reviewer', $banding->id), [
                'selectedReviewerId' => $this->superAdmin->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $banding->refresh();

        $this->assertSame('under_review', $banding->status);
        $this->assertSame($this->superAdmin->id, (int) $banding->reviewer_id);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.banding.submit-decision', $banding->id), [
                'decisionType' => 'accept',
                'keputusan' => 'Banding diterima untuk validasi ulang E2E.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_VALIDASI_ADMIN, (int) $banding->akreditasi->fresh()->status);
        $this->assertSame('accepted', $banding->fresh()->status);
        $this->assertNotNull($banding->fresh()->decided_at);
    }

    public function test_super_admin_can_assign_and_reject_banding(): void
    {
        $banding = $this->bandingScenario('BF-BANDING-002');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.banding.assign-reviewer', $banding->id), [
                'selectedReviewerId' => $this->superAdmin->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.banding.submit-decision', $banding->id), [
                'decisionType' => 'reject',
                'keputusan' => 'Banding ditolak karena alasan belum memadai.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_DITOLAK, (int) $banding->akreditasi->fresh()->status);
        $this->assertSame('rejected', $banding->fresh()->status);
        $this->assertNotNull($banding->fresh()->decided_at);
    }

    private function scenario(string $code): Akreditasi
    {
        return Akreditasi::where('catatan', 'like', "[{$code}]%")
            ->firstOrFail();
    }

    private function bandingScenario(string $code): Banding
    {
        return Banding::whereHas('akreditasi', fn ($query) => $query->where('catatan', 'like', "[{$code}]%"))
            ->firstOrFail();
    }
}

<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Assessment;
use App\Models\Asesor;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);
        $this->admin = User::where('email', 'bf.admin@test.local')->firstOrFail();
    }

    public function test_admin_can_open_pengajuan_for_berkas_review(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-001');

        $this->actingAs($this->admin)
            ->post(route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid))
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_VERIFIKASI_BERKAS, (int) $akreditasi->fresh()->status);
    }

    public function test_admin_can_approve_berkas_and_assign_two_asesors(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-002');
        [$asesor1, $asesor2] = $this->asesorUsers();

        $this->actingAs($this->admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor2->id,
            ])
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_ASSESSMENT, (int) $akreditasi->fresh()->status);
        $this->assertSame(2, Assessment::where('akreditasi_id', $akreditasi->id)->count());
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => Asesor::where('user_id', $asesor1->id)->firstOrFail()->id,
            'tipe' => 1,
        ]);
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => Asesor::where('user_id', $asesor2->id)->firstOrFail()->id,
            'tipe' => 2,
        ]);
    }

    public function test_admin_cannot_assign_same_asesor_twice(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-002');
        [$asesor1] = $this->asesorUsers();

        $this->actingAs($this->admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor1->id,
            ])
            ->assertSessionHasErrors('asesor2Id');

        $this->assertSame(Akreditasi::STATUS_VERIFIKASI_BERKAS, (int) $akreditasi->fresh()->status);
    }

    public function test_admin_can_reject_berkas_and_unlock_pesantren_data(): void
    {
        $akreditasi = $this->scenario('BF-NEG-010');
        $akreditasi->user->pesantren->update(['is_locked' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.akreditasi-detail.reject-berkas', $akreditasi->uuid), [
                'berkasRejectionSections' => ['profil', 'ipm'],
                'berkasRejectionCatatan' => 'Dokumen profil dan IPM perlu dilengkapi ulang.',
            ])
            ->assertRedirect(route('admin.akreditasi'));

        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $this->admin->id,
            'type' => 'admin_verifikasi',
            'status' => 'pending',
        ]);
        $this->assertFalse((bool) $akreditasi->user->pesantren->fresh()->is_locked);
        $this->assertSame(1, AkreditasiRejection::where('akreditasi_id', $akreditasi->id)->count());
    }

    private function scenario(string $code): Akreditasi
    {
        return Akreditasi::where('catatan', 'like', "[{$code}]%")
            ->firstOrFail();
    }

    /** @return array{0: User, 1: User} */
    private function asesorUsers(): array
    {
        return [
            User::where('email', 'bf.asesor1@test.local')->firstOrFail(),
            User::where('email', 'bf.asesor2@test.local')->firstOrFail(),
        ];
    }
}

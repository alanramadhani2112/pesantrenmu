<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsesorRejectDocumentHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ketua_can_reject_document_at_assessment_status(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.reject-document'), [
            'akreditasi_id' => $akreditasi->id,
            'perbaikan' => ['profil'],
            'catatan' => 'Dokumen profil perlu diperbaiki sebelum lanjut visitasi.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'type' => 'asesor',
            'status' => 'pending',
        ]);
    }
}

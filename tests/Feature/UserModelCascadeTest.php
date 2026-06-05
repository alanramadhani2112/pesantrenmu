<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Coverage: User::boot() cascade delete — pesantren, asesor, akreditasi,
 * ipm, sdm, edpm, file cleanup.
 */
class UserModelCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    private function makePesantrenUser(): User
    {
        return User::factory()->create(['role_id' => 3]);
    }

    public function test_deleting_user_cascades_to_pesantren(): void
    {
        $user = $this->makePesantrenUser();
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Test']);

        $user->delete();

        $this->assertDatabaseMissing('pesantrens', ['user_id' => $user->id]);
    }

    public function test_deleting_user_cascades_to_ipm(): void
    {
        $user = $this->makePesantrenUser();
        Ipm::create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('ipms', ['user_id' => $user->id]);
    }

    public function test_deleting_user_cascades_to_sdm(): void
    {
        $user = $this->makePesantrenUser();
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Test']);
        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'smp',
            'santri_l' => 10,
            'santri_p' => 10,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('sdm_pesantrens', ['user_id' => $user->id]);
    }

    public function test_deleting_user_cascades_to_akreditasi(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 6]);

        $user->delete();

        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_deleting_user_cascades_to_asesor(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => 'Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $user->delete();

        $this->assertDatabaseMissing('asesors', ['user_id' => $user->id]);
    }

    public function test_deleting_pesantren_removes_uploaded_files(): void
    {
        $user = $this->makePesantrenUser();
        Storage::disk('public')->put('pesantren_docs/nsp.pdf', 'content');

        $pesantren = Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Test',
            'sertifikat_nsp' => 'pesantren_docs/nsp.pdf',
        ]);

        $pesantren->delete();

        Storage::disk('public')->assertMissing('pesantren_docs/nsp.pdf');
    }

    public function test_deleting_ipm_removes_uploaded_files(): void
    {
        $user = $this->makePesantrenUser();
        Storage::disk('public')->put('ipm_docs/nsp.pdf', 'content');

        $ipm = Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm_docs/nsp.pdf',
        ]);

        $ipm->delete();

        Storage::disk('public')->assertMissing('ipm_docs/nsp.pdf');
    }

    public function test_deleting_akreditasi_removes_uploaded_files(): void
    {
        $user = $this->makePesantrenUser();
        Storage::disk('public')->put('akreditasi/kartu.pdf', 'content');

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6,
            'kartu_kendali' => 'akreditasi/kartu.pdf',
        ]);

        $akreditasi->delete();

        Storage::disk('public')->assertMissing('akreditasi/kartu.pdf');
    }

    public function test_deleting_user_is_wrapped_in_transaction(): void
    {
        // Verifikasi bahwa cascade delete tidak meninggalkan partial state
        $user = $this->makePesantrenUser();
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Test']);
        Ipm::create(['user_id' => $user->id]);
        Akreditasi::create(['user_id' => $user->id, 'status' => 6]);

        $user->delete();

        // Semua cascade harus selesai
        $this->assertDatabaseMissing('pesantrens', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('ipms', ['user_id' => $user->id]);
        $this->assertSame(
            0,
            Akreditasi::withTrashed()->where('user_id', $user->id)->whereNull('deleted_at')->count()
        );
    }

    public function test_user_factory_auto_generates_uuid(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid, 'Factory-created user must have a UUID');
        $this->assertIsString($user->uuid);
        $this->assertTrue(Str::isUuid($user->uuid), 'Value must be a valid UUID v4');
    }

    public function test_user_created_via_new_auto_generates_uuid(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test-uuid@spm.test',
            'password' => bcrypt('password'),
            'role_id' => 3,
        ]);
        $user->save();

        $this->assertNotNull($user->uuid, 'Eloquent-created user must have a UUID');
        $this->assertTrue(Str::isUuid($user->uuid), 'Value must be a valid UUID');
    }

    public function test_user_created_with_explicit_uuid_preserves_it(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $user = new User([
            'name' => 'Explicit UUID',
            'email' => 'explicit-uuid@spm.test',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'uuid' => $uuid,
        ]);
        $user->save();

        $this->assertSame($uuid, $user->uuid, 'Explicitly set UUID must be preserved');
    }
}

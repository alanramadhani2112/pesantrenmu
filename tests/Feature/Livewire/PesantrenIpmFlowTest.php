<?php

namespace Tests\Feature\Livewire;

use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenIpmFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_ipm_requires_all_documents_before_final_save(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.ipm')
            ->call('save')
            ->assertHasErrors([
                'nsp_file_upload' => 'required',
                'lulus_santri_file_upload' => 'required',
                'kurikulum_file_upload' => 'required',
                'buku_ajar_file_upload' => 'required',
            ])
            ->assertSee('Data IPM belum lengkap')
            ->assertSee('File NSP wajib diisi.');
    }

    public function test_ipm_saves_complete_pdf_documents(): void
    {
        Storage::fake('public');

        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.ipm')
            ->set('nsp_file_upload', UploadedFile::fake()->create('nsp.pdf', 100, 'application/pdf'))
            ->set('lulus_santri_file_upload', UploadedFile::fake()->create('lulus.pdf', 100, 'application/pdf'))
            ->set('kurikulum_file_upload', UploadedFile::fake()->create('kurikulum.pdf', 100, 'application/pdf'))
            ->set('buku_ajar_file_upload', UploadedFile::fake()->create('buku-ajar.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notification-received');

        $ipm = Ipm::where('user_id', $user->id)->firstOrFail();

        foreach (['nsp_file', 'lulus_santri_file', 'kurikulum_file', 'buku_ajar_file'] as $field) {
            $this->assertNotEmpty($ipm->{$field});
            Storage::disk('public')->assertExists($ipm->{$field});
        }
    }

    public function test_ipm_uses_reusable_metronic_file_components_without_emoji_locks(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.ipm')
            ->assertSee('data-ui-file-upload="metronic"', false)
            ->assertSee('data-ui-section-card="metronic"', false)
            ->assertSee('Simpan Perubahan')
            ->assertDontSee('🔒')
            ->assertDontSee('🔓');
    }

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren IPM',
            'is_locked' => false,
        ]);

        return $user->refresh();
    }
}

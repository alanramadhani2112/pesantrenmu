<?php

namespace Tests\Feature\Property;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Property 3: Pesantren onboarding step completion reflects data state
 *
 * For any Pesantren user with arbitrary data completeness states, the onboarding step
 * completion status SHALL mark a step as completed if and only if the corresponding
 * data section meets its completion criteria:
 * - Profil: all required fields filled
 * - IPM: all 4 required file fields filled
 * - SDM: at least one SdmPesantren record exists
 * - EDPM: user has AkreditasiEdpm records
 * - Akreditasi: user has Akreditasi with status >= 6
 *
 * **Validates: Requirements 5.4, 5.5**
 */
class OnboardingStepCompletionPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected OnboardingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(OnboardingService::class);
    }

    /**
     * Profil required fields as defined in SidebarProgressService.
     */
    private const PROFIL_FIELDS = [
        'nama_pesantren',
        'ns_pesantren',
        'alamat',
        'provinsi',
        'kota_kabupaten',
        'tahun_pendirian',
        'nama_mudir',
        'layanan_satuan_pendidikan',
    ];

    /**
     * IPM required fields as defined in SidebarProgressService.
     */
    private const IPM_FIELDS = [
        'nsp_file',
        'lulus_santri_file',
        'kurikulum_file',
        'buku_ajar_file',
    ];

    /**
     * Generate random data provider with 120 iterations of randomized Pesantren data states.
     */
    public static function randomOnboardingStatesProvider(): array
    {
        $cases = [];
        $seed = crc32('onboarding_step_completion_property_test');
        mt_srand($seed);

        for ($i = 0; $i < 120; $i++) {
            // Generate random bitmask for profil fields.
            $profilBitmask = mt_rand(0, (1 << count(self::PROFIL_FIELDS)) - 1);
            // Generate random bitmask for IPM fields (4 fields → 0-15)
            $ipmBitmask = mt_rand(0, 15);
            // Generate random SDM presence (true/false)
            $sdmPresent = (bool) mt_rand(0, 1);
            // Generate random EDPM presence (true/false)
            $edpmPresent = (bool) mt_rand(0, 1);
            // Generate random Akreditasi status (0-10, where >= 6 means submitted)
            $akreditasiStatus = mt_rand(0, 10);

            $cases["iteration_{$i}"] = [
                $profilBitmask,
                $ipmBitmask,
                $sdmPresent,
                $edpmPresent,
                $akreditasiStatus,
            ];
        }

        return $cases;
    }

    /**
     * Property 3: Pesantren onboarding step completion reflects data state
     *
     * **Validates: Requirements 5.4, 5.5**
     *
     */
#[DataProvider('randomOnboardingStatesProvider')]
public function test_property_3_onboarding_step_completion_reflects_data_state(
        int $profilBitmask,
        int $ipmBitmask,
        bool $sdmPresent,
        bool $edpmPresent,
        int $akreditasiStatus
    ): void {
        $user = User::factory()->create(['role_id' => 3]);

        // --- Set up Profil data ---
        $profilData = ['user_id' => $user->id];
        $profilFilledCount = 0;
        foreach (self::PROFIL_FIELDS as $index => $field) {
            if ($profilBitmask & (1 << $index)) {
                $profilData[$field] = $field === 'layanan_satuan_pendidikan'
                    ? ['spm']
                    : 'test_value_' . $field;
                $profilFilledCount++;
            } else {
                $profilData[$field] = $field === 'layanan_satuan_pendidikan' ? [] : '';
            }
        }
        Pesantren::create($profilData);

        // --- Set up IPM data ---
        $ipmData = ['user_id' => $user->id];
        $ipmFilledCount = 0;
        foreach (self::IPM_FIELDS as $index => $field) {
            if ($ipmBitmask & (1 << $index)) {
                $ipmData[$field] = 'uploads/' . $field . '_test.pdf';
                $ipmFilledCount++;
            } else {
                $ipmData[$field] = null;
            }
        }
        Ipm::create($ipmData);

        // --- Set up SDM data ---
        if ($sdmPresent) {
            SdmPesantren::create([
                'user_id' => $user->id,
                'tingkat' => 'spm',
            ]);
        }

        // --- Set up EDPM data ---
        if ($edpmPresent) {
            // Create an Akreditasi record (with any status) to link EDPM records
            $akreditasiForEdpm = Akreditasi::create([
                'user_id' => $user->id,
                'uuid' => fake()->uuid(),
                'status' => 1, // Low status, just for EDPM linkage
            ]);

            // Create master data needed for FK constraint
            $komponen = MasterEdpmKomponen::create(['nama' => 'Test Komponen']);
            $butir = MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => 'SK-001',
                'nomor_butir' => '1.1',
                'butir_pernyataan' => 'Test butir pernyataan',
            ]);

            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasiForEdpm->id,
                'pesantren_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => 'Test isian',
            ]);
        }

        // --- Set up Akreditasi data (for the 'akreditasi' step) ---
        // Only create if status > 0 (status 0 means no akreditasi record)
        if ($akreditasiStatus > 0) {
            Akreditasi::create([
                'user_id' => $user->id,
                'uuid' => fake()->uuid(),
                'status' => $akreditasiStatus,
            ]);
        }

        // --- Get completion status from OnboardingService ---
        $completionStatus = $this->service->getStepCompletionStatus($user->id, 3);

        // --- Verify 'profil' step ---
        $expectedProfilComplete = ($profilFilledCount === count(self::PROFIL_FIELDS));
        $this->assertEquals(
            $expectedProfilComplete,
            $completionStatus['profil'],
            "Profil step: expected " . ($expectedProfilComplete ? 'true' : 'false')
            . " for {$profilFilledCount}/" . count(self::PROFIL_FIELDS) . " fields filled (bitmask: {$profilBitmask})"
        );

        // --- Verify 'ipm' step ---
        $expectedIpmComplete = ($ipmFilledCount === count(self::IPM_FIELDS));
        $this->assertEquals(
            $expectedIpmComplete,
            $completionStatus['ipm'],
            "IPM step: expected " . ($expectedIpmComplete ? 'true' : 'false')
            . " for {$ipmFilledCount}/" . count(self::IPM_FIELDS) . " fields filled (bitmask: {$ipmBitmask})"
        );

        // --- Verify 'sdm' step ---
        $this->assertEquals(
            $sdmPresent,
            $completionStatus['sdm'],
            "SDM step: expected " . ($sdmPresent ? 'true' : 'false')
        );

        // --- Verify 'edpm' step ---
        $this->assertEquals(
            $edpmPresent,
            $completionStatus['edpm'],
            "EDPM step: expected " . ($edpmPresent ? 'true' : 'false')
        );

        // --- Verify 'akreditasi' step ---
        // The akreditasi step is complete when user has Akreditasi with status >= 6
        $expectedAkreditasiComplete = ($akreditasiStatus >= 6);
        $this->assertEquals(
            $expectedAkreditasiComplete,
            $completionStatus['akreditasi'],
            "Akreditasi step: expected " . ($expectedAkreditasiComplete ? 'true' : 'false')
            . " for status={$akreditasiStatus}"
        );
    }
}

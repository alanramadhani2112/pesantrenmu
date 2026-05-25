<?php

namespace Tests\Feature\Property;

use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\SidebarProgressService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Property 1: Progress status calculation is consistent with data presence
 *
 * For any combination of filled and unfilled required fields for a Pesantren user
 * (across Profil, IPM, and SDM sections), the SidebarProgressService::getSectionProgress()
 * method SHALL return:
 * - 'not_started' when zero required fields are filled
 * - 'complete' when all required fields are filled
 * - 'incomplete' when at least one but not all required fields are filled
 *
 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**
 */
class ProgressStatusPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected SidebarProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(SidebarProgressService::class);
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
     * Generate random data provider with 120 iterations of randomized field combinations.
     */
    public static function randomProgressCombinationsProvider(): array
    {
        $cases = [];
        $seed = crc32('progress_status_property_test');
        mt_srand($seed);

        for ($i = 0; $i < 120; $i++) {
            // Generate random bitmask for profil fields.
            $profilBitmask = mt_rand(0, (1 << count(self::PROFIL_FIELDS)) - 1);
            // Generate random bitmask for IPM fields (4 fields → 0-15)
            $ipmBitmask = mt_rand(0, 15);
            // Generate random SDM presence (true/false)
            $sdmPresent = (bool) mt_rand(0, 1);

            $cases["iteration_{$i}_profil_{$profilBitmask}_ipm_{$ipmBitmask}_sdm_" . ($sdmPresent ? '1' : '0')] = [
                $profilBitmask,
                $ipmBitmask,
                $sdmPresent,
            ];
        }

        return $cases;
    }

    /**
     * Property 1: Progress status calculation is consistent with data presence
     *
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**
     *
     */
#[DataProvider('randomProgressCombinationsProvider')]
public function test_property_1_progress_status_consistent_with_data_presence(
        int $profilBitmask,
        int $ipmBitmask,
        bool $sdmPresent
    ): void {
        $user = User::factory()->create(['role_id' => 3]);

        // --- Set up Profil data ---
        $profilFilledCount = 0;

        foreach (self::PROFIL_FIELDS as $index => $field) {
            if ($profilBitmask & (1 << $index)) {
                $profilFilledCount++;
            }
        }

        // Always create a Pesantren record to test field-level checking.
        // Use empty string for "not filled" fields (empty('') === true, so service treats as unfilled).
        // The nama_pesantren column is NOT NULL, so we always provide a value.
        $profilData = ['user_id' => $user->id];
        foreach (self::PROFIL_FIELDS as $index => $field) {
            if ($profilBitmask & (1 << $index)) {
                $profilData[$field] = $field === 'layanan_satuan_pendidikan'
                    ? ['spm']
                    : 'test_value_' . $field;
            } else {
                // Use empty string for unfilled fields (satisfies NOT NULL constraint
                // while still being treated as "empty" by the service)
                $profilData[$field] = $field === 'layanan_satuan_pendidikan' ? [] : '';
            }
        }

        Pesantren::create($profilData);

        // --- Set up IPM data ---
        $ipmFilledCount = 0;

        foreach (self::IPM_FIELDS as $index => $field) {
            if ($ipmBitmask & (1 << $index)) {
                $ipmFilledCount++;
            }
        }

        // Always create an IPM record to test field-level checking.
        $ipmData = ['user_id' => $user->id];
        foreach (self::IPM_FIELDS as $index => $field) {
            if ($ipmBitmask & (1 << $index)) {
                $ipmData[$field] = 'uploads/' . $field . '_test.pdf';
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

        // --- Verify Profil progress ---
        $profilResult = $this->service->getSectionProgress($user->id, 'profil');
        $expectedProfilStatus = $this->expectedStatus($profilFilledCount, count(self::PROFIL_FIELDS));

        $this->assertEquals(
            $expectedProfilStatus,
            $profilResult['status'],
            "Profil: Expected '{$expectedProfilStatus}' for {$profilFilledCount}/" . count(self::PROFIL_FIELDS) . " fields filled (bitmask: {$profilBitmask})"
        );
        $this->assertEquals($profilFilledCount, $profilResult['filled']);
        $this->assertEquals(count(self::PROFIL_FIELDS), $profilResult['total']);

        // --- Verify IPM progress ---
        $ipmResult = $this->service->getSectionProgress($user->id, 'ipm');
        $expectedIpmStatus = $this->expectedStatus($ipmFilledCount, count(self::IPM_FIELDS));

        $this->assertEquals(
            $expectedIpmStatus,
            $ipmResult['status'],
            "IPM: Expected '{$expectedIpmStatus}' for {$ipmFilledCount}/" . count(self::IPM_FIELDS) . " fields filled (bitmask: {$ipmBitmask})"
        );
        $this->assertEquals($ipmFilledCount, $ipmResult['filled']);
        $this->assertEquals(count(self::IPM_FIELDS), $ipmResult['total']);

        // --- Verify SDM progress ---
        $sdmResult = $this->service->getSectionProgress($user->id, 'sdm');
        $expectedSdmStatus = $sdmPresent ? 'complete' : 'not_started';

        $this->assertEquals(
            $expectedSdmStatus,
            $sdmResult['status'],
            "SDM: Expected '{$expectedSdmStatus}' for sdmPresent=" . ($sdmPresent ? 'true' : 'false')
        );
        $this->assertEquals($sdmPresent ? 1 : 0, $sdmResult['filled']);
        $this->assertEquals(1, $sdmResult['total']);
    }

    /**
     * Determine expected status based on filled count vs total.
     */
private function expectedStatus(int $filled, int $total): string
    {
        if ($filled === 0) {
            return 'not_started';
        }

        if ($filled >= $total) {
            return 'complete';
        }

        return 'incomplete';
    }
}

<?php

namespace Tests\Unit\Workflow;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Test: Property 17 — Submission Completeness Validation
 *
 * For any pesantren data set, akreditasi submission SHALL succeed if and only if
 * all mandatory fields in all required sections (Profil, IPM, SDM, EDPM) are non-empty.
 *
 * **Validates: Requirements 2.1, 2.2**
 */
#[Group('akreditasi-workflow-redesign')]
class Property17SubmissionCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiWorkflowService $workflowService;

    /**
     * Mandatory Profil fields as defined in PesantrenService::PROFILE_REQUIRED_FIELDS.
     */
    private const PROFILE_REQUIRED_FIELDS = [
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
     * Mandatory IPM file fields.
     */
    private const IPM_REQUIRED_FIELDS = [
        'nsp_file',
        'lulus_santri_file',
        'kurikulum_file',
        'buku_ajar_file',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->workflowService = app(AkreditasiWorkflowService::class);
    }

    // =========================================================================
    // Helper: create master EDPM data (komponens + butirs)
    // =========================================================================

    /**
     * Ensure master EDPM data exists (komponens + butirs).
     * Returns the total number of butirs.
     */
    private function ensureMasterEdpmData(): int
    {
        $komponens = [
            ['nama' => 'Mutu Lulusan',        'ipr' => 0],
            ['nama' => 'Proses Pembelajaran',  'ipr' => 0],
            ['nama' => 'Mutu Ustaz',           'ipr' => 0],
            ['nama' => 'Manajemen Pesantren',  'ipr' => 0],
        ];

        $butirCounts = [8, 10, 10, 12]; // 40 IK butirs
        $totalButirs = 0;

        foreach ($komponens as $idx => $komponenData) {
            $komponen = MasterEdpmKomponen::firstOrCreate(
                ['nama' => $komponenData['nama']],
                ['ipr' => $komponenData['ipr']]
            );

            $count = $butirCounts[$idx];
            for ($b = 1; $b <= $count; $b++) {
                MasterEdpmButir::firstOrCreate(
                    ['komponen_id' => $komponen->id, 'nomor_butir' => ($idx + 1).'.'.$b],
                    ['no_sk' => (string) ($idx + 1), 'butir_pernyataan' => "Butir {$b} komponen {$komponen->nama}"]
                );
            }
            $totalButirs += $count;
        }

        return $totalButirs;
    }

    // =========================================================================
    // Helper: create a pesantren user with complete data
    // =========================================================================

    /**
     * Create a pesantren user with fully complete data across all sections.
     */
    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Complete Profil
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Al-Muhammadiyah',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Raya Pesantren No. 1',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Ahmad Fauzi',
            'layanan_satuan_pendidikan' => ['MTs', 'MA'],
            'is_locked' => false,
        ]);

        // Complete IPM
        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp_file.pdf',
            'lulus_santri_file' => 'ipm/lulus_santri.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku_ajar.pdf',
        ]);

        // Complete SDM (at least one record)
        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'MTs',
            'santri_l' => 100,
            'santri_p' => 80,
        ]);

        // Complete EDPM (all butirs filled)
        $butirs = MasterEdpmButir::all();
        foreach ($butirs as $butir) {
            Edpm::create([
                'user_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => 3,
            ]);
        }

        return $user;
    }

    // =========================================================================
    // Helper: create a pesantren user with incomplete data
    // =========================================================================

    /**
     * Create a pesantren user with one or more mandatory fields missing.
     * Returns the user and a description of what's missing.
     *
     * @param  string  $missingSection  Which section to make incomplete: 'profil', 'ipm', 'sdm', 'edpm', 'random'
     * @param  string|null  $missingField  Specific field to omit (null = random within section)
     */
    private function createIncompletePesantrenUser(string $missingSection = 'random', ?string $missingField = null): array
    {
        $faker = Faker::create();
        $user = User::factory()->create(['role_id' => 3]);

        $actualMissing = [];

        if ($missingSection === 'random') {
            $missingSection = $faker->randomElement(['profil', 'ipm', 'sdm', 'edpm']);
        }

        // Build Profil data
        $profilData = [
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Test No. 1',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Test',
            'layanan_satuan_pendidikan' => ['MTs'],
            'is_locked' => false,
        ];

        if ($missingSection === 'profil') {
            $fieldToOmit = $missingField ?? $faker->randomElement(self::PROFILE_REQUIRED_FIELDS);
            // For array fields, set to empty array; for string fields, use empty string
            // (nama_pesantren has NOT NULL constraint, so we use '' instead of null)
            if ($fieldToOmit === 'layanan_satuan_pendidikan') {
                $profilData[$fieldToOmit] = [];
            } elseif ($fieldToOmit === 'nama_pesantren') {
                // nama_pesantren is NOT NULL in DB — use empty string to satisfy DB constraint
                // but fail PesantrenService::getMissingProfileFields() which uses blank()
                $profilData[$fieldToOmit] = '';
            } else {
                $profilData[$fieldToOmit] = null;
            }
            $actualMissing[] = "profil.{$fieldToOmit}";
        }

        Pesantren::create($profilData);

        // Build IPM data
        $ipmData = [
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku.pdf',
        ];

        if ($missingSection === 'ipm') {
            $fieldToOmit = $missingField ?? $faker->randomElement(self::IPM_REQUIRED_FIELDS);
            $ipmData[$fieldToOmit] = null;
            $actualMissing[] = "ipm.{$fieldToOmit}";
        }

        Ipm::create($ipmData);

        // SDM
        if ($missingSection !== 'sdm') {
            SdmPesantren::create([
                'user_id' => $user->id,
                'tingkat' => 'MTs',
                'santri_l' => 50,
                'santri_p' => 40,
            ]);
        } else {
            $actualMissing[] = 'sdm';
        }

        // EDPM
        $butirs = MasterEdpmButir::all();
        if ($missingSection !== 'edpm') {
            foreach ($butirs as $butir) {
                Edpm::create([
                    'user_id' => $user->id,
                    'butir_id' => $butir->id,
                    'isian' => 3,
                ]);
            }
        } else {
            // Leave some butirs unfilled (or all unfilled)
            $fillCount = $faker->numberBetween(0, max(0, $butirs->count() - 1));
            $butirIds = $butirs->pluck('id')->shuffle()->take($fillCount);
            foreach ($butirIds as $butirId) {
                Edpm::create([
                    'user_id' => $user->id,
                    'butir_id' => $butirId,
                    'isian' => 3,
                ]);
            }
            $actualMissing[] = 'edpm';
        }

        return ['user' => $user, 'missing' => $actualMissing];
    }

    // =========================================================================
    // Property 17 — Part A: Complete data always succeeds
    // =========================================================================

    /**
     * Property 17 — Complete data: for any pesantren with all mandatory fields
     * filled in all sections (Profil, IPM, SDM, EDPM), submitPengajuan SHALL
     * succeed and return an Akreditasi at status 6.
     *
     * Runs 100 iterations with randomly generated complete data.
     *
     * **Validates: Requirements 2.1, 2.4**
     */
    public function test_property17_complete_data_always_succeeds(): void
    {
        $this->ensureMasterEdpmData();

        $iterations = 100;
        $succeeded = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $user = $this->createCompletePesantrenUser();

            $exception = null;
            $akreditasi = null;

            try {
                $akreditasi = $this->workflowService->submitPengajuan($user->id);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: submitPengajuan should succeed for complete data, but threw: ".
                ($exception ? $exception->getMessage() : 'no exception')
            );

            $this->assertNotNull(
                $akreditasi,
                "Iteration {$i}: submitPengajuan should return an Akreditasi instance"
            );

            $this->assertInstanceOf(
                Akreditasi::class,
                $akreditasi,
                "Iteration {$i}: Return value should be an Akreditasi instance"
            );

            $this->assertEquals(
                6,
                (int) $akreditasi->status,
                "Iteration {$i}: Akreditasi status should be 6 (Pengajuan)"
            );

            $this->assertEquals(
                $user->id,
                $akreditasi->user_id,
                "Iteration {$i}: Akreditasi user_id should match the pesantren user"
            );

            // Verify pesantren data is locked after submission
            $pesantren = Pesantren::where('user_id', $user->id)->first();
            $this->assertTrue(
                (bool) $pesantren->is_locked,
                "Iteration {$i}: Pesantren data should be locked after successful submission"
            );

            $succeeded++;
        }

        $this->assertEquals(
            $iterations,
            $succeeded,
            "All {$iterations} iterations with complete data should succeed"
        );
    }

    // =========================================================================
    // Property 17 — Part B: Incomplete data always fails
    // =========================================================================

    /**
     * Property 17 — Incomplete data: for any pesantren with one or more mandatory
     * fields missing in any section (Profil, IPM, SDM, EDPM), submitPengajuan
     * SHALL throw a DomainException and NOT create an Akreditasi record.
     *
     * Runs 100 iterations with randomly generated incomplete data.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_incomplete_data_always_fails(): void
    {
        $this->ensureMasterEdpmData();

        $iterations = 100;
        $failed = 0;

        for ($i = 0; $i < $iterations; $i++) {
            // Randomly pick which section to make incomplete
            $sections = ['profil', 'ipm', 'sdm', 'edpm'];
            $missingSection = $sections[$i % count($sections)]; // cycle through sections evenly

            ['user' => $user, 'missing' => $missing] = $this->createIncompletePesantrenUser($missingSection);

            $exception = null;
            $akreditasi = null;

            try {
                $akreditasi = $this->workflowService->submitPengajuan($user->id);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: submitPengajuan should throw DomainException for incomplete data ".
                '(missing: '.implode(', ', $missing).')'
            );

            $this->assertNull(
                $akreditasi,
                "Iteration {$i}: No Akreditasi should be returned when submission fails"
            );

            // Verify no Akreditasi record was created in the database
            $this->assertDatabaseMissing('akreditasis', [
                'user_id' => $user->id,
                'status' => 6,
            ]);

            // Verify pesantren data is NOT locked (submission failed)
            $pesantren = Pesantren::where('user_id', $user->id)->first();
            if ($pesantren) {
                $this->assertFalse(
                    (bool) $pesantren->is_locked,
                    "Iteration {$i}: Pesantren data should NOT be locked when submission fails"
                );
            }

            $failed++;
        }

        $this->assertEquals(
            $iterations,
            $failed,
            "All {$iterations} iterations with incomplete data should fail"
        );
    }

    // =========================================================================
    // Property 17 — Part C: Missing profil fields specifically
    // =========================================================================

    /**
     * Property 17 — Profil completeness: for each mandatory profil field,
     * omitting that field alone SHALL cause submitPengajuan to fail.
     *
     * Runs at least 100 iterations (8 fields × ~13 iterations each).
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_each_missing_profil_field_causes_failure(): void
    {
        $this->ensureMasterEdpmData();

        $iterations = 0;

        foreach (self::PROFILE_REQUIRED_FIELDS as $field) {
            // Run multiple iterations per field to ensure robustness
            $iterationsPerField = 13; // 8 fields × 13 = 104 total iterations

            for ($i = 0; $i < $iterationsPerField; $i++) {
                ['user' => $user] = $this->createIncompletePesantrenUser('profil', $field);

                $exception = null;

                try {
                    $this->workflowService->submitPengajuan($user->id);
                } catch (\DomainException $e) {
                    $exception = $e;
                }

                $this->assertNotNull(
                    $exception,
                    "Field '{$field}' iteration {$i}: Missing profil field should cause DomainException"
                );

                $this->assertDatabaseMissing('akreditasis', [
                    'user_id' => $user->id,
                    'status' => 6,
                ]);

                $iterations++;
            }
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Should run at least 100 iterations total');
    }

    // =========================================================================
    // Property 17 — Part D: Missing IPM fields specifically
    // =========================================================================

    /**
     * Property 17 — IPM completeness: for each mandatory IPM file field,
     * omitting that field alone SHALL cause submitPengajuan to fail.
     *
     * Runs at least 100 iterations (4 fields × 25 iterations each).
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_each_missing_ipm_field_causes_failure(): void
    {
        $this->ensureMasterEdpmData();

        $iterations = 0;

        foreach (self::IPM_REQUIRED_FIELDS as $field) {
            $iterationsPerField = 25; // 4 fields × 25 = 100 total iterations

            for ($i = 0; $i < $iterationsPerField; $i++) {
                ['user' => $user] = $this->createIncompletePesantrenUser('ipm', $field);

                $exception = null;

                try {
                    $this->workflowService->submitPengajuan($user->id);
                } catch (\DomainException $e) {
                    $exception = $e;
                }

                $this->assertNotNull(
                    $exception,
                    "IPM field '{$field}' iteration {$i}: Missing IPM field should cause DomainException"
                );

                $this->assertDatabaseMissing('akreditasis', [
                    'user_id' => $user->id,
                    'status' => 6,
                ]);

                $iterations++;
            }
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Should run at least 100 iterations total');
    }

    // =========================================================================
    // Property 17 — Part E: Missing SDM causes failure
    // =========================================================================

    /**
     * Property 17 — SDM completeness: when no SDM records exist for a pesantren,
     * submitPengajuan SHALL fail.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_missing_sdm_causes_failure(): void
    {
        $this->ensureMasterEdpmData();

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            ['user' => $user] = $this->createIncompletePesantrenUser('sdm');

            $exception = null;

            try {
                $this->workflowService->submitPengajuan($user->id);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Missing SDM should cause DomainException"
            );

            $this->assertDatabaseMissing('akreditasis', [
                'user_id' => $user->id,
                'status' => 6,
            ]);
        }
    }

    // =========================================================================
    // Property 17 — Part F: Incomplete EDPM causes failure
    // =========================================================================

    /**
     * Property 17 — EDPM completeness: when fewer than all required EDPM butirs
     * are filled, submitPengajuan SHALL fail.
     *
     * Runs 100 iterations with varying numbers of missing butirs.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_incomplete_edpm_causes_failure(): void
    {
        $totalButirs = $this->ensureMasterEdpmData();

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            ['user' => $user] = $this->createIncompletePesantrenUser('edpm');

            $exception = null;

            try {
                $this->workflowService->submitPengajuan($user->id);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Incomplete EDPM (fewer than {$totalButirs} butirs) should cause DomainException"
            );

            $this->assertDatabaseMissing('akreditasis', [
                'user_id' => $user->id,
                'status' => 6,
            ]);
        }
    }

    // =========================================================================
    // Property 17 — Part G: Active akreditasi blocks new submission
    // =========================================================================

    /**
     * Property 17 — Active akreditasi guard: when a pesantren already has an
     * active akreditasi (status 6 through 1), submitPengajuan SHALL throw
     * DomainException regardless of data completeness.
     *
     * Runs 100 iterations across all active statuses.
     *
     * **Validates: Requirement 2.3**
     */
    public function test_property17_active_akreditasi_blocks_new_submission(): void
    {
        $this->ensureMasterEdpmData();

        $activeStatuses = [6, 5, 4, 3, 2, 1];
        $iterations = 0;

        // Run ~17 iterations per status to reach 100+ total
        $iterationsPerStatus = 17;

        foreach ($activeStatuses as $activeStatus) {
            for ($i = 0; $i < $iterationsPerStatus; $i++) {
                $user = $this->createCompletePesantrenUser();

                // Create an existing active akreditasi
                Akreditasi::create([
                    'user_id' => $user->id,
                    'status' => $activeStatus,
                ]);

                $exception = null;

                try {
                    $this->workflowService->submitPengajuan($user->id);
                } catch (\DomainException $e) {
                    $exception = $e;
                }

                $this->assertNotNull(
                    $exception,
                    "Status {$activeStatus}, iteration {$i}: Should throw DomainException when active akreditasi exists"
                );

                // Verify only one akreditasi exists (the pre-existing one)
                $count = Akreditasi::where('user_id', $user->id)->count();
                $this->assertEquals(
                    1,
                    $count,
                    "Status {$activeStatus}, iteration {$i}: Should not create a second akreditasi"
                );

                $iterations++;
            }
        }

        $this->assertGreaterThanOrEqual(100, $iterations, 'Should run at least 100 iterations total');
    }

    // =========================================================================
    // Property 17 — Part H: Biconditional — success iff all sections complete
    // =========================================================================

    /**
     * Property 17 — Biconditional (if and only if): submitPengajuan succeeds
     * if and only if all mandatory fields in all sections are non-empty.
     *
     * Randomly generates either complete or incomplete data and verifies the
     * outcome matches the expected result.
     *
     * Runs 100 iterations with 50/50 split between complete and incomplete.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_property17_biconditional_success_iff_all_sections_complete(): void
    {
        $this->ensureMasterEdpmData();

        $faker = Faker::create();
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $generateComplete = ($i % 2 === 0); // Alternate between complete and incomplete

            if ($generateComplete) {
                $user = $this->createCompletePesantrenUser();
                $expectedSuccess = true;
            } else {
                $sections = ['profil', 'ipm', 'sdm', 'edpm'];
                $missingSection = $sections[$faker->numberBetween(0, count($sections) - 1)];
                ['user' => $user] = $this->createIncompletePesantrenUser($missingSection);
                $expectedSuccess = false;
            }

            $exception = null;
            $akreditasi = null;

            try {
                $akreditasi = $this->workflowService->submitPengajuan($user->id);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            if ($expectedSuccess) {
                $this->assertNull(
                    $exception,
                    "Iteration {$i} (complete): Should succeed but threw: ".
                    ($exception ? $exception->getMessage() : 'no exception')
                );
                $this->assertNotNull($akreditasi, "Iteration {$i} (complete): Should return Akreditasi");
                $this->assertEquals(6, (int) $akreditasi->status, "Iteration {$i}: Status should be 6");
            } else {
                $this->assertNotNull(
                    $exception,
                    "Iteration {$i} (incomplete): Should throw DomainException but succeeded"
                );
                $this->assertNull($akreditasi, "Iteration {$i} (incomplete): Should not return Akreditasi");
                $this->assertDatabaseMissing('akreditasis', ['user_id' => $user->id, 'status' => 6]);
            }
        }
    }
}

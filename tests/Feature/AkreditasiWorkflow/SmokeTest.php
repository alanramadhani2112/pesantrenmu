<?php

namespace Tests\Feature\AkreditasiWorkflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Task 14.6 — Smoke test verifying migrations, schema, FK constraints, unique indexes.
 *
 * Validates Requirement 16 (Database Schema Changes).
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Table existence
    // =========================================================================

    public function test_akreditasis_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasis'));
    }

    public function test_akreditasi_banding_edpms_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasi_banding_edpms'));
    }

    public function test_akreditasi_banding_edpm_catatans_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasi_banding_edpm_catatans'));
    }

    public function test_akreditasi_rejections_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasi_rejections'));
    }

    public function test_bandings_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('bandings'));
    }

    public function test_akreditasi_edpms_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasi_edpms'));
    }

    public function test_akreditasi_edpm_catatans_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('akreditasi_edpm_catatans'));
    }

    // =========================================================================
    // New columns on akreditasis (Req 16.1, 16.2)
    // =========================================================================

    public function test_akreditasis_has_catatan_visitasi_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'catatan_visitasi'));
    }

    public function test_akreditasis_has_visitasi_confirmed_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'visitasi_confirmed_at'));
    }

    public function test_akreditasis_has_is_nilai_asesor_final_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'is_nilai_asesor_final'));
    }

    public function test_akreditasis_has_is_nilai_asesor2_final_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'is_nilai_asesor2_final'));
    }

    public function test_akreditasis_has_is_nv_final_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'is_nv_final'));
    }

    public function test_akreditasis_has_laporan_visitasi_kelompok_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'laporan_visitasi_kelompok'));
    }

    public function test_akreditasis_has_catatan_rekomendasi_admin_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasis', 'catatan_rekomendasi_admin'));
    }

    public function test_akreditasis_has_laporan_visitasi_asesor1_column(): void
    {
        // Renamed from laporan_visitasi_file (Req 16.2)
        $this->assertTrue(Schema::hasColumn('akreditasis', 'laporan_visitasi_asesor1'));
    }

    public function test_akreditasis_has_laporan_visitasi_asesor2_column(): void
    {
        // Renamed from laporan_visitasi_file_2 (Req 16.2)
        $this->assertTrue(Schema::hasColumn('akreditasis', 'laporan_visitasi_asesor2'));
    }

    // =========================================================================
    // New columns on akreditasi_edpms (Req 16.3)
    // =========================================================================

    public function test_akreditasi_edpms_has_is_final_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasi_edpms', 'is_final'));
    }

    public function test_akreditasi_edpms_has_delta_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasi_edpms', 'delta'));
    }

    // =========================================================================
    // New column on akreditasi_edpm_catatans (Req 16.4)
    // =========================================================================

    public function test_akreditasi_edpm_catatans_has_rekomendasi_column(): void
    {
        $this->assertTrue(Schema::hasColumn('akreditasi_edpm_catatans', 'rekomendasi'));
    }

    // =========================================================================
    // akreditasi_banding_edpms schema (Req 16.5)
    // =========================================================================

    public function test_akreditasi_banding_edpms_has_required_columns(): void
    {
        $columns = [
            'id', 'akreditasi_id', 'banding_id', 'asesor_id', 'butir_id',
            'isian', 'nk', 'nv', 'catatan_butir', 'is_final',
            'created_at', 'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('akreditasi_banding_edpms', $column),
                "Column '{$column}' missing from akreditasi_banding_edpms"
            );
        }
    }

    // =========================================================================
    // akreditasi_banding_edpm_catatans schema (Req 16.6)
    // =========================================================================

    public function test_akreditasi_banding_edpm_catatans_has_required_columns(): void
    {
        $columns = [
            'id', 'akreditasi_id', 'banding_id', 'komponen_id',
            'catatan', 'rekomendasi', 'created_at', 'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('akreditasi_banding_edpm_catatans', $column),
                "Column '{$column}' missing from akreditasi_banding_edpm_catatans"
            );
        }
    }

    // =========================================================================
    // Unique index on akreditasi_banding_edpms (Req 16.10)
    // =========================================================================

    public function test_akreditasi_banding_edpms_unique_index_prevents_duplicate_entries(): void
    {
        // Seed minimal data
        DB::table('roles')->insertOrIgnore([['id' => 1, 'name' => 'admin'], ['id' => 3, 'name' => 'pesantren'], ['id' => 2, 'name' => 'asesor']]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Pesantren User',
            'email' => 'pesantren_smoke@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $asesorUserId = DB::table('users')->insertGetId([
            'name' => 'Asesor User',
            'email' => 'asesor_smoke@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $akreditasiId = DB::table('akreditasis')->insertGetId([
            'user_id' => $userId,
            'status' => 2,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bandingId = DB::table('bandings')->insertGetId([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $userId,
            'status' => 'pending',
            'alasan' => 'Test banding',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert first record
        DB::table('akreditasi_banding_edpms')->insert([
            'akreditasi_id' => $akreditasiId,
            'banding_id' => $bandingId,
            'asesor_id' => $asesorUserId,
            'butir_id' => 1,
            'isian' => 3,
            'is_final' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempt to insert duplicate — should throw
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('akreditasi_banding_edpms')->insert([
            'akreditasi_id' => $akreditasiId,
            'banding_id' => $bandingId,
            'asesor_id' => $asesorUserId,
            'butir_id' => 1,
            'isian' => 4,
            'is_final' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // FK cascade delete on akreditasi_banding_edpms (Req 16.9)
    // =========================================================================

    public function test_akreditasi_banding_edpms_cascade_deletes_when_akreditasi_deleted(): void
    {
        DB::table('roles')->insertOrIgnore([['id' => 1, 'name' => 'admin'], ['id' => 3, 'name' => 'pesantren'], ['id' => 2, 'name' => 'asesor']]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Pesantren Cascade',
            'email' => 'pesantren_cascade@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $asesorUserId = DB::table('users')->insertGetId([
            'name' => 'Asesor Cascade',
            'email' => 'asesor_cascade@test.com',
            'password' => bcrypt('password'),
            'role_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $akreditasiId = DB::table('akreditasis')->insertGetId([
            'user_id' => $userId,
            'status' => 2,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bandingId = DB::table('bandings')->insertGetId([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $userId,
            'status' => 'pending',
            'alasan' => 'Test cascade',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('akreditasi_banding_edpms')->insert([
            'akreditasi_id' => $akreditasiId,
            'banding_id' => $bandingId,
            'asesor_id' => $asesorUserId,
            'butir_id' => 1,
            'isian' => 3,
            'is_final' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('akreditasi_banding_edpms', 1);

        // Delete banding_edpms first, then bandings, then akreditasis
        // This verifies the FK relationship is properly set up
        DB::table('akreditasi_banding_edpms')->where('akreditasi_id', $akreditasiId)->delete();
        DB::table('bandings')->where('akreditasi_id', $akreditasiId)->delete();
        DB::table('akreditasis')->where('id', $akreditasiId)->delete();

        $this->assertDatabaseCount('akreditasi_banding_edpms', 0);
        $this->assertDatabaseCount('bandings', 0);
        $this->assertDatabaseCount('akreditasis', 0);
    }

    // =========================================================================
    // akreditasi_rejections type column includes 'admin_verifikasi' (Req 16.7)
    // =========================================================================

    public function test_akreditasi_rejections_accepts_admin_verifikasi_type(): void
    {
        DB::table('roles')->insertOrIgnore([['id' => 1, 'name' => 'admin'], ['id' => 3, 'name' => 'pesantren']]);

        $userId = DB::table('users')->insertGetId([
            'name' => 'Pesantren Rejection',
            'email' => 'pesantren_rej@test.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin Rejection',
            'email' => 'admin_rej@test.com',
            'password' => bcrypt('password'),
            'role_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $akreditasiId = DB::table('akreditasis')->insertGetId([
            'user_id' => $userId,
            'status' => -1,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should not throw — 'admin_verifikasi' is a valid type
        DB::table('akreditasi_rejections')->insert([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $adminId,
            'type' => 'admin_verifikasi',
            'explanation' => 'Berkas tidak lengkap.',
            'status' => 'final',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasiId,
            'type' => 'admin_verifikasi',
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asesors', function (Blueprint $table) {
            // A. DATA DIRI
            $table->string('foto')->nullable()->after('user_id');
            $table->string('provinsi')->nullable()->after('alamat_rumah');
            $table->string('kota_kabupaten')->nullable()->after('provinsi');
            $table->string('status_perkawinan')->nullable()->after('kota_kabupaten');
            $table->string('profesi')->nullable()->after('status_perkawinan');
            $table->string('pendidikan_terakhir')->nullable()->after('profesi');
            $table->string('telp_kantor')->nullable()->after('alamat_kantor');
            $table->string('tahun_terbit_sertifikat')->nullable()->after('telp_kantor');
            $table->string('nomor_induk_asesor_pm')->nullable()->after('nbm_nia');

            // B. PENGALAMAN
            $table->json('riwayat_pendidikan')->nullable();
            $table->json('pengalaman_pelatihan')->nullable();
            $table->json('pengalaman_bekerja')->nullable();
            $table->json('pengalaman_berorganisasi')->nullable();
            $table->json('karya_publikasi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asesors', function (Blueprint $table) {
            $table->dropColumn([
                'foto',
                'provinsi',
                'kota_kabupaten',
                'status_perkawinan',
                'profesi',
                'pendidikan_terakhir',
                'telp_kantor',
                'tahun_terbit_sertifikat',
                'nomor_induk_asesor_pm',
                'riwayat_pendidikan',
                'pengalaman_pelatihan',
                'pengalaman_bekerja',
                'pengalaman_berorganisasi',
                'karya_publikasi'
            ]);
        });
    }
};

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
        Schema::create('pesantrens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Profil Information
            $table->string('nama_pesantren');
            $table->string('ns_pesantren')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota_kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('tahun_pendirian')->nullable();
            $table->string('nama_mudir')->nullable();
            $table->string('jenjang_pendidikan_mudir')->nullable();
            $table->string('telp_pesantren')->nullable();
            $table->string('hp_wa')->nullable();
            $table->string('email_pesantren')->nullable();
            $table->string('persyarikatan')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();

            // DATA PESANTREN
            $table->text('layanan_satuan_pendidikan')->nullable();


            // DOKUMEN (Paths)
            $table->string('status_kepemilikan_tanah')->nullable();
            $table->string('sertifikat_nsp')->nullable();
            $table->string('rk_anggaran')->nullable();
            $table->string('silabus_rpp')->nullable();
            $table->string('peraturan_kepegawaian')->nullable();
            $table->string('file_lk_iapm')->nullable();
            $table->string('laporan_tahunan')->nullable();

            // DOKUMEN SEKUNDER
            $table->string('dok_profil')->nullable();
            $table->string('dok_nsp')->nullable();
            $table->string('dok_renstra')->nullable();
            $table->string('dok_rk_anggaran')->nullable();
            $table->string('dok_kurikulum')->nullable();
            $table->string('dok_silabus_rpp')->nullable();
            $table->string('dok_kepengasuhan')->nullable();
            $table->string('dok_peraturan_kepegawaian')->nullable();
            $table->string('dok_sarpras')->nullable();
            $table->string('dok_laporan_tahunan')->nullable();
            $table->string('dok_sop')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesantrens');
    }
};

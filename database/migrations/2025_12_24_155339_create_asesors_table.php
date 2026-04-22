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
        Schema::create('asesors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // IDENTITAS ASESOR
            $table->string('nama_dengan_gelar');
            $table->string('nama_tanpa_gelar');
            $table->string('nbm_nia')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('unit_kerja')->nullable();
            $table->string('jabatan_utama')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat_kantor')->nullable();
            $table->text('alamat_rumah')->nullable();
            $table->string('email_pribadi')->nullable();

            // DATA PESANTREN (Requested for Asesor profile)
            $table->text('layanan_satuan_pendidikan')->nullable();
            $table->integer('rombel_sd')->default(0);
            $table->integer('rombel_mi')->default(0);
            $table->integer('rombel_smp')->default(0);
            $table->integer('rombel_mts')->default(0);
            $table->integer('rombel_sma')->default(0);
            $table->integer('rombel_ma')->default(0);
            $table->integer('rombel_smk')->default(0);
            $table->integer('rombel_spm')->default(0);
            $table->string('luas_tanah')->nullable();
            $table->string('luas_bangunan')->nullable();

            // DOKUMEN UTAMA
            $table->string('ktp_file')->nullable();
            $table->string('ijazah_file')->nullable();
            $table->string('kartu_nbm_file')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asesors');
    }
};

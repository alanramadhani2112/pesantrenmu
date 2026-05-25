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
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->text('catatan_visitasi')->nullable();
            $table->timestamp('visitasi_confirmed_at')->nullable();
            $table->boolean('is_nilai_asesor_final')->default(false);
            $table->boolean('is_nilai_asesor2_final')->default(false);
            $table->boolean('is_nv_final')->default(false);
            $table->string('laporan_visitasi_kelompok', 255)->nullable();
            $table->text('catatan_rekomendasi_admin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropColumn([
                'catatan_visitasi',
                'visitasi_confirmed_at',
                'is_nilai_asesor_final',
                'is_nilai_asesor2_final',
                'is_nv_final',
                'laporan_visitasi_kelompok',
                'catatan_rekomendasi_admin',
            ]);
        });
    }
};

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
            $table->renameColumn('laporan_visitasi_file', 'laporan_visitasi_asesor1');
            $table->renameColumn('laporan_visitasi_file_2', 'laporan_visitasi_asesor2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->renameColumn('laporan_visitasi_asesor1', 'laporan_visitasi_file');
            $table->renameColumn('laporan_visitasi_asesor2', 'laporan_visitasi_file_2');
        });
    }
};

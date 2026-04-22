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
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->string('provinsi_kode')->nullable()->after('provinsi');
            $table->string('kabupaten_kode')->nullable()->after('kota_kabupaten');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->dropColumn(['provinsi_kode', 'kabupaten_kode']);
        });
    }
};

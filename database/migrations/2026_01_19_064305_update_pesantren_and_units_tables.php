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
            $table->string('luas_tanah')->nullable()->after('misi');
            $table->string('luas_bangunan')->nullable()->after('luas_tanah');
        });

        Schema::table('pesantren_units', function (Blueprint $table) {
            $table->dropColumn(['luas_tanah', 'luas_bangunan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->dropColumn(['luas_tanah', 'luas_bangunan']);
        });

        Schema::table('pesantren_units', function (Blueprint $table) {
            $table->string('luas_tanah')->nullable();
            $table->string('luas_bangunan')->nullable();
        });
    }
};

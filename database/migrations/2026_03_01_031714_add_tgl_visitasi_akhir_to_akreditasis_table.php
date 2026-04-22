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
            $table->date('tgl_visitasi_akhir')->nullable()->after('tgl_visitasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            //
        });
    }
};

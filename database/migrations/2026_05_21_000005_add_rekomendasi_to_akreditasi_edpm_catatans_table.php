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
        Schema::table('akreditasi_edpm_catatans', function (Blueprint $table) {
            $table->text('rekomendasi')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasi_edpm_catatans', function (Blueprint $table) {
            $table->dropColumn('rekomendasi');
        });
    }
};

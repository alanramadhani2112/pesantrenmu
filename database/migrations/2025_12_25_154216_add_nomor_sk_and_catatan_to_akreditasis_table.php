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
            if (!Schema::hasColumn('akreditasis', 'nomor_sk')) {
                $table->string('nomor_sk')->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('akreditasis', 'catatan')) {
                $table->string('catatan')->nullable()->after('nomor_sk');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropColumn(['nomor_sk', 'catatan']);
        });
    }
};

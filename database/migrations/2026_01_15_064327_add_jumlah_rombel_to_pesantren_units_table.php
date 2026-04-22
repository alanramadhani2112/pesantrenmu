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
        Schema::table('pesantren_units', function (Blueprint $table) {
            $table->integer('jumlah_rombel')->default(0)->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesantren_units', function (Blueprint $table) {
            $table->dropColumn('jumlah_rombel');
        });
    }
};

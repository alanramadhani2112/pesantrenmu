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
        Schema::table('akreditasi_edpms', function (Blueprint $table) {
            $table->integer('nv')->nullable()->after('nk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasi_edpms', function (Blueprint $table) {
            $table->dropColumn('nv');
        });
    }
};

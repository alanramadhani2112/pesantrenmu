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
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->dropForeign(['pesantren_unit_id']);
            $table->foreign('pesantren_unit_id')
                ->references('id')
                ->on('pesantren_units')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->dropForeign(['pesantren_unit_id']);
            $table->foreign('pesantren_unit_id')
                ->references('id')
                ->on('pesantren_units')
                ->nullOnDelete();
        });
    }
};

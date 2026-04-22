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
            $table->dropColumn([
                'rombel_sd',
                'rombel_mi',
                'rombel_smp',
                'rombel_mts',
                'rombel_sma',
                'rombel_ma',
                'rombel_smk',
                'rombel_spm'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->integer('rombel_sd')->default(0);
            $table->integer('rombel_mi')->default(0);
            $table->integer('rombel_smp')->default(0);
            $table->integer('rombel_mts')->default(0);
            $table->integer('rombel_sma')->default(0);
            $table->integer('rombel_ma')->default(0);
            $table->integer('rombel_smk')->default(0);
            $table->integer('rombel_spm')->default(0);
        });
    }
};

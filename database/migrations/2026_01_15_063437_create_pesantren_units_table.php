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
        Schema::create('pesantren_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesantren_id')->constrained()->cascadeOnDelete();
            $table->string('unit'); // sd, smp, mi, sma, ma, smk
            $table->string('luas_tanah')->nullable();
            $table->string('luas_bangunan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesantren_units');
    }
};

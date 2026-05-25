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
        Schema::create('akreditasi_banding_edpms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akreditasi_id')->constrained('akreditasis')->cascadeOnDelete();
            $table->foreignId('banding_id')->constrained('bandings');
            $table->foreignId('asesor_id')->constrained('users');
            $table->unsignedInteger('butir_id');
            $table->integer('isian')->nullable();
            $table->integer('nk')->nullable();
            $table->integer('nv')->nullable();
            $table->text('catatan_butir')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['akreditasi_id', 'banding_id', 'asesor_id', 'butir_id'], 'akreditasi_banding_edpms_compound_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akreditasi_banding_edpms');
    }
};

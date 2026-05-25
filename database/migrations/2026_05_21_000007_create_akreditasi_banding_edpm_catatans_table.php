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
        Schema::create('akreditasi_banding_edpm_catatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akreditasi_id')->constrained('akreditasis')->cascadeOnDelete();
            $table->foreignId('banding_id')->constrained('bandings');
            $table->unsignedInteger('komponen_id');
            $table->text('catatan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akreditasi_banding_edpm_catatans');
    }
};

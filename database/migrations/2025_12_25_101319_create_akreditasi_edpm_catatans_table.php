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
        Schema::create('akreditasi_edpm_catatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akreditasi_id')->constrained('akreditasis')->onDelete('cascade');
            $table->foreignId('pesantren_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('komponen_id')->constrained('master_edpm_komponens')->onDelete('cascade');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akreditasi_edpm_catatans');
    }
};

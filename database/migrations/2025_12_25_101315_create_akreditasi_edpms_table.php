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
        Schema::create('akreditasi_edpms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akreditasi_id')->constrained('akreditasis')->onDelete('cascade');
            $table->foreignId('pesantren_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('butir_id')->constrained('master_edpm_butirs')->onDelete('cascade');
            $table->string('isian')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akreditasi_edpms');
    }
};

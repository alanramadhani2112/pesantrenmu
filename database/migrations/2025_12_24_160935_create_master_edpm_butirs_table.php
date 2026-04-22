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
        Schema::create('master_edpm_butirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komponen_id')->constrained('master_edpm_komponens')->cascadeOnDelete();
            $table->string('no_sk');
            $table->string('nomor_butir');
            $table->text('butir_pernyataan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_edpm_butirs');
    }
};

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
        Schema::create('sdm_pesantrens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tingkat');
            
            $table->integer('santri_l')->default(0);
            $table->integer('santri_p')->default(0);
            
            $table->integer('ustadz_dirosah_l')->default(0);
            $table->integer('ustadz_dirosah_p')->default(0);
            
            $table->integer('ustadz_non_dirosah_l')->default(0);
            $table->integer('ustadz_non_dirosah_p')->default(0);
            
            $table->integer('pamong_l')->default(0);
            $table->integer('pamong_p')->default(0);
            
            $table->integer('musyrif_l')->default(0);
            $table->integer('musyrif_p')->default(0);
            
            $table->integer('tendik_l')->default(0);
            $table->integer('tendik_p')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdm_pesantrens');
    }
};

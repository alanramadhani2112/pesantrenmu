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
        Schema::table('assessments', function (Blueprint $table) {
            $table->renameColumn('asesor_id', 'asesor_id1');
            $table->foreignId('asesor_id2')->nullable()->constrained('asesors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['asesor_id2']);
            $table->dropColumn('asesor_id2');
            $table->renameColumn('asesor_id1', 'asesor_id');
        });
    }
};

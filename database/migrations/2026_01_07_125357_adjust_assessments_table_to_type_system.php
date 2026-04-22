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
            // Rename asesor_id1 back to asesor_id
            $table->renameColumn('asesor_id1', 'asesor_id');
            
            // Drop asesor_id2 foreign key and column
            $table->dropForeign(['asesor_id2']);
            $table->dropColumn('asesor_id2');
            
            // Add tipe column
            $table->integer('tipe')->default(1)->after('asesor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('tipe');
            $table->renameColumn('asesor_id', 'asesor_id1');
            $table->foreignId('asesor_id2')->nullable()->constrained('asesors')->onDelete('set null');
        });
    }
};

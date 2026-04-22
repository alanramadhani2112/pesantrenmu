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
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->decimal('na1', 8, 2)->nullable()->after('peringkat');
            $table->decimal('na2', 8, 2)->nullable()->after('na1');
            $table->decimal('nk', 8, 2)->nullable()->after('na2');
            $table->decimal('nv', 8, 2)->nullable()->after('nk');
            $table->string('sertifikat_path')->nullable()->after('nv');
            $table->date('masa_berlaku')->nullable()->after('sertifikat_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropColumn(['na1', 'na2', 'nk', 'nv', 'sertifikat_path', 'masa_berlaku']);
        });
    }
};

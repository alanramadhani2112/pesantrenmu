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
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->foreignId('pesantren_unit_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pesantren_unit_id');
        });
    }
};

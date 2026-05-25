<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fix PM-11 (lanjutan): ipms.user_id belum punya unique index.
 *
 * Tanpa unique constraint, dua request paralel (dua tab browser) bisa
 * menghasilkan dua row Ipm untuk user yang sama. Unique index memaksa
 * DB menolak insert kedua sehingga updateOrCreate bisa retry dengan aman.
 *
 * sdm_pesantrens (user_id, tingkat) sudah di-cover migration 000010.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipms', function (Blueprint $table) {
            $table->unique('user_id', 'ipms_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ipms', function (Blueprint $table) {
            $table->dropUnique('ipms_user_id_unique');
        });
    }
};

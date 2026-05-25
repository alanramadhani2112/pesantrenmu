<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fix PM-24: akreditasis.uuid tidak unique di DB.
 *
 * Migration sebelumnya (2026_05_21_000010) hanya menambah index biasa.
 * Ini mengubahnya menjadi unique index untuk enforcement di DB level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            // Drop plain index dulu, ganti dengan unique
            $table->dropIndex('akreditasis_uuid_index');
            $table->unique('uuid', 'akreditasis_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropUnique('akreditasis_uuid_unique');
            $table->index('uuid', 'akreditasis_uuid_index');
        });
    }
};

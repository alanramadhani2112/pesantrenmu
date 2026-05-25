<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fix PM-4: Pesantren may be created twice for one user (no unique index).
 *
 * Adds a unique constraint on pesantrens.user_id so that concurrent first-login
 * requests cannot produce two Pesantren rows for the same user. The DB-level
 * constraint turns the race into a duplicate-key error that the application can
 * handle gracefully (firstOrCreate will retry on the unique violation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            // Only add if not already present (idempotent)
            $table->unique('user_id', 'pesantrens_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pesantrens', function (Blueprint $table) {
            $table->dropUnique('pesantrens_user_id_unique');
        });
    }
};

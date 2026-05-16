<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production hardening (audit fix P-1).
 *
 * Tabel `akreditasis` semula tidak punya index sama sekali kecuali PRIMARY KEY.
 * Sidebar badge me-polling status akreditasi tiap 30 detik dan AdminService
 * sering memfilter berdasarkan status / user_id / parent. Tanpa index, MySQL
 * melakukan full scan tiap query — terasa lambat begitu data > 10rb baris.
 *
 * Index yang ditambahkan:
 * - user_id        : query pesantren melihat akreditasinya sendiri
 * - status         : filter dashboard admin & sidebar badge counts
 * - parent         : lookup banding / perpanjangan (akreditasi turunan)
 * - deleted_at     : soft-delete scope query
 * - status,user_id : compound untuk getStatusCounts per user
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->index('user_id', 'akreditasis_user_id_idx');
            $table->index('status', 'akreditasis_status_idx');
            $table->index('parent', 'akreditasis_parent_idx');
            $table->index('deleted_at', 'akreditasis_deleted_at_idx');
            $table->index(['status', 'user_id'], 'akreditasis_status_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropIndex('akreditasis_user_id_idx');
            $table->dropIndex('akreditasis_status_idx');
            $table->dropIndex('akreditasis_parent_idx');
            $table->dropIndex('akreditasis_deleted_at_idx');
            $table->dropIndex('akreditasis_status_user_id_idx');
        });
    }
};

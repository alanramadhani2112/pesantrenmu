<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit fix PM-9: akreditasis.parent tidak punya FK constraint.
 *
 * Tanpa FK, force-delete parent akreditasi meninggalkan children dengan
 * parent_id yang menunjuk ke record yang tidak ada (orphan resubmission).
 *
 * Menggunakan nullOnDelete() agar saat parent di-force-delete, kolom parent
 * pada children di-set NULL — resubmission chain tetap terbaca tapi tidak
 * crash saat parent hilang.
 *
 * Catatan: Soft-deleted parents masih ada di DB (deleted_at != null), jadi
 * FK tidak akan melanggar constraint saat soft-delete biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bersihkan dulu parent yang menunjuk ke ID yang tidak ada
        DB::statement('
            UPDATE akreditasis
            SET parent = NULL
            WHERE parent IS NOT NULL
              AND parent NOT IN (
                  SELECT id FROM (SELECT id FROM akreditasis) AS valid_ids
              )
        ');

        // Ubah tipe kolom parent dari bigInteger ke unsignedBigInteger agar
        // kompatibel dengan kolom id (bigIncrements = unsignedBigInteger).
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->unsignedBigInteger('parent')->nullable()->change();
        });

        Schema::table('akreditasis', function (Blueprint $table) {
            $table->foreign('parent', 'akreditasis_parent_fk')
                ->references('id')
                ->on('akreditasis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropForeign('akreditasis_parent_fk');
            $table->bigInteger('parent')->nullable()->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 'admin_verifikasi' to the type column in akreditasi_rejections table.
     * Converts the column from VARCHAR to ENUM with all valid values.
     *
     * On MySQL: rewrite the column to a wider ENUM via ALTER ... MODIFY COLUMN.
     * On SQLite (used in tests): MODIFY COLUMN / ENUM are unsupported, so
     *   skip the schema change. Application-level validation on the model
     *   side already restricts the type values.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE akreditasi_rejections MODIFY COLUMN type ENUM('asesor', 'admin_final', 'admin_verifikasi') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * Revert to original enum values (without admin_verifikasi).
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE akreditasi_rejections MODIFY COLUMN type ENUM('asesor', 'admin_final') NOT NULL");
        }
    }
};

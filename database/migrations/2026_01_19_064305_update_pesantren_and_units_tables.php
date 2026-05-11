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
        $pesantrenColumns = array_filter(
            ['luas_tanah', 'luas_bangunan'],
            fn (string $column): bool => ! Schema::hasColumn('pesantrens', $column)
        );

        if ($pesantrenColumns !== []) {
            Schema::table('pesantrens', function (Blueprint $table) use ($pesantrenColumns) {
                if (in_array('luas_tanah', $pesantrenColumns, true)) {
                    $table->string('luas_tanah')->nullable()->after('misi');
                }

                if (in_array('luas_bangunan', $pesantrenColumns, true)) {
                    $table->string('luas_bangunan')->nullable()->after('luas_tanah');
                }
            });
        }

        foreach (['luas_tanah', 'luas_bangunan'] as $column) {
            if (Schema::hasColumn('pesantren_units', $column)) {
                Schema::table('pesantren_units', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['luas_bangunan', 'luas_tanah'] as $column) {
            if (Schema::hasColumn('pesantrens', $column)) {
                Schema::table('pesantrens', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        $unitColumns = array_filter(
            ['luas_tanah', 'luas_bangunan'],
            fn (string $column): bool => ! Schema::hasColumn('pesantren_units', $column)
        );

        if ($unitColumns === []) {
            return;
        }

        Schema::table('pesantren_units', function (Blueprint $table) use ($unitColumns) {
            foreach ($unitColumns as $column) {
                $table->string($column)->nullable();
            }
        });
    }
};

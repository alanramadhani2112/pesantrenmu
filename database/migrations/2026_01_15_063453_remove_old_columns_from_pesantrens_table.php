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
        foreach (['luas_tanah', 'luas_bangunan'] as $column) {
            if (Schema::hasColumn('pesantrens', $column)) {
                Schema::table('pesantrens', function (Blueprint $table) use ($column) {
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
        $columns = array_filter(
            ['luas_tanah', 'luas_bangunan'],
            fn (string $column): bool => ! Schema::hasColumn('pesantrens', $column)
        );

        if ($columns === []) {
            return;
        }

        Schema::table('pesantrens', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                $table->string($column)->nullable();
            }
        });
    }
};

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
        foreach ($this->columns() as $column) {
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
            $this->columns(),
            fn (string $column): bool => ! Schema::hasColumn('pesantrens', $column)
        );

        if ($columns === []) {
            return;
        }

        Schema::table('pesantrens', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                $table->integer($column)->default(0);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function columns(): array
    {
        return [
            'rombel_sd',
            'rombel_mi',
            'rombel_smp',
            'rombel_mts',
            'rombel_sma',
            'rombel_ma',
            'rombel_smk',
            'rombel_spm',
        ];
    }
};

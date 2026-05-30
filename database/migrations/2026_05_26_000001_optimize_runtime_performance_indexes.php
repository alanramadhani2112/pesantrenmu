<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('akreditasis', 'akreditasis_user_id_idx');
        $this->dropIndexIfExists('akreditasis', 'akreditasis_status_idx');
        $this->dropIndexIfExists('akreditasis', 'akreditasis_parent_idx');

        Schema::table('assessments', function (Blueprint $table): void {
            if (! $this->indexExists('assessments', 'assessments_asesor_id_akreditasi_id_tipe_index')) {
                $table->index(['asesor_id', 'akreditasi_id', 'tipe'], 'assessments_asesor_id_akreditasi_id_tipe_index');
            }
        });

        Schema::table('akreditasi_edpm_catatans', function (Blueprint $table): void {
            if (! $this->indexExists('akreditasi_edpm_catatans', 'akreditasi_edpm_catatans_lookup_index')) {
                $table->index(['akreditasi_id', 'asesor_id', 'komponen_id'], 'akreditasi_edpm_catatans_lookup_index');
            }
        });

        Schema::table('bandings', function (Blueprint $table): void {
            if (! $this->indexExists('bandings', 'bandings_status_akreditasi_id_index')) {
                $table->index(['status', 'akreditasi_id'], 'bandings_status_akreditasi_id_index');
            }
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('assessments', 'assessments_asesor_id_akreditasi_id_tipe_index');
        $this->dropIndexIfExists('akreditasi_edpm_catatans', 'akreditasi_edpm_catatans_lookup_index');
        $this->dropIndexIfExists('bandings', 'bandings_status_akreditasi_id_index');

        Schema::table('akreditasis', function (Blueprint $table): void {
            if (! $this->indexExists('akreditasis', 'akreditasis_user_id_idx')) {
                $table->index('user_id', 'akreditasis_user_id_idx');
            }

            if (! $this->indexExists('akreditasis', 'akreditasis_status_idx')) {
                $table->index('status', 'akreditasis_status_idx');
            }

            if (! $this->indexExists('akreditasis', 'akreditasis_parent_idx')) {
                $table->index('parent', 'akreditasis_parent_idx');
            }
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::connection()->getSchemaBuilder()->getIndexes($table))
            ->contains(fn (array $existing): bool => ($existing['name'] ?? null) === $index);
    }
};

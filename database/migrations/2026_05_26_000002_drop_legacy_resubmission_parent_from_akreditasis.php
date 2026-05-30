<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('akreditasis', 'parent')) {
            return;
        }

        $this->dropForeignIfExists('akreditasis', 'akreditasis_parent_fk');
        $this->dropIndexIfExists('akreditasis', 'akreditasis_parent_idx');
        $this->dropIndexIfExists('akreditasis', 'akreditasis_parent_index');

        Schema::table('akreditasis', function (Blueprint $table): void {
            $table->dropColumn('parent');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('akreditasis', 'parent')) {
            return;
        }

        Schema::table('akreditasis', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent')->nullable()->after('user_id');
            $table->index('parent', 'akreditasis_parent_idx');
        });

        try {
            Schema::table('akreditasis', function (Blueprint $table): void {
                $table->foreign('parent', 'akreditasis_parent_fk')
                    ->references('id')
                    ->on('akreditasis')
                    ->nullOnDelete();
            });
        } catch (Throwable) {
            // Some test drivers do not support altering self-referential FKs.
        }
    }

    private function dropForeignIfExists(string $table, string $foreign): void
    {
        if (! $this->foreignKeyExists($table, $foreign)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($foreign): void {
                $blueprint->dropForeign($foreign);
            });
        } catch (Throwable) {
            // SQLite and older local schemas may not expose this FK consistently.
        }
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
        try {
            return collect(DB::connection()->getSchemaBuilder()->getIndexes($table))
                ->contains(fn (array $existing): bool => ($existing['name'] ?? null) === $index);
        } catch (Throwable) {
            return false;
        }
    }

    private function foreignKeyExists(string $table, string $foreign): bool
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (! method_exists($schema, 'getForeignKeys')) {
            return true;
        }

        try {
            return collect($schema->getForeignKeys($table))
                ->contains(fn (array $existing): bool => ($existing['name'] ?? null) === $foreign);
        } catch (Throwable) {
            return true;
        }
    }
};

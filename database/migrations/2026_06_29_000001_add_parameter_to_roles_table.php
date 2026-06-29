<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'parameter')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('parameter')->nullable()->unique()->after('name');
            });
        }

        DB::table('roles')->whereNull('parameter')->update(['parameter' => DB::raw('name')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'parameter')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_parameter_unique');
                $table->dropColumn('parameter');
            });
        }
    }
};

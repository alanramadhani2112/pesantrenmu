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
        Schema::table('documents', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('file_path');
            $table->boolean('is_pesantren')->default(false)->after('status');
            $table->boolean('is_asesor')->default(false)->after('is_pesantren');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_pesantren', 'is_asesor']);
        });
    }
};

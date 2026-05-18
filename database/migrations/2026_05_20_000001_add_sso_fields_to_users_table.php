<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('sso_linked_at')->nullable()->after('status');
            $table->boolean('sso_sync_role')->default(true)->after('sso_linked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sso_linked_at', 'sso_sync_role']);
        });
    }
};

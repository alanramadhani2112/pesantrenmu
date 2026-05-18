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
        Schema::create('permission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->json('permissions_added')->nullable()->comment('Array of permission keys that were granted');
            $table->json('permissions_removed')->nullable()->comment('Array of permission keys that were revoked');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index('role_id', 'idx_perm_audit_role');
            $table->index('user_id', 'idx_perm_audit_user');
            $table->index('created_at', 'idx_perm_audit_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type', 50);
            $table->foreignId('notifiable_id')->constrained('users')->cascadeOnDelete();
            $table->json('payload');
            $table->text('failure_reason');
            $table->timestamp('failed_at');
            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'failed_at']);
            $table->index('notifiable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_notifications');
    }
};

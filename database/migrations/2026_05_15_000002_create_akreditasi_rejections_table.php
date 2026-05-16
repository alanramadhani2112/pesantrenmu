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
        Schema::create('akreditasi_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akreditasi_id')->constrained('akreditasis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type'); // 'asesor' or 'admin_final'
            $table->json('items')->nullable();
            $table->json('categories')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('rejection_number')->default(1);
            $table->timestamp('perbaikan_deadline')->nullable();
            $table->timestamp('perbaikan_submitted_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['akreditasi_id', 'type']);
            $table->index(['akreditasi_id', 'status']);
            $table->unique(['akreditasi_id', 'type', 'rejection_number'], 'akreditasi_rejections_unique_type_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akreditasi_rejections');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the master taxonomy of document categories.
     *
     * Each Document belongs to exactly one DocumentCategory. Visibility is
     * stored as a single ENUM (not two booleans) so that a "secret" category
     * cannot be accidentally exposed to the wrong role through a misconfigured
     * checkbox. The mutual exclusion is enforced at the database level.
     */
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('document');

            // Visibility: who is allowed to SEE templates under this category.
            // - public           : visible to admin + asesor + pesantren (mis. IAPM, panduan umum).
            // - pesantren_secret : visible to admin + pesantren only (mis. kartu kendali).
            // - asesor_secret    : visible to admin + asesor only (mis. laporan visitasi).
            $table->enum('visibility', ['public', 'pesantren_secret', 'asesor_secret']);

            // Upload permissions. Admin can ALWAYS upload (root capability).
            // These flags only control whether non-admin roles may upload
            // filled-in copies in the future. For now master templates are
            // admin-only; these columns are forward-compat.
            $table->boolean('pesantren_can_upload')->default(false);
            $table->boolean('asesor_can_upload')->default(false);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_categories');
    }
};

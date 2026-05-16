<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link existing documents to the new document_categories taxonomy.
     *
     * Backward compat: we keep the legacy `type`, `is_pesantren`, and
     * `is_asesor` columns on the table during the transition so any older
     * code paths or unmigrated data are not broken. The Service layer now
     * authoritatively reads category_id; the legacy columns are only
     * populated for symmetry by the seeder.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('type')
                ->constrained('document_categories')
                ->nullOnDelete();

            // Audit trail: who uploaded this template and as which role.
            $table->unsignedTinyInteger('uploaded_by_role')->nullable()->after('category_id');
            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->after('uploaded_by_role')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['uploaded_by_user_id']);
            $table->dropColumn(['category_id', 'uploaded_by_role', 'uploaded_by_user_id']);
        });
    }
};

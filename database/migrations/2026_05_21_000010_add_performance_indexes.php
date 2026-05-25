<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes — audit findings P-1, P-2, P-10, P-11, P-13, P-14, P-18.
 *
 * P-1  : akreditasis — zero indexes on the hottest table in the system.
 * P-2  : akreditasi_edpms — composite unique for per-butir lookup.
 * P-10 : edpms — composite unique (user_id, butir_id).
 * P-11 : sdm_pesantrens — composite unique (user_id, tingkat).
 * P-13 : documents — composite indexes on status+type, status+is_pesantren, status+is_asesor.
 * P-14 : notifications — composite index including read_at for unread-count queries.
 * P-18 : assessments — composite (akreditasi_id, tipe) for assessment1/assessment2 hasOne.
 */
return new class extends Migration
{
    public function up(): void
    {
        // P-1: akreditasis — the system hot table
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->index('user_id', 'akreditasis_user_id_index');
            $table->index('parent', 'akreditasis_parent_index');
            $table->index('status', 'akreditasis_status_index');
            $table->index('uuid', 'akreditasis_uuid_index');
            $table->index(['user_id', 'status'], 'akreditasis_user_id_status_index');
            $table->index(['status', 'deleted_at'], 'akreditasis_status_deleted_at_index');
        });

        // P-2: akreditasi_edpms — composite unique for per-butir lookup
        Schema::table('akreditasi_edpms', function (Blueprint $table) {
            $table->unique(
                ['akreditasi_id', 'asesor_id', 'butir_id'],
                'akreditasi_edpms_unique_eval'
            );
        });

        // P-10: edpms — composite unique (user_id, butir_id)
        Schema::table('edpms', function (Blueprint $table) {
            $table->unique(['user_id', 'butir_id'], 'edpms_user_id_butir_id_unique');
        });

        // P-11: sdm_pesantrens — composite unique (user_id, tingkat)
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->unique(['user_id', 'tingkat'], 'sdm_pesantrens_user_id_tingkat_unique');
        });

        // P-13: documents — composite indexes
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['status', 'type'], 'documents_status_type_index');
            $table->index(['status', 'is_pesantren'], 'documents_status_is_pesantren_index');
            $table->index(['status', 'is_asesor'], 'documents_status_is_asesor_index');
        });

        // P-14: notifications — composite including read_at
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_notifiable_read_at_index'
            );
        });

        // P-18: assessments — composite (akreditasi_id, tipe)
        Schema::table('assessments', function (Blueprint $table) {
            $table->index(['akreditasi_id', 'tipe'], 'assessments_akreditasi_id_tipe_index');
        });
    }

    public function down(): void
    {
        Schema::table('akreditasis', function (Blueprint $table) {
            $table->dropIndex('akreditasis_user_id_index');
            $table->dropIndex('akreditasis_parent_index');
            $table->dropIndex('akreditasis_status_index');
            $table->dropIndex('akreditasis_uuid_index');
            $table->dropIndex('akreditasis_user_id_status_index');
            $table->dropIndex('akreditasis_status_deleted_at_index');
        });
        Schema::table('akreditasi_edpms', function (Blueprint $table) {
            $table->dropUnique('akreditasi_edpms_unique_eval');
        });
        Schema::table('edpms', function (Blueprint $table) {
            $table->dropUnique('edpms_user_id_butir_id_unique');
        });
        Schema::table('sdm_pesantrens', function (Blueprint $table) {
            $table->dropUnique('sdm_pesantrens_user_id_tingkat_unique');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_status_type_index');
            $table->dropIndex('documents_status_is_pesantren_index');
            $table->dropIndex('documents_status_is_asesor_index');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_at_index');
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_akreditasi_id_tipe_index');
        });
    }
};

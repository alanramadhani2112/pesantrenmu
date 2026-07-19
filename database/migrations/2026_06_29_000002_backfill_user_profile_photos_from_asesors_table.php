<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('asesors')
            ->select('id', 'user_id', 'foto')
            ->whereNotNull('asesors.foto')
            ->where('asesors.foto', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($asesors) {
                foreach ($asesors as $asesor) {
                    DB::table('users')
                        ->where('id', $asesor->user_id)
                        ->where(function ($query) {
                            $query->whereNull('profile_photo_path')
                                ->orWhere('profile_photo_path', '');
                        })
                        ->update(['profile_photo_path' => $asesor->foto]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: avoid deleting user profile photos that may have been changed after backfill.
    }
};

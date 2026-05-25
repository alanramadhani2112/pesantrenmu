<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Ipm extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [].
     * Callers MUST set `user_id` from `Auth::id()`, never from request input.
     */
    protected $fillable = [
        'user_id',
        'nsp_file',
        'lulus_santri_file',
        'kurikulum_file',
        'buku_ajar_file',
    ];

    protected static function boot()
    {
        parent::boot();

        // Audit fix PM-3: delete uploaded IPM files when the record is deleted.
        static::deleting(function ($ipm) {
            $paths = collect(['nsp_file', 'lulus_santri_file', 'kurikulum_file', 'buku_ajar_file'])
                ->map(fn ($col) => $ipm->$col)
                ->filter()
                ->values()
                ->all();

            if ($paths) {
                Storage::disk('public')->delete($paths);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

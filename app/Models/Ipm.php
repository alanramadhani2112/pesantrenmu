<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

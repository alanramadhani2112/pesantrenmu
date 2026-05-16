<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkreditasiCatatan extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [].
     * Callers MUST set `user_id` from `Auth::id()`, never from request input.
     */
    protected $fillable = [
        'akreditasi_id',
        'user_id',
        'tipe',
        'catatan',
        'perbaikan',
    ];

    public function akreditasi()
    {
        return $this->belongsTo(Akreditasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

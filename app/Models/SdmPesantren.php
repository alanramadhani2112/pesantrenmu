<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdmPesantren extends Model
{
    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [].
     * Callers MUST set `user_id` and `pesantren_unit_id` server-side, never
     * from request input.
     */
    protected $fillable = [
        'user_id',
        'pesantren_unit_id',
        'tingkat',
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pesantrenUnit()
    {
        return $this->belongsTo(PesantrenUnit::class);
    }
}

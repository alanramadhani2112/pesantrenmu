<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkreditasiBandingEdpmCatatan extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'banding_id',
        'komponen_id',
        'catatan',
        'rekomendasi',
    ];

    protected $casts = [
        'komponen_id' => 'integer',
    ];

    /**
     * The akreditasi this catatan belongs to.
     */
    public function akreditasi(): BelongsTo
    {
        return $this->belongsTo(Akreditasi::class);
    }

    /**
     * The banding this catatan belongs to.
     */
    public function banding(): BelongsTo
    {
        return $this->belongsTo(Banding::class);
    }
}

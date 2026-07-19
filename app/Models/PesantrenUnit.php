<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesantrenUnit extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [].
     * Callers MUST set `pesantren_id` server-side from the tenant's own
     * record, never from request input.
     */
    protected $fillable = [
        'pesantren_id',
        'unit',
        'jumlah_rombel',
    ];

    public function pesantren()
    {
        return $this->belongsTo(Pesantren::class);
    }
}

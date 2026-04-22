<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesantrenUnit extends Model
{
    protected $guarded = [];

    public function pesantren()
    {
        return $this->belongsTo(Pesantren::class);
    }
}

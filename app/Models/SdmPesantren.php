<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdmPesantren extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pesantrenUnit()
    {
        return $this->belongsTo(PesantrenUnit::class);
    }
}

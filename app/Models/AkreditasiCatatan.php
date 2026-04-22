<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkreditasiCatatan extends Model
{
    protected $guarded = [];

    public function akreditasi()
    {
        return $this->belongsTo(Akreditasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

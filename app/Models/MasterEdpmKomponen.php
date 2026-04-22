<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEdpmKomponen extends Model
{
    protected $fillable = ['nama', 'ipr'];

    public function butirs()
    {
        return $this->hasMany(MasterEdpmButir::class, 'komponen_id');
    }
}

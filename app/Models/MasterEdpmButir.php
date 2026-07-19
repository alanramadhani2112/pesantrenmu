<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterEdpmButir extends Model
{
    use HasFactory;

    protected $fillable = ['komponen_id', 'no_sk', 'nomor_butir', 'butir_pernyataan'];

    public function komponen()
    {
        return $this->belongsTo(MasterEdpmKomponen::class, 'komponen_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdpmCatatan extends Model
{
    protected $fillable = ['user_id', 'komponen_id', 'catatan'];
}

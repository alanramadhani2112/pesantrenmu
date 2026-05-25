<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    /**
     * Explicit fillable — audit fix C-2.
     * `access_token` sengaja dimasukkan agar UserService bisa menyimpannya,
     * tapi kolom ini di-encrypt at rest (lihat $casts di bawah).
     */
    protected $fillable = [
        'user_id',
        'data',
        'access_token',
    ];

    protected $casts = [
        'data' => 'array',
        // L-3 fix: enkripsi access_token di DB sehingga DB compromise tidak
        // langsung mengekspos token IdP yang masih aktif.
        'access_token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::deleting(function ($user) {
            // Delete related models that might have their own deleting events
            if ($user->pesantren) {
                $user->pesantren->delete();
            }

            if ($user->asesor) {
                $user->asesor->delete();
            }

            foreach ($user->akreditasis as $akreditasi) {
                $akreditasi->delete();
            }

            // Delete other related models
            $user->ipm()->delete();
            $user->sdm()->delete();
            $user->edpms()->delete();
            $user->edpmCatatans()->delete();
            $user->profile_data()->delete();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'uuid',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->id === 1;
    }

    public function isAsesor(): bool
    {
        return $this->role?->id === 2;
    }

    public function isPesantren(): bool
    {
        return $this->role?->id === 3;
    }

    public function pesantren()
    {
        return $this->hasOne(Pesantren::class);
    }

    public function ipm()
    {
        return $this->hasOne(Ipm::class);
    }

    public function sdm()
    {
        return $this->hasMany(SdmPesantren::class);
    }

    public function asesor()
    {
        return $this->hasOne(Asesor::class);
    }

    public function edpms()
    {
        return $this->hasMany(Edpm::class);
    }

    public function edpmCatatans()
    {
        return $this->hasMany(EdpmCatatan::class);
    }

    public function akreditasis()
    {
        return $this->hasMany(Akreditasi::class);
    }

    public function profile_data(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class);
    }
}

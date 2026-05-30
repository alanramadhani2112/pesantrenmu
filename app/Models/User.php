<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function ($user) {
            // P-6 fix: wrap entire cascade in a single transaction so a mid-loop
            // failure doesn't leave partial state (e.g. pesantren deleted but
            // akreditasi children still exist).
            DB::transaction(function () use ($user) {
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
        'sso_linked_at',
        'sso_sync_role',
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
            'sso_linked_at' => 'datetime',
            'sso_sync_role' => 'boolean',
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

    /**
     * Super admin = god mode. Mutually exclusive with isAdmin().
     */
    public function isSuperAdmin(): bool
    {
        return $this->role?->id === 4;
    }

    /**
     * Check if user has a given permission key.
     *
     * Super admin always returns true (bypasses everything).
     * Other roles consult the role_permission pivot.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        return $this->role
            ->permissions()
            ->where('permissions.key', $key)
            ->exists();
    }

    /**
     * Convenience guard: user can access admin-area pages.
     * Both classic admin (id=1) and super admin (id=4) qualify.
     */
    public function canAccessAdminArea(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
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

    public function profile_data(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class);
    }

    public function onboarding()
    {
        return $this->hasOne(UserOnboarding::class);
    }
}

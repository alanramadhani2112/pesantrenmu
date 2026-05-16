<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ID_ADMIN = 1;
    public const ID_ASESOR = 2;
    public const ID_PESANTREN = 3;
    public const ID_SUPER_ADMIN = 4;

    protected $fillable = ['name', 'parameter'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this
            ->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Sync the granted permissions for this role.
     *
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function grantPermission(int $permissionId): void
    {
        $this->permissions()->syncWithoutDetaching([$permissionId]);
    }

    public function revokePermission(int $permissionId): void
    {
        $this->permissions()->detach($permissionId);
    }

    /**
     * @return array<int, string>
     */
    public function permissionKeys(): array
    {
        return $this->permissions()->pluck('key')->all();
    }
}

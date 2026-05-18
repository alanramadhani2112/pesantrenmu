<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission represents a single feature-level capability key
 * (e.g. "akreditasi.approve"). Roles are linked to permissions via the
 * `role_permission` pivot table. Super admin bypasses the pivot entirely.
 */
class Permission extends Model
{
    public const GROUP_AKREDITASI = 'akreditasi';
    public const GROUP_ASESOR = 'asesor';
    public const GROUP_PESANTREN = 'pesantren';
    public const GROUP_BANDING = 'banding';
    public const GROUP_MASTER = 'master';
    public const GROUP_ACCOUNT = 'account';
    public const GROUP_TRASH = 'trash';
    public const GROUP_NOTIFICATION = 'notification';

    protected $fillable = [
        'key',
        'label',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this
            ->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    public function scopeOfGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}

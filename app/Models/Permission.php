<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission represents a single feature-level capability key
 * (e.g. "documents.manage"). Roles are linked to permissions via the
 * `role_permission` pivot table. Super admin bypasses the pivot entirely.
 */
class Permission extends Model
{
    public const GROUP_DOCUMENTS = 'documents';
    public const GROUP_AKREDITASI = 'akreditasi';
    public const GROUP_USERS = 'users';
    public const GROUP_BANDING = 'banding';
    public const GROUP_PROFILE = 'profile';
    public const GROUP_DASHBOARD = 'dashboard';
    public const GROUP_SYSTEM = 'system';

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

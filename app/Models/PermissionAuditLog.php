<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit log for role-permission matrix changes.
 *
 * Each row records a single role's permission diff at the moment an admin
 * saves the permission matrix.  The record is intentionally immutable —
 * updates and deletes are blocked at the model level.
 */
class PermissionAuditLog extends Model
{
    /** No updated_at column — this table only has created_at. */
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'permissions_added' => 'array',
        'permissions_removed' => 'array',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // -------------------------------------------------------------------------
    // Immutability guards
    // -------------------------------------------------------------------------

    /**
     * @throws \RuntimeException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Permission audit logs are immutable.');
    }

    /**
     * @throws \RuntimeException
     */
    public function delete(): bool
    {
        throw new \RuntimeException('Permission audit logs cannot be deleted.');
    }

    /**
     * @throws \RuntimeException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('Permission audit logs are immutable.');
        }

        return parent::save($options);
    }
}

<?php

namespace App\Policies;

use App\Models\Akreditasi;
use App\Models\User;

/**
 * Tenant boundary for Akreditasi records.
 *
 * Audit fix H-2 (P0): protects multi-tenant data leak across pesantrens.
 * Asesor read/update permitted only when assigned via Assessment relation.
 * Super admin god-mode handled by Gate::before in AuthServiceProvider.
 *
 * Permission checks use the granular `akreditasi.*` permission keys
 * via `User::hasPermission()`. Tenant-scoping (ownership / asesor assignment)
 * is enforced in addition to the permission check.
 */
class AkreditasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('akreditasi.view');
    }

    public function view(User $user, Akreditasi $akreditasi): bool
    {
        if (! $user->hasPermission('akreditasi.view')) {
            return false;
        }

        // Admin users with view permission can see all records
        if ($user->canAccessAdminArea()) {
            return true;
        }

        // Pesantren users can only view their own records
        if ($user->isPesantren()) {
            return $akreditasi->user_id === $user->id;
        }

        // Asesor users can only view records they are assigned to
        if ($user->isAsesor()) {
            return $this->isAssignedAsesor($user, $akreditasi);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPesantren();
    }

    public function update(User $user, Akreditasi $akreditasi): bool
    {
        if (! $user->hasPermission('akreditasi.view')) {
            return false;
        }

        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            return $akreditasi->user_id === $user->id;
        }

        if ($user->isAsesor()) {
            return $this->isAssignedAsesor($user, $akreditasi);
        }

        return false;
    }

    public function approve(User $user, Akreditasi $akreditasi): bool
    {
        return $user->hasPermission('akreditasi.approve');
    }

    public function reject(User $user, Akreditasi $akreditasi): bool
    {
        return $user->hasPermission('akreditasi.reject');
    }

    public function delete(User $user, Akreditasi $akreditasi): bool
    {
        return $user->hasPermission('akreditasi.delete');
    }

    public function finalize(User $user, Akreditasi $akreditasi): bool
    {
        return $user->hasPermission('akreditasi.finalize');
    }

    public function submitBanding(User $user, Akreditasi $akreditasi): bool
    {
        return $user->isPesantren() && $akreditasi->user_id === $user->id;
    }

    /**
     * True if the asesor user is assigned to either assessment slot
     * (assessment1 / assessment2) on this akreditasi.
     */
    protected function isAssignedAsesor(User $user, Akreditasi $akreditasi): bool
    {
        $asesor = $user->asesor;
        if (! $asesor) {
            return false;
        }

        return $akreditasi->assessments()
            ->where('asesor_id', $asesor->id)
            ->exists();
    }
}

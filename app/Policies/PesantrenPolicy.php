<?php

namespace App\Policies;

use App\Models\Pesantren;
use App\Models\User;

/**
 * Tenant boundary for Pesantren records.
 *
 * Audit fix H-2 (P0): without this policy, a regression like `Pesantren::find($id)->update($request->all())`
 * would let pesantren A overwrite pesantren B's data.
 *
 * Super admin god-mode is handled by Gate::before in AuthServiceProvider — these
 * methods only receive non-super-admin users.
 */
class PesantrenPolicy
{
    /**
     * List view. Admin sees all, pesantren only sees their own (returns true,
     * caller must scope query by user_id).
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea() || $user->isPesantren();
    }

    /**
     * Detail view. Admin can view any. Pesantren only their own. Asesor allowed
     * for assigned akreditasi (caller verifies assignment separately).
     */
    public function view(User $user, Pesantren $pesantren): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            return $pesantren->user_id === $user->id;
        }

        // Asesor read access controlled at AkreditasiPolicy / assignment level.
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPesantren() || $user->canAccessAdminArea();
    }

    /**
     * Update permitted for the owning pesantren and for admin (legitimate
     * support flows). Foreign pesantren users are explicitly blocked - this
     * is the multi-tenant boundary.
     */
    public function update(User $user, Pesantren $pesantren): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if (! $user->isPesantren()) {
            return false;
        }

        return $pesantren->user_id === $user->id;
    }

    public function delete(User $user, Pesantren $pesantren): bool
    {
        // Pesantren cannot delete their own record once created. Only admin.
        return $user->canAccessAdminArea();
    }
}

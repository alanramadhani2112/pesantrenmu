<?php

namespace App\Policies;

use App\Models\SdmPesantren;
use App\Models\User;

/**
 * Tenant boundary for SDM (Sumber Daya Manusia) records.
 *
 * Audit fix H-2 (P0). Owner-only writes. Admin can view all.
 * Super admin god-mode via Gate::before.
 */
class SdmPesantrenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea() || $user->isPesantren();
    }

    public function view(User $user, SdmPesantren $sdm): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            return $sdm->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPesantren();
    }

    public function update(User $user, SdmPesantren $sdm): bool
    {
        return $user->isPesantren() && $sdm->user_id === $user->id;
    }

    public function delete(User $user, SdmPesantren $sdm): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        return $user->isPesantren() && $sdm->user_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Asesor;
use App\Models\User;

/**
 * Authorization for Asesor profile records.
 *
 * Audit fix H-2 (P0). Admin can list/manage. Asesor can view/update only
 * their own profile. Super admin god-mode via Gate::before.
 */
class AsesorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea();
    }

    public function view(User $user, Asesor $asesor): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isAsesor()) {
            return $asesor->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminArea();
    }

    public function update(User $user, Asesor $asesor): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        return $user->isAsesor() && $asesor->user_id === $user->id;
    }

    public function delete(User $user, Asesor $asesor): bool
    {
        return $user->canAccessAdminArea();
    }
}

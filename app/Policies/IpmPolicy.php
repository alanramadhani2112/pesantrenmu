<?php

namespace App\Policies;

use App\Models\Ipm;
use App\Models\User;

/**
 * Tenant boundary for IPM (Indikator Pemenuhan Mutu) records.
 *
 * Audit fix H-2 (P0). Owner-only writes. Admin can view all. Super admin
 * god-mode via Gate::before.
 */
class IpmPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea() || $user->isPesantren();
    }

    public function view(User $user, Ipm $ipm): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            return $ipm->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPesantren();
    }

    public function update(User $user, Ipm $ipm): bool
    {
        return $user->isPesantren() && $ipm->user_id === $user->id;
    }

    public function delete(User $user, Ipm $ipm): bool
    {
        return $user->canAccessAdminArea();
    }
}

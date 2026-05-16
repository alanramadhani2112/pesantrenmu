<?php

namespace App\Policies;

use App\Models\Banding;
use App\Models\User;

/**
 * Tenant boundary for Banding (appeal) records.
 *
 * Audit fix H-2 (P0). Submitter (pesantren) sees their own. Reviewer (admin)
 * sees ones assigned to them OR any if they have admin role. Super admin
 * god-mode via Gate::before.
 */
class BandingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea() || $user->isPesantren();
    }

    public function view(User $user, Banding $banding): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            return $banding->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isPesantren();
    }

    public function update(User $user, Banding $banding): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        if ($user->isPesantren()) {
            // Pesantren can only edit while their own banding is still in
            // 'pending' status, never after admin starts reviewing.
            return $banding->user_id === $user->id && $banding->status === 'pending';
        }

        return false;
    }

    public function review(User $user, Banding $banding): bool
    {
        return $user->canAccessAdminArea();
    }

    public function delete(User $user, Banding $banding): bool
    {
        return $user->canAccessAdminArea();
    }
}

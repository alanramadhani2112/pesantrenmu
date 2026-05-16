<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Document master template authorization.
 *
 * Audit fix H-2 (P0). Visibility comes from the document category's
 * visibleToRole rule (already enforced via scope). Mutations are admin-only
 * unless the category explicitly allows pesantren/asesor uploads.
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminArea() || $user->isAsesor() || $user->isPesantren();
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        $category = $document->category;
        if (! $category) {
            return false;
        }

        if ($user->isPesantren()) {
            // Pesantren see public + pesantren_secret categories.
            return in_array($category->visibility, [
                \App\Models\DocumentCategory::VISIBILITY_PUBLIC,
                \App\Models\DocumentCategory::VISIBILITY_PESANTREN_SECRET,
            ], true);
        }

        if ($user->isAsesor()) {
            // Asesor see public + asesor_secret categories.
            return in_array($category->visibility, [
                \App\Models\DocumentCategory::VISIBILITY_PUBLIC,
                \App\Models\DocumentCategory::VISIBILITY_ASESOR_SECRET,
            ], true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdminArea();
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        $category = $document->category;
        if (! $category) {
            return false;
        }

        // Non-admins can only update documents they uploaded themselves AND
        // their role is allowed to upload to this category.
        if ($document->uploaded_by_user_id !== $user->id) {
            return false;
        }

        if ($user->isPesantren()) {
            return (bool) $category->pesantren_can_upload;
        }

        if ($user->isAsesor()) {
            return (bool) $category->asesor_can_upload;
        }

        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->canAccessAdminArea()) {
            return true;
        }

        return $document->uploaded_by_user_id === $user->id;
    }
}

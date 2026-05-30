<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository,
    ) {}

    public function getPaginatedDocuments(
        ?string $search = null,
        int $perPage = 10,
        string $sortField = 'created_at',
        bool $sortAsc = false
    ): LengthAwarePaginator {
        return $this->documentRepository->getPaginatedDocuments($search, $perPage, $sortField, $sortAsc);
    }

    public function findDocument(int $id): ?Document
    {
        return $this->documentRepository->find($id);
    }

    /**
     * Persist a document template.
     *
     * Authoritative columns are `category_id` and `file_path`. The legacy
     * `type`, `is_pesantren`, and `is_asesor` columns are mirrored from the
     * chosen category for backward compatibility with anything still
     * reading them directly.
     */
    public function saveDocument(array $data, ?int $id = null, $newFile = null): void
    {
        $category = isset($data['category_id'])
            ? DocumentCategory::find($data['category_id'])
            : null;

        $payload = [
            'title' => $data['title'],
            'status' => (int) ($data['status'] ?? 0),
            'category_id' => $category?->id,
            // Mirror legacy columns from the category for back-compat.
            'type' => $category?->slug,
            'is_pesantren' => $category?->visibility === DocumentCategory::VISIBILITY_PUBLIC
                || $category?->visibility === DocumentCategory::VISIBILITY_PESANTREN_SECRET,
            'is_asesor' => $category?->visibility === DocumentCategory::VISIBILITY_PUBLIC
                || $category?->visibility === DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'description' => $data['description'] ?? null,
        ];

        if ($newFile) {
            // Store new file first; only delete old if store succeeds
            $newPath = $newFile->store('documents', 'public');
            if ($newPath) {
                if ($id) {
                    $existing = $this->findDocument($id);
                    if ($existing && $existing->file_path && Storage::disk('public')->exists($existing->file_path)) {
                        Storage::disk('public')->delete($existing->file_path);
                    }
                }
                $payload['file_path'] = $newPath;
                $payload['uploaded_by_user_id'] = Auth::id();
                $payload['uploaded_by_role'] = Auth::user()?->role_id;
            }
        }

        if ($id) {
            $this->documentRepository->update($id, $payload);
        } else {
            $this->documentRepository->create($payload);
        }
    }

    public function deleteDocument(int $id): bool
    {
        $doc = $this->findDocument($id);
        if (! $doc) {
            return false;
        }

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        return $this->documentRepository->delete($id);
    }

    /**
     * Active documents visible to the current viewer.
     *
     * Visibility is derived ENTIRELY from the document's category.visibility,
     * never from the legacy is_pesantren / is_asesor booleans. This makes
     * "secret" categories impossible to leak through a misconfigured admin
     * checkbox.
     *
     * @param  string|null  $role  'admin' | 'asesor' | 'pesantren' | null (guest)
     * @param  string|null  $categorySlug  document_categories.slug, or 'all'/null
     */
    public function getActiveDocuments(
        ?string $role = null,
        ?string $categorySlug = null,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->documentRepository->getActiveForRole($role, $categorySlug, $search, $perPage);
    }

    /**
     * Convenience helper used by the asesor akreditasi-detail view.
     */
    public function getVisitasiTemplate(): ?Document
    {
        return Document::query()
            ->active()
            ->categorySlug('visitasi')
            ->visibleToRole('asesor')
            ->latest('id')
            ->first();
    }
}

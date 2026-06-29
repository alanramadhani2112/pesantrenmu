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
            'type' => $category?->slug,
            'is_pesantren' => $category?->visibility === DocumentCategory::VISIBILITY_PUBLIC
                || $category?->visibility === DocumentCategory::VISIBILITY_PESANTREN_SECRET,
            'is_asesor' => $category?->visibility === DocumentCategory::VISIBILITY_PUBLIC
                || $category?->visibility === DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'description' => $data['description'] ?? null,
        ];

        $existingPath = null;
        $newPath = null;

        if ($newFile) {
            $existingPath = $id ? $this->findDocument($id)?->file_path : null;
            $newPath = $newFile->store('documents', 'public');
            $payload['file_path'] = $newPath;
            $payload['uploaded_by_user_id'] = Auth::id();
            $payload['uploaded_by_role'] = Auth::user()?->role_id;
        }

        try {
            if ($id) {
                $this->documentRepository->update($id, $payload);
            } else {
                $this->documentRepository->create($payload);
            }
        } catch (\Throwable $e) {
            if ($newPath && Storage::disk('public')->exists($newPath)) {
                Storage::disk('public')->delete($newPath);
            }

            throw $e;
        }

        if ($newPath && $existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
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

    public function getActiveDocuments(
        ?string $role = null,
        ?string $categorySlug = null,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->documentRepository->getActiveForRole($role, $categorySlug, $search, $perPage);
    }

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

<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function getPaginatedDocuments(?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return Document::query()
            ->with('category:id,name,slug,visibility')
            ->when($search, function (Builder $query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Document
    {
        return Document::with('category')->find($id);
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $doc = Document::find($id);
        if ($doc) {
            return $doc->update($data);
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $doc = Document::find($id);
        if ($doc) {
            return $doc->delete();
        }

        return false;
    }

    public function getActiveForRole(
        ?string $role,
        ?string $categorySlug = null,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Document::query()
            ->with('category:id,name,slug,visibility,icon')
            ->active()
            ->visibleToRole($role);

        if ($categorySlug && $categorySlug !== 'all') {
            $query->categorySlug($categorySlug);
        }

        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }
}

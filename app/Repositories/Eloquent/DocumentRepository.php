<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function getPaginatedDocuments(?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return Document::query()
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function find(int $id): ?Document
    {
        return Document::find($id);
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $doc = $this->find($id);
        if ($doc) {
            return $doc->update($data);
        }
        return false;
    }

    public function delete(int $id): bool
    {
        $doc = $this->find($id);
        if ($doc) {
            return $doc->delete();
        }
        return false;
    }
}

<?php

namespace App\Services;

use App\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Document;

class DocumentService
{
    protected $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function getPaginatedDocuments(?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->documentRepository->getPaginatedDocuments($search, $perPage, $sortField, $sortAsc);
    }

    public function findDocument(int $id): ?Document
    {
        return $this->documentRepository->find($id);
    }

    public function saveDocument(array $data, ?int $id = null, $newFile = null): void
    {
        $payload = [
            'title' => $data['title'],
            'status' => $data['status'],
            'is_pesantren' => $data['is_pesantren'],
            'is_asesor' => $data['is_asesor'],
            'type' => $data['type'],
        ];

        if ($newFile) {
            if ($id) {
                $doc = $this->findDocument($id);
                if ($doc && $doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }
            $path = $newFile->store('documents', 'public');
            $payload['file_path'] = $path;
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
        if ($doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
            return $this->documentRepository->delete($id);
        }
        return false;
    }

    public function getActiveDocuments(?string $role = null, ?string $type = null): \Illuminate\Support\Collection
    {
        $query = Document::where('status', 1);

        if ($role === 'asesor') {
            $query->where('is_asesor', true);
        } elseif ($role === 'pesantren') {
            $query->where('is_pesantren', true);
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        return $query->latest()->get();
    }

    public function getVisitasiTemplate(): ?Document
    {
        return Document::where('type', 'visitasi')->where('status', 1)->first();
    }
}

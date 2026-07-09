<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function index(Request $request, ?string $doc = 'all')
    {
        $user = auth()->user();

        if (! $user || (! $user->canAccessAdminArea() && ! $user->isAsesor() && ! $user->isPesantren())) {
            abort(403);
        }

        $roleScope = match (true) {
            $user->canAccessAdminArea() => 'admin',
            $user->isAsesor() => 'asesor',
            $user->isPesantren() => 'pesantren',
            default => abort(403),
        };

        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);

        $documents = $this->documentService->getActiveDocuments(
            $roleScope,
            $doc,
            $search ?: null,
            $perPage
        );

        // Derive page title
        if ($doc === 'all' || $doc === '' || $doc === null) {
            $pageTitle = 'Daftar Dokumen';
        } else {
            $firstDoc = $documents->first();
            if ($firstDoc && $firstDoc->category) {
                $pageTitle = $firstDoc->category->name;
            } else {
                $category = DocumentCategory::query()
                    ->where('slug', $doc)
                    ->value('name');
                $pageTitle = $category ?: 'Daftar Dokumen';
            }
        }

        return view('documents.index', compact('documents', 'search', 'perPage', 'doc', 'pageTitle'));
    }

    public function download(Document $document)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $document->loadMissing('category');

        if (! $user->canAccessAdminArea()) {
            abort_unless((int) $document->status === 1, 404);
            abort_unless($document->category?->is_active, 404);

            $visibility = $document->category->visibility;
            $allowed = match (true) {
                $user->isAsesor() => in_array($visibility, [
                    DocumentCategory::VISIBILITY_PUBLIC,
                    DocumentCategory::VISIBILITY_ASESOR_SECRET,
                ], true),
                $user->isPesantren() => in_array($visibility, [
                    DocumentCategory::VISIBILITY_PUBLIC,
                    DocumentCategory::VISIBILITY_PESANTREN_SECRET,
                ], true),
                default => false,
            };

            abort_unless($allowed, 403);
        }

        $disk = Storage::disk('local')->exists($document->file_path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($document->file_path), 404);

        return Storage::disk($disk)->download($document->file_path, basename($document->file_path));
    }
}

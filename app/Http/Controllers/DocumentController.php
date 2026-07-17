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

        if ($user->isPesantren() && ($doc === 'all' || $doc === '' || $doc === null)) {
            return redirect()->route('documents.index', ['doc' => 'iapm']);
        }

        if ($user->isPesantren() && $doc === 'kartu_kendali') {
            return redirect()->route('pesantren.akreditasi', ['focus' => 'kartu_kendali']);
        }

        $search = $request->input('search', '');
        $perPage = min(max($request->integer('perPage', 10), 5), 50);

        $documents = $this->documentService->getActiveDocuments(
            $roleScope,
            $doc,
            $search ?: null,
            $perPage
        );

        $categoriesQuery = DocumentCategory::query()->active()->ordered();
        $documentCategories = match ($roleScope) {
            'pesantren' => $categoriesQuery->visibleToPesantren()->get(),
            'asesor' => $categoriesQuery->visibleToAsesor()->get(),
            default => $categoriesQuery->get(),
        };

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

        return view('documents.index', compact('documents', 'search', 'perPage', 'doc', 'pageTitle', 'documentCategories'));
    }

    public function view(Document $document)
    {
        [$disk, $path] = $this->authorizedDocumentPath($document);
        $absolutePath = Storage::disk($disk)->path($path);

        return response()->stream(function () use ($absolutePath) {
            readfile($absolutePath);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Document $document)
    {
        [$disk, $path] = $this->authorizedDocumentPath($document);

        return Storage::disk($disk)->download($path, basename($path));
    }

    private function authorizedDocumentPath(Document $document): array
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

        abort_if(blank($document->file_path), 404);

        $disk = Storage::disk('local')->exists($document->file_path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($document->file_path), 404);

        return [$disk, $document->file_path];
    }
}

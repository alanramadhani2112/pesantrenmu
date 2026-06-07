<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Http\Request;

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
                $category = \App\Models\DocumentCategory::query()
                    ->where('slug', $doc)
                    ->value('name');
                $pageTitle = $category ?: 'Daftar Dokumen';
            }
        }

        return view('documents.index', compact('documents', 'search', 'perPage', 'doc', 'pageTitle'));
    }
}

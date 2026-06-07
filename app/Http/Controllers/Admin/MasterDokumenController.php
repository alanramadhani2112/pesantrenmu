<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MasterDokumenController extends Controller
{
    public function __construct(private DocumentService $service)
    {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sort', 'created_at');
        $sortAsc = $request->input('direction', 'asc') === 'asc';

        $documents = $this->service->getPaginatedDocuments($search, $perPage, $sortField, $sortAsc);
        $categories = DocumentCategory::active()->ordered()->get();

        return view('admin.master-dokumen.index', compact(
            'documents', 'categories', 'search', 'sortField', 'sortAsc', 'perPage'
        ));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.dokumen');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|integer|in:0,1',
            'category_id' => 'required|integer|exists:document_categories,id',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        $this->service->saveDocument($data, null, $request->file('file'));

        return back()->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('master.dokumen');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|integer|in:0,1',
            'category_id' => 'required|integer|exists:document_categories,id',
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        $this->service->saveDocument($data, $id, $request->file('file'));

        return back()->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        Gate::authorize('master.dokumen');

        if ($this->service->deleteDocument($id)) {
            return back()->with('success', 'Dokumen berhasil dihapus.');
        }

        return back()->with('error', 'Dokumen tidak dapat dihapus.');
    }
}

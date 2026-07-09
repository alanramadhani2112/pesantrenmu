<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MasterKategoriDokumenController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sort', $request->input('sortField', 'sort_order'));
        $sortField = in_array($sortField, ['name', 'sort_order'], true) ? $sortField : 'sort_order';
        $direction = $request->input('direction');
        $sortAsc = $direction ? $direction === 'asc' : ($request->input('sortAsc', 'true') === 'true');

        $categories = DocumentCategory::query()
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.master-kategori-dokumen.index', compact(
            'categories', 'search', 'sortField', 'sortAsc', 'perPage'
        ));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.kategori');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('document_categories', 'slug')],
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'visibility' => 'required|in:'.implode(',', DocumentCategory::VISIBILITIES),
            'pesantren_can_upload' => 'boolean',
            'asesor_can_upload' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0|max:9999',
        ], [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan underscore.',
        ]);

        $data['pesantren_can_upload'] = $request->boolean('pesantren_can_upload');
        $data['asesor_can_upload'] = $request->boolean('asesor_can_upload');
        $data['is_active'] = $request->boolean('is_active');

        DocumentCategory::create($data);

        return back()->with('success', 'Kategori dokumen berhasil dibuat.');
    }

    public function update(Request $request, DocumentCategory $category)
    {
        Gate::authorize('master.kategori');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('document_categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'visibility' => 'required|in:'.implode(',', DocumentCategory::VISIBILITIES),
            'pesantren_can_upload' => 'boolean',
            'asesor_can_upload' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0|max:9999',
        ], [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan underscore.',
        ]);

        $data['pesantren_can_upload'] = $request->boolean('pesantren_can_upload');
        $data['asesor_can_upload'] = $request->boolean('asesor_can_upload');
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return back()->with('success', 'Kategori dokumen berhasil diperbarui.');
    }

    public function destroy(DocumentCategory $category)
    {
        Gate::authorize('master.kategori');

        if ($category->documents()->exists()) {
            return back()->with('error', 'Kategori masih memiliki dokumen, tidak bisa dihapus.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dokumen berhasil dihapus.');
    }
}

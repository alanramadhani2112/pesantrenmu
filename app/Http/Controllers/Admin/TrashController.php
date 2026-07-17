<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TrashService;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function __construct(private TrashService $trashService) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $perPage = min(max($request->integer('perPage', 10), 5), 50);

        $trashedAkreditasis = $this->trashService->getPaginatedTrashed(
            $search !== '' ? $search : null,
            $perPage
        );

        $retentionDays = (int) config('akreditasi.trash.retention_days', 90);
        $trashCount = $this->trashService->getTrashCount();

        return view('admin.trash.index', compact(
            'trashedAkreditasis', 'search', 'perPage', 'retentionDays', 'trashCount'
        ));
    }

    public function restorePreview(int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $previewData = $this->trashService->getRestorePreview($id);

        return response()->json($previewData);
    }

    public function restore(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $request->validate(['id' => 'required|integer|exists:akreditasis,id']);

        try {
            $count = $this->trashService->restore($request->integer('id'));

            return back()->with('success', 'Akreditasi berhasil dipulihkan beserta '.($count - 1).' record terkait.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memulihkan akreditasi: '.$e->getMessage());
        }
    }

    public function forceDelete(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $request->validate(['id' => 'required|integer|exists:akreditasis,id']);

        try {
            $count = $this->trashService->forceDelete($request->integer('id'));

            return back()->with('success', "{$count} record berhasil dihapus permanen.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus permanen: '.$e->getMessage());
        }
    }
}

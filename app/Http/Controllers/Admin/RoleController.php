<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function __construct(private RoleService $service)
    {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sort', 'id');
        $sortAsc = $request->input('direction', 'desc') !== 'desc';

        $roles = $this->service->getPaginatedRoles($search, $perPage, $sortField, $sortAsc);

        return view('admin.roles.index', compact('roles', 'search', 'sortField', 'sortAsc', 'perPage'));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.role');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'parameter' => 'required|string|max:255|unique:roles,parameter',
        ]);

        $this->service->saveRole($data);

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        Gate::authorize('master.role');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'parameter' => 'required|string|max:255|unique:roles,parameter,' . $id,
        ]);

        $this->service->saveRole($data, $id);

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        Gate::authorize('master.role');

        $this->service->deleteRole($id);

        return back()->with('success', 'Role berhasil dihapus.');
    }
}

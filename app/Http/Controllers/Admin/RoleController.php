<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function __construct(private RoleService $service) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $search = $request->input('search', '');
        $perPage = min(max($request->integer('perPage', 10), 5), 50);
        $sortField = $request->input('sort', $request->input('sortField', 'id'));
        $sortField = in_array($sortField, ['id', 'name', 'parameter'], true) ? $sortField : 'id';
        $direction = $request->input('direction');
        $sortAsc = $direction ? $direction === 'asc' : filter_var($request->input('sortAsc', 'false'), FILTER_VALIDATE_BOOLEAN);

        $roles = $this->service->getPaginatedRoles($search, $perPage, $sortField, $sortAsc);

        return view('admin.roles.index', compact('roles', 'search', 'sortField', 'sortAsc', 'perPage'));
    }

    public function store(Request $request)
    {
        $this->authorizeRoleMutation();

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'parameter' => 'required|string|max:255|unique:roles,parameter',
        ]);

        $this->service->saveRole($data);

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeRoleMutation($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$id,
            'parameter' => 'required|string|max:255|unique:roles,parameter,'.$id,
        ]);

        $this->service->saveRole($data, $id);

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->authorizeRoleMutation($id);

        $this->service->deleteRole($id);

        return back()->with('success', 'Role berhasil dihapus.');
    }

    private function authorizeRoleMutation(?int $id = null): void
    {
        Gate::authorize('master.role');
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($id !== null) {
            abort_if(in_array($id, [1, 2, 3, 4], true), 403);
        }
    }
}

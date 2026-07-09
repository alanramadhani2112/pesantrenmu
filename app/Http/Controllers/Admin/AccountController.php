<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function __construct(
        private UserService $userService,
        private RoleService $roleService
    ) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $roles = $this->roleService->getAllRoles();
        $activeTab = $request->integer('activeTab', 1);
        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sortField', 'id');
        $sortAsc = $request->input('sortAsc', 'false') === 'true';

        $users = $this->userService->getPaginatedAccounts(
            $activeTab,
            $search ?: null,
            $perPage,
            $sortField,
            $sortAsc
        );

        $roleCounts = [];
        foreach ($roles as $role) {
            $roleCounts[$role->id] = $this->userService->getCountByRole($role->id);
        }

        return view('admin.accounts.index', compact(
            'users', 'roles', 'activeTab', 'search', 'perPage',
            'sortField', 'sortAsc', 'roleCounts'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('account.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['boolean'],
            'sso_sync_role' => ['boolean'],
            'password' => ['nullable', 'string'],
        ]);

        if (empty($validated['password'])) {
            $validated['password'] = Str::random(16);
        }

        $this->userService->saveAccount($validated);

        return back()->with('success', 'Data Akun berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('account.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$id],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['boolean'],
            'sso_sync_role' => ['boolean'],
            'password' => ['nullable', 'string'],
        ]);

        $this->userService->saveAccount($validated, $id);

        return back()->with('success', 'Data Akun berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('account.delete');

        if ($this->userService->deleteAccount($id)) {
            return back()->with('success', 'Data Akun berhasil dihapus.');
        }

        return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri atau terjadi kesalahan.');
    }

    public function toggleStatus(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('account.toggle');

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        if ($this->userService->toggleAccountStatus($request->integer('user_id'))) {
            return back()->with('success', 'Status akun berhasil diubah.');
        }

        return back()->with('error', 'Gagal mengubah status akun.');
    }

    public function unlinkSso(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('account.create');

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $user = User::find($request->integer('user_id'));

        if (! $user) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        $user->profile_data()->delete();
        $user->update(['sso_linked_at' => null]);

        return back()->with('success', 'SSO berhasil di-unlink dari akun '.$user->name.'.');
    }
}

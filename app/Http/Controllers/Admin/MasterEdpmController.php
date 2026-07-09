<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MasterEdpmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MasterEdpmController extends Controller
{
    public function __construct(private MasterEdpmService $service) {}

    public function index()
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $komponens = $this->service->getKomponensData();

        return view('admin.master-edpm.index', compact('komponens'));
    }

    public function storeKomponen(Request $request)
    {
        Gate::authorize('master.edpm');

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'ipr' => 'boolean',
        ]);

        $data['ipr'] = $request->boolean('ipr');

        $this->service->saveKomponen($data);

        return back()->with('success', 'Komponen berhasil ditambahkan.');
    }

    public function updateKomponen(Request $request, int $id)
    {
        Gate::authorize('master.edpm');

        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'ipr' => 'boolean',
        ]);

        $data['ipr'] = $request->boolean('ipr');

        $this->service->saveKomponen($data, $id);

        return back()->with('success', 'Komponen berhasil diperbarui.');
    }

    public function destroyKomponen(int $id)
    {
        Gate::authorize('master.edpm');

        $this->service->deleteKomponen($id);

        return back()->with('success', 'Komponen berhasil dihapus.');
    }

    public function storeButir(Request $request)
    {
        Gate::authorize('master.edpm');

        $data = $request->validate([
            'komponen_id' => 'required|integer|exists:master_edpm_komponens,id',
            'no_sk' => 'nullable|string|max:255',
            'nomor_butir' => 'required|string|max:50',
            'butir_pernyataan' => 'required|string',
        ]);

        $this->service->saveButir($data);

        return back()->with('success', 'Butir pernyataan berhasil ditambahkan.');
    }

    public function updateButir(Request $request, int $id)
    {
        Gate::authorize('master.edpm');

        $data = $request->validate([
            'komponen_id' => 'required|integer|exists:master_edpm_komponens,id',
            'no_sk' => 'nullable|string|max:255',
            'nomor_butir' => 'required|string|max:50',
            'butir_pernyataan' => 'required|string',
        ]);

        $this->service->saveButir($data, $id);

        return back()->with('success', 'Butir pernyataan berhasil diperbarui.');
    }

    public function destroyButir(int $id)
    {
        Gate::authorize('master.edpm');

        $this->service->deleteButir($id);

        return back()->with('success', 'Butir pernyataan berhasil dihapus.');
    }
}

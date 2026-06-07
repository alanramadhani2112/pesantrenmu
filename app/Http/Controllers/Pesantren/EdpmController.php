<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Services\PesantrenService;
use Illuminate\Http\Request;

class EdpmController extends Controller
{
    public function __construct(private PesantrenService $pesantrenService) {}

    public function show()
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $data = $this->pesantrenService->getEdpmData(auth()->id());
        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        $komponens = $data['komponens'];
        $existingEdpms = $data['existingEdpms'];
        $existingCatatans = $data['existingCatatans'];

        $evaluasis = [];
        $links = [];
        $catatans = [];

        foreach ($komponens as $komponen) {
            $catatans[$komponen->id] = $existingCatatans[$komponen->id] ?? '';
            foreach ($komponen->butirs as $butir) {
                $evaluasis[$butir->id] = $existingEdpms[$butir->id]->isian ?? '';
                $links[$butir->id] = $existingEdpms[$butir->id]->link ?? '';
            }
        }

        return view('pesantren.edpm', compact('pesantren', 'komponens', 'evaluasis', 'links', 'catatans'));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $data = $this->pesantrenService->getEdpmData(auth()->id());
        $komponens = $data['komponens'];

        $evaluasis = $request->input('evaluasis', []);
        $links = $request->input('links', []);
        $catatans = $request->input('catatans', []);

        // Validate all butirs
        $rules = [];
        $messages = [];

        foreach ($komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                $rules["evaluasis.{$butir->id}"] = 'required|numeric|min:1|max:4';
                $rules["links.{$butir->id}"] = 'required|url';
                $messages["evaluasis.{$butir->id}.required"] = "Harap pilih nilai evaluasi untuk butir {$butir->nomor_butir}";
                $messages["evaluasis.{$butir->id}.numeric"] = "Nilai harus berupa angka pada butir {$butir->nomor_butir}";
                $messages["evaluasis.{$butir->id}.min"] = "Nilai minimal adalah 1 untuk butir {$butir->nomor_butir}";
                $messages["evaluasis.{$butir->id}.max"] = "Nilai maksimal adalah 4 untuk butir {$butir->nomor_butir}";
                $messages["links.{$butir->id}.required"] = "Harap isi tautan bukti untuk butir {$butir->nomor_butir}";
                $messages["links.{$butir->id}.url"] = "Format tautan bukti tidak valid untuk butir {$butir->nomor_butir}";
            }
        }

        $request->validate($rules, $messages);

        $success = $this->pesantrenService->saveEdpmEvaluation(
            auth()->id(),
            $evaluasis,
            $links,
            $catatans
        );

        if ($success) {
            return back()->with('success', 'Data EDPM berhasil disimpan.');
        }

        return back()->with('error', 'Gagal menyimpan data EDPM.');
    }

    public function saveDraft(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $evaluasis = $request->input('evaluasis', []);
        $links = $request->input('links', []);
        $catatans = $request->input('catatans', []);

        $success = $this->pesantrenService->saveEdpmDraft(
            auth()->id(),
            $evaluasis,
            $links,
            $catatans
        );

        if ($success) {
            return back()->with('success', 'Draft EDPM berhasil disimpan.');
        }

        return back()->with('error', 'Gagal menyimpan draft EDPM.');
    }
}

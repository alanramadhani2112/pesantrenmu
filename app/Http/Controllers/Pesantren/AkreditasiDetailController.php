<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Services\BandingService;
use App\Services\PesantrenService;
use App\Services\RejectionService;
use App\Services\AkreditasiWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AkreditasiDetailController extends Controller
{
    public function __construct(
        private PesantrenService $pesantrenService,
        private BandingService $bandingService,
        private RejectionService $rejectionService,
        private AkreditasiWorkflowService $workflowService
    ) {}

    public function show(string $uuid, Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $data = $this->pesantrenService->getAkreditasiDetail($uuid, Auth::id());

        // Get rejection status for the akreditasi
        $akreditasi = \App\Models\Akreditasi::where('uuid', $uuid)->firstOrFail();
        $rejectionStatus = $this->rejectionService->getRejectionStatus($akreditasi->id);

        $activeTab = $request->input('tab', $request->input('activeTab', 'profil'));
        if ($activeTab === 'edpm_pesantren') {
            $activeTab = 'edpm';
        }

        return view('pesantren.akreditasi-detail', array_merge($data, [
            'activeTab' => $activeTab,
            'rejectionStatus' => $rejectionStatus,
        ]));
    }

    public function uploadKartuKendali(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer',
            'kartu_kendali_file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'kartu_kendali_file.required' => 'File kartu kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'File harus berformat PDF, JPG, JPEG, atau PNG.',
            'kartu_kendali_file.max' => 'Ukuran file tidak boleh lebih dari 2MB.',
        ]);

        $filePath = $request->file('kartu_kendali_file')->store('kartu_kendali', 'public');

        if (! $filePath) {
            return back()->with('error', 'Gagal mengunggah file.');
        }

        $success = $this->pesantrenService->uploadKartuKendali(
            $request->integer('akreditasi_id'),
            Auth::id(),
            $filePath
        );

        if ($success) {
            return back()->with('success', 'Kartu kendali berhasil diunggah.');
        }

        Storage::disk('public')->delete($filePath);
        return back()->with('error', 'Gagal menyimpan kartu kendali.');
    }


    public function submitPerbaikan(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $validated = $request->validate([
            'akreditasi_id' => 'required|integer',
        ]);

        try {
            $this->workflowService->submitPerbaikan(
                $validated['akreditasi_id'],
                Auth::id()
            );
            return back()->with('success', 'Perbaikan berhasil dikirim. Asesor akan meninjau ulang.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}


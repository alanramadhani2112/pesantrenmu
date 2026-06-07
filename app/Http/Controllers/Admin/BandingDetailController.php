<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banding;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use App\Services\BandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BandingDetailController extends Controller
{
    public function show(int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $banding = Banding::with([
            'akreditasi.assessments.asesor',
            'akreditasi.catatans',
            'akreditasi.user.pesantren',
            'reviewer',
        ])->findOrFail($id);

        Gate::authorize('review', $banding);

        $adminUsers = User::where('role_id', 1)->get();

        $previousBandings = Banding::where('akreditasi_id', $banding->akreditasi_id)
            ->where('id', '!=', $banding->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.banding.detail', compact(
            'banding', 'adminUsers', 'previousBandings'
        ));
    }

    public function assignReviewer(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $banding = Banding::findOrFail($id);
        Gate::authorize('banding.review');
        Gate::authorize('review', $banding);

        $validated = $request->validate([
            'selectedReviewerId' => 'required|integer|exists:users,id',
        ]);

        $bandingService = app(BandingService::class);
        $result = $bandingService->assignReviewer($banding->id, (int) $validated['selectedReviewerId']);

        if ($result) {
            return back()->with('success', 'Peninjau berhasil ditugaskan.');
        }

        return back()->with('error', 'Gagal menugaskan peninjau.');
    }

    public function reassignReviewer(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $banding = Banding::findOrFail($id);
        Gate::authorize('banding.review');
        Gate::authorize('review', $banding);

        $validated = $request->validate([
            'selectedReviewerId' => 'required|integer|exists:users,id',
        ]);

        $bandingService = app(BandingService::class);
        $result = $bandingService->reassignReviewer($banding->id, (int) $validated['selectedReviewerId']);

        if ($result) {
            return back()->with('success', 'Peninjau berhasil diganti.');
        }

        return back()->with('error', 'Gagal mengganti peninjau.');
    }

    public function submitDecision(Request $request, int $id)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $banding = Banding::findOrFail($id);
        Gate::authorize('banding.decide');
        Gate::authorize('review', $banding);

        $validated = $request->validate([
            'decisionType' => 'required|string|in:accept,reject',
            'keputusan' => 'required|string|min:10',
        ]);

        try {
            $workflowService = app(AkreditasiWorkflowService::class);
            $status = $validated['decisionType'] === 'accept' ? 'diterima' : 'ditolak';
            $workflowService->decideBanding($banding->id, Auth::id(), $status, $validated['keputusan']);

            $message = $validated['decisionType'] === 'accept'
                ? 'Banding diterima. Akreditasi kembali ke tahap Validasi Akhir Admin.'
                : 'Banding ditolak.';

            return back()->with('success', $message);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

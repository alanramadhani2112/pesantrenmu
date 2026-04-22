<?php

namespace App\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use App\Models\Assessment;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isPesantren = $user->isPesantren();
        $isAsesor = $user->isAsesor();

        // 1. Stats based on role
        if ($isAdmin) {
            $stats = [
                'total_aktif' => Akreditasi::whereIn('status', [3, 4, 5, 6])->count(),
                'verifikasi' => Akreditasi::where('status', 3)->count(),
                'assessment' => Akreditasi::where('status', 5)->count(),
                'visitasi' => Akreditasi::where('status', 4)->count(),
                'terakreditasi' => Akreditasi::where('status', 1)->count(),
                'ditolak' => Akreditasi::where('status', 2)->count(),
            ];
        } elseif ($isPesantren) {
            $stats = [
                'total_aktif' => Akreditasi::where('user_id', $user->id)->whereIn('status', [3, 4, 5, 6])->count(),
                'verifikasi' => Akreditasi::where('user_id', $user->id)->where('status', 3)->count(),
                'assessment' => Akreditasi::where('user_id', $user->id)->where('status', 5)->count(),
                'visitasi' => Akreditasi::where('user_id', $user->id)->where('status', 4)->count(),
                'terakreditasi' => Akreditasi::where('user_id', $user->id)->where('status', 1)->count(),
                'ditolak' => Akreditasi::where('user_id', $user->id)->where('status', 2)->count(),
            ];
        } elseif ($isAsesor) {
            $asesor = $user->asesor;
            $asesorId = $asesor ? $asesor->id : 0;
            $stats = [
                'total_aktif' => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->whereIn('status', [4, 5]))->count(),
                'verifikasi' => Akreditasi::where('status', 3)->count(),
                'assessment' => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', 5))->count(),
                'visitasi' => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', 4))->count(),
                'terakreditasi' => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', 1))->count(),
                'ditolak' => Akreditasi::where('status', 2)->count(),
            ];
        }

        // 2. Chart Data
        $submissionQuery = Akreditasi::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'));

        if ($isPesantren) {
            $submissionQuery->where('user_id', $user->id);
        } elseif ($isAsesor) {
            $asesorId = $user->asesor?->id ?? 0;
            $submissionQuery->whereHas('assessments', fn($q) => $q->where('asesor_id', $asesorId));
        }

        $monthlySubmissions = $submissionQuery->groupBy('month')->orderBy('month')->get()->pluck('count', 'month')->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $monthlySubmissions[$i] ?? 0;
        }

        // 3. Monitoring Asesor
        $totalAsesor = Asesor::count();
        $totalTugasAktif = Assessment::whereHas('akreditasi', function ($q) {
            $q->whereIn('status', [3, 4, 5]);
        })->count();

        $asesorPunyaTugasIds = Assessment::whereHas('akreditasi', function ($q) {
            $q->whereIn('status', [3, 4, 5]);
        })->pluck('asesor_id')->unique()->toArray();

        $asesorTanpaTugas = $totalAsesor - count($asesorPunyaTugasIds);
        $avgBeban = $totalAsesor > 0 ? round($totalTugasAktif / $totalAsesor, 1) : 0;

        return view('livewire.home', compact(
            'isAdmin',
            'isPesantren',
            'isAsesor',
            'stats',
            'chartData',
            'totalAsesor',
            'totalTugasAktif',
            'asesorTanpaTugas',
            'avgBeban'
        ));
    }
}

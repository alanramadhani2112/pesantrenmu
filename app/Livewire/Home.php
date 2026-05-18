<?php

namespace App\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use App\Models\Assessment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Home extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->canAccessAdminArea();
        $isPesantren = $user->isPesantren();
        $isAsesor = $user->isAsesor();
        $stats = [
            'total_aktif' => 0,
            'verifikasi' => 0,
            'assessment' => 0,
            'visitasi' => 0,
            'terakreditasi' => 0,
            'ditolak' => 0,
        ];

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
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM created_at)',
            default => 'MONTH(created_at)',
        };

        $submissionQuery = Akreditasi::selectRaw($monthExpression . ' as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'));

        if ($isPesantren) {
            $submissionQuery->where('user_id', $user->id);
        } elseif ($isAsesor) {
            $asesorId = $user->asesor?->id ?? 0;
            $submissionQuery->whereHas('assessments', fn($q) => $q->where('asesor_id', $asesorId));
        }

        $monthlySubmissions = $submissionQuery
            ->groupByRaw($monthExpression)
            ->orderByRaw($monthExpression)
            ->get()
            ->pluck('count', 'month')
            ->toArray();

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

        // 4. Greeting based on time
        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        // 5. Recent Activity (5 latest)
        $recentQuery = Akreditasi::with(['user.pesantren'])
            ->orderBy('updated_at', 'desc')
            ->limit(5);

        if ($isPesantren) {
            $recentQuery->where('user_id', $user->id);
        } elseif ($isAsesor) {
            $asesorId = $user->asesor?->id ?? 0;
            $recentQuery->whereHas('assessments', fn($q) => $q->where('asesor_id', $asesorId));
        }

        $recentActivities = $recentQuery->get()->map(function ($akreditasi) {
            return [
                'id' => $akreditasi->id,
                'uuid' => $akreditasi->uuid,
                'pesantren_name' => $akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name,
                'status' => (int) $akreditasi->status,
                'status_label' => Akreditasi::getStatusLabel($akreditasi->status),
                'updated_at' => $akreditasi->updated_at,
                'peringkat' => $akreditasi->peringkat,
            ];
        });

        // 6. Pesantren Readiness Checklist
        $readiness = [];
        if ($isPesantren) {
            $pesantren = $user->pesantren;
            $ipm = $pesantren ? $user->ipm : null;
            $sdmCount = $pesantren ? $user->sdm()->count() : 0;
            $edpmCount = $pesantren ? $user->edpms()->count() : 0;

            $docFields = ['status_kepemilikan_tanah','sertifikat_nsp','rk_anggaran','silabus_rpp','peraturan_kepegawaian','file_lk_iapm','laporan_tahunan','dok_profil','dok_nsp','dok_renstra','dok_rk_anggaran','dok_kurikulum','dok_silabus_rpp','dok_kepengasuhan','dok_peraturan_kepegawaian','dok_sarpras','dok_laporan_tahunan','dok_sop'];
            $docFilled = 0;
            if ($pesantren) {
                foreach ($docFields as $field) {
                    if (!empty($pesantren->$field)) $docFilled++;
                }
            }

            $readiness = [
                ['key' => 'profil', 'label' => 'Profil Pesantren', 'done' => $pesantren && !empty($pesantren->nama_pesantren) && !empty($pesantren->alamat), 'route' => 'pesantren.profile'],
                ['key' => 'ipm', 'label' => 'Data IPM', 'done' => $ipm && (!empty($ipm->nsp_file) || !empty($ipm->lulus_santri_file)), 'route' => 'pesantren.ipm'],
                ['key' => 'sdm', 'label' => 'Data SDM', 'done' => $sdmCount > 0, 'route' => 'pesantren.sdm'],
                ['key' => 'edpm', 'label' => 'Evaluasi Diri (EDPM)', 'done' => $edpmCount > 0, 'route' => 'pesantren.edpm'],
                ['key' => 'dokumen', 'label' => "Dokumen ($docFilled/" . count($docFields) . ")", 'done' => $docFilled >= 7, 'route' => 'pesantren.profile'],
            ];
        }

        return view('livewire.home', compact(
            'isAdmin',
            'isPesantren',
            'isAsesor',
            'stats',
            'chartData',
            'totalAsesor',
            'totalTugasAktif',
            'asesorTanpaTugas',
            'avgBeban',
            'greeting',
            'recentActivities',
            'readiness'
        ));
    }
}

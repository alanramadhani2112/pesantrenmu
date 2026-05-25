<?php

namespace App\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use App\Models\Assessment;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Home extends Component
{
    private function activeStatuses(): array
    {
        return [
            AkreditasiStateMachine::STATUS_PENGAJUAN,
            AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
            AkreditasiStateMachine::STATUS_ASSESSMENT,
            AkreditasiStateMachine::STATUS_VISITASI,
            AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        ];
    }

    private function statusStatsSelect(): string
    {
        $activeStatuses = implode(',', $this->activeStatuses());

        return sprintf(
            'SUM(CASE WHEN status IN (%s) THEN 1 ELSE 0 END) as total_aktif,
                SUM(CASE WHEN status = %d THEN 1 ELSE 0 END) as verifikasi,
                SUM(CASE WHEN status = %d THEN 1 ELSE 0 END) as assessment,
                SUM(CASE WHEN status = %d THEN 1 ELSE 0 END) as visitasi,
                SUM(CASE WHEN status = %d THEN 1 ELSE 0 END) as terakreditasi,
                SUM(CASE WHEN status = %d THEN 1 ELSE 0 END) as ditolak',
            $activeStatuses,
            AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
            AkreditasiStateMachine::STATUS_ASSESSMENT,
            AkreditasiStateMachine::STATUS_VISITASI,
            AkreditasiStateMachine::STATUS_SELESAI,
            AkreditasiStateMachine::STATUS_DITOLAK,
        );
    }

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

        // P-7 fix: single aggregated query instead of 6 sequential COUNT(*) per render.
        // Each role gets one round-trip instead of six.
        if ($isAdmin) {
            $row = Akreditasi::selectRaw($this->statusStatsSelect())->first();
            $stats = [
                'total_aktif'   => (int) ($row->total_aktif ?? 0),
                'verifikasi'    => (int) ($row->verifikasi ?? 0),
                'assessment'    => (int) ($row->assessment ?? 0),
                'visitasi'      => (int) ($row->visitasi ?? 0),
                'terakreditasi' => (int) ($row->terakreditasi ?? 0),
                'ditolak'       => (int) ($row->ditolak ?? 0),
            ];
        } elseif ($isPesantren) {
            $row = Akreditasi::where('user_id', $user->id)->selectRaw($this->statusStatsSelect())->first();
            $stats = [
                'total_aktif'   => (int) ($row->total_aktif ?? 0),
                'verifikasi'    => (int) ($row->verifikasi ?? 0),
                'assessment'    => (int) ($row->assessment ?? 0),
                'visitasi'      => (int) ($row->visitasi ?? 0),
                'terakreditasi' => (int) ($row->terakreditasi ?? 0),
                'ditolak'       => (int) ($row->ditolak ?? 0),
            ];
        } elseif ($isAsesor) {
            $asesor = $user->asesor;
            $asesorId = $asesor ? $asesor->id : 0;
            $stats = [
                'total_aktif'   => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->whereIn('status', $this->activeStatuses()))->count(),
                'verifikasi'    => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS))->count(),
                'assessment'    => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', AkreditasiStateMachine::STATUS_ASSESSMENT))->count(),
                'visitasi'      => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', AkreditasiStateMachine::STATUS_VISITASI))->count(),
                'terakreditasi' => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', AkreditasiStateMachine::STATUS_SELESAI))->count(),
                'ditolak'       => Assessment::where('asesor_id', $asesorId)->whereHas('akreditasi', fn($q) => $q->where('status', AkreditasiStateMachine::STATUS_DITOLAK))->count(),
            ];
        }

        // 2. Chart Data
        // L-1 fix: driver-specific SQL literal diekstrak ke konstanta agar
        // tidak ada string interpolasi yang bisa jadi injection vector di masa depan.
        // Semua nilai adalah literal statis dari driver name, bukan user input.
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql'  => 'EXTRACT(MONTH FROM created_at)',
            default  => 'MONTH(created_at)',
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
            $q->whereIn('status', [AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, AkreditasiStateMachine::STATUS_ASSESSMENT, AkreditasiStateMachine::STATUS_VISITASI]);
        })->count();

        $asesorPunyaTugasIds = Assessment::whereHas('akreditasi', function ($q) {
            $q->whereIn('status', [AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, AkreditasiStateMachine::STATUS_ASSESSMENT, AkreditasiStateMachine::STATUS_VISITASI]);
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
            $edpmCount = $pesantren ? $user->edpms()->count() : 0;
            $progressService = app(\App\Services\SidebarProgressService::class);
            $sectionProgress = [
                'profil' => $progressService->getSectionProgress($user->id, 'profil')['status'],
                'ipm' => $progressService->getSectionProgress($user->id, 'ipm')['status'],
                'sdm' => $progressService->getSectionProgress($user->id, 'sdm')['status'],
            ];

            $docFields = ['status_kepemilikan_tanah','sertifikat_nsp','rk_anggaran','silabus_rpp','peraturan_kepegawaian','file_lk_iapm','laporan_tahunan','dok_profil','dok_nsp','dok_renstra','dok_rk_anggaran','dok_kurikulum','dok_silabus_rpp','dok_kepengasuhan','dok_peraturan_kepegawaian','dok_sarpras','dok_laporan_tahunan','dok_sop'];
            $docFilled = 0;
            if ($pesantren) {
                foreach ($docFields as $field) {
                    if (!empty($pesantren->$field)) $docFilled++;
                }
            }

            $readiness = [
                ['key' => 'profil', 'label' => 'Profil Pesantren', 'done' => $sectionProgress['profil'] === 'complete', 'route' => 'pesantren.profile'],
                ['key' => 'ipm', 'label' => 'Data IPM', 'done' => $sectionProgress['ipm'] === 'complete', 'route' => 'pesantren.ipm'],
                ['key' => 'sdm', 'label' => 'Data SDM', 'done' => $sectionProgress['sdm'] === 'complete', 'route' => 'pesantren.sdm'],
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

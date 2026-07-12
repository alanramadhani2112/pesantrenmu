<?php

namespace App\Http\Controllers;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\SidebarProgressService;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $isAdmin = $user->isAdmin();
        $isAdminArea = $user->canAccessAdminArea();
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

        if ($isAdminArea) {
            $row = Akreditasi::selectRaw($this->statusStatsSelect())->first();
            $stats = $this->normalizeStats($row);
        } elseif ($isPesantren) {
            $row = Akreditasi::where('user_id', $user->id)->selectRaw($this->statusStatsSelect())->first();
            $stats = $this->normalizeStats($row);
        } elseif ($isAsesor) {
            $asesor = $user->asesor;
            if ($asesor) {
                $row = Assessment::query()
                    ->join('akreditasis', 'akreditasis.id', '=', 'assessments.akreditasi_id')
                    ->where('assessments.asesor_id', $asesor->id)
                    ->selectRaw($this->statusStatsSelect('akreditasis.status'))
                    ->first();

                $stats = $this->normalizeStats($row);
            }
        }

        // Chart Data
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM created_at)',
            default => 'MONTH(created_at)',
        };

        $submissionQuery = Akreditasi::selectRaw($monthExpression.' as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'));

        if ($isPesantren) {
            $submissionQuery->where('user_id', $user->id);
        } elseif ($isAsesor) {
            $asesorId = $user->asesor?->id ?? 0;
            $submissionQuery->whereHas('assessments', fn ($q) => $q->where('asesor_id', $asesorId));
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

        // Monitoring Asesor
        $totalAsesor = 0;
        $totalTugasAktif = 0;
        $asesorTanpaTugas = 0;
        $avgBeban = 0;
        $totalPesantren = 0;
        $totalAkun = 0;

        if ($isAdminArea) {
            $totalAsesor = Asesor::count();
            $totalPesantren = Pesantren::count();
            $totalAkun = User::count();

            $monitoring = Assessment::query()
                ->join('akreditasis', 'akreditasis.id', '=', 'assessments.akreditasi_id')
                ->whereIn('akreditasis.status', [
                    AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
                    AkreditasiStateMachine::STATUS_ASSESSMENT,
                    AkreditasiStateMachine::STATUS_VISITASI,
                ])
                ->selectRaw('COUNT(*) as total_tugas_aktif, COUNT(DISTINCT assessments.asesor_id) as asesor_punya_tugas')
                ->first();

            $totalTugasAktif = (int) ($monitoring->total_tugas_aktif ?? 0);
            $asesorPunyaTugas = (int) ($monitoring->asesor_punya_tugas ?? 0);
            $asesorTanpaTugas = max(0, $totalAsesor - $asesorPunyaTugas);
            $avgBeban = $totalAsesor > 0 ? round($totalTugasAktif / $totalAsesor, 1) : 0;
        }

        // Greeting based on time
        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        // Recent Activity (5 latest)
        $recentQuery = Akreditasi::with(['user.pesantren'])
            ->orderBy('updated_at', 'desc')
            ->limit(5);

        if ($isPesantren) {
            $recentQuery->where('user_id', $user->id);
        } elseif ($isAsesor) {
            $asesorId = $user->asesor?->id ?? 0;
            $recentQuery->whereHas('assessments', fn ($q) => $q->where('asesor_id', $asesorId));
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

        // Pesantren Readiness Checklist
        $readiness = [];
        if ($isPesantren) {
            $pesantren = $user->pesantren;
            $progressService = app(SidebarProgressService::class);
            $sectionProgress = [
                'profil' => $progressService->getSectionProgress($user->id, 'profil'),
                'ipm' => $progressService->getSectionProgress($user->id, 'ipm'),
                'sdm' => $progressService->getSectionProgress($user->id, 'sdm'),
                'edpm' => $progressService->getSectionProgress($user->id, 'edpm'),
            ];

            $readiness = [
                ['key' => 'profil', 'label' => 'Profil Pesantren', 'done' => $sectionProgress['profil']['status'] === 'complete', 'route' => 'pesantren.profile', 'meta' => $sectionProgress['profil']['filled'].'/'.$sectionProgress['profil']['total']],
                ['key' => 'ipm', 'label' => 'IPM', 'done' => $sectionProgress['ipm']['status'] === 'complete', 'route' => 'pesantren.ipm', 'meta' => $sectionProgress['ipm']['filled'].'/'.$sectionProgress['ipm']['total']],
                ['key' => 'sdm', 'label' => 'Data SDM', 'done' => $sectionProgress['sdm']['status'] === 'complete', 'route' => 'pesantren.sdm', 'meta' => $sectionProgress['sdm']['filled'].'/'.$sectionProgress['sdm']['total']],
                ['key' => 'edpm', 'label' => 'EDPM/IPR', 'done' => $sectionProgress['edpm']['status'] === 'complete', 'route' => 'pesantren.edpm', 'meta' => $sectionProgress['edpm']['filled'].'/'.$sectionProgress['edpm']['total']],
            ];
        }

        return view('dashboard.index', compact(
            'isSuperAdmin',
            'isAdmin',
            'isAdminArea',
            'isPesantren',
            'isAsesor',
            'stats',
            'chartData',
            'totalAsesor',
            'totalTugasAktif',
            'asesorTanpaTugas',
            'avgBeban',
            'totalPesantren',
            'totalAkun',
            'greeting',
            'recentActivities',
            'readiness'
        ));
    }

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

    private function statusStatsSelect(string $statusColumn = 'status'): string
    {
        $activeStatuses = implode(',', array_map('intval', $this->activeStatuses()));

        return sprintf(
            'SUM(CASE WHEN %1$s IN (%2$s) THEN 1 ELSE 0 END) as total_aktif,
                SUM(CASE WHEN %1$s = %3$d THEN 1 ELSE 0 END) as verifikasi,
                SUM(CASE WHEN %1$s = %4$d THEN 1 ELSE 0 END) as assessment,
                SUM(CASE WHEN %1$s = %5$d THEN 1 ELSE 0 END) as visitasi,
                SUM(CASE WHEN %1$s = %6$d THEN 1 ELSE 0 END) as terakreditasi,
                SUM(CASE WHEN %1$s = %7$d THEN 1 ELSE 0 END) as ditolak',
            $statusColumn,
            $activeStatuses,
            AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
            AkreditasiStateMachine::STATUS_ASSESSMENT,
            AkreditasiStateMachine::STATUS_VISITASI,
            AkreditasiStateMachine::STATUS_SELESAI,
            AkreditasiStateMachine::STATUS_DITOLAK,
        );
    }

    private function normalizeStats(?object $row): array
    {
        return [
            'total_aktif' => (int) ($row->total_aktif ?? 0),
            'verifikasi' => (int) ($row->verifikasi ?? 0),
            'assessment' => (int) ($row->assessment ?? 0),
            'visitasi' => (int) ($row->visitasi ?? 0),
            'terakreditasi' => (int) ($row->terakreditasi ?? 0),
            'ditolak' => (int) ($row->ditolak ?? 0),
        ];
    }
}

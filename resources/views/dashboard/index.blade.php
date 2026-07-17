@extends('layouts.app')

@section('content')
@php
    $roleLabel = $isSuperAdmin ? 'Super Admin' : ($isAdmin ? 'Admin' : ($isPesantren ? 'Pesantren' : 'Asesor'));
    $pageSubtitle = match (true) {
        $isSuperAdmin => 'Pantau kesehatan sistem, governance akses, dan operasi akreditasi.',
        $isAdmin => 'Kelola pengajuan akreditasi dan pantau kinerja asesor.',
        $isPesantren => 'Pantau kesiapan data dan status pengajuan akreditasi Anda.',
        $isAsesor => 'Selesaikan tugas penilaian dan visitasi yang ditugaskan.',
        default => 'Ringkasan aktivitas akreditasi pesantren.',
    };

    $primaryAction = match (true) {
        $isSuperAdmin => ['label' => 'Kelola Hak Akses', 'route' => route('admin.role-permission.index')],
        $isAdmin => ['label' => 'Kelola Akreditasi', 'route' => route('admin.akreditasi')],
        $isPesantren => ['label' => 'Pengajuan Akreditasi', 'route' => route('pesantren.akreditasi')],
        $isAsesor => ['label' => 'Lihat Tugas', 'route' => route('asesor.akreditasi')],
        default => null,
    };

    $statCards = match (true) {
        ($isSuperAdmin || $isAdmin) => [
            ['label' => 'Perlu Anda Verifikasi', 'value' => $stats['verifikasi'], 'variant' => 'warning', 'icon' => 'timer'],
            ['label' => 'Sedang Dinilai Asesor', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Proses Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Pengajuan Berjalan', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Berhasil Terakreditasi', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
            ['label' => 'Perlu Diperbaiki', 'value' => $stats['ditolak'], 'variant' => 'danger', 'icon' => 'cross-circle'],
        ],
        $isPesantren => [
            ['label' => 'Pengajuan Berjalan', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Sedang Dinilai Asesor', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Proses Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Berhasil Terakreditasi', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
            ['label' => 'Perlu Diperbaiki', 'value' => $stats['ditolak'], 'variant' => 'danger', 'icon' => 'cross-circle'],
        ],
        $isAsesor => [
            ['label' => 'Tugas Aktif Anda', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Perlu Anda Nilai', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Jadwal Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Tugas Selesai', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
        ],
        default => [],
    };

    $hasMonthlyData = array_sum($chartData) > 0;
    $hasStatusData = ($stats['terakreditasi'] + $stats['ditolak']) > 0;

    $userName = auth()->user()->name;
    $firstName = trim(explode(' ', $userName)[0] ?? $userName);
    $today = \Carbon\Carbon::now()->translatedFormat('l, d F Y');

    $contextualMessage = match (true) {
        $isSuperAdmin && $stats['verifikasi'] > 0 => "Ada {$stats['verifikasi']} pengajuan dan area governance yang perlu dipantau hari ini.",
        $isSuperAdmin => 'Panel kendali sistem siap digunakan. Pantau akses, akun, dan operasi utama dari sini.',
        $isAdmin && $stats['verifikasi'] > 0 => "Ada {$stats['verifikasi']} pengajuan yang perlu Anda verifikasi hari ini.",
        $isAdmin => 'Semua pengajuan sudah ditindaklanjuti. Sistem berjalan normal.',
        $isPesantren && $stats['ditolak'] > 0 => "Ada {$stats['ditolak']} pengajuan yang perlu diperbaiki. Cek catatan dari asesor.",
        $isPesantren && $stats['total_aktif'] > 0 => 'Pengajuan Anda sedang diproses. Pantau perkembangannya di halaman pengajuan.',
        $isPesantren => 'Lengkapi data pesantren Anda untuk memulai pengajuan akreditasi.',
        $isAsesor && $stats['total_aktif'] > 0 => "Anda memiliki {$stats['total_aktif']} tugas yang perlu diselesaikan.",
        $isAsesor => 'Belum ada tugas baru. Anda akan diberitahu saat ada penugasan.',
        default => 'Selamat datang di PesantrenMu.',
    };

    $quickActions = match (true) {
        $isSuperAdmin => [
            ['label' => 'Hak Akses', 'icon' => 'security-user', 'route' => route('admin.role-permission.index'), 'variant' => 'primary'],
            ['label' => 'Role Sistem', 'icon' => 'key', 'route' => route('admin.roles.index'), 'variant' => 'info'],
            ['label' => 'Akun Pengguna', 'icon' => 'profile-user', 'route' => route('accounts.index'), 'variant' => 'success'],
            ['label' => 'Notif Gagal', 'icon' => 'notification-bing', 'route' => route('admin.failed-notifications'), 'variant' => 'warning'],
            ['label' => 'Kelola Akreditasi', 'icon' => 'shield-tick', 'route' => route('admin.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Trash', 'icon' => 'trash', 'route' => route('admin.trash'), 'variant' => 'danger'],
        ],
        $isAdmin => [
            ['label' => 'Kelola Akreditasi', 'icon' => 'shield-tick', 'route' => route('admin.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Data Pesantren', 'icon' => 'category', 'route' => route('admin.pesantren.index'), 'variant' => 'info'],
            ['label' => 'Data Asesor', 'icon' => 'profile-user', 'route' => route('admin.asesor.index'), 'variant' => 'success'],
            ['label' => 'Master EDPM', 'icon' => 'document', 'route' => route('admin.master-edpm'), 'variant' => 'warning'],
        ],
        $isPesantren => [
            ['label' => 'Profil Pesantren', 'icon' => 'profile-user', 'route' => route('pesantren.profile'), 'variant' => 'primary'],
            ['label' => 'IPM', 'icon' => 'data', 'route' => route('pesantren.ipm'), 'variant' => 'info'],
            ['label' => 'Data SDM', 'icon' => 'people', 'route' => route('pesantren.sdm'), 'variant' => 'success'],
            ['label' => 'EDPM', 'icon' => 'document', 'route' => route('pesantren.edpm'), 'variant' => 'warning'],
        ],
        $isAsesor => [
            ['label' => 'Tugas Akreditasi', 'icon' => 'shield-tick', 'route' => route('asesor.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Profil Asesor', 'icon' => 'profile-user', 'route' => route('asesor.profile'), 'variant' => 'info'],
        ],
        default => [],
    };

    $recentRouteFor = function ($uuid) use ($isAdmin, $isPesantren, $isAsesor) {
        if ($isAdmin) return route('admin.akreditasi-detail', $uuid);
        if ($isPesantren) return route('pesantren.akreditasi-detail', $uuid);
        if ($isAsesor) return route('asesor.akreditasi-detail', $uuid);
        return '#';
    };

    $pesantrenNextAction = null;
    if ($isPesantren) {
        $nextReadiness = collect($readiness)->firstWhere('done', false);
        $pesantrenNextAction = $stats['total_aktif'] > 0
            ? [
                'label' => 'Pantau Pengajuan',
                'route' => $activeAkreditasiUuid ? route('pesantren.akreditasi-detail', $activeAkreditasiUuid) : route('pesantren.akreditasi'),
                'copy' => 'Pengajuan sedang diproses',
            ]
            : ($nextReadiness
                ? ['label' => $nextReadiness['label'], 'route' => route($nextReadiness['route']), 'copy' => 'Lengkapi bagian ini dulu']
                : ['label' => 'Ajukan Akreditasi', 'route' => route('pesantren.akreditasi'), 'copy' => 'Semua data utama siap']);

        $readinessMap = collect($readiness)->mapWithKeys(fn ($s) => [route($s['route']), $s['done']])->toArray();
    }

    $latestPesantrenActivity = $isPesantren ? $recentActivities->first() : null;
    $akreditasiTimeline = [
        6 => 'Pengajuan',
        5 => 'Verifikasi',
        4 => 'Assessment',
        3 => 'Visitasi',
        2 => 'Penilaian',
        1 => 'Validasi',
        0 => 'Hasil',
    ];
    $activeTimelineIndex = $latestPesantrenActivity
        ? array_search($latestPesantrenActivity['status'], array_keys($akreditasiTimeline), true)
        : false;
@endphp

<div data-dashboard-page="metronic" data-dashboard-role="{{ $isAsesor ? 'asesor' : ($isPesantren ? 'pesantren' : ($isAdminArea ? 'admin' : 'user')) }}" x-data='dashboardCharts(@json($chartData), @json($stats))'>
    <x-ui.page title="Dashboard" :subtitle="$pageSubtitle">
        <x-slot name="toolbar">
            <x-ui.badge variant="secondary">{{ $roleLabel }}</x-ui.badge>
        </x-slot>

        @unless($isPesantren && $latestPesantrenActivity && $stats['total_aktif'] > 0)
        {{-- Greeting Hero --}}
        <div class="spm-dashboard-hero rounded p-5 mb-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4 position-relative">
                <div class="d-flex flex-column flex-grow-1">
                    <div class="text-white opacity-75 fw-semibold fs-8 fs-md-7 mb-1">{{ $today }}</div>
                    <h2 class="text-white fw-semibold fs-3 fs-md-2 mb-2">{{ $greeting }}, {{ $firstName }}.</h2>
                    <div class="text-white opacity-75 fw-semibold fs-7 fs-md-6">{{ $contextualMessage }}</div>
                </div>

                @if($isPesantren && $pesantrenNextAction && $stats['total_aktif'] === 0)
                    <div class="spm-dashboard-next-action">
                        <span class="spm-dashboard-next-action-label">Langkah berikut</span>
                        <span class="spm-dashboard-next-action-title">{{ $pesantrenNextAction['label'] }}</span>
                        <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                            <span class="text-white opacity-75 fs-8 fw-semibold">{{ $pesantrenNextAction['copy'] }}</span>
                            <x-ui.button :href="$pesantrenNextAction['route']" variant="light" size="sm">
                                Buka
                            </x-ui.button>
                        </div>
                    </div>
                @elseif(! $isPesantren && $primaryAction)
                    <div class="flex-shrink-0">
                        <x-ui.button :href="$primaryAction['route']" variant="light" size="sm" class="btn-md-md w-100 w-md-auto">
                            <x-ui.icon name="arrow-right" class="fs-4 me-1" />
                            {{ $primaryAction['label'] }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        @endunless
        {{-- Quick Actions --}}
        @if(count($quickActions) > 0 && ! $isPesantren && ! $isAsesor)
            <div class="row g-3 mb-5 spm-dashboard-quick-actions">
                @foreach($quickActions as $action)
                    <div class="col-6 col-md-4 col-lg-3">
                        <x-ui.action-card
                            :href="$action['route']"
                            :icon="$action['icon']"
                            :title="$action['label']"
                            :variant="$action['variant']"
                            icon-class="fs-3 fs-md-2"
                            class="spm-quick-action spm-quick-action--dashboard"
                        />
                    </div>
                @endforeach
            </div>
        @endif

        @if($isSuperAdmin || $isAdmin)
            <div class="row g-5">
                <div class="col-12 col-lg-7 col-xl-8">
                    <x-ui.card title="Perlu Ditindaklanjuti" subtitle="Proses aktif yang perlu ditindaklanjuti admin." class="h-100 spm-dashboard-stat">
                        <div class="row g-4">
                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-gray-300 bg-body p-4 h-100">
                                    <x-ui.badge variant="warning" class="mb-3">Verifikasi</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['verifikasi'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-4">Pengajuan menunggu validasi awal.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-warning" size="sm">Buka Pengajuan</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-gray-300 bg-body p-4 h-100">
                                    <x-ui.badge variant="info" class="mb-3">Penilaian</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['assessment'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-4">Pengajuan sedang dinilai asesor.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-info" size="sm">Pantau Proses</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-gray-300 bg-body p-4 h-100">
                                    <x-ui.badge variant="primary" class="mb-3">Visitasi</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['visitasi'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-4">Visitasi berjalan atau menunggu hasil.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light" size="sm">Lihat Jadwal</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-lg-5 col-xl-4">
                    <x-ui.card title="Monitoring Asesor" subtitle="Distribusi dan kapasitas tugas aktif." class="h-100 spm-dashboard-stat">
                        <div class="d-flex flex-column">
                            <x-ui.metric-row label="Total Asesor Aktif" :value="$totalAsesor" variant="primary" icon="profile-user" />
                            <x-ui.metric-row label="Total Tugas Aktif" :value="$totalTugasAktif" variant="info" icon="document" />
                            <x-ui.metric-row label="Asesor Tanpa Tugas" :value="$asesorTanpaTugas" variant="danger" icon="people" />
                            <x-ui.metric-row label="Rata-rata Beban" value="{{ $avgBeban }} tugas/asesor" variant="warning" icon="chart-line" class="border-bottom-0" />
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @elseif($isPesantren)
            @php
                $doneCount = collect($readiness)->where('done', true)->count();
                $totalSteps = count($readiness);
                $progressPercent = $totalSteps > 0 ? round(($doneCount / $totalSteps) * 100) : 0;
                $hasActivePengajuan = $latestPesantrenActivity && $stats['total_aktif'] > 0;
                $nextActionTitle = match (true) {
                    ! $latestPesantrenActivity => 'Lengkapi data, lalu ajukan akreditasi.',
                    $latestPesantrenActivity['status'] < 0 => 'Cek catatan dan lakukan perbaikan.',
                    $latestPesantrenActivity['status'] === 6 => 'Menunggu verifikasi admin.',
                    $latestPesantrenActivity['status'] === 5 => 'Berkas sedang diverifikasi admin.',
                    $latestPesantrenActivity['status'] === 4 => 'Menunggu review asesor.',
                    $latestPesantrenActivity['status'] === 3 => 'Pantau jadwal visitasi.',
                    $latestPesantrenActivity['status'] === 2 => 'Menunggu penilaian pasca visitasi.',
                    $latestPesantrenActivity['status'] === 1 => 'Menunggu validasi akhir admin.',
                    default => 'Lihat hasil akhir akreditasi.',
                };
            @endphp

            <div class="row g-5">
                <div class="{{ $hasActivePengajuan ? 'col-12' : 'col-12 col-lg-8' }}">
                    @if($hasActivePengajuan)
                        <x-ui.card title="Pengajuan Berjalan" subtitle="Pantau posisi dan langkah berikutnya dari satu tempat." class="h-100 spm-dashboard-stat">
                            <div class="p-5">
                                <div class="row g-4 align-items-stretch mb-5">
                                    <div class="col-lg-8">
                                        <div class="rounded border border-dashed border-gray-300 bg-body p-4 h-100">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-4">
                                                <div>
                                                    <div class="text-muted fw-semibold fs-8 mb-2">Tahap saat ini</div>
                                                    <h3 class="fw-semibold text-gray-900 mb-2">{{ $latestPesantrenActivity['status_label'] }}</h3>
                                                    <div class="text-muted fw-semibold fs-7">Periode {{ $latestPesantrenActivity['periode'] ?? '-' }} - update {{ $latestPesantrenActivity['updated_at']->translatedFormat('d M Y, H:i') }}</div>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <x-ui.status-badge :variant="\App\Support\AkreditasiStatusPresenter::variant($latestPesantrenActivity['status'])">
                                                        {{ $latestPesantrenActivity['status_label'] }}
                                                    </x-ui.status-badge>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="rounded border border-dashed border-gray-300 bg-body p-4 h-100">
                                            <div class="text-muted fw-semibold fs-8 mb-2">Langkah berikut</div>
                                            <div class="fw-semibold text-gray-900 mb-2">{{ $nextActionTitle }}</div>
                                            <div class="text-muted fs-7">Data terkunci selama pengajuan diproses.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-semibold text-gray-900 fs-7">Alur Akreditasi</span>
                                        <span class="text-muted fw-semibold fs-8">Tahap {{ ($activeTimelineIndex === false ? 0 : $activeTimelineIndex + 1) }}/{{ count($akreditasiTimeline) }}</span>
                                    </div>
                                    <x-ui.progress
                                        :value="($activeTimelineIndex === false ? 0 : (($activeTimelineIndex + 1) / count($akreditasiTimeline)) * 100)"
                                        variant="success"
                                        :label="'Progress Pengajuan'"
                                        :meta="($activeTimelineIndex === false ? 0 : $activeTimelineIndex + 1) . '/' . count($akreditasiTimeline)"
                                        class="spm-dashboard-progress"
                                    />
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-5">
                                    @foreach($akreditasiTimeline as $status => $label)
                                        @php
                                            $index = $loop->index;
                                            $isCurrent = $latestPesantrenActivity['status'] === $status;
                                            $isDone = $activeTimelineIndex !== false && $index < $activeTimelineIndex;
                                            $variant = $isCurrent ? 'primary' : ($isDone ? 'success' : 'secondary');
                                        @endphp
                                        <x-ui.badge :variant="$variant" class="px-3 py-2">
                                            {{ $index + 1 }}. {{ $label }}
                                        </x-ui.badge>
                                    @endforeach
                                </div>

                                @if($latestPesantrenActivity['latest_catatan'] ?? null)
                                <x-ui.alert variant="warning" icon="information-5" title="Catatan terbaru" class="mb-5">
                                        <div style="white-space: pre-line;">{{ $latestPesantrenActivity['latest_catatan'] }}</div>
                                    </x-ui.alert>
                                @endif

                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 border-top pt-5">
                                    <div class="text-muted fw-semibold fs-7">Tidak perlu mengubah data kecuali ada catatan perbaikan dari admin atau asesor.</div>
                                    <x-ui.button :href="$recentRouteFor($latestPesantrenActivity['uuid'])" variant="primary" size="sm">
                                        Lihat Detail Pengajuan
                                    </x-ui.button>
                                </div>
                            </div>
                        </x-ui.card>
                    @else
                        <x-ui.card title="Kesiapan Pengajuan Akreditasi" subtitle="Lengkapi semua data berikut sebelum mengajukan akreditasi.">
                            <div class="d-flex flex-column gap-0">
                                <div class="d-flex align-items-center justify-content-between px-5 pt-4 pb-3">
                                    <span class="fw-semibold text-gray-900 fs-6">{{ $doneCount }}/{{ $totalSteps }} langkah selesai</span>
                                    <span class="fw-semibold fs-6 {{ $progressPercent >= 100 ? 'text-success' : 'text-primary' }}">{{ $progressPercent >= 100 ? 'Siap diajukan' : 'Belum lengkap' }}</span>
                                </div>

                                <div class="px-5 pb-5">
                                    <x-ui.progress :value="$progressPercent" :variant="$progressPercent >= 100 ? 'success' : 'primary'" :label="'Kesiapan Data'" :meta="$progressPercent . '%'" class="spm-dashboard-progress" />
                                </div>

                                <div class="separator"></div>

                                @foreach($readiness as $step)
                                    <a href="{{ route($step['route']) }}" class="d-flex align-items-center gap-4 px-5 py-4 text-decoration-none border-bottom border-dashed spm-readiness-item {{ $step['done'] ? '' : 'spm-readiness-item-pending' }}">
                                        <x-ui.symbol-icon
                                            :icon="$step['done'] ? 'check' : 'information'"
                                            :variant="$step['done'] ? 'success' : 'warning'"
                                            size="35px"
                                            shape="circle"
                                            icon-class="fs-4"
                                        />
                                        <div class="flex-grow-1 min-w-0">
                                            <span class="fw-semibold fs-7 {{ $step['done'] ? 'text-gray-600' : 'text-gray-900' }}">{{ $step['label'] }}</span>
                                            <span class="d-block text-muted fs-8 mt-1">{{ $step['meta'] ?? '' }} terisi</span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <x-ui.badge :variant="$step['done'] ? 'success' : 'warning'">{{ $step['done'] ? 'Lengkap' : 'Lengkapi' }}</x-ui.badge>
                                        </div>
                                    </a>
                                @endforeach

                                @if($progressPercent >= 100)
                                    <div class="px-5 py-4 bg-body border-top border-dashed">
                                        <div class="d-flex align-items-center gap-3">
                                            <x-ui.icon name="check-circle" class="fs-2x text-success" />
                                            <div>
                                                <div class="fw-semibold text-gray-900">Data Anda sudah lengkap!</div>
                                                <div class="text-muted fs-7">Anda bisa mengajukan akreditasi sekarang.</div>
                                            </div>
                                            <x-ui.button :href="route('pesantren.akreditasi')" variant="success" size="sm" class="ms-auto">Ajukan Akreditasi</x-ui.button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </x-ui.card>
                    @endif
                </div>

                @unless($hasActivePengajuan)
                <div class="col-12 col-lg-4">
                    <x-ui.card title="Aksi Berikutnya" subtitle="Panduan singkat untuk langkah Anda." class="h-100 spm-dashboard-stat">
                        <div class="p-5 d-flex flex-column gap-4">
                            <div class="rounded bg-body border border-dashed border-gray-300 p-4">
                                <div class="fw-semibold text-gray-900 mb-1">{{ $nextActionTitle }}</div>
                                <div class="text-muted fs-7">{{ $hasActivePengajuan ? 'Tidak perlu mengubah data kecuali ada catatan perbaikan.' : 'Mulai dari bagian yang belum lengkap.' }}</div>
                            </div>

                            @if($hasActivePengajuan)
                                <div class="rounded bg-body border border-dashed border-gray-300 p-4">
                                    <div class="fw-semibold text-gray-900 mb-1">Data Pesantren Terkunci</div>
                                    <div class="text-muted fs-7">Profil, IPM, SDM, dan EDPM/IPR dikunci selama pengajuan berjalan.</div>
                                </div>
                                <x-ui.button :href="route('pesantren.akreditasi')" variant="light-primary" size="sm" class="w-100">Ke Pusat Akreditasi</x-ui.button>
                            @else
                                <x-ui.button :href="$pesantrenNextAction['route']" variant="primary" size="sm" class="w-100">{{ $pesantrenNextAction['label'] }}</x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                </div>
                @endunless
            </div>
        @elseif($isAsesor)
            @php
                $asesorWorkflow = [
                    [
                        'label' => 'Review Berkas',
                        'copy' => 'Cek profil, IPM, SDM, dan EDPM sebelum visitasi.',
                        'value' => $stats['assessment'],
                        'route' => route('asesor.akreditasi.review'),
                        'variant' => 'primary',
                        'icon' => 'eye',
                    ],
                    [
                        'label' => 'Atur Jadwal',
                        'copy' => 'Tetapkan jadwal visitasi untuk pengajuan siap visitasi.',
                        'value' => $stats['visitasi'],
                        'route' => route('asesor.akreditasi.jadwal'),
                        'variant' => 'warning',
                        'icon' => 'calendar-tick',
                    ],
                    [
                        'label' => 'Input Nilai',
                        'copy' => 'Isi instrumen penilaian setelah visitasi selesai.',
                        'value' => $stats['assessment'],
                        'route' => route('asesor.akreditasi.nilai'),
                        'variant' => 'info',
                        'icon' => 'pencil',
                    ],
                    [
                        'label' => 'Upload Laporan',
                        'copy' => 'Lengkapi laporan individu dan kelompok visitasi.',
                        'value' => $stats['visitasi'],
                        'route' => route('asesor.akreditasi.laporan-visitasi'),
                        'variant' => 'success',
                        'icon' => 'file-up',
                    ],
                ];
            @endphp

            <div class="row g-5 spm-asesor-dashboard-workspace">
                <div class="col-12 col-xl-8">
                    <x-ui.card title="Fokus Hari Ini" subtitle="Mulai dari tahap paling kiri yang masih memiliki pekerjaan." class="h-100 spm-asesor-focus-card spm-dashboard-stat">
                        <div class="d-flex flex-column gap-4">
                            @foreach($asesorWorkflow as $step)
                                <x-ui.action-card
                                    :href="$step['route']"
                                    :icon="$step['icon']"
                                    :variant="$step['variant']"
                                    :description="$step['copy']"
                                    align="start"
                                    class="spm-asesor-workflow-item"
                                >
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="fw-semibold text-gray-900 fs-6">{{ $step['label'] }}</span>
                                        <x-ui.badge :variant="$step['variant']">{{ $step['value'] }} tugas</x-ui.badge>
                                    </div>

                                    <x-slot:actions>
                                        <x-ui.badge variant="secondary">Buka</x-ui.badge>
                                    </x-slot:actions>
                                </x-ui.action-card>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-xl-4">
                    <x-ui.card title="Ringkasan" subtitle="Status pekerjaan asesor saat ini." class="h-100 spm-asesor-summary-card spm-dashboard-stat">
                        <div class="d-flex flex-column gap-4">
                            <div class="rounded border border-dashed border-gray-300 bg-body p-4">
                                <div class="text-muted fw-semibold fs-8 mb-2">Tugas aktif</div>
                                <div class="fs-2x fw-semibold text-gray-900 mb-2">{{ $stats['total_aktif'] }}</div>
                                <x-ui.button :href="route('asesor.akreditasi')" variant="light-primary" size="sm">Lihat Semua Tugas</x-ui.button>
                            </div>
                            <div class="d-flex flex-column">
                                <x-ui.metric-row label="Perlu Penilaian" :value="$stats['assessment']" variant="info" icon="document" />
                                <x-ui.metric-row label="Tahap Visitasi" :value="$stats['visitasi']" variant="warning" icon="geolocation" />
                                <x-ui.metric-row label="Selesai" :value="$stats['terakreditasi']" variant="success" icon="check-circle" class="border-bottom-0" />
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @endif

        @unless($isPesantren || $isAsesor)
        <div class="row g-5 mt-0">
            @foreach($statCards as $card)
                <div class="col-6 col-lg-4">
                    <x-ui.stat-card
                        class="spm-dashboard-stat"
                        :label="$card['label']"
                        :value="$card['value']"
                        :variant="$card['variant']"
                        :icon="$card['icon']"
                    />
                </div>
            @endforeach
        </div>

        <div class="row g-5 mt-0">
            <div class="col-12 col-lg-7 col-xl-8">
                <x-ui.card title="Pengajuan Akreditasi per Bulan" subtitle="Tren pengajuan tahun {{ date('Y') }}" class="h-100 spm-dashboard-stat">
                    <x-slot name="toolbar">
                        <x-ui.badge variant="primary">{{ date('Y') }}</x-ui.badge>
                    </x-slot>

                    @if($hasMonthlyData)
                        <div class="h-300px">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Belum ada pengajuan tahun ini"
                            description="Data grafik akan muncul setelah pengajuan akreditasi mulai masuk."
                            class="min-h-300px"
                        >
                            <x-slot name="icon">
                                <x-ui.icon name="chart-line-up" class="fs-2x" />
                            </x-slot>
                            @if($isPesantren)
                                <x-slot name="action">
                                    <x-ui.button :href="route('pesantren.akreditasi')" variant="primary" size="sm">
                                        Mulai Pengajuan
                                    </x-ui.button>
                                </x-slot>
                            @endif
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>
            </div>

            <div class="col-12 col-lg-5 col-xl-4">
                <x-ui.card title="Distribusi Status" subtitle="Hasil akhir pengajuan akreditasi." class="h-100 spm-dashboard-stat">
                    @if($hasStatusData)
                        <div class="d-flex flex-column align-items-center justify-content-center">
                            <div class="position-relative h-225px w-225px d-flex align-items-center justify-content-center">
                                <canvas id="statusChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                    <span class="fs-2hx fw-semibold text-gray-900">{{ $stats['terakreditasi'] + $stats['ditolak'] }}</span>
                                <span class="d-block text-muted fw-semibold fs-8">Selesai</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center gap-4 mt-5">
                                <span class="d-flex align-items-center gap-2 text-muted fw-semibold fs-7">
                                    <span class="bullet bullet-dot bg-success"></span>
                                    Terakreditasi
                                </span>
                                <span class="d-flex align-items-center gap-2 text-muted fw-semibold fs-7">
                                    <span class="bullet bullet-dot bg-danger"></span>
                                    Ditolak
                                </span>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state
                            title="Belum ada hasil akhir"
                            description="Ringkasan status akan tersedia setelah pengajuan selesai divalidasi."
                            class="min-h-200px"
                            variant="info"
                        >
                            <x-slot name="icon">
                                <x-ui.icon name="chart-pie-simple" class="fs-2x" />
                            </x-slot>
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>
            </div>
        </div>

        @endunless

        @unless($isPesantren)
        {{-- Recent Activity --}}
                    <div class="row g-5 mt-0">
            <div class="col-12">
                <x-ui.card
                    title="Aktivitas Terbaru"
                    subtitle="{{ $isAdmin ? 'Pengajuan akreditasi terbaru dari seluruh pesantren.' : ($isPesantren ? 'Riwayat pengajuan akreditasi pesantren Anda.' : 'Tugas penilaian terbaru yang ditugaskan kepada Anda.') }}"
                >
                    @if($recentActivities->count() > 0)
                        @if($isPesantren)
                        <x-ui.simple-table>
                            <thead>
                                <tr class="fs-8 fw-semibold text-muted">
                                    <th class="min-w-100px ps-4">Periode</th>
                                    <th class="min-w-120px">Tahapan</th>
                                    <th class="min-w-120px">Status</th>
                                    <th class="min-w-150px d-none d-sm-table-cell">Update Terakhir</th>
                                    <th class="text-end pe-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivities as $activity)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-gray-900">{{ $activity['periode'] ?? '-' }}</td>
                                        <td>{{ ucfirst($activity['tahapan'] ?? '-') }}</td>
                                        <td>
                                            <x-ui.status-badge :variant="\App\Support\AkreditasiStatusPresenter::variant($activity['status'])">
                                                {{ $activity['status_label'] }}
                                            </x-ui.status-badge>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="text-muted fw-semibold fs-7">
                                                {{ $activity['updated_at']->translatedFormat('d M Y, H:i') }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <x-ui.button :href="$recentRouteFor($activity['uuid'])" variant="light" size="sm" class="btn-icon btn-sm-auto">
                                                <x-ui.icon name="eye" class="fs-5" />
                                                <span class="d-none d-sm-inline ms-1">Detail</span>
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.simple-table>
                    @else
                        <x-ui.simple-table>
                            <thead>
                                <tr class="fs-8 fw-semibold text-muted">
                                    <th class="min-w-200px ps-4">Pesantren</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-100px d-none d-md-table-cell">Peringkat</th>
                                    <th class="min-w-150px d-none d-sm-table-cell">Terakhir Diperbarui</th>
                                    <th class="text-end pe-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivities as $activity)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <x-ui.symbol-icon :text="strtoupper(substr($activity['pesantren_name'], 0, 1))" variant="primary" label-class="fw-semibold" />
                                                <div class="d-flex flex-column min-w-0">
                                                    <span class="text-gray-900 fw-semibold fs-7 fs-md-6 text-truncate">{{ $activity['pesantren_name'] }}</span>
                                                    <span class="text-muted fw-semibold fs-8 d-sm-none">
                                                        {{ $activity['updated_at']->translatedFormat('d M Y') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <x-ui.status-badge :variant="\App\Support\AkreditasiStatusPresenter::variant($activity['status'])">
                                                {{ $activity['status_label'] }}
                                            </x-ui.status-badge>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @if($activity['peringkat'])
                                                <span class="fw-semibold text-gray-700">{{ $activity['peringkat'] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="text-muted fw-semibold fs-7">
                                                {{ $activity['updated_at']->translatedFormat('d M Y, H:i') }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <x-ui.button :href="$recentRouteFor($activity['uuid'])" variant="light" size="sm" class="btn-icon btn-sm-auto">
                                                <x-ui.icon name="eye" class="fs-5" />
                                                <span class="d-none d-sm-inline ms-1">Detail</span>
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.simple-table>
                    @endif
                    @else
                        <x-ui.empty-state
                            title="Belum ada aktivitas"
                            description="Aktivitas terbaru akan muncul setelah ada pengajuan akreditasi."
                            class="min-h-200px"
                            variant="info"
                        >
                            <x-slot name="icon">
                                <x-ui.icon name="time" class="fs-2x" />
                            </x-slot>
                            @if($isSuperAdmin || $isAdmin)
                                <x-slot name="action">
                                    <x-ui.button :href="route('admin.pesantren.index')" variant="primary" size="sm">
                                        Kelola Data Pesantren
                                    </x-ui.button>
                                </x-slot>
                            @elseif($isPesantren)
                                <x-slot name="action">
                                    <x-ui.button :href="$pesantrenNextAction['route']" variant="primary" size="sm">
                                        {{ $pesantrenNextAction['label'] }}
                                    </x-ui.button>
                                </x-slot>
                            @endif
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>
            </div>
        </div>
        @endunless
    </x-ui.page>
</div>
@endsection

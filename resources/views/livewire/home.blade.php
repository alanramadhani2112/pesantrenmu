@php
    $roleLabel = $isAdmin ? 'Admin' : ($isPesantren ? 'Pesantren' : 'Asesor');
    $pageSubtitle = match (true) {
        $isAdmin => 'Kelola pengajuan akreditasi dan pantau kinerja asesor.',
        $isPesantren => 'Pantau kesiapan data dan status pengajuan akreditasi Anda.',
        $isAsesor => 'Selesaikan tugas penilaian dan visitasi yang ditugaskan.',
        default => 'Ringkasan aktivitas akreditasi pesantren.',
    };

    $primaryAction = match (true) {
        $isAdmin => ['label' => 'Kelola Akreditasi', 'route' => route('admin.akreditasi')],
        $isPesantren => ['label' => 'Pengajuan Akreditasi', 'route' => route('pesantren.akreditasi')],
        $isAsesor => ['label' => 'Lihat Tugas', 'route' => route('asesor.akreditasi')],
        default => null,
    };

    $statCards = match (true) {
        $isAdmin => [
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
        $isAdmin => [
            ['label' => 'Kelola Akreditasi', 'icon' => 'shield-tick', 'route' => route('admin.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Data Pesantren', 'icon' => 'category', 'route' => route('admin.pesantren.index'), 'variant' => 'info'],
            ['label' => 'Data Asesor', 'icon' => 'profile-user', 'route' => route('admin.asesor.index'), 'variant' => 'success'],
            ['label' => 'Master EDPM', 'icon' => 'document', 'route' => route('admin.master-edpm'), 'variant' => 'warning'],
        ],
        $isPesantren => [
            ['label' => 'Profil Pesantren', 'icon' => 'profile-user', 'route' => route('pesantren.profile'), 'variant' => 'primary'],
            ['label' => 'Data IPM', 'icon' => 'check-circle', 'route' => route('pesantren.ipm'), 'variant' => 'info'],
            ['label' => 'Data SDM', 'icon' => 'profile-user', 'route' => route('pesantren.sdm'), 'variant' => 'success'],
            ['label' => 'EDPM', 'icon' => 'document', 'route' => route('pesantren.edpm'), 'variant' => 'warning'],
        ],
        $isAsesor => [
            ['label' => 'Tugas Akreditasi', 'icon' => 'shield-tick', 'route' => route('asesor.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Profil Asesor', 'icon' => 'profile-user', 'route' => route('asesor.profile'), 'variant' => 'info'],
        ],
        default => [],
    };

    $statusVariantMap = [
        1 => 'success',
        2 => 'danger',
        3 => 'warning',
        4 => 'info',
        5 => 'info',
        6 => 'primary',
    ];

    $recentRouteFor = function ($uuid) use ($isAdmin, $isPesantren, $isAsesor) {
        if ($isAdmin) return route('admin.akreditasi-detail', $uuid);
        if ($isPesantren) return route('pesantren.akreditasi-detail', $uuid);
        if ($isAsesor) return route('asesor.akreditasi-detail', $uuid);
        return '#';
    };
@endphp

<div data-dashboard-page="metronic" x-data='dashboardCharts(@json($chartData), @json($stats))'>
    <x-ui.page title="Dashboard" :subtitle="$pageSubtitle">
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">{{ $roleLabel }}</x-ui.badge>

            @if($primaryAction)
                <x-ui.button :href="$primaryAction['route']" variant="primary" size="sm">
                    {{ $primaryAction['label'] }}
                </x-ui.button>
            @endif
        </x-slot>

        {{-- Greeting Hero --}}
        <div class="spm-dashboard-hero rounded p-5 p-md-6 mb-6">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex flex-column flex-grow-1">
                    <div class="text-white opacity-75 fw-semibold fs-8 fs-md-7 text-uppercase mb-1">{{ $today }}</div>
                    <h2 class="text-white fw-semibold fs-3 fs-md-2 mb-2">{{ $greeting }}, {{ $firstName }}.</h2>
                    <div class="text-white opacity-75 fw-semibold fs-7 fs-md-6">{{ $contextualMessage }}</div>
                </div>

                @if($primaryAction)
                    <div class="flex-shrink-0">
                        <x-ui.button :href="$primaryAction['route']" variant="light" size="sm" class="btn-md-md w-100 w-md-auto">
                            <x-ui.icon name="arrow-right" class="fs-4 me-1" />
                            {{ $primaryAction['label'] }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        @if(count($quickActions) > 0)
            <div class="row g-3 g-md-4 mb-6">
                @foreach($quickActions as $action)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ $action['route'] }}"
                           class="card border-0 shadow-sm h-100 text-decoration-none spm-quick-action">
                            <div class="card-body d-flex flex-column align-items-center text-center p-4 p-md-5">
                                <div class="symbol symbol-40px symbol-md-50px mb-3">
                                    <div class="symbol-label bg-light-{{ $action['variant'] }} text-{{ $action['variant'] }}">
                                        <x-ui.icon :name="$action['icon']" class="fs-3 fs-md-2" />
                                    </div>
                                </div>
                                <span class="fw-semibold fs-8 fs-md-7 text-gray-800">{{ $action['label'] }}</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if($isAdmin)
            <div class="row g-6">
                <div class="col-12 col-lg-7 col-xl-8">
                    <x-ui.card title="Perlu Ditindaklanjuti" subtitle="Prioritas proses aktif yang membutuhkan perhatian admin." class="h-100">
                        <div class="row g-5">
                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-warning bg-light-warning p-5 h-100">
                                    <x-ui.badge variant="warning" class="mb-4">Verifikasi</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['verifikasi'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-5">Pengajuan menunggu validasi awal.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-warning" size="sm">Buka Pengajuan</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-info bg-light-info p-5 h-100">
                                    <x-ui.badge variant="info" class="mb-4">Penilaian</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['assessment'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-5">Pengajuan sedang dinilai asesor.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-info" size="sm">Pantau Proses</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-primary bg-light-primary p-5 h-100">
                                    <x-ui.badge variant="primary" class="mb-4">Visitasi</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['visitasi'] }}</div>
                                    <div class="text-muted fw-medium fs-8 mb-5">Visitasi berjalan atau menunggu hasil.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light" size="sm">Lihat Jadwal</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-lg-5 col-xl-4">
                    <x-ui.card title="Monitoring Asesor" subtitle="Distribusi dan kapasitas tugas aktif." class="h-100">
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
            {{-- Pesantren: Readiness Progress Tracker --}}
            <div class="row g-6">
                <div class="col-12 col-lg-7 col-xl-8">
                    <x-ui.card title="Kesiapan Pengajuan Akreditasi" subtitle="Lengkapi semua data berikut sebelum mengajukan akreditasi.">
                        <div class="d-flex flex-column gap-0">
                            @php
                                $doneCount = collect($readiness)->where('done', true)->count();
                                $totalSteps = count($readiness);
                                $progressPercent = $totalSteps > 0 ? round(($doneCount / $totalSteps) * 100) : 0;
                            @endphp

                            <div class="d-flex align-items-center justify-content-between px-6 pt-4 pb-3">
                                <div>
                                    <span class="fw-semibold text-gray-900 fs-6">{{ $doneCount }}/{{ $totalSteps }} langkah selesai</span>
                                </div>
                                <span class="fw-semibold fs-6 {{ $progressPercent === 100 ? 'text-success' : 'text-primary' }}">{{ $progressPercent }}%</span>
                            </div>

                            <div class="px-6 pb-4">
                                <x-ui.progress
                                    :value="$progressPercent"
                                    :variant="$progressPercent === 100 ? 'success' : 'primary'"
                                />
                            </div>

                            <div class="separator"></div>

                            @foreach($readiness as $step)
                                <a href="{{ route($step['route']) }}" class="d-flex align-items-center gap-4 px-6 py-4 text-decoration-none border-bottom border-dashed spm-readiness-item {{ $step['done'] ? '' : 'spm-readiness-item-pending' }}">
                                    <div class="symbol symbol-35px flex-shrink-0">
                                        @if($step['done'])
                                            <span class="symbol-label bg-light-success text-success rounded-circle">
                                                <x-ui.icon name="check" class="fs-4" />
                                            </span>
                                        @else
                                            <span class="symbol-label bg-light-warning text-warning rounded-circle">
                                                <x-ui.icon name="information" class="fs-4" />
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <span class="fw-semibold fs-7 {{ $step['done'] ? 'text-gray-600' : 'text-gray-900' }}">{{ $step['label'] }}</span>
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if($step['done'])
                                            <x-ui.badge variant="success">Lengkap</x-ui.badge>
                                        @else
                                            <span class="text-primary fw-semibold fs-8">Lengkapi →</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach

                            @if($progressPercent === 100)
                                <div class="px-6 py-5 bg-light-success">
                                    <div class="d-flex align-items-center gap-3">
                                        <x-ui.icon name="check-circle" class="fs-2x text-success" />
                                        <div>
                                            <div class="fw-semibold text-gray-900">Data Anda sudah lengkap!</div>
                                            <div class="text-muted fs-7">Anda bisa mengajukan akreditasi sekarang.</div>
                                        </div>
                                        <x-ui.button :href="route('pesantren.akreditasi')" variant="success" size="sm" class="ms-auto">
                                            Ajukan Akreditasi
                                        </x-ui.button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-lg-5 col-xl-4">
                    <x-ui.card title="Status Pengajuan" subtitle="Ringkasan pengajuan akreditasi Anda." class="h-100">
                        <div class="d-flex flex-column">
                            <x-ui.metric-row label="Pengajuan Berjalan" :value="$stats['total_aktif']" variant="primary" icon="abstract-26" />
                            <x-ui.metric-row label="Sedang Dinilai" :value="$stats['assessment']" variant="info" icon="document" />
                            <x-ui.metric-row label="Proses Visitasi" :value="$stats['visitasi']" variant="warning" icon="geolocation" />
                            <x-ui.metric-row label="Perlu Diperbaiki" :value="$stats['ditolak']" variant="danger" icon="cross-circle" class="border-bottom-0" />
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @elseif($isAsesor)
            <div class="row g-6">
                <div class="col-12 col-lg-7 col-xl-8">
                    <x-ui.card title="Tugas Aktif" subtitle="Prioritaskan penilaian dan visitasi yang sedang berjalan." class="h-100">
                        <div class="row g-5">
                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-primary bg-light-primary p-5 h-100">
                                    <x-ui.badge variant="primary" class="mb-4">Total Tugas</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['total_aktif'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Penilaian atau visitasi yang masih aktif.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light" size="sm">Buka Tugas</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-info bg-light-info p-5 h-100">
                                    <x-ui.badge variant="info" class="mb-4">Penilaian</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['assessment'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Tugas penilaian instrumen yang perlu diproses.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light-info" size="sm">Isi Instrumen</x-ui.button>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <div class="rounded border border-dashed border-warning bg-light-warning p-5 h-100">
                                    <x-ui.badge variant="warning" class="mb-4">Visitasi</x-ui.badge>
                                    <div class="fs-2x fw-semibold text-gray-900 mb-1">{{ $stats['visitasi'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Tugas visitasi yang perlu dijadwalkan atau diselesaikan.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light-warning" size="sm">Atur Visitasi</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-lg-5 col-xl-4">
                    <x-ui.card title="Ringkasan Pekerjaan" subtitle="Status pekerjaan asesor saat ini." class="h-100">
                        <div class="d-flex flex-column">
                            <x-ui.metric-row label="Tugas Aktif" :value="$stats['total_aktif']" variant="primary" icon="abstract-26" />
                            <x-ui.metric-row label="Perlu Penilaian" :value="$stats['assessment']" variant="info" icon="document" />
                            <x-ui.metric-row label="Tahap Visitasi" :value="$stats['visitasi']" variant="warning" icon="geolocation" />
                            <x-ui.metric-row label="Selesai" :value="$stats['terakreditasi']" variant="success" icon="check-circle" class="border-bottom-0" />
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @endif

        <div class="row g-6 mt-0">
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

        <div class="row g-6 mt-0">
            <div class="col-12 col-lg-7 col-xl-8">
                <x-ui.card title="Pengajuan Akreditasi per Bulan" subtitle="Tren pengajuan tahun {{ date('Y') }}" class="h-100">
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
                <x-ui.card title="Distribusi Status" subtitle="Hasil akhir pengajuan akreditasi." class="h-100">
                    @if($hasStatusData)
                        <div class="d-flex flex-column align-items-center justify-content-center">
                            <div class="position-relative h-250px w-250px d-flex align-items-center justify-content-center">
                                <canvas id="statusChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                    <span class="fs-2hx fw-semibold text-gray-900">{{ $stats['terakreditasi'] + $stats['ditolak'] }}</span>
                                    <span class="d-block text-muted fw-semibold fs-8 text-uppercase">Selesai</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center gap-5 mt-6">
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
                            class="min-h-250px"
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

        {{-- Recent Activity --}}
        <div class="row g-6 mt-0">
            <div class="col-12">
                <x-ui.card
                    title="Aktivitas Terbaru"
                    subtitle="{{ $isAdmin ? 'Pengajuan akreditasi terbaru dari seluruh pesantren.' : ($isPesantren ? 'Riwayat pengajuan akreditasi pesantren Anda.' : 'Tugas penilaian terbaru yang ditugaskan kepada Anda.') }}"
                >
                    @if($recentActivities->count() > 0)
                        <x-ui.simple-table>
                            <thead>
                                <tr class="text-uppercase fs-8 fw-semibold text-muted">
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
                                                <div class="symbol symbol-40px flex-shrink-0">
                                                    <div class="symbol-label bg-light-primary text-primary fw-semibold">
                                                        {{ strtoupper(substr($activity['pesantren_name'], 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column min-w-0">
                                                    <span class="text-gray-900 fw-semibold fs-7 fs-md-6 text-truncate">{{ $activity['pesantren_name'] }}</span>
                                                    <span class="text-muted fw-semibold fs-8 d-sm-none">
                                                        {{ $activity['updated_at']->translatedFormat('d M Y') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <x-ui.status-badge :variant="$statusVariantMap[$activity['status']] ?? 'secondary'">
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
                            @if($isAdmin)
                                <x-slot name="action">
                                    <x-ui.button :href="route('admin.pesantren.index')" variant="primary" size="sm">
                                        Kelola Data Pesantren
                                    </x-ui.button>
                                </x-slot>
                            @elseif($isPesantren)
                                <x-slot name="action">
                                    <x-ui.button :href="route('pesantren.akreditasi')" variant="primary" size="sm">
                                        Buat Pengajuan
                                    </x-ui.button>
                                </x-slot>
                            @endif
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </x-ui.page>
</div>

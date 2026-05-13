@php
    $roleLabel = $isAdmin ? 'Admin' : ($isPesantren ? 'Pesantren' : 'Asesor');
    $pageSubtitle = match (true) {
        $isAdmin => 'Pantau antrean verifikasi, penilaian, visitasi, dan beban kerja asesor.',
        $isPesantren => 'Lihat kesiapan data, status pengajuan, dan tindak lanjut yang perlu dilakukan.',
        $isAsesor => 'Prioritaskan tugas penilaian dan visitasi yang sedang berjalan.',
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
            ['label' => 'Menunggu Verifikasi', 'value' => $stats['verifikasi'], 'variant' => 'warning', 'icon' => 'timer'],
            ['label' => 'Tahap Penilaian', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Tahap Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Total Pengajuan Aktif', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Terakreditasi', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
            ['label' => 'Ditolak / Perlu Revisi', 'value' => $stats['ditolak'], 'variant' => 'danger', 'icon' => 'cross-circle'],
        ],
        $isPesantren => [
            ['label' => 'Pengajuan Aktif', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Tahap Penilaian', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Tahap Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Terakreditasi', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
            ['label' => 'Ditolak / Perlu Revisi', 'value' => $stats['ditolak'], 'variant' => 'danger', 'icon' => 'cross-circle'],
        ],
        $isAsesor => [
            ['label' => 'Tugas Aktif', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
            ['label' => 'Perlu Penilaian', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
            ['label' => 'Tahap Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
            ['label' => 'Selesai', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
        ],
        default => [],
    };

    $hasMonthlyData = array_sum($chartData) > 0;
    $hasStatusData = ($stats['terakreditasi'] + $stats['ditolak']) > 0;

    $userName = auth()->user()->name;
    $firstName = trim(explode(' ', $userName)[0] ?? $userName);
    $today = \Carbon\Carbon::now()->translatedFormat('l, d F Y');

    $contextualMessage = match (true) {
        $isAdmin && $stats['verifikasi'] > 0 => "Ada {$stats['verifikasi']} pengajuan menunggu verifikasi.",
        $isAdmin => 'Semua pengajuan sudah ditindaklanjuti. Tetap pantau kegiatan asesor.',
        $isPesantren && $stats['ditolak'] > 0 => "Ada {$stats['ditolak']} pengajuan yang perlu direvisi.",
        $isPesantren && $stats['total_aktif'] > 0 => 'Pengajuan sedang berjalan. Pantau status di halaman pengajuan.',
        $isPesantren => 'Siapkan data pesantren sebelum memulai pengajuan akreditasi.',
        $isAsesor && $stats['total_aktif'] > 0 => "Ada {$stats['total_aktif']} tugas yang perlu Anda selesaikan.",
        $isAsesor => 'Tidak ada tugas aktif saat ini. Nikmati waktu Anda.',
        default => 'Selamat datang di PesantrenMu.',
    };

    $quickActions = match (true) {
        $isAdmin => [
            ['label' => 'Kelola Akreditasi', 'icon' => 'verify', 'route' => route('admin.akreditasi'), 'variant' => 'primary'],
            ['label' => 'Data Pesantren', 'icon' => 'home-2', 'route' => route('admin.pesantren.index'), 'variant' => 'info'],
            ['label' => 'Data Asesor', 'icon' => 'profile-user', 'route' => route('admin.asesor.index'), 'variant' => 'success'],
            ['label' => 'Master EDPM', 'icon' => 'document', 'route' => route('admin.master-edpm'), 'variant' => 'warning'],
        ],
        $isPesantren => [
            ['label' => 'Profil Pesantren', 'icon' => 'home-2', 'route' => route('pesantren.profile'), 'variant' => 'primary'],
            ['label' => 'Data IPM', 'icon' => 'check-circle', 'route' => route('pesantren.ipm'), 'variant' => 'info'],
            ['label' => 'Data SDM', 'icon' => 'profile-user', 'route' => route('pesantren.sdm'), 'variant' => 'success'],
            ['label' => 'EDPM', 'icon' => 'document', 'route' => route('pesantren.edpm'), 'variant' => 'warning'],
        ],
        $isAsesor => [
            ['label' => 'Tugas Akreditasi', 'icon' => 'verify', 'route' => route('asesor.akreditasi'), 'variant' => 'primary'],
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
        <div class="spm-dashboard-hero rounded p-6 mb-6">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex flex-column">
                    <div class="text-white opacity-75 fw-semibold fs-7 text-uppercase mb-1">{{ $today }}</div>
                    <h2 class="text-white fw-bolder fs-1 mb-2">{{ $greeting }}, {{ $firstName }}.</h2>
                    <div class="text-white opacity-75 fw-semibold fs-6">{{ $contextualMessage }}</div>
                </div>

                @if($primaryAction)
                    <div class="d-none d-md-block">
                        <a href="{{ $primaryAction['route'] }}" class="btn btn-light fw-bold">
                            <x-ui.icon name="arrow-right" class="fs-4 me-1" />
                            {{ $primaryAction['label'] }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        @if(count($quickActions) > 0)
            <div class="row g-4 mb-6">
                @foreach($quickActions as $action)
                    <div class="col-6 col-md-3">
                        <a href="{{ $action['route'] }}"
                           class="card border-0 shadow-sm h-100 text-decoration-none spm-quick-action">
                            <div class="card-body d-flex flex-column align-items-center text-center p-5">
                                <div class="symbol symbol-50px mb-3">
                                    <div class="symbol-label bg-light-{{ $action['variant'] }} text-{{ $action['variant'] }}">
                                        <x-ui.icon :name="$action['icon']" class="fs-2" />
                                    </div>
                                </div>
                                <span class="fw-bold fs-7 text-gray-900">{{ $action['label'] }}</span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if($isAdmin)
            <div class="row g-6">
                <div class="col-12 col-xl-8">
                    <x-ui.card title="Perlu Ditindaklanjuti" subtitle="Prioritas proses aktif yang membutuhkan perhatian admin." class="h-100">
                        <div class="row g-5">
                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-warning bg-light-warning p-5 h-100">
                                    <x-ui.badge variant="warning" class="mb-4">Verifikasi</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['verifikasi'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Pengajuan menunggu validasi awal.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-warning" size="sm">Buka Pengajuan</x-ui.button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-info bg-light-info p-5 h-100">
                                    <x-ui.badge variant="info" class="mb-4">Penilaian</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['assessment'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Pengajuan sedang dinilai asesor.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light-info" size="sm">Pantau Proses</x-ui.button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-primary bg-light-primary p-5 h-100">
                                    <x-ui.badge variant="primary" class="mb-4">Visitasi</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['visitasi'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Visitasi berjalan atau menunggu hasil.</div>
                                    <x-ui.button :href="route('admin.akreditasi')" variant="light" size="sm">Lihat Jadwal</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-xl-4">
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
            <div class="row g-6">
                <div class="col-12 col-xl-8">
                    <x-ui.card title="Kesiapan Pengajuan" subtitle="Ikuti status kesiapan sebelum masuk ke pengajuan akreditasi." class="h-100">
                        <div class="row g-5 align-items-stretch">
                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-primary bg-light-primary p-5 h-100">
                                    <x-ui.icon name="document" class="fs-2x text-primary mb-4" />
                                    <div class="fw-bold text-gray-900 mb-1">Lengkapi Data</div>
                                    <div class="text-muted fw-semibold fs-7">Profil, IPM, SDM, EDPM, dan dokumen dicek otomatis saat pengajuan.</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed {{ $stats['total_aktif'] > 0 ? 'border-info bg-light-info' : 'border-success bg-light-success' }} p-5 h-100">
                                    <x-ui.icon name="check-circle" class="fs-2x {{ $stats['total_aktif'] > 0 ? 'text-info' : 'text-success' }} mb-4" />
                                    <div class="fw-bold text-gray-900 mb-1">{{ $stats['total_aktif'] > 0 ? 'Sedang Diproses' : 'Siap Cek Pengajuan' }}</div>
                                    <div class="text-muted fw-semibold fs-7">{{ $stats['total_aktif'] > 0 ? 'Pantau tahapan aktif dan tunggu instruksi berikutnya.' : 'Mulai pengajuan untuk mengecek kelengkapan data.' }}</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed {{ $stats['ditolak'] > 0 ? 'border-warning bg-light-warning' : 'border-gray-300 bg-light' }} p-5 h-100">
                                    <x-ui.icon name="document" class="fs-2x {{ $stats['ditolak'] > 0 ? 'text-warning' : 'text-muted' }} mb-4" />
                                    <div class="fw-bold text-gray-900 mb-1">{{ $stats['ditolak'] > 0 ? 'Ada Revisi' : 'Belum Ada Revisi' }}</div>
                                    <div class="text-muted fw-semibold fs-7">Catatan dan revisi dapat dilihat dari detail pengajuan.</div>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-6"></div>

                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                            <div>
                                <div class="fw-bold text-gray-900">Langkah berikutnya</div>
                                <div class="text-muted fw-semibold fs-7">Gunakan halaman pengajuan untuk membuat, memantau, atau menindaklanjuti akreditasi.</div>
                            </div>
                            <x-ui.button :href="route('pesantren.akreditasi')" variant="primary" size="sm">Buka Pengajuan</x-ui.button>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-xl-4">
                    <x-ui.card title="Status Pengajuan Aktif" subtitle="Ringkasan tahapan yang sedang berjalan." class="h-100">
                        <div class="d-flex flex-column">
                            <x-ui.metric-row label="Pengajuan Aktif" :value="$stats['total_aktif']" variant="primary" icon="abstract-26" />
                            <x-ui.metric-row label="Tahap Penilaian" :value="$stats['assessment']" variant="info" icon="document" />
                            <x-ui.metric-row label="Tahap Visitasi" :value="$stats['visitasi']" variant="warning" icon="geolocation" />
                            <x-ui.metric-row label="Perlu Revisi" :value="$stats['ditolak']" variant="danger" icon="cross-circle" class="border-bottom-0" />
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @elseif($isAsesor)
            <div class="row g-6">
                <div class="col-12 col-xl-8">
                    <x-ui.card title="Tugas Aktif" subtitle="Prioritaskan penilaian dan visitasi yang sedang berjalan." class="h-100">
                        <div class="row g-5">
                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-primary bg-light-primary p-5 h-100">
                                    <x-ui.badge variant="primary" class="mb-4">Total Tugas</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['total_aktif'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Penilaian atau visitasi yang masih aktif.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light" size="sm">Buka Tugas</x-ui.button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-info bg-light-info p-5 h-100">
                                    <x-ui.badge variant="info" class="mb-4">Penilaian</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['assessment'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Tugas penilaian instrumen yang perlu diproses.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light-info" size="sm">Isi Instrumen</x-ui.button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="rounded border border-dashed border-warning bg-light-warning p-5 h-100">
                                    <x-ui.badge variant="warning" class="mb-4">Visitasi</x-ui.badge>
                                    <div class="fs-2x fw-bolder text-gray-900 mb-1">{{ $stats['visitasi'] }}</div>
                                    <div class="text-muted fw-semibold fs-7 mb-5">Tugas visitasi yang perlu dijadwalkan atau diselesaikan.</div>
                                    <x-ui.button :href="route('asesor.akreditasi')" variant="light-warning" size="sm">Atur Visitasi</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-12 col-xl-4">
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
                <div class="col-12 col-sm-6 col-xl-4">
                    <x-ui.stat-card
                        class="spm-dashboard-stat"
                        :label="$card['label']"
                        :value="$card['value']"
                        :variant="$card['variant']"
                    >
                        <x-slot name="icon">
                            <x-ui.icon :name="$card['icon']" class="fs-2x" />
                        </x-slot>
                    </x-ui.stat-card>
                </div>
            @endforeach
        </div>

        <div class="row g-6 mt-0">
            <div class="col-12 col-xl-8">
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

            <div class="col-12 col-xl-4">
                <x-ui.card title="Distribusi Status" subtitle="Hasil akhir pengajuan akreditasi." class="h-100">
                    @if($hasStatusData)
                        <div class="d-flex flex-column align-items-center justify-content-center">
                            <div class="position-relative h-250px w-250px d-flex align-items-center justify-content-center">
                                <canvas id="statusChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                    <span class="fs-2hx fw-bolder text-gray-900">{{ $stats['terakreditasi'] + $stats['ditolak'] }}</span>
                                    <span class="d-block text-muted fw-bold fs-8 text-uppercase">Selesai</span>
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
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0">
                                <thead>
                                    <tr class="text-uppercase fs-8 fw-bold text-muted">
                                        <th class="min-w-200px">Pesantren</th>
                                        <th class="min-w-100px">Status</th>
                                        <th class="min-w-100px">Peringkat</th>
                                        <th class="min-w-150px">Terakhir Diperbarui</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivities as $activity)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="symbol symbol-40px">
                                                        <div class="symbol-label bg-light-primary text-primary fw-bolder">
                                                            {{ strtoupper(substr($activity['pesantren_name'], 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <span class="text-gray-900 fw-bold fs-6">{{ $activity['pesantren_name'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <x-ui.status-badge :variant="$statusVariantMap[$activity['status']] ?? 'secondary'">
                                                    {{ $activity['status_label'] }}
                                                </x-ui.status-badge>
                                            </td>
                                            <td>
                                                @if($activity['peringkat'])
                                                    <span class="fw-bold text-gray-700">{{ $activity['peringkat'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted fw-semibold fs-7">
                                                    {{ $activity['updated_at']->translatedFormat('d M Y, H:i') }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <x-ui.button :href="$recentRouteFor($activity['uuid'])" variant="light" size="sm">
                                                    <x-ui.icon name="eye" class="fs-5 me-1" />
                                                    Detail
                                                </x-ui.button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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

@php
    $roleLabel = $isAdmin ? 'Admin' : ($isPesantren ? 'Pesantren' : 'Asesor');
    $pageSubtitle = match (true) {
        $isAdmin => 'Pantau pengajuan, status proses, dan beban asesor dari satu layar ringkas.',
        $isPesantren => 'Ikuti progres pengajuan dan selesaikan data yang masih membutuhkan perhatian.',
        $isAsesor => 'Fokus pada tugas assessment dan visitasi yang sedang berjalan.',
        default => 'Ringkasan aktivitas sistem.',
    };

    $primaryAction = match (true) {
        $isAdmin => ['label' => 'Kelola Akreditasi', 'route' => route('admin.akreditasi')],
        $isPesantren => ['label' => 'Pengajuan Akreditasi', 'route' => route('pesantren.akreditasi')],
        $isAsesor => ['label' => 'Lihat Tugas', 'route' => route('asesor.akreditasi')],
        default => null,
    };

    $statCards = [
        ['label' => $isAsesor ? 'Tugas Aktif' : 'Total Pengajuan Aktif', 'value' => $stats['total_aktif'], 'variant' => 'primary', 'icon' => 'abstract-26'],
        ['label' => 'Menunggu Verifikasi', 'value' => $stats['verifikasi'], 'variant' => 'warning', 'icon' => 'timer'],
        ['label' => 'Tahap Assessment', 'value' => $stats['assessment'], 'variant' => 'info', 'icon' => 'document'],
        ['label' => 'Tahap Visitasi', 'value' => $stats['visitasi'], 'variant' => 'warning', 'icon' => 'geolocation'],
        ['label' => $isAsesor ? 'Selesai' : 'Terakreditasi', 'value' => $stats['terakreditasi'], 'variant' => 'success', 'icon' => 'check-circle'],
    ];

    if (! $isAsesor) {
        $statCards[] = ['label' => 'Ditolak / Perlu Revisi', 'value' => $stats['ditolak'], 'variant' => 'danger', 'icon' => 'cross-circle'];
    }

    $hasMonthlyData = array_sum($chartData) > 0;
    $hasStatusData = ($stats['terakreditasi'] + $stats['ditolak']) > 0;
@endphp

<div data-dashboard-page="metronic" x-data='dashboardCharts(@json($chartData), @json($stats))'>
    <x-ui.page title="Dashboard" :subtitle="$pageSubtitle">
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">{{ $roleLabel }}</x-ui.badge>

            @if($primaryAction)
                <x-ui.button :href="$primaryAction['route']" variant="primary" size="sm" wire:navigate>
                    {{ $primaryAction['label'] }}
                </x-ui.button>
            @endif
        </x-slot>

        <div class="row g-6">
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
                <x-ui.card title="Pengajuan Akreditasi per Bulan" subtitle="Tiap bulan {{ date('Y') }}" class="h-100">
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
                        />
                    @endif
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="{{ $isAdmin ? 'Monitoring Asesor' : 'Ringkasan Status' }}"
                    subtitle="{{ $isAdmin ? 'Distribusi dan beban tugas asesor aktif' : 'Distribusi hasil proses akreditasi' }}"
                    class="h-100"
                >
                    @if($isAdmin)
                        <div class="d-flex flex-column">
                            <x-ui.metric-row label="Total Asesor Aktif" :value="$totalAsesor" variant="primary" icon="profile-user" />
                            <x-ui.metric-row label="Total Tugas Aktif" :value="$totalTugasAktif" variant="info" icon="document" />
                            <x-ui.metric-row label="Asesor Tanpa Tugas" :value="$asesorTanpaTugas" variant="danger" icon="people" />
                            <x-ui.metric-row label="Rata-rata Beban" value="{{ $avgBeban }} tugas/asesor" variant="warning" icon="chart-line" class="border-bottom-0" />
                        </div>
                    @else
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
                            />
                        @endif
                    @endif
                </x-ui.card>
            </div>
        </div>

        @if($isAdmin)
            <div class="row g-6 mt-0">
                <div class="col-12">
                    <x-ui.card title="Ringkasan Status Akreditasi Pesantren" subtitle="Distribusi hasil akhir pengajuan">
                        @if($hasStatusData)
                            <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-8">
                                <div class="position-relative h-250px w-250px d-flex align-items-center justify-content-center">
                                    <canvas id="statusChart"></canvas>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                        <span class="fs-2hx fw-bolder text-gray-900">{{ $stats['terakreditasi'] + $stats['ditolak'] }}</span>
                                        <span class="d-block text-muted fw-bold fs-8 text-uppercase">Pesantren</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-4">
                                    <x-ui.badge variant="success">Terakreditasi: {{ $stats['terakreditasi'] }}</x-ui.badge>
                                    <x-ui.badge variant="danger">Ditolak: {{ $stats['ditolak'] }}</x-ui.badge>
                                </div>
                            </div>
                        @else
                            <x-ui.empty-state
                                title="Belum ada hasil akhir"
                                description="Distribusi status akan muncul setelah ada pengajuan yang berhasil atau ditolak."
                                class="min-h-250px"
                            />
                        @endif
                    </x-ui.card>
                </div>
            </div>
        @endif
    </x-ui.page>
</div>

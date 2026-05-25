@props([
    'status' => null,
    'title' => 'Alur Proses Akreditasi',
    'subtitle' => 'Posisi pengajuan dalam alur kerja LP2M.',
])

@php
    $hasCurrentStatus = $status !== null && $status !== '';
    $currentStatus = $hasCurrentStatus ? (int) $status : null;

    $terminalStep = match ($currentStatus) {
        \App\Models\Akreditasi::STATUS_DITOLAK => [
            'status' => \App\Models\Akreditasi::STATUS_DITOLAK,
            'title' => 'Ditolak',
            'description' => 'Pengajuan berhenti atau menunggu tindak lanjut banding.',
            'variant' => 'danger',
        ],
        \App\Models\Akreditasi::STATUS_BANDING => [
            'status' => \App\Models\Akreditasi::STATUS_BANDING,
            'title' => 'Banding',
            'description' => 'Pengajuan masuk proses peninjauan banding.',
            'variant' => 'warning',
        ],
        default => [
            'status' => \App\Models\Akreditasi::STATUS_SELESAI,
            'title' => 'Hasil Akhir',
            'description' => 'SK, peringkat, sertifikat, dan rekomendasi tersedia.',
            'variant' => 'success',
        ],
    };

    $steps = [
        [
            'status' => \App\Models\Akreditasi::STATUS_PENGAJUAN,
            'title' => 'Pengajuan',
            'description' => 'Pesantren mengirim profil, IPM, EDPM/IPR, dan SDM.',
            'variant' => 'primary',
        ],
        [
            'status' => \App\Models\Akreditasi::STATUS_VERIFIKASI_BERKAS,
            'title' => 'Review Awal',
            'description' => 'Admin memeriksa kelengkapan berkas awal.',
            'variant' => 'warning',
        ],
        [
            'status' => \App\Models\Akreditasi::STATUS_ASSESSMENT,
            'title' => 'Review Asesor',
            'description' => 'Ketua Kelompok meninjau berkas dan menyiapkan jadwal visitasi.',
            'variant' => 'info',
        ],
        [
            'status' => \App\Models\Akreditasi::STATUS_VISITASI,
            'title' => 'Visitasi',
            'description' => 'Tim asesor melakukan visitasi lapangan dan mengonfirmasi selesai.',
            'variant' => 'info',
        ],
        [
            'status' => \App\Models\Akreditasi::STATUS_PASCA_VISITASI,
            'title' => 'Penilaian Pasca Visitasi',
            'description' => 'Nilai Ketua, Nilai Anggota, Nilai Kelompok, laporan, dan kartu kendali dilengkapi.',
            'variant' => 'primary',
        ],
        [
            'status' => \App\Models\Akreditasi::STATUS_VALIDASI_ADMIN,
            'title' => 'Validasi Admin',
            'description' => 'Nilai Verifikasi mengikuti Nilai Kelompok sebagai default dan dapat diedit admin.',
            'variant' => 'warning',
        ],
        [
            'status' => $terminalStep['status'],
            'title' => $terminalStep['title'],
            'description' => $terminalStep['description'],
            'variant' => $terminalStep['variant'],
        ],
    ];

    $currentIndex = $hasCurrentStatus
        ? match ($currentStatus) {
            \App\Models\Akreditasi::STATUS_PENGAJUAN => 0,
            \App\Models\Akreditasi::STATUS_VERIFIKASI_BERKAS => 1,
            \App\Models\Akreditasi::STATUS_ASSESSMENT => 2,
            \App\Models\Akreditasi::STATUS_VISITASI => 3,
            \App\Models\Akreditasi::STATUS_PASCA_VISITASI => 4,
            \App\Models\Akreditasi::STATUS_VALIDASI_ADMIN => 5,
            \App\Models\Akreditasi::STATUS_SELESAI,
            \App\Models\Akreditasi::STATUS_DITOLAK,
            \App\Models\Akreditasi::STATUS_BANDING => 6,
            default => null,
        }
        : null;
@endphp

<x-ui.card
    :title="$title"
    :subtitle="$subtitle"
    data-akreditasi-workflow="metronic"
    {{ $attributes->merge(['class' => 'spm-workflow-card']) }}
>
    <x-ui.stepper
        class="spm-workflow-stepper"
        aria-label="Alur proses akreditasi"
    >
        @foreach($steps as $index => $step)
            @php
                $isComplete = $currentIndex !== null && $index < $currentIndex;
                $isCurrent = $currentIndex !== null && $index === $currentIndex;
                $stateClass = $isCurrent ? 'current' : ($isComplete ? 'completed' : 'pending');
            @endphp

            <div
                class="stepper-item {{ $stateClass }} spm-workflow-step spm-workflow-step--{{ $step['variant'] }}"
                data-workflow-step="{{ $step['status'] }}"
            >
                <div class="stepper-wrapper">
                    <div class="stepper-icon spm-workflow-step-icon">
                        <i class="stepper-check">
                            <x-ui.icon name="check-circle" class="fs-7" />
                        </i>
                        <span class="stepper-number">{{ $index + 1 }}</span>
                    </div>

                    <div class="stepper-label">
                        <h3 class="stepper-title">{{ $step['title'] }}</h3>
                        <div class="stepper-desc">{{ $step['description'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </x-ui.stepper>
</x-ui.card>

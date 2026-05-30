@props([
    'komponens' => collect(),
    'evaluasis' => [],
    'links' => [],
    'catatans' => [],
    'title' => 'EDPM/IPR Pesantren',
    'subtitle' => 'Isian evaluasi diri, bukti, dan catatan kinerja per komponen.',
])

@php
    $allKomponens = collect($komponens ?? []);
    $groups = [
        'edpm' => [
            'label' => 'Komponen EDPM',
            'short' => 'EDPM',
            'variant' => 'primary',
            'description' => 'Evaluasi Data Pesantren Muhammadiyah per komponen utama.',
            'komponens' => $allKomponens->filter(fn ($komponen) => ! (bool) $komponen->ipr)->values(),
        ],
        'ipr' => [
            'label' => 'Komponen IPR',
            'short' => 'IPR',
            'variant' => 'success',
            'description' => 'Indikator Pemenuhan Relatif yang menjadi bagian penilaian akhir.',
            'komponens' => $allKomponens->filter(fn ($komponen) => (bool) $komponen->ipr)->values(),
        ],
    ];

    $allButirs = $allKomponens->flatMap(fn ($komponen) => $komponen->butirs ?? collect());
    $totalButirs = $allButirs->count();
    $filledButirs = $allButirs->filter(fn ($butir) => filled($evaluasis[$butir->id] ?? null))->count();
    $proofCount = $allButirs->filter(fn ($butir) => filled($links[$butir->id] ?? null))->count();
    $formatComponentName = function ($name) {
        $cleanName = preg_replace('/^[A-Z]\.\s*/i', '', (string) $name);

        return \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($cleanName));
    };
@endphp

<div data-akreditasi-edpm-review="metronic" {{ $attributes->merge(['class' => 'd-flex flex-column gap-6 spm-edpm-review']) }}>
    <x-ui.section-card :title="$title" :subtitle="$subtitle">
        <div class="p-6">
            <div class="row g-5">
                <div class="col-md-4">
                    <div class="spm-edpm-review-metric">
                        <div class="spm-detail-label">Komponen EDPM</div>
                        <div class="spm-detail-value">{{ $groups['edpm']['komponens']->count() }} komponen</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="spm-edpm-review-metric">
                        <div class="spm-detail-label">Komponen IPR</div>
                        <div class="spm-detail-value">{{ $groups['ipr']['komponens']->count() }} komponen</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="spm-edpm-review-metric">
                        <div class="spm-detail-label">Kelengkapan Isian</div>
                        <div class="spm-detail-value">{{ $filledButirs }}/{{ $totalButirs }} butir</div>
                        <div class="text-muted fw-semibold fs-8 mt-1">{{ $proofCount }} bukti terlampir</div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    @foreach ($groups as $group)
        @if($group['komponens']->isNotEmpty())
            <x-ui.section-card :title="$group['label']" :subtitle="$group['description']">
                <div class="p-6 d-flex flex-column gap-5">
                    @foreach ($group['komponens'] as $komponen)
                        @php
                            $butirsCount = $komponen->butirs->count();
                            $komponenFilled = $komponen->butirs->filter(fn ($butir) => filled($evaluasis[$butir->id] ?? null))->count();
                            $catatan = $catatans[$komponen->id] ?? null;
                        @endphp

                        <div class="spm-edpm-review-component" data-ui-edpm-component="metronic">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3 mb-4">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                        <x-ui.badge :variant="$group['variant']">{{ $group['short'] }}</x-ui.badge>
                                        <x-ui.status-badge variant="secondary">{{ $butirsCount }} butir</x-ui.status-badge>
                                        <x-ui.status-badge :variant="$komponenFilled === $butirsCount ? 'success' : 'warning'">
                                            {{ $komponenFilled }}/{{ $butirsCount }} terisi
                                        </x-ui.status-badge>
                                    </div>
                                    <h4 class="spm-card-title mb-0">{{ $formatComponentName($komponen->nama) }}</h4>
                                </div>
                            </div>

                            <x-ui.simple-table dense tableClass="spm-edpm-review-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-90px">No SK</th>
                                        <th class="w-100px">No Butir</th>
                                        <th>Butir Pernyataan</th>
                                        <th class="text-center w-120px">Isian</th>
                                        <th class="text-center pe-4 w-145px">Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponen->butirs as $butir)
                                        @php
                                            $evaluasi = $evaluasis[$butir->id] ?? null;
                                            $link = $links[$butir->id] ?? null;
                                        @endphp
                                        <tr>
                                            <td class="ps-4 text-muted fw-semibold">{{ $butir->no_sk ?: '-' }}</td>
                                            <td>
                                                <x-ui.badge variant="primary">{{ $butir->nomor_butir }}</x-ui.badge>
                                            </td>
                                            <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                            <td class="text-center">
                                                @if(filled($evaluasi))
                                                    <x-ui.badge variant="warning">{{ $evaluasi }}</x-ui.badge>
                                                @else
                                                    <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                @endif
                                            </td>
                                            <td class="text-center pe-4">
                                                @if(filled($link))
                                                    <x-ui.button :href="$link" target="_blank" variant="light-primary" size="sm">
                                                        Bukti
                                                    </x-ui.button>
                                                @else
                                                    <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>

                            <div class="spm-edpm-review-note mt-4">
                                <div class="spm-detail-label mb-2">Catatan Komponen</div>
                                <div class="spm-detail-value spm-detail-value-muted">
                                    {{ filled($catatan) ? $catatan : 'Tidak ada catatan.' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.section-card>
        @endif
    @endforeach
</div>

@php
    use App\StateMachine\AkreditasiStateMachine;
@endphp

@if($activeTab === 'laporan_visitasi')
    <div class="d-flex flex-column gap-6">
        {{-- Post-Visitasi Document Checklist --}}
        @if(in_array((int) $akreditasi->status, [
            AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            AkreditasiStateMachine::STATUS_SELESAI,
        ]))
            @php
                $requiredDocs = [
                    ['key' => 'laporan_visitasi_asesor1',  'label' => 'Laporan Visitasi Ketua Kelompok',  'uploader' => 'Ketua Kelompok'],
                    ['key' => 'laporan_visitasi_asesor2',  'label' => 'Laporan Visitasi Anggota Kelompok', 'uploader' => 'Anggota Kelompok'],
                    ['key' => 'laporan_visitasi_kelompok', 'label' => 'Laporan Visitasi Kelompok',          'uploader' => 'Ketua Kelompok'],
                    ['key' => 'kartu_kendali',             'label' => 'Kartu Kendali',                       'uploader' => 'Pesantren'],
                ];
                $available = 0;
                foreach ($requiredDocs as $doc) {
                    if (!empty($akreditasi->{$doc['key']})) {
                        $available++;
                    }
                }
                $isComplete = $available === count($requiredDocs);
            @endphp
            <x-ui.section-card title="Kelengkapan Dokumen Penilaian Pasca Visitasi">
                <x-slot:toolbar>
                    <x-ui.status-badge :variant="$isComplete ? 'success' : 'warning'">
                        <x-ui.icon :name="$isComplete ? 'check-circle' : 'information'" class="fs-4 me-2" />
                        {{ $available }}/{{ count($requiredDocs) }} Lengkap
                    </x-ui.status-badge>
                </x-slot:toolbar>
                <div class="p-6">
                    <div class="d-flex flex-column gap-3">
                        @foreach($requiredDocs as $doc)
                            @php $has = !empty($akreditasi->{$doc['key']}) @endphp
                            <div class="d-flex align-items-center gap-3">
                                <span class="symbol symbol-30px">
                                    <span class="symbol-label {{ $has ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                                        <span class="svg-icon svg-icon-2">
                                            @if($has)
                                                <i class="bi bi-check-lg fw-semibold"></i>
                                            @else
                                                <i class="bi bi-x-lg fw-semibold"></i>
                                            @endif
                                        </span>
                                    </span>
                                </span>
                                <span class="fw-semibold text-gray-800">{{ $doc['label'] }}</span>
                                <span class="text-muted fs-8 ms-auto">{{ $doc['uploader'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        @if($akreditasi->status >= 3)
            <x-ui.section-card title="Kartu Kendali" subtitle="Dokumen kontrol validasi dari pesantren.">
                <div class="p-6">
                    <div class="spm-document-list">
                        <x-ui.document-item
                            label="Dokumen Kartu Kendali"
                            :href="$akreditasi->kartu_kendali ? Storage::url($akreditasi->kartu_kendali) : null"
                            description="Diunggah oleh pesantren untuk validasi."
                        />
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        <x-ui.section-card title="Laporan Hasil Visitasi" subtitle="Dokumen laporan dari ketua dan anggota asesor.">
            <div class="p-6">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <div class="spm-document-list">
                            <x-ui.document-item
                                label="Laporan Visitasi Ketua Kelompok"
                                :href="$akreditasi->laporan_visitasi_asesor1 ? Storage::url($akreditasi->laporan_visitasi_asesor1) : null"
                                description="Diunggah oleh Ketua Kelompok."
                            />
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="spm-document-list">
                            <x-ui.document-item
                                label="Laporan Visitasi Anggota Kelompok"
                                :href="$akreditasi->laporan_visitasi_asesor2 ? Storage::url($akreditasi->laporan_visitasi_asesor2) : null"
                                description="Diunggah oleh Anggota Kelompok."
                            />
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="spm-document-list">
                            <x-ui.document-item
                                label="Laporan Visitasi Kelompok"
                                :href="$akreditasi->laporan_visitasi_kelompok ? Storage::url($akreditasi->laporan_visitasi_kelompok) : null"
                                description="Diunggah oleh Ketua Kelompok."
                            />
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.section-card>
    </div>
@endif

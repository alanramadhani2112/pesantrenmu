{{-- Tab: Laporan Visitasi --}}
@php
    use App\StateMachine\AkreditasiStateMachine;
    use Illuminate\Support\Facades\Storage;

    $isReportReadOnly = $isLocked || in_array((int) $akreditasi->status, [
        AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        AkreditasiStateMachine::STATUS_SELESAI,
        AkreditasiStateMachine::STATUS_DITOLAK,
    ], true);
    $laporanIndividu = $asesorTipe === 1
        ? $akreditasi->laporan_individu_asesor1
        : $akreditasi->laporan_individu_asesor2;
@endphp

<x-ui.section-card title="Upload Laporan Visitasi" subtitle="Unggah laporan individu atau kelompok dari satu area yang sama.">
    <div class="p-5">
        <x-ui.alert variant="info" title="Butir penilaian ada di tab lain" class="mb-5">
            Halaman ini hanya untuk unggah laporan visitasi. Untuk melihat komponen, butir pertanyaan, dan input nilai, buka tab <strong>Butir Penilaian</strong>.
            <div class="mt-3">
                <x-ui.button type="button" variant="light-primary" size="sm" x-on:click="activeTab = 'instrumen'">
                    Buka Butir Penilaian
                </x-ui.button>
            </div>
        </x-ui.alert>

        <div class="spm-upload-status-grid mb-5">
            <div class="spm-upload-status-item">
                <x-ui.icon name="document" class="fs-2 {{ !empty($laporanIndividu) ? 'text-success' : 'text-muted' }}" />
                <div>
                    <div class="fw-semibold text-gray-900">Laporan Individu</div>
                    @if(!empty($laporanIndividu))
                        <a href="{{ Storage::url($laporanIndividu) }}" target="_blank" class="text-success fs-8">Lihat Dokumen</a>
                    @else
                        <span class="text-muted fs-8">Belum diunggah</span>
                    @endif
                </div>
            </div>

            @if($asesorTipe === 1)
                <div class="spm-upload-status-item">
                    <x-ui.icon name="document" class="fs-2 {{ !empty($akreditasi->laporan_kelompok) ? 'text-success' : 'text-muted' }}" />
                    <div>
                        <div class="fw-semibold text-gray-900">Laporan Kelompok</div>
                        @if(!empty($akreditasi->laporan_kelompok))
                            <a href="{{ Storage::url($akreditasi->laporan_kelompok) }}" target="_blank" class="text-success fs-8">Lihat Dokumen</a>
                        @else
                            <span class="text-muted fs-8">Belum diunggah</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="spm-upload-panel">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                <div>
                    <h6 class="fw-semibold mb-1">Unggah Laporan</h6>
                    <p class="text-muted fs-7 mb-0">Format PDF/DOC/DOCX, maksimal 5MB.</p>
                </div>
                @if($isReportReadOnly)
                    <x-ui.badge variant="secondary">Mode review</x-ui.badge>
                @endif
            </div>

            @if($isReportReadOnly)
                <x-ui.alert variant="info" title="Upload dikunci">
                    Laporan visitasi sudah masuk tahap validasi/final. Dokumen hanya bisa dilihat.
                </x-ui.alert>
            @elseif($asesorTipe === 1)
                <form
                    method="POST"
                    enctype="multipart/form-data"
                    x-data="{ reportType: 'individu', routes: { individu: @js(route('asesor.akreditasi.upload-laporan-individu')), kelompok: @js(route('asesor.akreditasi.upload-laporan-kelompok')) } }"
                    x-bind:action="routes[reportType]"
                >
                    @csrf
                    <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">

                    <div class="spm-report-type-toggle mb-4" role="group" aria-label="Jenis laporan">
                        <label class="spm-report-type-option" x-bind:class="{ 'is-active': reportType === 'individu' }">
                            <input type="radio" class="d-none" value="individu" x-model="reportType">
                            <span>Laporan Individu</span>
                        </label>
                        <label class="spm-report-type-option" x-bind:class="{ 'is-active': reportType === 'kelompok' }">
                            <input type="radio" class="d-none" value="kelompok" x-model="reportType">
                            <span>Laporan Kelompok</span>
                        </label>
                    </div>

                    <div class="d-flex align-items-end gap-3 spm-upload-row">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold" for="laporanVisitasiFile">File laporan</label>
                            <input id="laporanVisitasiFile" data-ui-file-upload="metronic" type="file" class="form-control" x-bind:name="reportType === 'kelompok' ? 'laporan_kelompok_file' : 'laporan_individu_file'" accept=".pdf,.doc,.docx" required>
                        </div>
                        <x-ui.button type="submit" variant="primary">
                            <x-ui.icon name="file-up" class="fs-5 me-1" />
                            Unggah Laporan
                        </x-ui.button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('asesor.akreditasi.upload-laporan-individu') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                    <div class="d-flex align-items-end gap-3 spm-upload-row">
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold" for="laporanIndividuFile">File laporan individu</label>
                            <input id="laporanIndividuFile" data-ui-file-upload="metronic" type="file" class="form-control" name="laporan_individu_file" accept=".pdf,.doc,.docx" required>
                        </div>
                        <x-ui.button type="submit" variant="primary">
                            <x-ui.icon name="file-up" class="fs-5 me-1" />
                            Unggah Laporan
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-ui.section-card>

{{-- Tab: Laporan Visitasi --}}
@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-ui.section-card title="Upload Laporan Visitasi" subtitle="Halaman ini khusus unggah dokumen laporan. Butir dan komponen ada di tab Butir Penilaian.">
    <div class="p-6">
        <x-ui.alert variant="info" title="Butir penilaian ada di tab lain" class="mb-6">
            Halaman ini hanya untuk unggah laporan visitasi. Untuk melihat komponen, butir pertanyaan, dan input nilai, buka tab <strong>Butir Penilaian</strong>.
            <div class="mt-3">
                <x-ui.button type="button" variant="light-primary" size="sm" x-on:click="activeTab = 'instrumen'">
                    Buka Butir Penilaian
                </x-ui.button>
            </div>
        </x-ui.alert>
        {{-- Laporan Individu --}}
        <div class="spm-upload-panel mb-6">
            <h6 class="fw-semibold mb-3">Laporan Individu</h6>
            <p class="text-muted fs-7 mb-3">Unggah laporan visitasi individu Anda (PDF/DOCX, maks 5MB).</p>

            @if(!empty($akreditasi->laporan_individu_asesor1) && $asesorTipe === 1)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-ui.icon name="document" class="fs-2 text-success" />
                    <div>
                        <div class="fw-semibold text-success">Laporan Terunggah</div>
                        <a href="{{ Storage::url($akreditasi->laporan_individu_asesor1) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @elseif(!empty($akreditasi->laporan_individu_asesor2) && $asesorTipe === 2)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-ui.icon name="document" class="fs-2 text-success" />
                    <div>
                        <div class="fw-semibold text-success">Laporan Terunggah</div>
                        <a href="{{ Storage::url($akreditasi->laporan_individu_asesor2) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @endif

            @if(!$isLocked)
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
                            Unggah
                        </x-ui.button>
                    </div>
                    <div class="form-text mt-1">Format: PDF atau DOCX. Maksimal 5MB.</div>
                </form>
            @endif
        </div>

        {{-- Laporan Kelompok (Asesor 1 only) --}}
        @if($asesorTipe === 1)
            <hr class="my-6">
            <div>
                <h6 class="fw-semibold mb-3">Laporan Kelompok</h6>
                <p class="text-muted fs-7 mb-3">Unggah laporan visitasi kelompok (hanya Ketua Tim).</p>

                @if(!empty($akreditasi->laporan_kelompok))
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <x-ui.icon name="document" class="fs-2 text-success" />
                        <div>
                            <div class="fw-semibold text-success">Laporan Terunggah</div>
                            <a href="{{ Storage::url($akreditasi->laporan_kelompok) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                        </div>
                    </div>
                @endif

                @if(!$isLocked)
                    <form method="POST" action="{{ route('asesor.akreditasi.upload-laporan-kelompok') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                        <div class="d-flex align-items-end gap-3 spm-upload-row">
                            <div class="flex-grow-1">
                                <label class="form-label fw-semibold" for="laporanKelompokFile">File laporan kelompok</label>
                                <input id="laporanKelompokFile" data-ui-file-upload="metronic" type="file" class="form-control" name="laporan_kelompok_file" accept=".pdf,.doc,.docx" required>
                            </div>
                            <x-ui.button type="submit" variant="primary">
                                <x-ui.icon name="file-up" class="fs-5 me-1" />
                                Unggah
                            </x-ui.button>
                        </div>
                        <div class="form-text mt-1">Format: PDF atau DOCX. Maksimal 5MB.</div>
                    </form>
                @endif
            </div>
        @endif

    </div>
</x-ui.section-card>

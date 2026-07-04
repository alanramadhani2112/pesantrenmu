{{-- Tab: Laporan Visitasi --}}
@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-ui.section-card title="Laporan Visitasi" subtitle="Unggah laporan individu dan kelompok visitasi">
    <div class="p-6">

        {{-- Laporan Individu --}}
        <div class="mb-6">
            <h6 class="fw-semibold mb-3">Laporan Individu</h6>
            <p class="text-muted fs-7 mb-3">Unggah laporan visitasi individu Anda (PDF/DOCX, maks 5MB).</p>

            @if(!empty($akreditasi->laporan_individu_asesor1) && $asesorTipe === 1)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="ki-solid ki-file text-success fs-2"></i>
                    <div>
                        <div class="fw-semibold text-success">Laporan Terunggah</div>
                        <a href="{{ Storage::url($akreditasi->laporan_individu_asesor1) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @elseif(!empty($akreditasi->laporan_individu_asesor2) && $asesorTipe === 2)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="ki-solid ki-file text-success fs-2"></i>
                    <div>
                        <div class="fw-semibold text-success">Laporan Terunggah</div>
                        <a href="{{ Storage::url($akreditasi->laporan_individu_asesor2) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @endif

            @if(!$isLocked)
                <form method="POST" action="{{ route('asesor.akreditasi.upload-laporan-individu', $akreditasi->uuid) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="d-flex align-items-end gap-3">
                        <div class="flex-grow-1">
                            <input data-ui-file-upload="metronic" type="file" class="form-control form-control-sm" name="laporan_individu" accept=".pdf,.doc,.docx" required>
                        </div>
                        <x-ui.button type="submit" variant="primary" size="sm">
                            <i class="ki-solid ki-cloud-add fs-5 me-1"></i>
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
                        <i class="ki-solid ki-file text-success fs-2"></i>
                        <div>
                            <div class="fw-semibold text-success">Laporan Terunggah</div>
                            <a href="{{ Storage::url($akreditasi->laporan_kelompok) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                        </div>
                    </div>
                @endif

                @if(!$isLocked)
                    <form method="POST" action="{{ route('asesor.akreditasi.upload-laporan-kelompok', $akreditasi->uuid) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex align-items-end gap-3">
                            <div class="flex-grow-1">
                                <input data-ui-file-upload="metronic" type="file" class="form-control form-control-sm" name="laporan_kelompok" accept=".pdf,.doc,.docx" required>
                            </div>
                            <x-ui.button type="submit" variant="primary" size="sm">
                                <i class="ki-solid ki-cloud-add fs-5 me-1"></i>
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

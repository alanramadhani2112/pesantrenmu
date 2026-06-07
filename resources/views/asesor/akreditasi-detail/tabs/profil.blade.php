{{-- Tab: Profil Pesantren --}}
<x-ui.section-card title="Profil Pesantren" subtitle="Data dasar pesantren yang mengajukan akreditasi">
    <div class="p-6">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <div class="spm-detail-label">Nama Pesantren</div>
                    <div class="spm-detail-value">{{ $pesantren->nama_pesantren ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">NSP</div>
                    <div class="spm-detail-value">{{ $pesantren->nsp ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">Alamat</div>
                    <div class="spm-detail-value">{{ $pesantren->alamat ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">Kota/Kabupaten</div>
                    <div class="spm-detail-value">{{ $pesantren->kota ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <div class="spm-detail-label">Provinsi</div>
                    <div class="spm-detail-value">{{ $pesantren->provinsi ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">Mudir/Pimpinan</div>
                    <div class="spm-detail-value">{{ $pesantren->mudir ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">Tahun Pendirian</div>
                    <div class="spm-detail-value">{{ $pesantren->tahun_pendirian ?? '-' }}</div>
                </div>
                <div class="mb-3">
                    <div class="spm-detail-label">Tanggal Visitasi</div>
                    <div class="spm-detail-value">{{ $akreditasi->tgl_visitasi ? \Illuminate\Support\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') : 'Belum Dijadwalkan' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-ui.section-card>

{{-- Layanan & Fasilitas --}}
@if($pesantren->units && $pesantren->units->count() > 0)
<x-ui.section-card title="Layanan Pendidikan" subtitle="Unit layanan pendidikan pesantren" class="mt-4">
    <div class="p-6">
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th>No</th>
                        <th>Nama Unit</th>
                        <th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pesantren->units as $unit)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $unit->nama ?? '-' }}</td>
                            <td>{{ $unit->jenis ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-ui.section-card>
@endif

{{-- Dokumen Utama --}}
<x-ui.section-card title="Dokumen Utama" subtitle="Dokumen utama kelengkapan akreditasi" class="mt-4">
    <div class="p-6">
        <div class="row g-3">
            @foreach($dokumenUtama as $field => $label)
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2">
                        @if(!empty($pesantren->$field))
                            <i class="ki-solid ki-check-circle text-success fs-5"></i>
                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-primary fw-semibold fs-7">{{ $label }}</a>
                        @else
                            <i class="ki-solid ki-cross-circle text-muted fs-5"></i>
                            <span class="text-muted fs-7">{{ $label }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui.section-card>

{{-- Dokumen Sekunder --}}
<x-ui.section-card title="Dokumen Sekunder" subtitle="Dokumen pendukung akreditasi" class="mt-4">
    <div class="p-6">
        <div class="row g-3">
            @foreach($dokumenSekunder as $field => $label)
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2">
                        @if(!empty($pesantren->$field))
                            <i class="ki-solid ki-check-circle text-success fs-5"></i>
                            <a href="{{ Storage::url($pesantren->$field) }}" target="_blank" class="text-primary fw-semibold fs-7">{{ $label }}</a>
                        @else
                            <i class="ki-solid ki-cross-circle text-muted fs-5"></i>
                            <span class="text-muted fs-7">{{ $label }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui.section-card>

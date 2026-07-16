{{-- Tab: Profil Pesantren --}}
<x-ui.section-card title="Profil Pesantren" subtitle="Data dasar pesantren yang mengajukan akreditasi" class="spm-asesor-profile-panel">
<div class="p-5">
        <div class="row g-4 spm-asesor-detail-grid">
            <div class="col-md-6">
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Nama Pesantren</div>
                    <div class="spm-detail-value">{{ $pesantren->nama_pesantren ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">NSP</div>
                    <div class="spm-detail-value">{{ $pesantren->nsp ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Alamat</div>
                    <div class="spm-detail-value">{{ $pesantren->alamat ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Kota/Kabupaten</div>
                    <div class="spm-detail-value">{{ $pesantren->kota ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Provinsi</div>
                    <div class="spm-detail-value">{{ $pesantren->provinsi ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Mudir/Pimpinan</div>
                    <div class="spm-detail-value">{{ $pesantren->mudir ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Tahun Pendirian</div>
                    <div class="spm-detail-value">{{ $pesantren->tahun_pendirian ?? '-' }}</div>
                </div>
                <div class="spm-asesor-detail-field">
                    <div class="spm-detail-label">Tanggal Visitasi</div>
                    <div class="spm-detail-value">{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') : 'Belum Dijadwalkan' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-ui.section-card>

{{-- Layanan & Fasilitas --}}
@if($pesantren->units && $pesantren->units->count() > 0)
<x-ui.section-card title="Layanan Pendidikan" subtitle="Unit layanan pendidikan pesantren" class="mt-5 spm-asesor-table-panel">
<div class="p-5">
        <x-ui.simple-table table-class="table-row-gray-200">
                <thead>
                    <tr class="fw-semibold text-muted">
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
        </x-ui.simple-table>
    </div>
</x-ui.section-card>
@endif

{{-- Dokumen Pesantren --}}
<x-ui.section-card title="Dokumen Pesantren" subtitle="Dokumen utama dan pendukung akreditasi" class="mt-5 spm-asesor-document-panel">
    <div class="p-5">
        <div class="row g-5">
            <div class="col-lg-6">
                <h5 class="fw-semibold text-gray-800 mb-4">Dokumen Utama</h5>
                <div class="spm-document-grid">
                    @foreach($dokumenUtama as $field => $label)
                        <x-ui.document-item :label="$label" :href="!empty($pesantren->$field) ? Storage::url($pesantren->$field) : null" />
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <h5 class="fw-semibold text-gray-800 mb-4">Dokumen Sekunder</h5>
                <div class="spm-document-grid">
                    @foreach($dokumenSekunder as $field => $label)
                        <x-ui.document-item :label="$label" :href="!empty($pesantren->$field) ? Storage::url($pesantren->$field) : null" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-ui.section-card>

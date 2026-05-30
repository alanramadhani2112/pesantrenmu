            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas pesantren dan status akses data.">
                        <x-slot:toolbar>
                            @if($pesantren)
                                <x-ui.button
                                    type="button"
                                    @click="confirmToggleLock($wire, {{ $pesantren?->is_locked ? 'true' : 'false' }})"
                                    wire:loading.attr="disabled"
                                    :variant="$pesantren?->is_locked ? 'warning' : 'light'"
                                    size="sm"
                                >
                                    <x-ui.icon name="shield-tick" class="fs-4 me-1" wire:loading.remove wire:target="toggleLock" />
                                    <span wire:loading.remove wire:target="toggleLock">
                                        {{ $pesantren?->is_locked ? 'Buka Kunci Data' : 'Kunci Data' }}
                                    </span>
                                    <span wire:loading wire:target="toggleLock">Memproses...</span>
                                </x-ui.button>
                            @endif
                        </x-slot:toolbar>

                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />
                                <x-ui.detail-item label="Nama Mudir" value="{{ $pesantren->nama_mudir ?? '-' }}" />
                                <x-ui.detail-item label="Tahun Pendirian" value="{{ $pesantren->tahun_pendirian ?? '-' }}" />
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Layanan & Fasilitas" subtitle="Unit layanan pendidikan dan luas sarana.">
                        <div class="p-6">
                            <div class="row g-6">
                                <div class="col-lg-7">
                                    @if($pesantren && $pesantren->units && $pesantren->units->count() > 0)
                                        <x-ui.simple-table dense>
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Unit</th>
                                                    <th class="text-end pe-4">Jumlah Rombel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pesantren->units as $unit)
                                                    <tr>
                                                        <td class="ps-4 text-uppercase fw-semibold">{{ $unit->unit }}</td>
                                                        <td class="text-end pe-4">
                                                            <x-ui.badge variant="success">{{ $unit->jumlah_rombel }} Rombel</x-ui.badge>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </x-ui.simple-table>
                                    @else
                                        <x-ui.empty-state title="Belum Ada Unit" description="Data unit pendidikan belum diisi." />
                                    @endif
                                </div>

                                <div class="col-lg-5">
                                    <div class="d-flex flex-column gap-4">
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success" icon="geolocation" />
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info" icon="category" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pesantren" subtitle="Dokumen utama dan dokumen pendukung profil.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Utama</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenUtama as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Sekunder</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenSekunder as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Asesor & Visitasi" subtitle="Tim penilai dan jadwal penilaian.">
                        <div class="p-6">
                            <div class="row g-5">
                                @forelse ($akreditasi->assessments as $assessment)
                                    <x-ui.detail-item label="{{ $assessment->tipe == 1 ? 'Ketua' : 'Anggota' }}" value="{{ $assessment->asesor->user->name ?? '-' }}" />
                                @empty
                                    <div class="col-12">
                                        <x-ui.empty-state title="Belum Ada Asesor" description="Asesor belum ditugaskan untuk pengajuan ini." />
                                    </div>
                                @endforelse

                                @if ($akreditasi->assessments->isNotEmpty())
                                    @php $mainAssessment = $akreditasi->assessments->first(); @endphp
                                    <x-ui.detail-item label="Penilaian Mulai" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_mulai)->format('d M Y') }}" />
                                    <x-ui.detail-item label="Penilaian Berakhir" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_berakhir)->format('d M Y') }}" />
                                @endif

                                @if ($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Visitasi Mulai" value="{{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') }}" />
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-end justify-content-between gap-3">
                                            <div class="spm-detail-item">
                                                <div class="spm-detail-label">Visitasi Berakhir</div>
                                                <div class="spm-detail-value">
                                                    {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir ?? $akreditasi->tgl_visitasi)->format('d M Y') }}
                                                </div>
                                            </div>

                                            @if(in_array($akreditasi->status, [0, -1]))
                                                <x-ui.status-badge variant="secondary">Reschedule Terkunci</x-ui.status-badge>
                                            @else
                                                <x-ui.button type="button" wire:click="openVisitasiEditModal" variant="light" size="sm">
                                                    Reschedule
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

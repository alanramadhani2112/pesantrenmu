            @if ($activeTab === 'hasil')
                <div class="d-flex flex-column gap-6">
                    @if((int) $akreditasi->status === 0)
                        <x-ui.section-card title="Hasil Akreditasi Akhir" subtitle="Nilai, peringkat, SK, dan masa berlaku.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Nilai Akhir</div><div class="fs-2 fw-semibold text-success">{{ $akreditasi->nilai }}</div></div></div>
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Peringkat</div><div class="fs-2 fw-semibold text-success">{{ $akreditasi->peringkat }}</div></div></div>
                                    <x-ui.detail-item label="Nomor SK" value="{{ $akreditasi->nomor_sk }}" />
                                    <x-ui.detail-item label="Masa Berlaku">
                                        {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku)->format('d F Y') }}
                                        @if($akreditasi->masa_berlaku_akhir && $akreditasi->masa_berlaku != $akreditasi->masa_berlaku_akhir)
                                            - {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku_akhir)->format('d F Y') }}
                                        @endif
                                    </x-ui.detail-item>
                                    @if($akreditasi->sertifikat_path)
                                        <div class="col-12">
                                            <x-ui.button :href="Storage::url($akreditasi->sertifikat_path)" target="_blank" variant="success">Unduh Sertifikat</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>

                        {{-- Catatan Rekomendasi Asesor per Komponen --}}
                        @if(!empty($asesorRekomendasis) && count(array_filter($asesorRekomendasis)) > 0)
                            <x-ui.section-card title="Catatan Rekomendasi Asesor" subtitle="Rekomendasi perbaikan dari asesor per komponen.">
                                <div class="p-6">
                                    <div class="d-flex flex-column gap-5">
                                        @foreach($komponens as $komponen)
                                            @if(!empty($asesorRekomendasis[$komponen->id]))
                                                <div class="border border-gray-300 rounded p-4">
                                                    <div class="fw-semibold text-gray-800 mb-2">{{ $komponen->nama }}</div>
                                                    <div class="text-gray-700">{{ $asesorRekomendasis[$komponen->id] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.section-card>
                        @endif
                    @elseif((int) $akreditasi->status === -1)
                        <x-ui.alert variant="danger" icon="cross-circle" title="Pengajuan Ditolak">
                            Catatan: {{ $akreditasi->catatan }}
                        </x-ui.alert>

                    @endif

                    {{-- Banding Status Section --}}
                    @if($bandingStatus)
                        <x-ui.section-card title="Status Banding" subtitle="Status dan informasi pengajuan banding akreditasi.">
                            <div class="p-6">
                                <div class="d-flex flex-column gap-4">
                                    {{-- Banding status badge --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Status:</span>
                                        @php
                                            $bandingVariant = match ($bandingStatus->status) {
                                                'pending' => 'warning',
                                                'under_review' => 'info',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary',
                                            };
                                            $bandingLabel = match ($bandingStatus->status) {
                                                'pending' => 'Menunggu',
                                                'under_review' => 'Sedang Direview',
                                                'accepted' => 'Diterima',
                                                'rejected' => 'Ditolak',
                                                default => $bandingStatus->status,
                                            };
                                        @endphp
                                        <x-ui.badge variant="{{ $bandingVariant }}">{{ $bandingLabel }}</x-ui.badge>
                                    </div>

                                    {{-- Submission date --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Tanggal Pengajuan:</span>
                                        <span>{{ $bandingStatus->created_at->format('d F Y') }}</span>
                                    </div>

                                    {{-- Reason --}}
                                    <div>
                                        <span class="fw-semibold">Alasan Banding:</span>
                                        <div class="mt-1 text-muted">{{ $bandingStatus->alasan }}</div>
                                    </div>

                                    {{-- Decision (when decided) --}}
                                    @if(in_array($bandingStatus->status, ['accepted', 'rejected']))
                                        <div>
                                            <span class="fw-semibold">Keputusan:</span>
                                            <div class="mt-1 text-muted">{{ $bandingStatus->keputusan }}</div>
                                        </div>
                                    @endif

                                    {{-- Accepted banding returns to final admin validation. --}}
                                    @if($bandingStatus->status === 'accepted')
                                        <x-ui.alert variant="info" icon="timer" title="Menunggu Validasi Akhir Admin">
                                            Banding diterima dan proses akreditasi kembali ke tahap Validasi Akhir Admin.
                                        </x-ui.alert>
                                    @endif

                                    {{-- Remaining appeal count --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Sisa kesempatan banding:</span>
                                        <x-ui.badge variant="{{ $bandingEligibility['remaining'] <= 0 ? 'danger' : 'info' }}">
                                            {{ $bandingEligibility['remaining'] }}/{{ config('akreditasi.banding_limit') }}
                                        </x-ui.badge>
                                    </div>

                                    {{-- Ajukan Banding button --}}
                                    @if((int) $akreditasi->status === -1)
                                        <div>
                                            @if($bandingEligibility['allowed'])
                                                <x-ui.button type="button" variant="primary" size="sm" x-on:click="$dispatch('open-modal', 'banding-modal')">
                                                    Ajukan Banding
                                                </x-ui.button>
                                            @else
                                                <x-ui.button disabled title="Batas pengajuan banding telah tercapai">
                                                    Ajukan Banding
                                                </x-ui.button>
                                                <div class="text-danger fw-semibold mt-2">
                                                    Batas pengajuan banding telah tercapai
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @elseif(!empty($bandingEligibility))
                        {{-- Show banding eligibility even when no banding exists yet --}}
                        <x-ui.section-card title="Banding" subtitle="Informasi pengajuan banding akreditasi.">
                            <div class="p-6">
                                <div class="d-flex flex-column gap-4">
                                    {{-- Remaining appeal count --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Sisa kesempatan banding:</span>
                                        <x-ui.badge variant="{{ $bandingEligibility['remaining'] <= 0 ? 'danger' : 'info' }}">
                                            {{ $bandingEligibility['remaining'] }}/{{ config('akreditasi.banding_limit') }}
                                        </x-ui.badge>
                                    </div>

                                    {{-- Ajukan Banding button --}}
                                    @if((int) $akreditasi->status === -1)
                                        <div>
                                            @if($bandingEligibility['allowed'])
                                                <x-ui.button type="button" variant="primary" size="sm" x-on:click="$dispatch('open-modal', 'banding-modal')">
                                                    Ajukan Banding
                                                </x-ui.button>
                                            @else
                                                <x-ui.button disabled title="Batas pengajuan banding telah tercapai">
                                                    Ajukan Banding
                                                </x-ui.button>
                                                <div class="text-danger fw-semibold mt-2">
                                                    Batas pengajuan banding telah tercapai
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    <x-ui.section-card title="Data Penilaian" subtitle="Catatan rekomendasi asesor per komponen.">
                        <div class="p-6">
                            <x-ui.simple-table>
                                <thead>
                                    <tr>
                                        <th class="ps-4">Komponen</th>
                                        <th class="pe-4">Catatan Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        <tr>
                                            <td class="ps-4 text-uppercase fw-semibold">{{ $komponen->nama }}</td>
                                            <td class="pe-4">{!! $asesorCatatans[$komponen->id] ?? '-' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

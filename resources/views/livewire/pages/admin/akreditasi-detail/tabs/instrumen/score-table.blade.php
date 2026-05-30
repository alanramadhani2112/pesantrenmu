                    <x-ui.section-card title="Nilai Akhir" subtitle="Perbandingan Nilai Ketua, Nilai Anggota, Nilai Kelompok, Nilai Verifikasi Admin, catatan butir, dan rekomendasi.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-score-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-150px">Komponen</th>
                                        <th class="text-center w-80px">No SK</th>
                                        <th class="text-center w-90px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-90px">Nilai Ketua</th>
                                        <th class="text-center w-90px">Nilai Anggota</th>
                                        <th class="text-center w-100px">Nilai Kelompok</th>
                                        <th class="text-center w-110px">Nilai Verifikasi</th>
                                        <th class="w-220px">Catatan Butir</th>
                                        <th class="pe-4 w-260px">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @foreach ($komponen->butirs as $index => $butir)
                                            <tr>
                                                @if ($index === 0)
                                                    <td rowspan="{{ count($komponen->butirs) }}" class="ps-4 fw-semibold text-primary text-uppercase align-middle">
                                                        {{ $komponen->nama }}
                                                    </td>
                                                @endif
                                                <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                <td class="text-center fw-semibold">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center fw-semibold">{{ $asesor1Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-semibold">{{ $asesor2Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-semibold text-warning">{{ $asesor1Nks[$butir->id] ?? '' }}</td>
                                                <td class="text-center">
                                                    @if ($akreditasi->status == 1)
                                                        <x-ui.select
                                                            model="adminNvs.{{ $butir->id }}"
                                                            modifier="live"
                                                            :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                            placeholder="Pilih"
                                                            size="sm"
                                                            class="spm-score-control mx-auto"
                                                        />
                                                        @error('adminNvs.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    @else
                                                        <x-ui.badge variant="primary">{{ $adminNvs[$butir->id] ?? '' }}</x-ui.badge>
                                                    @endif
                                                </td>
                                                <td class="fs-8 text-muted">{{ $asesor1ButirCatatans[$butir->id] ?? '' }}</td>
                                                @if ($index === 0)
                                                    <td rowspan="{{ count($komponen->butirs) }}" class="pe-4 fs-8 text-gray-700 align-top">
                                                        {!! $asesor1Catatans[$komponen->id] ?? '-' !!}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

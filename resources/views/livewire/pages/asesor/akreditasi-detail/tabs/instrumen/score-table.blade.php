                        <x-ui.section-card title="Penilaian Visitasi" subtitle="Isi nilai sesuai peran. Nilai Kelompok dibuka setelah nilai Ketua dan Anggota final.">
                            <div class="p-6">
                                <x-ui.simple-table tableClass="spm-score-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4 w-150px">Komponen</th>
                                            <th class="text-center w-80px">No SK</th>
                                            <th class="text-center w-90px">No Butir</th>
                                            <th>Butir Pernyataan</th>
                                            <th class="text-center w-100px">EDPM</th>
                                            @if ($this->asesorTipe == 1)
                                                <th class="text-center w-110px">Nilai Ketua</th>
                                                <th class="text-center w-110px">Nilai Anggota</th>
                                                <th class="text-center w-90px">Delta</th>
                                                <th class="text-center w-120px">Nilai Kelompok</th>
                                                <th class="pe-4 w-240px">Catatan Butir</th>
                                            @else
                                                <th class="text-center pe-4 w-120px">Nilai Anggota</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $komponen)
                                            @foreach ($komponen->butirs as $index => $butir)
                                                <tr>
                                                    @if ($index === 0)
                                                        <td rowspan="{{ count($komponen->butirs) }}" class="ps-4 fw-semibold text-primary text-uppercase align-middle">{{ $komponen->nama }}</td>
                                                    @endif
                                                    <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                    <td class="text-center fw-semibold">{{ $butir->nomor_butir }}</td>
                                                    <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                    <td class="text-center">
                                                        <x-ui.badge variant="warning" class="spm-score-badge">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge>
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                            <x-ui.badge variant="primary">{{ $asesorEvaluasis[$butir->id] ?: '-' }}</x-ui.badge>
                                                        @else
                                                            @if($this->isAsesorNaFinal($butir->id))
                                                                <x-ui.badge variant="success">{{ $asesorEvaluasis[$butir->id] ?? '-' }} <x-ui.icon name="lock" class="fs-9 ms-1" /></x-ui.badge>
                                                            @else
                                                                <div class="d-flex align-items-center gap-1 justify-content-center">
                                                                    <x-ui.select
                                                                        model="asesorEvaluasis.{{ $butir->id }}"
                                                                        modifier="live"
                                                                        :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                        placeholder="-"
                                                                        size="sm"
                                                                        class="spm-score-control"
                                                                        :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && ($asesorTipe == 2 || !$isLocked) ? false : true"
                                                                    />
                                                                    @if(!empty($asesorEvaluasis[$butir->id]))
                                                                        <x-ui.button
                                                                            type="button"
                                                                            size="sm"
                                                                            variant="light-success"
                                                                            wire:click="saveNaValue({{ $butir->id }}, {{ $asesorEvaluasis[$butir->id] ?? 0 }}, true)"
                                                                            title="Kunci sebagai Final"
                                                                            class="px-2 py-1"
                                                                        >
                                                                            <x-ui.icon name="lock" class="fs-8" />
                                                                        </x-ui.button>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @error('asesorEvaluasis.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    </td>

                                                    @if ($this->asesorTipe == 1)
                                                        <td class="text-center fw-semibold text-success">{{ $otherAsesorEvaluasis[$butir->id] ?? '' }}</td>
                                                        <td class="text-center">
                                                            @if(! is_null($this->asesorDeltaValue($butir->id)))
                                                                <x-ui.badge :variant="$this->asesorDeltaVariant($butir->id)" class="spm-score-badge">
                                                                    {{ $this->asesorDeltaValue($butir->id) }}
                                                                </x-ui.badge>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                                <x-ui.badge variant="warning">{{ $asesorNks[$butir->id] ?: '-' }}</x-ui.badge>
                                                            @else
                                                                <x-ui.select
                                                                    model="asesorNks.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                    placeholder="{{ $nilaiKelompokUnlocked ? 'Pilih' : 'Terkunci' }}"
                                                                    size="sm"
                                                                    class="spm-score-control mx-auto"
                                                                    :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && $nilaiKelompokUnlocked ? false : true"
                                                                />
                                                            @endif
                                                            @error('asesorNks.' . $butir->id)
                                                                <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td class="pe-4">
                                                            @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                                <div class="fs-8 text-muted">{{ $asesorButirCatatans[$butir->id] ?: '-' }}</div>
                                                            @else
                                                                <x-ui.textarea
                                                                    model="asesorButirCatatans.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    rows="2"
                                                                    class="fs-8"
                                                                    placeholder="Catatan butir..."
                                                                    :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI ? false : true"
                                                                />
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </x-ui.simple-table>
                            </div>
                        </x-ui.section-card>

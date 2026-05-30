                        @if ($this->asesorTipe == 1)
                            <x-ui.section-card title="Catatan Rekomendasi Komponen" subtitle="Ringkasan rekomendasi hasil Nilai Kelompok per komponen.">
                                <div class="p-6">
                                    <div class="row g-5">
                                        @foreach ($komponens as $komponen)
                                            <div class="col-lg-6">
                                                <div class="spm-soft-panel h-100">
                                                    <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                                    @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                        <div class="spm-detail-value spm-detail-value-muted">{!! $asesorCatatans[$komponen->id] ?: '<span class="text-muted">Tidak ada catatan.</span>' !!}</div>
                                                    @else
                                                        <x-quill-editor
                                                            wire:model.live="asesorCatatans.{{ $komponen->id }}"
                                                            placeholder="Masukkan catatan rekomendasi {{ $komponen->nama }}..."
                                                            :disabled="$akreditasi->status != \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI"
                                                        />
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.section-card>
                        @endif
